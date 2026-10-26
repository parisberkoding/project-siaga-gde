<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\LunchForecast;
use App\Models\LunchTransaction;
use App\Models\Site;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class LunchAnalyticsController extends Controller
{
    /**
     * Analytics Lunch Management: akurasi forecast, variance forecast vs
     * aktual per site, top manual override, dan progres Learning Engine.
     */
    public function index(Request $request)
    {
        $days  = (int) $request->get('days', 14);
        $start = now()->subDays($days - 1)->toDateString();
        $end   = now()->toDateString();

        $siteIds = $request->user()?->unitSiteIds();

        $sites = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->get();

        // ── Akurasi harian ────────────────────────────────────────────
        $accuracyRows = LunchForecast::whereBetween('lunch_date', [$start, $end])
            ->inUnit($siteIds)
            ->whereNotNull('is_prediction_correct')
            ->selectRaw('lunch_date, count(*) as evaluated, sum(is_prediction_correct) as correct')
            ->groupBy('lunch_date')
            ->orderBy('lunch_date')
            ->get()
            ->keyBy(fn ($r) => $r->lunch_date instanceof \Carbon\Carbon
                ? $r->lunch_date->toDateString()
                : (string) $r->lunch_date);

        $accuracyTrend = collect(CarbonPeriod::create($start, $end))->map(function ($d) use ($accuracyRows) {
            $row = $accuracyRows->get($d->toDateString());

            return [
                'date'     => $d->format('d/m'),
                'accuracy' => $row && $row->evaluated > 0
                    ? round($row->correct / $row->evaluated * 100, 1)
                    : null,
            ];
        });

        // ── Variance forecast vs aktual per site ─────────────────────
        $forecastBySite = LunchForecast::whereBetween('lunch_date', [$start, $end])
            ->inUnit($siteIds)
            ->whereNotNull('predicted_site_id')
            ->selectRaw('predicted_site_id as site_id, count(*) as total')
            ->groupBy('predicted_site_id')
            ->pluck('total', 'site_id');

        $actualBySite = LunchTransaction::whereBetween('transaction_date', [$start, $end])
            ->inUnit($siteIds)
            ->selectRaw('site_id, count(*) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $variance = $sites->map(fn (Site $s) => [
            'site'     => $s->name,
            'forecast' => (int) ($forecastBySite[$s->id] ?? 0),
            'actual'   => (int) ($actualBySite[$s->id] ?? 0),
            'variance' => (int) ($actualBySite[$s->id] ?? 0) - (int) ($forecastBySite[$s->id] ?? 0),
        ]);

        // ── Top manual override ───────────────────────────────────────
        $topOverrides = LunchForecast::whereBetween('lunch_date', [$start, $end])
            ->inUnit($siteIds)
            ->where('prediction_source', 'manual')
            ->whereNotNull('employee_default_id')
            ->selectRaw('employee_default_id, count(*) as total')
            ->groupBy('employee_default_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('employee:id,employee_name')
            ->get();

        // ── Learning progress ─────────────────────────────────────────
        $learning = [
            'resolved'      => LunchTransaction::whereBetween('transaction_date', [$start, $end])->inUnit($siteIds)->resolved()->count(),
            'unresolved'    => LunchTransaction::whereBetween('transaction_date', [$start, $end])->inUnit($siteIds)->unresolved()->count(),
            'avgConfidence' => round((float) LunchTransaction::whereBetween('transaction_date', [$start, $end])
                ->inUnit($siteIds)->resolved()->avg('confidence_score'), 1),
            'byMethod'      => LunchTransaction::whereBetween('transaction_date', [$start, $end])
                ->inUnit($siteIds)
                ->selectRaw('resolution_method, count(*) as total')
                ->groupBy('resolution_method')
                ->pluck('total', 'resolution_method'),
        ];

        // ── Site trend (transaksi aktual per site per hari) ───────────
        $trendRows = LunchTransaction::whereBetween('transaction_date', [$start, $end])
            ->inUnit($siteIds)
            ->selectRaw('transaction_date, site_id, count(*) as total')
            ->groupBy('transaction_date', 'site_id')
            ->get();

        $siteTrend = collect(CarbonPeriod::create($start, $end))->map(function ($d) use ($trendRows, $sites) {
            $row = ['date' => $d->format('d/m')];
            foreach ($sites as $s) {
                $row[$s->code] = (int) $trendRows
                    ->first(fn ($t) => $t->site_id === $s->id
                        && (($t->transaction_date instanceof \Carbon\Carbon
                            ? $t->transaction_date->toDateString()
                            : (string) $t->transaction_date) === $d->toDateString()))
                    ?->total;
            }

            return $row;
        });

        return view('lunch.analytics.index', compact(
            'days', 'sites', 'accuracyTrend', 'variance', 'topOverrides', 'learning', 'siteTrend'
        ));
    }
}
