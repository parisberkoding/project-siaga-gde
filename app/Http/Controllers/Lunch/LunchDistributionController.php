<?php

namespace App\Http\Controllers\Lunch;

use App\Exports\CateringExport;
use App\Http\Controllers\Controller;
use App\Models\LunchDistribution;
use App\Models\LunchGuestReservation;
use App\Models\Site;
use App\Services\Lunch\LunchDistributionService;
use App\Services\UnitScope;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;

class LunchDistributionController extends Controller
{
    public function __construct(private LunchDistributionService $service)
    {
    }

    /**
     * Distribusi porsi per site per tanggal (angka yang dikirim vendor).
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->addDay()->toDateString());

        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $distributions = LunchDistribution::forDate($date)
            ->inUnit($siteIds)
            ->with(['site.unit', 'lockedBy', 'customPurchase'])
            ->get();

        $guests = LunchGuestReservation::whereDate('reservation_date', $date)
            ->inUnit($siteIds)
            ->with('site')
            ->orderBy('site_id')
            ->get();

        return view('lunch.distributions.index', compact('date', 'distributions', 'guests'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date']);

        $scope = UnitScope::for($request->user());

        $this->service->buildForDate($validated['date'], $request->user(), $scope->siteIds());

        return back()->with('success', "Distribusi {$validated['date']} berhasil dihitung ulang dari forecast + reservasi tamu.");
    }

    public function lock(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date']);

        $scope = UnitScope::for($request->user());

        $count = $this->service->lockForDate($validated['date'], $request->user(), $scope->siteIds());

        return back()->with('success', "{$count} distribusi dikunci. Angka pesanan vendor tidak akan berubah otomatis.");
    }

    /**
     * Form filter export Excel untuk vendor.
     */
    public function exportForm(Request $request)
    {
        $scope = UnitScope::for($request->user());

        $sites = $scope->sites();

        return view('lunch.distributions.export-form', compact('sites'));
    }

    /**
     * Download Excel catering report (3 sheet), dibatasi unit user.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'site_id'    => 'nullable|exists:sites,id',
        ]);

        $scope = UnitScope::for($request->user());

        if (! empty($validated['site_id'])) {
            $scope->ensureSite((int) $validated['site_id']);
        }

        $fileName = 'Catering-' . $validated['start_date'] . '-sd-' . $validated['end_date'] . '.xlsx';

        $export = new CateringExport(
            $validated['start_date'],
            $validated['end_date'],
            ! empty($validated['site_id']) ? (int) $validated['site_id'] : null,
            $scope->siteIds()
        );

        return $export->download($fileName);
    }

    /**
     * Halaman cetak QR lokasi untuk QR Attendance, hanya site di unit user.
     */
    public function qrCodes(Request $request)
    {
        $scope = UnitScope::for($request->user());

        $sites = $scope->sites();

        return view('lunch.qr-codes', compact('sites'));
    }

    /**
     * Gambar QR (SVG) untuk satu site. QR berisi URL halaman attendance
     * publik, sehingga karyawan cukup scan dengan kamera HP apa pun.
     */
    public function qrImage(Request $request, Site $site)
    {
        UnitScope::for($request->user())->ensureSite($site);

        $url = route('lunch.attendance.form', ['siteCode' => $site->code]);

        $writer = new Writer(new ImageRenderer(
            new RendererStyle(400, 1),
            new SvgImageBackEnd()
        ));

        return response($writer->writeString($url), 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
