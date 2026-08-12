<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchTransaction;
use App\Models\Site;
use Illuminate\Http\Request;

/**
 * Halaman khusus vendor catering (& superadmin): melihat daftar
 * siapa yang makan pada tanggal tertentu per site.
 *
 * Akses: role vendor & superadmin (vendor disiapkan, menu sidebar
 * disembunyikan sementara hingga siap diaktifkan).
 */
class VendorLunchController extends Controller
{
    public function today(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $siteIds = $request->user()?->unitSiteIds();

        $sites = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->orderBy('name')->get();

        // Kehadiran aktual per site (siapa makan hari ini)
        $attendanceBySite = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->with('site', 'employeeDefault')
            ->orderBy('scanned_at')
            ->get()
            ->groupBy('site_id');

        // Forecast per site (perkiraan jumlah)
        $forecastBySite = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->whereNotNull('predicted_site_id')
            ->selectRaw('predicted_site_id, count(*) as total')
            ->groupBy('predicted_site_id')
            ->pluck('total', 'predicted_site_id');

        // Distribusi terkunci per site
        $distributions = LunchDistribution::forDate($date)->inUnit($siteIds)->with('site')->get()->keyBy('site_id');

        return view('lunch.vendor.today', compact(
            'date', 'sites', 'attendanceBySite', 'forecastBySite', 'distributions'
        ));
    }
}
