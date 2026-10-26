<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDefault;
use App\Models\LunchCustomPurchase;
use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchGuestReservation;
use App\Models\LunchTransaction;
use App\Models\Site;
use App\Services\Lunch\LunchForecastService;
use App\Services\UnitScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LunchDashboardController extends Controller
{
    /**
     * Dashboard = "check-in hari ini".
     *
     * Fokus: siapa yang sudah scan, berapa porsi hari ini, tamu yang perlu
     * konfirmasi, dan pintasan ke Data Forecast. Forecast detail, override,
     * dan distribusi tidak diduplikasi di sini agar alurnya tidak bercabang.
     */
    public function index(Request $request, LunchForecastService $forecastService)
    {
        $date = $request->get('date', now()->toDateString());
        $yesterday = Carbon::parse($date)->subDay()->toDateString();

        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $sites = Site::active()
            ->with('unit')
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->orderBy('name')
            ->get();

        $forecastBySite = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->whereNotNull('predicted_site_id')
            ->selectRaw('predicted_site_id, count(*) as total')
            ->groupBy('predicted_site_id')
            ->pluck('total', 'predicted_site_id');

        $attendanceBySite = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->selectRaw('site_id, count(*) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $guestBySite = LunchGuestReservation::whereDate('reservation_date', $date)
            ->inUnit($siteIds)
            ->confirmed()
            ->selectRaw('site_id, sum(guest_count) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $accuracyYesterday = $forecastService->accuracyForDate($yesterday, $siteIds);

        $unresolvedToday = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->unresolved()
            ->count();

        $pendingGuests = LunchGuestReservation::pending()
            ->inUnit($siteIds)
            ->whereDate('reservation_date', '>=', now()->toDateString())
            ->whereDate('reservation_date', '<=', now()->addDays(2)->toDateString())
            ->count();

        $distributions = LunchDistribution::forDate($date)
            ->inUnit($siteIds)
            ->with(['site', 'customPurchase'])
            ->get();

        $customPurchases = LunchCustomPurchase::whereDate('purchase_date', $date)
            ->inUnit($siteIds)
            ->with('site')
            ->get();

        $recentTransactions = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->with('site')
            ->orderByDesc('scanned_at')
            ->limit(10)
            ->get();

        $pendingEmployees = EmployeeDefault::active()
            ->inUnit($siteIds)
            ->pendingVerification()
            ->count();

        $tomorrow = now()->addDay()->toDateString();
        $tomorrowForecastCount = LunchForecast::forDate($tomorrow)
            ->inUnit($siteIds)
            ->whereNotNull('predicted_site_id')
            ->count();

        $units = $scope->units();

        return view('lunch.dashboard', compact(
            'date', 'sites', 'forecastBySite', 'attendanceBySite', 'guestBySite',
            'accuracyYesterday', 'unresolvedToday', 'pendingGuests', 'distributions',
            'customPurchases', 'recentTransactions', 'pendingEmployees',
            'tomorrowForecastCount', 'units'
        ));
    }
}
