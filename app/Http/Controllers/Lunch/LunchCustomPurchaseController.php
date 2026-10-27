<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\LunchCustomPurchase;
use App\Models\Site;
use App\Services\Lunch\LunchDistributionService;
use App\Services\UnitScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Custom Purchase — pembelian manual yang menggantikan katering pada
 * tanggal + site tertentu (perayaan, pelayanan, apresiasi pegawai).
 *
 * Bila `replaces_catering` aktif, distribusi katering pada tanggal tersebut
 * otomatis di-nol-kan sehingga vendor tidak ditagih dua kali.
 */
class LunchCustomPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $month = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $status = $request->get('status');
        $siteFilter = $request->filled('site_id') ? (int) $request->get('site_id') : null;

        if ($siteFilter) {
            $scope->ensureSite($siteFilter);
        }

        $purchases = LunchCustomPurchase::with(['site.unit', 'items', 'creator', 'approver'])
            ->inUnit($siteIds)
            ->whereDate('purchase_date', '>=', $monthStart->toDateString())
            ->whereDate('purchase_date', '<=', $monthEnd->toDateString())
            ->when($siteFilter, fn ($q) => $q->where('site_id', $siteFilter))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $sites = $scope->sites();
        $units = $scope->units();

        $all = LunchCustomPurchase::inUnit($siteIds)
            ->whereDate('purchase_date', '>=', $monthStart->toDateString())
            ->whereDate('purchase_date', '<=', $monthEnd->toDateString())
            ->get();

        $summary = [
            'count' => $all->count(),
            'total' => (float) $all->sum('total_amount'),
            'draft' => $all->where('status', LunchCustomPurchase::STATUS_DRAFT)->count(),
            'completed' => $all->where('status', LunchCustomPurchase::STATUS_COMPLETED)->count(),
            'replaces' => $all->where('replaces_catering', true)->count(),
        ];

        return view('lunch.custom-purchases.index', compact(
            'purchases', 'sites', 'units', 'summary', 'month', 'monthStart', 'status', 'siteFilter'
        ));
    }

    public function store(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $validated = $this->validatePayload($request, $scope);

        $purchase = DB::transaction(function () use ($validated, $request) {
            $site = Site::findOrFail((int) $validated['site_id']);

            $purchase = LunchCustomPurchase::create([
                'unit_id'           => $site->unit_id,
                'site_id'           => $site->id,
                'purchase_date'     => $validated['purchase_date'],
                'title'             => $validated['title'],
                'description'       => $validated['description'] ?? null,
                'vendor_name'       => $validated['vendor_name'] ?? null,
                'status'            => $validated['status'] ?? LunchCustomPurchase::STATUS_DRAFT,
                'replaces_catering' => (bool) ($validated['replaces_catering'] ?? true),
                'total_amount'      => $validated['total_amount'] ?? 0,
                'created_by'        => $request->user()?->id,
            ]);

            $this->syncItems($purchase, $validated['items'] ?? []);
            $purchase->recalculateTotal();

            return $purchase;
        });

        $this->refreshDistribution($purchase, $request);

        return back()->with('success', 'Custom purchase berhasil dibuat.');
    }

    public function update(Request $request, LunchCustomPurchase $customPurchase)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($customPurchase->site_id);

        $validated = $this->validatePayload($request, $scope);

        $oldSiteId = (int) $customPurchase->site_id;
        $oldDate = $customPurchase->purchase_date?->toDateString();

        DB::transaction(function () use ($customPurchase, $validated) {
            $site = Site::findOrFail((int) $validated['site_id']);

            $customPurchase->update([
                'unit_id'           => $site->unit_id,
                'site_id'           => $site->id,
                'purchase_date'     => $validated['purchase_date'],
                'title'             => $validated['title'],
                'description'       => $validated['description'] ?? null,
                'vendor_name'       => $validated['vendor_name'] ?? null,
                'status'            => $validated['status'] ?? $customPurchase->status,
                'replaces_catering' => (bool) ($validated['replaces_catering'] ?? $customPurchase->replaces_catering),
                'total_amount'      => $validated['total_amount'] ?? $customPurchase->total_amount,
            ]);

            $this->syncItems($customPurchase, $validated['items'] ?? []);
            $customPurchase->recalculateTotal();
        });

        $fresh = $customPurchase->fresh();
        $this->refreshDistribution($fresh, $request);

        // Bila tanggal/site berubah, pulihkan distribusi lokasi lama juga.
        if ($oldDate && ($oldSiteId !== (int) $fresh->site_id || $oldDate !== $fresh->purchase_date?->toDateString())) {
            app(LunchDistributionService::class)->buildForDate($oldDate, $request->user(), [$oldSiteId]);
        }

        return back()->with('success', 'Custom purchase berhasil diperbarui.');
    }

    public function destroy(Request $request, LunchCustomPurchase $customPurchase)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($customPurchase->site_id);

        $customPurchase->delete();

        $this->refreshDistribution($customPurchase, $request);

        return back()->with('success', 'Custom purchase dihapus.');
    }

    public function approve(Request $request, LunchCustomPurchase $customPurchase)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($customPurchase->site_id);

        $customPurchase->update([
            'status'      => LunchCustomPurchase::STATUS_APPROVED,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        $this->refreshDistribution($customPurchase, $request);

        return back()->with('success', 'Custom purchase disetujui.');
    }

    public function complete(Request $request, LunchCustomPurchase $customPurchase)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($customPurchase->site_id);

        $customPurchase->update(['status' => LunchCustomPurchase::STATUS_COMPLETED]);

        $this->refreshDistribution($customPurchase, $request);

        return back()->with('success', 'Custom purchase ditandai selesai.');
    }

    public function cancel(Request $request, LunchCustomPurchase $customPurchase)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($customPurchase->site_id);

        $customPurchase->update(['status' => LunchCustomPurchase::STATUS_CANCELLED]);

        $this->refreshDistribution($customPurchase, $request);

        return back()->with('success', 'Custom purchase dibatalkan. Katering kembali dihitung normal.');
    }

    /* =========================================================
     * Helpers
     * =======================================================*/

    private function validatePayload(Request $request, UnitScope $scope): array
    {
        $validated = $request->validate([
            'site_id'             => 'required|exists:sites,id',
            'purchase_date'       => 'required|date',
            'title'               => 'required|string|max:150',
            'description'         => 'nullable|string|max:2000',
            'vendor_name'         => 'nullable|string|max:150',
            'status'              => 'nullable|in:draft,approved,completed,cancelled',
            'replaces_catering'   => 'nullable|boolean',
            'total_amount'        => 'nullable|numeric|min:0',
            'items'               => 'nullable|array',
            'items.*.item_name'   => 'required_with:items|string|max:150',
            'items.*.qty'         => 'nullable|numeric|min:0',
            'items.*.unit'        => 'nullable|string|max:30',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
            'items.*.notes'       => 'nullable|string|max:500',
        ]);

        $scope->ensureSite((int) $validated['site_id']);

        return $validated;
    }

    private function syncItems(LunchCustomPurchase $purchase, array $items): void
    {
        $purchase->items()->delete();

        foreach ($items as $item) {
            if (empty($item['item_name'])) {
                continue;
            }

            $purchase->items()->create([
                'item_name'  => $item['item_name'],
                'qty'        => $item['qty'] ?? 1,
                'unit'       => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'] ?? 0,
                'notes'      => $item['notes'] ?? null,
            ]);
        }
    }

    /**
     * Bangun ulang distribusi tanggal + site agar custom purchase langsung
     * menol-kan porsi katering (atau mengembalikannya bila dibatalkan).
     */
    private function refreshDistribution(LunchCustomPurchase $purchase, Request $request): void
    {
        app(LunchDistributionService::class)->buildForDate(
            $purchase->purchase_date->toDateString(),
            $request->user(),
            [$purchase->site_id]
        );
    }
}
