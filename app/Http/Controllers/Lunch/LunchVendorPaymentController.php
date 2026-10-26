<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\LunchDistribution;
use App\Models\LunchVendor;
use App\Models\LunchVendorInvoice;
use App\Models\LunchVendorPayment;
use App\Models\Site;
use App\Services\UnitScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data Pengeluaran & Pembayaran Vendor.
 *
 * Alur:
 *   vendor (harga/porsi) → generate invoice dari total porsi distribusi
 *   → koreksi nominal bila perlu → approve → catat pembayaran (bisa bertahap).
 *
 * Semua query dibatasi unit user. Vendor level unit (site_id NULL) dipakai
 * untuk site yang belum punya vendor khusus.
 */
class LunchVendorPaymentController extends Controller
{
    public function index(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $periodStart = $request->get('period_start', now()->startOfMonth()->toDateString());
        $periodEnd = $request->get('period_end', now()->endOfMonth()->toDateString());
        $status = $request->get('status');
        $siteFilter = $request->filled('site_id') ? (int) $request->get('site_id') : null;

        if ($siteFilter) {
            $scope->ensureSite($siteFilter);
        }

        $invoices = LunchVendorInvoice::with(['vendor', 'site.unit', 'payments', 'approver'])
            ->inUnit($siteIds)
            ->when($siteFilter, fn ($q) => $q->where('site_id', $siteFilter))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->whereDate('period_end', '>=', $periodStart)
            ->whereDate('period_start', '<=', $periodEnd)
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $vendors = LunchVendor::with(['unit', 'site'])
            ->inUnit($siteIds)
            ->orderBy('name')
            ->get();

        $sites = $scope->sites();
        $units = $scope->units();

        $allInvoices = LunchVendorInvoice::with('payments')
            ->inUnit($siteIds)
            ->whereDate('period_end', '>=', $periodStart)
            ->whereDate('period_start', '<=', $periodEnd)
            ->get();

        $summary = [
            'total_billed' => (float) $allInvoices->sum('total_amount'),
            'total_paid' => (float) $allInvoices->sum(fn ($i) => $i->paidAmount()),
            'total_remaining' => (float) $allInvoices->sum(fn ($i) => $i->remainingAmount()),
            'overdue' => $allInvoices->filter(fn ($i) => $i->isOverdue())->count(),
            'draft' => $allInvoices->where('status', LunchVendorInvoice::STATUS_DRAFT)->count(),
            'paid' => $allInvoices->where('status', LunchVendorInvoice::STATUS_PAID)->count(),
        ];

        return view('lunch.vendor-payments.index', compact(
            'invoices', 'vendors', 'sites', 'units', 'summary',
            'periodStart', 'periodEnd', 'status', 'siteFilter'
        ));
    }

    public function show(Request $request, LunchVendorInvoice $invoice)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($invoice->site_id);

        $invoice->load(['vendor', 'site.unit', 'payments.creator', 'creator', 'approver']);

