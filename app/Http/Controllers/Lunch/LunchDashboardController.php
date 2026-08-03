<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
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
        $date      = $request->get('date', now()->toDateString());
        $yesterday = Carbon::parse($date)->subDay()->toDateString();

        $sites = Site::active()->get();

        $forecastBySite = LunchForecast::forDate($date)
            ->whereNotNull('predicted_site_id')
            ->selectRaw('predicted_site_id, count(*) as total')
            ->groupBy('predicted_site_id')
            ->pluck('total', 'predicted_site_id');

        $attendanceBySite = LunchTransaction::forDate($date)
            ->selectRaw('site_id, count(*) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $guestBySite = LunchGuestReservation::whereDate('reservation_date', $date)
            ->confirmed()
            ->selectRaw('site_id, sum(guest_count) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $accuracyYesterday = $forecastService->accuracyForDate($yesterday);

        $unresolvedToday = LunchTransaction::forDate($date)->unresolved()->count();

        $pendingGuests = LunchGuestReservation::pending()
            ->whereDate('reservation_date', '>=', now()->toDateString())
            ->whereDate('reservation_date', '<=', now()->addDays(2)->toDateString())
            ->count();

        $distributions = LunchDistribution::forDate($date)->with('site')->get();

        return view('lunch.dashboard', compact(
            'date', 'sites', 'forecastBySite', 'attendanceBySite', 'guestBySite',
            'accuracyYesterday', 'unresolvedToday', 'pendingGuests', 'distributions'
        ));
    }
}
