<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDefault;
use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchGuestReservation;
use App\Models\LunchTransaction;
use App\Models\Site;
use App\Services\Lunch\LunchForecastService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LunchDashboardController extends Controller
{
    /**
     * Dashboard Lunch Management: forecast hari ini, kehadiran aktual
     * (QR), akurasi kemarin, tamu, dan item yang butuh perhatian.
     */
    public function index(Request $request, LunchForecastService $forecastService)
    {
        $date = $request->get('date', now()->toDateString());
        $yesterday = Carbon::parse($date)->subDay()->toDateString();

        $siteIds = $request->user()?->unitSiteIds();

        $sites = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
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

        $accuracyYesterday = $forecastService->accuracyForDate($yesterday);

        $unresolvedToday = LunchTransaction::forDate($date)->inUnit($siteIds)->unresolved()->count();

        $pendingGuests = LunchGuestReservation::pending()
            ->inUnit($siteIds)
            ->whereDate('reservation_date', '>=', now()->toDateString())
            ->whereDate('reservation_date', '<=', now()->addDays(2)->toDateString())
            ->count();

        $distributions = LunchDistribution::forDate($date)->inUnit($siteIds)->with('site')->get();

        // Step-status untuk panduan alur (seamless) — relatif terhadap hari ini
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $stepStatus = [
            'employee_count' => EmployeeDefault::active()->inUnit($siteIds)->count(),
            'forecast_tomorrow' => LunchForecast::forDate($tomorrow)->inUnit($siteIds)->exists(),
            'distribution_today' => LunchDistribution::forDate($today)->inUnit($siteIds)->get(),
            'attendance_today' => LunchTransaction::forDate($today)->inUnit($siteIds)->count(),
        ];
        $stepStatus['distribution_today_locked'] = $stepStatus['distribution_today']->where('is_locked', true)->count() > 0;

        return view('lunch.dashboard', compact(
            'date', 'sites', 'forecastBySite', 'attendanceBySite', 'guestBySite',
            'accuracyYesterday', 'unresolvedToday', 'pendingGuests', 'distributions',
            'stepStatus'
        ));
    }
}