        return view('lunch.vendor-payments.show', compact('invoice'));
    }

    /* =========================================================
     * Vendor
     * =======================================================*/

    public function storeVendor(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $validated = $this->validateVendor($request, $scope);

        LunchVendor::create($this->vendorAttributes($validated, $scope));

        return back()->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function updateVendor(Request $request, LunchVendor $vendor)
    {
        $scope = UnitScope::for($request->user());
        $this->ensureVendor($scope, $vendor);

        $validated = $this->validateVendor($request, $scope);

        $vendor->update($this->vendorAttributes($validated, $scope));

        return back()->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroyVendor(Request $request, LunchVendor $vendor)
    {
        $scope = UnitScope::for($request->user());
        $this->ensureVendor($scope, $vendor);

        if ($vendor->invoices()->exists()) {
            $vendor->update(['is_active' => false]);

            return back()->with('success', 'Vendor dinonaktifkan karena sudah memiliki riwayat invoice.');
        }

        $vendor->delete();

        return back()->with('success', 'Vendor berhasil dihapus.');
    }

    /* =========================================================
     * Invoice
     * =======================================================*/

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'site_id'      => 'nullable|exists:sites,id',
        ]);

        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        if (! empty($validated['site_id'])) {
            $scope->ensureSite((int) $validated['site_id']);
        }

        $sites = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->when(! empty($validated['site_id']), fn ($q) => $q->where('id', (int) $validated['site_id']))
            ->get();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($validated, $sites, $request, &$created, &$updated, &$skipped) {
            foreach ($sites as $site) {
                $vendor = LunchVendor::active()
                    ->where(fn ($q) => $q->where('site_id', $site->id)
                        ->orWhere(fn ($q2) => $q2->whereNull('site_id')->where('unit_id', $site->unit_id)))
                    ->orderByRaw('site_id IS NULL')
                    ->first();

                if (! $vendor || ! $vendor->price_per_portion) {
                    $skipped++;
                    continue;
                }

                $totalPortion = (int) LunchDistribution::query()
                    ->where('site_id', $site->id)
                    ->whereDate('distribution_date', '>=', $validated['period_start'])
                    ->whereDate('distribution_date', '<=', $validated['period_end'])
                    ->sum('total_count');

                if ($totalPortion <= 0) {
                    $skipped++;
                    continue;
                }

                $price = (float) $vendor->price_per_portion;
                $calculated = round($totalPortion * $price, 2);

                $invoice = LunchVendorInvoice::firstOrNew([
                    'vendor_id'    => $vendor->id,
                    'site_id'      => $site->id,
                    'period_start' => $validated['period_start'],
                    'period_end'   => $validated['period_end'],
                ]);

                $isNew = ! $invoice->exists;

                $invoice->fill([
                    'unit_id'             => $site->unit_id,
                    'total_portion'       => $totalPortion,
                    'price_per_portion'   => $price,
                    'calculated_amount'   => $calculated,
                    'created_by'          => $invoice->created_by ?? $request->user()?->id,
                ]);

                // Nominal manual dipertahankan bila invoice sudah di luar draft.
                if ($isNew || $invoice->status === LunchVendorInvoice::STATUS_DRAFT) {
                    $invoice->total_amount = $calculated;
                }

                if ($isNew) {
                    $invoice->status = LunchVendorInvoice::STATUS_DRAFT;
                    $created++;
                } else {
                    $updated++;
                }

                $invoice->save();
            }
        });

        return back()->with(
            'success',
            "Generate invoice selesai: {$created} baru, {$updated} diperbarui, {$skipped} site dilewati (belum ada vendor/harga/porsi)."
        );
    }

    public function updateInvoice(Request $request, LunchVendorInvoice $invoice)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($invoice->site_id);

        $validated = $request->validate([
            'total_amount' => 'nullable|numeric|min:0',
            'due_date'     => 'nullable|date',
            'notes'        => 'nullable|string|max:1000',
            'status'       => 'nullable|in:draft,submitted,approved,cancelled',
        ]);

        if (array_key_exists('total_amount', $validated) && $validated['total_amount'] !== null) {
            $invoice->total_amount = $validated['total_amount'];
        }

        if (array_key_exists('due_date', $validated)) {
            $invoice->due_date = $validated['due_date'];
        }

        if (array_key_exists('notes', $validated)) {
            $invoice->notes = $validated['notes'];
        }

        if (! empty($validated['status'])) {
            $invoice->status = $validated['status'];
        }

        $invoice->save();

        if (empty($validated['status'])) {
            $this->syncInvoiceStatus($invoice->fresh(['payments']));
        }

        return back()->with('success', 'Invoice berhasil diperbarui.');
    }

    public function approve(Request $request, LunchVendorInvoice $invoice)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($invoice->site_id);

        if ($invoice->status === LunchVendorInvoice::STATUS_CANCELLED) {
            return back()->with('error', 'Invoice yang dibatalkan tidak bisa disetujui.');
        }

        $invoice->update([
            'status'      => LunchVendorInvoice::STATUS_APPROVED,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Invoice disetujui.');
    }

    public function storePayment(Request $request, LunchVendorInvoice $invoice)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($invoice->site_id);

        if ($invoice->status === LunchVendorInvoice::STATUS_CANCELLED) {
            return back()->with('error', 'Invoice yang dibatalkan tidak bisa dibayar.');
        }

        $validated = $request->validate([
            'amount'    => 'required|numeric|min:1',
            'paid_at'   => 'required|date',
            'method'    => 'required|in:transfer,cash,giro',
            'reference' => 'nullable|string|max:100',
            'proof'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'notes'     => 'nullable|string|max:1000',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('lunch/vendor-payments', 'public');
        }

        // Jangan sampai pembayaran melebihi sisa tagihan.
        if ((float) $validated['amount'] > $invoice->remainingAmount() + 0.01) {
            return back()->with('error', 'Nominal pembayaran melebihi sisa tagihan (Rp '
                .number_format($invoice->remainingAmount(), 0, ',', '.').').');
        }

        LunchVendorPayment::create([
            'lunch_vendor_invoice_id' => $invoice->id,
            'amount'                  => $validated['amount'],
            'paid_at'                 => $validated['paid_at'],
            'method'                  => $validated['method'],
            'reference'               => $validated['reference'] ?? null,
            'proof_path'              => $proofPath,
            'notes'                   => $validated['notes'] ?? null,
            'created_by'              => $request->user()?->id,
        ]);

        $this->syncInvoiceStatus($invoice->fresh(['payments']));

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function destroyPayment(Request $request, LunchVendorPayment $payment)
    {
        $invoice = $payment->invoice;
        $scope = UnitScope::for($request->user());

        if (! $invoice || ! $scope->allowsSite($invoice->site_id)) {
            abort(403, 'Pembayaran ini berada di luar unit Anda.');
        }

        if ($payment->proof_path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->delete();

        $this->syncInvoiceStatus($invoice->fresh(['payments']));

        return back()->with('success', 'Pembayaran dihapus.');
    }

    /* =========================================================
     * Helpers
     * =======================================================*/

    private function syncInvoiceStatus(LunchVendorInvoice $invoice): void
    {
        if ($invoice->status === LunchVendorInvoice::STATUS_CANCELLED) {
            return;
        }

        $paid = $invoice->paidAmount();

        if ($paid <= 0) {
            $status = $invoice->approved_at
                ? LunchVendorInvoice::STATUS_APPROVED
                : LunchVendorInvoice::STATUS_DRAFT;
        } elseif ($paid >= (float) $invoice->total_amount) {
            $status = LunchVendorInvoice::STATUS_PAID;
        } else {
            $status = LunchVendorInvoice::STATUS_PARTIAL;
        }

        $invoice->update(['status' => $status]);
    }

    private function ensureVendor(UnitScope $scope, LunchVendor $vendor): void
    {
        if ($vendor->site_id) {
            $scope->ensureSite($vendor->site_id);

            return;
        }

        if ($vendor->unit_id) {
            $scope->ensureUnit($vendor->unit_id);

            return;
        }

        if ($scope->isScoped()) {
            abort(403, 'Vendor ini berada di luar unit Anda.');
        }
    }

    private function validateVendor(Request $request, UnitScope $scope): array
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:150',
            'unit_id'           => 'nullable|exists:units,id',
            'site_id'           => 'nullable|exists:sites,id',
            'contact_person'    => 'nullable|string|max:150',
            'phone'             => 'nullable|string|max:50',
            'price_per_portion' => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string|max:1000',
            'is_active'         => 'nullable|boolean',
        ]);

        if (! empty($validated['site_id'])) {
            $scope->ensureSite((int) $validated['site_id']);
        }

        if (! empty($validated['unit_id'])) {
            $scope->ensureUnit((int) $validated['unit_id']);
        }

        return $validated;
    }

    private function vendorAttributes(array $validated, UnitScope $scope): array
    {
        $siteId = $validated['site_id'] ?? null;
        $unitId = $validated['unit_id'] ?? null;

        if ($siteId) {
            $unitId = Site::whereKey($siteId)->value('unit_id');
        }

        if (! $unitId && ! $scope->isGlobal()) {
            $unitId = $scope->user()?->unit_id;
        }

        return [
            'name'              => $validated['name'],
            'unit_id'           => $unitId,
            'site_id'           => $siteId,
            'contact_person'    => $validated['contact_person'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'price_per_portion' => $validated['price_per_portion'] ?? null,
            'notes'             => $validated['notes'] ?? null,
            'is_active'         => (bool) ($validated['is_active'] ?? true),
        ];
    }
}
