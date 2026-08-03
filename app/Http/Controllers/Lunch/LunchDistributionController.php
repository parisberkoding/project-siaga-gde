<?php

namespace App\Http\Controllers\Lunch;

use App\Exports\CateringExport;
use App\Http\Controllers\Controller;
use App\Models\LunchDistribution;
use App\Models\LunchGuestReservation;
use App\Models\Site;
use App\Services\Lunch\LunchDistributionService;
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

        $distributions = LunchDistribution::forDate($date)
            ->with(['site', 'lockedBy'])
            ->get();

        $guests = LunchGuestReservation::whereDate('reservation_date', $date)
            ->with('site')
            ->orderBy('site_id')
            ->get();

        return view('lunch.distributions.index', compact('date', 'distributions', 'guests'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date']);

        $this->service->buildForDate($validated['date'], $request->user());

        return back()->with('success', "Distribusi {$validated['date']} berhasil dihitung ulang dari forecast + reservasi tamu.");
    }

    public function lock(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date']);

        $count = $this->service->lockForDate($validated['date'], $request->user());

        return back()->with('success', "{$count} distribusi dikunci. Angka pesanan vendor tidak akan berubah otomatis.");
    }

    /**
     * Form filter export Excel untuk vendor.
     */
    public function exportForm()
    {
        return view('lunch.distributions.export-form');
    }

    /**
     * Download Excel catering report (3 sheet).
     */
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'site_id'    => 'nullable|exists:sites,id',
        ]);

        $fileName = 'CateringPatuha-' . $request->start_date . '-sd-' . $request->end_date . '.xlsx';

        $export = new CateringExport(
            $request->start_date,
            $request->end_date,
            $request->site_id ? (int) $request->site_id : null
        );

        return $export->download($fileName);
    }

    /**
     * Halaman cetak QR lokasi untuk QR Attendance.
     */
    public function qrCodes()
    {
        $sites = Site::active()->get();

        return view('lunch.qr-codes', compact('sites'));
    }

    /**
     * Gambar QR (SVG) untuk satu site. QR berisi URL halaman attendance
     * publik, sehingga karyawan cukup scan dengan kamera HP apa pun.
     */
    public function qrImage(Site $site)
    {
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
