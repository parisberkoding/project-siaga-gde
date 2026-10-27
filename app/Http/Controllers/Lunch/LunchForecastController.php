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
use Illuminate\Http\Request;

class LunchForecastController extends Controller
{
    public function __construct(private LunchForecastService $service)
    {
    }

    /**
     * Data Forecast — halaman kerja utama admin GA.
     *
     * Satu halaman memuat: ringkasan per site, daftar forecast (bisa
     * di-override inline), reservasi tamu, distribusi + status kunci, dan
     * custom purchase pada tanggal tersebut. Tujuannya mengurangi klik
     * berpindah-pindah halaman.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->addDay()->toDateString());
        $user = $request->user();
        $scope = UnitScope::for($user);

        $units = $scope->units();

        $unitFilter = $request->filled('unit_id') ? (int) $request->get('unit_id') : null;

        if ($unitFilter !== null) {
            $scope->ensureUnit($unitFilter);
            $siteIds = Site::where('unit_id', $unitFilter)->pluck('id')->all();
        } else {
            $siteIds = $scope->siteIds();
        }

        $siteFilter = $request->filled('site_id') ? (int) $request->get('site_id') : null;

        $query = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->with(['employee.defaultSite.unit', 'employee.unit', 'predictedSite.unit', 'actualSite'])
            ->when($siteFilter, fn ($q) => $q->where('predicted_site_id', $siteFilter))
            ->when($request->get('source'), fn ($q, $source) => $q->where('prediction_source', $source))
            ->when($request->get('search'), function ($q, $search) {
                $q->whereHas('employee', fn ($e) => $e->where('employee_name', 'like', "%{$search}%"));
            })
            ->orderBy('predicted_site_id')
            ->orderBy('id');

        $forecasts = $query->paginate(50)->withQueryString();

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

        $attendanceBySite = LunchTransaction::forDate($date)
            ->inUnit($siteIds)
            ->selectRaw('site_id, count(*) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $sites = Site::active()
            ->with('unit')
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->orderBy('name')
            ->get();

        $employees = EmployeeDefault::active()
            ->inUnit($siteIds)
            ->with(['defaultSite', 'unit'])
            ->orderBy('employee_name')
            ->get(['id', 'slug', 'employee_name', 'default_site_id', 'unit_id', 'verification_status']);

        $accuracy = $this->service->accuracyForDate($date, $siteIds);

        $guests = LunchGuestReservation::whereDate('reservation_date', $date)
            ->inUnit($siteIds)
            ->with('site')
            ->orderBy('site_id')
            ->get();

        $distributions = LunchDistribution::forDate($date)
            ->inUnit($siteIds)
            ->with(['site', 'customPurchase'])
            ->get()
            ->keyBy('site_id');

        $customPurchases = LunchCustomPurchase::whereDate('purchase_date', $date)
            ->inUnit($siteIds)
            ->with(['site', 'items'])
            ->get();

        $customBySite = $customPurchases->keyBy('site_id');

        $pendingEmployees = EmployeeDefault::active()
            ->inUnit($siteIds)
            ->pendingVerification()
            ->count();

        $unitFilter = $unitFilter;

        return view('lunch.forecasts.index', compact(
            'date', 'forecasts', 'totalsBySite', 'totalsBySource', 'sites', 'employees',
            'accuracy', 'units', 'unitFilter', 'siteFilter', 'guests', 'distributions',
            'customPurchases', 'customBySite', 'pendingEmployees', 'attendanceBySite'
        ));
    }

    /**
     * Generate / regenerate forecast untuk satu tanggal, dibatasi unit user.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date']);

        $scope = UnitScope::for($request->user());

        $batch = $this->service->generateForDate(
            $validated['date'],
            $request->user(),
            'manual',
            $scope->siteIds()
        );

        return back()->with(
            'success',
            "Forecast {$validated['date']} berhasil digenerate (batch #{$batch->id})."
        );
    }

    /**
     * Manual override — prioritas tertinggi, tidak ditimpa engine.
     * Hanya untuk karyawan & site di unit yang sama.
     */
    public function override(Request $request)
    {
        $validated = $request->validate([
            'slug'    => 'required|exists:employee_defaults,slug',
            'date'    => 'required|date',
            'site_id' => 'required|exists:sites,id',
        ]);

        $scope = UnitScope::for($request->user());

        $employee = EmployeeDefault::where('slug', $validated['slug'])->firstOrFail();
        $site = Site::findOrFail((int) $validated['site_id']);

        $scope->ensureEmployee($employee);

        $unitId = $employee->resolvedUnitId();
        if ($unitId && (int) $site->unit_id !== (int) $unitId) {
            return back()->with('error', "Site {$site->name} berada di luar unit {$employee->employee_name}.");
        }

        $this->service->applyOverride($employee, $validated['date'], $site->id, $request->user());

        return back()->with('success', "Override untuk {$employee->employee_name} tersimpan.");
    }
}
