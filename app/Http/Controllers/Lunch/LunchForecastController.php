<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDefault;
use App\Models\LunchForecast;
use App\Models\Site;
use App\Services\Lunch\LunchForecastService;
use Illuminate\Http\Request;

class LunchForecastController extends Controller
{
    public function __construct(private LunchForecastService $service)
    {
    }

    /**
     * Daftar forecast per tanggal + ringkasan per site & per sumber.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->addDay()->toDateString());

        $siteIds = $request->user()?->unitSiteIds();

        $forecasts = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->with(['employee.defaultSite', 'predictedSite', 'actualSite'])
            ->orderBy('predicted_site_id')
            ->paginate(50)
            ->withQueryString();

        $totalsBySite = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->whereNotNull('predicted_site_id')
            ->selectRaw('predicted_site_id, count(*) as total')
            ->groupBy('predicted_site_id')
            ->pluck('total', 'predicted_site_id');

        $totalsBySource = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->selectRaw('prediction_source, count(*) as total')
            ->groupBy('prediction_source')
            ->pluck('total', 'prediction_source');

        $sites     = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->get();
        $employees = EmployeeDefault::active()->inUnit($siteIds)->orderBy('employee_name')->get(['id', 'slug', 'employee_name']);
        $accuracy  = $this->service->accuracyForDate($date);

        return view('lunch.forecasts.index', compact(
            'date', 'forecasts', 'totalsBySite', 'totalsBySource', 'sites', 'employees', 'accuracy'
        ));
    }

    /**
     * Generate / regenerate forecast untuk satu tanggal.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date']);

        $batch = $this->service->generateForDate($validated['date'], $request->user(), 'manual');

        return back()->with('success', "Forecast {$validated['date']} berhasil digenerate (batch #{$batch->id}, {$batch->forecasts()->count()} karyawan).");
    }

    /**
     * Manual override — prioritas tertinggi, tidak ditimpa engine.
     */
    public function override(Request $request)
    {
        $validated = $request->validate([
            'slug'    => 'required|exists:employee_defaults,slug',
            'date'    => 'required|date',
            'site_id' => 'required|exists:sites,id',
        ]);

        $employee = EmployeeDefault::where('slug', $validated['slug'])->firstOrFail();

        $this->service->applyOverride($employee, $validated['date'], (int) $validated['site_id'], $request->user());

        return back()->with('success', "Override untuk {$employee->employee_name} tersimpan.");
    }
}
