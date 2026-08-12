<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\LunchTransaction;
use App\Models\Site;
use Illuminate\Http\Request;

class LunchTransactionController extends Controller
{
    /**
     * Daftar kehadiran (Lunch Transaction) — ground truth dari QR
     * Attendance. Mendukung filter tanggal/site/status resolve & search.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $siteIds = $request->user()?->unitSiteIds();

        $query = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->with(['site', 'employee', 'tomorrowSite']);

        if ($siteId = $request->get('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($request->get('status') === 'unresolved') {
            $query->unresolved();
        } elseif ($request->get('status') === 'resolved') {
            $query->resolved();
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('raw_alias', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByDesc('scanned_at')->paginate(50)->withQueryString();

        $totalsBySite = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->selectRaw('site_id, count(*) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $unresolvedCount = LunchTransaction::forDate($date)->inUnit($siteIds)->unresolved()->count();

        $sites = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->get();

        return view('lunch.transactions.index', compact(
            'date', 'transactions', 'totalsBySite', 'unresolvedCount', 'sites'
        ));
    }

    /**
     * Endpoint polling ringan untuk pembaruan realtime tabel kehadiran.
     */
    public function feed(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $siteIds = $request->user()?->unitSiteIds();

        return response()->json([
            'total'      => LunchTransaction::forDate($date)->inUnit($siteIds)->count(),
            'unresolved' => LunchTransaction::forDate($date)->inUnit($siteIds)->unresolved()->count(),
            'by_site'    => LunchTransaction::forDate($date)
                ->inUnit($siteIds)
                ->selectRaw('site_id, count(*) as total')
                ->groupBy('site_id')
                ->pluck('total', 'site_id'),
            'latest'     => LunchTransaction::forDate($date)
                ->inUnit($siteIds)
                ->with('site:id,code')
                ->orderByDesc('scanned_at')
                ->limit(10)
                ->get()
                ->map(fn ($t) => [
                    'name'       => $t->employee_name ?? $t->raw_alias,
                    'site'       => $t->site?->code,
                    'resolved'   => $t->isResolved(),
                    'scanned_at' => $t->scanned_at?->format('H:i:s'),
                ]),
        ]);
    }
}
