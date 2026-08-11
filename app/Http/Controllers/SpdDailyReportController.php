<?php

namespace App\Http\Controllers;

use App\Models\LpjPenggantian;
use App\Models\LpjPenggantianDetail;
use App\Models\SpdDailyReport;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Services\ImageAnalysisService;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * SpdDailyReportController — LAPORAN HARIAN SPD (pre-fixed report).
 *
 * Alur baru pelaporan SPD:
 *  1. Driver mengirim laporan per hari selama perjalanan dinas
 *     (status draft → submitted). Item di-upload satu-per-satu via AJAX
 *     dan langsung tersimpan server-side → refresh tidak menghilangkan data.
 *  2. Setelah tanggal selesai SPD lewat, driver membuka halaman validasi
 *     yang menampilkan SEMUA file yang sudah dikirim; bisa menghapus/mengubah
 *     item yang salah sebelum LPJ dibuat.
 *  3. Tombol "Submit sebagai LPJ" menggabungkan seluruh laporan harian menjadi
 *     satu LpjPenggantian (fixed report) berstatus pending → direview admin GA.
 *
 * Informasi keuangan (uang_dari / jumlah_diberikan) TIDAK diisi driver —
 * diisi oleh admin GA / keuangan saat review LPJ.
 */
class SpdDailyReportController extends Controller
{
    protected ImageAnalysisService $analysis;

    public function __construct(ImageAnalysisService $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * Surat SPD milik user: approved, belum punya LPJ, berikut laporan hariannya.
     */
    private function findSuratForUser($suratId): SuratSpd
    {
        return SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with(['vehicle', 'dailyReports.strukItems'])
            ->findOrFail($suratId);
    }

    /**
     * GET /pelaporan/spd/{surat}/daily — daftar laporan harian satu surat.
     */
    public function index(SuratSpd $surat)
    {
        $surat = $this->findSuratForUser($surat->id);

        $reports = $surat->dailyReports->sortByDesc('tanggal')->values();
        $draft = $reports->first(fn ($r) => $r->isDraft());

        return view('pelaporan.spd.daily-index', compact('surat', 'reports', 'draft'));
    }

    /**
     * GET /pelaporan/spd/{surat}/daily/create — form laporan harian baru.
     * Kalau masih ada draft aktif, langsung dialihkan ke draft itu.
     */
    public function create(SuratSpd $surat)
    {
        $surat = $this->findSuratForUser($surat->id);

        $existingDraft = $surat->dailyReports->first(fn ($r) => $r->isDraft());
        if ($existingDraft) {
            return redirect()->route('pelaporan.spd.daily.edit', [$surat, $existingDraft])
                ->with('info', 'Anda masih punya draft laporan harian yang belum dikirim — lanjutkan draft tersebut.');
        }

        return view('pelaporan.spd.daily-form', [
            'surat' => $surat,
            'daily' => new SpdDailyReport(),
        ]);
    }

    /**
     * GET /pelaporan/spd/{surat}/daily/{daily}/edit — edit draft laporan harian.
     */
    public function edit(SuratSpd $surat, SpdDailyReport $daily)
    {
        $daily = SpdDailyReport::with('strukItems')
            ->where('user_id', Auth::id())
            ->draft()
            ->findOrFail($daily->id);

        $surat = $this->findSuratForUser($surat->id);

        return view('pelaporan.spd.daily-form', compact('surat', 'daily'));
    }

    /**
     * POST /pelaporan/spd/{surat}/daily — kirim laporan harian baru.
     */
    public function store(Request $request, SuratSpd $surat)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'      => 'required|date',
            'uraian'       => 'nullable|string|max:2000',
            'catatan'      => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $surat = $this->findSuratForUser($surat->id);

        $daily = $surat->dailyReports->first(fn ($r) => $r->isDraft());
        if (! $daily) {
            $daily = SpdDailyReport::create([
                'surat_spd_id' => $surat->id,
                'user_id'      => Auth::id(),
                'tanggal'      => $request->tanggal,
            ]);
        }

        $this->submitDaily($daily, $request);

        return redirect()->route('pelaporan.spd.daily.index', $surat)
            ->with('success', 'Laporan harian ' . $daily->tanggal->translatedFormat('d M Y') . ' berhasil dikirim.');
    }

    /**
     * PUT /pelaporan/spd/{surat}/daily/{daily} — update draft & kirim.
     */
    public function update(Request $request, SuratSpd $surat, SpdDailyReport $daily)
    {
        $daily = SpdDailyReport::where('user_id', Auth::id())->draft()->findOrFail($daily->id);
        $surat = $this->findSuratForUser($surat->id);

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'uraian'  => 'nullable|string|max:2000',
            'catatan' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $this->submitDaily($daily, $request);

        return redirect()->route('pelaporan.spd.daily.index', $surat)
            ->with('success', 'Laporan harian berhasil diperbarui & dikirim.');
    }

    private function submitDaily(SpdDailyReport $daily, Request $request): void
    {
        DB::transaction(function () use ($daily, $request) {
            $daily->update([
                'tanggal'      => $request->tanggal,
                'uraian'       => $request->uraian ?? '',
                'catatan'      => $request->catatan ?? '',
                'status'       => SpdDailyReport::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            // Sinkronkan tanggal item dengan tanggal laporan harian
            $daily->strukItems()->update(['tanggal' => $request->tanggal]);
        });
    }

    /**
     * DELETE /pelaporan/spd/{surat}/daily/{daily} — hapus draft.
     */
    public function destroy(SuratSpd $surat, SpdDailyReport $daily)
    {
        $daily = SpdDailyReport::where('user_id', Auth::id())->draft()->findOrFail($daily->id);
        $surat = $this->findSuratForUser($surat->id);

        $daily->delete(); // struk_items ikut cascade

        return redirect()->route('pelaporan.spd.daily.index', $surat)
            ->with('success', 'Draft laporan harian dihapus.');
    }

    /**
     * POST /pelaporan/spd/{surat}/daily/item — AJAX upload 1 struk ke draft.
     * Membuat draft otomatis bila belum ada → refresh tidak menghilangkan data.
     */
    public function uploadItem(Request $request, SuratSpd $surat)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'nullable|date',
            'struk'   => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $surat = $this->findSuratForUser($surat->id);

        $daily = $surat->dailyReports->first(fn ($r) => $r->isDraft());
        if (! $daily) {
            $daily = SpdDailyReport::create([
                'surat_spd_id' => $surat->id,
                'user_id'      => Auth::id(),
                'tanggal'      => $request->tanggal ?? now()->toDateString(),
            ]);
        }

        // AI/OCR
        $tmpPath = $request->file('struk')->store('temp/struk-ocr', 'local');
        $absolute = Storage::disk('local')->path($tmpPath);
        $result = $this->analysis->analyzeImage($absolute);
        Storage::disk('local')->delete($tmpPath);

        $kategori = StrukItem::normalizeKategori($result['kategori'] ?? 'Lainnya');
        $metadata = $result['metadata'] ?? [];
        $text = $result['text'] ?? '';
        $nominal = (float) ($metadata['nominal'] ?? 0);

        $path = $this->storeStrukImage($request->file('struk'), $surat->nomor_surat);
        $itemDate = $request->tanggal ?? $daily->tanggal;
        $itemCode = StrukItem::generateItemCode($kategori, $surat->nomor_surat, $itemDate);

        $item = StrukItem::create([
            'spd_id'              => $surat->id,
            'spd_daily_report_id' => $daily->id,
            'nomor_surat_spd'     => $surat->nomor_surat,
            'item_code'           => $itemCode,
            'path_image'          => $path,
            'struk_kategori'      => $kategori,
            'kategori_source'     => 'ai',
            'metadata_json'       => ! empty($metadata) ? $metadata : null,
            'nominal'             => $nominal,
            'tanggal'             => $itemDate,
            'keterangan'          => self::defaultKeterangan($kategori),
            'ocr_text'            => $text ?: null,
            'urutan'              => $daily->strukItems()->count() + 1,
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id'         => $item->id,
                'slug'       => $item->slug,
                'item_code'  => $item->item_code,
                'kategori'   => $item->struk_kategori,
                'nominal'    => $nominal,
                'tanggal'    => $item->tanggal ? $item->tanggal->format('Y-m-d') : null,
                'url'        => \Storage::disk('public')->url($item->path_image),
                'keterangan' => $item->keterangan,
            ],
            'daily_report_id' => $daily->id,
        ]);
    }

    /**
     * PATCH /pelaporan/spd/{surat}/daily/item/{item} — AJAX ubah nominal/kategori item.
     * Diizinkan selama LPJ final belum dibuat.
     */
    public function updateItem(Request $request, SuratSpd $surat, StrukItem $item)
    {
        $item = $this->assertEditableItem($surat, $item);

        $validator = Validator::make($request->all(), [
            'nominal'        => 'required|numeric|min:0',
            'struk_kategori' => 'required|in:BBM,Tol,Parkir,Lainnya,Tambahan',
            'keterangan'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $oldKeterangan = $request->has('keterangan') ? (string) $item->keterangan : null;
        $changed = (float) $item->nominal !== (float) $request->nominal
            || $item->struk_kategori !== $request->struk_kategori
            || ($request->has('keterangan') && $oldKeterangan !== trim((string) $request->keterangan));

        $item->update([
            'nominal'        => $request->nominal,
            'struk_kategori' => $request->struk_kategori,
            'kategori_source' => 'manual',
            'keterangan'     => $request->has('keterangan') ? trim((string) $request->keterangan) : $item->keterangan,
            'edited_at'      => $changed ? now() : $item->edited_at,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /pelaporan/spd/{surat}/daily/item/{item} — AJAX hapus item.
     * Diizinkan selama LPJ final belum dibuat.
     */
    public function deleteItem(SuratSpd $surat, StrukItem $item)
    {
        $item = $this->assertEditableItem($surat, $item);
        $item->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Pastikan struk item milik laporan harian user dari surat ini;
     * blokir edit setelah LPJ dibuat.
     */
    private function assertEditableItem(SuratSpd $surat, StrukItem $item): StrukItem
    {
        $item->load('dailyReport.suratSpd');

        if ($item->dailyReport->surat_spd_id !== $surat->id) {
            abort(404);
        }

        if ($item->dailyReport->user_id !== Auth::id()) {
            abort(404);
        }

        if ($item->dailyReport->suratSpd->hasLpj()) {
            abort(422, 'LPJ sudah dibuat — item tidak bisa diubah lagi.');
        }

        return $item;
    }

    /**
     * GET /pelaporan/spd/{surat}/validasi — halaman validasi seluruh laporan harian.
     * Menampilkan SEMUA file yang dikirim driver; item bisa dihapus/diubah
     * sebelum dibuat menjadi LPJ.
     */
    public function validasi(SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with([
                'vehicle',
                'dailyReports' => fn ($q) => $q->submitted()->orderBy('tanggal'),
                'dailyReports.strukItems',
            ])
            ->findOrFail($surat->id);

        $submitted = $surat->dailyReports;
        $items = $submitted->flatMap(fn ($r) => $r->strukItems);

        return view('pelaporan.spd.validasi', compact('surat', 'submitted', 'items'));
    }

    /**
     * POST /pelaporan/spd/{surat}/finalize — gabung seluruh laporan harian
     * menjadi satu LPJ (fixed report) berstatus pending.
     */
    public function finalize(Request $request, SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with([
                'vehicle',
                'dailyReports' => fn ($q) => $q->submitted()->orderBy('tanggal'),
                'dailyReports.strukItems',
            ])
            ->findOrFail($surat->id);

        if (! $surat->tanggal_kembali->isPast()) {
            return redirect()->back()
                ->with('error', 'SPD belum selesai — tunggu hingga tanggal kembali lewat sebelum membuat LPJ.');
        }

        $submitted = $surat->dailyReports;
        if ($submitted->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada laporan harian yang dikirim.');
        }

        $validator = Validator::make($request->all(), [
            'nama'                 => 'required|string|max:255',
            'jabatan'              => 'nullable|string|max:255',
            'uraian'               => 'nullable|string|max:3000',
            'odo_before'           => 'nullable|numeric|min:0',
            'odo_after'            => 'nullable|numeric|min:0',
            'kebutuhan_bbm_liter'  => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $allItems = $submitted->flatMap(fn ($r) => $r->strukItems);
        $financialItems = $allItems->whereIn('struk_kategori', ['BBM', 'Tol', 'Parkir', 'Lainnya']);

        if ($financialItems->isEmpty()) {
            return redirect()->back()
                ->with('error', 'Belum ada item pengeluaran (BBM/Tol/Parkir/Lainnya) pada laporan harian.');
        }

        $totalPengeluaran = (float) $financialItems->sum('nominal');

        $lpj = DB::transaction(function () use ($surat, $submitted, $allItems, $financialItems, $totalPengeluaran, $request) {
            $lpj = LpjPenggantian::create([
                'surat_spd_id'        => $surat->id,
                'user_id'             => Auth::id(),
                'nama'                => $request->nama,
                'jabatan'             => $request->jabatan ?? '',
                'vehicle_id'          => $surat->vehicle_id,
                'nomor_polisi'        => $surat->vehicle?->consumerial_code ?? '-',
                'odo_before'          => $request->odo_before,
                'odo_after'           => $request->odo_after,
                'kebutuhan_bbm_liter' => $request->kebutuhan_bbm_liter,
                'uraian'              => $request->uraian ?? '',
                'uang_dari'           => 'KAS',
                'jumlah_diberikan'    => 0,
                'total_pengeluaran'   => $totalPengeluaran,
                'catatan'             => '',
                'status'              => 'pending',
            ]);

            $urutan = 0;
            foreach ($financialItems as $item) {
                $urutan++;
                $tipe = $item->struk_kategori === 'Lainnya' ? 'Lain-lain' : $item->struk_kategori;

                $detail = LpjPenggantianDetail::create([
                    'lpj_penggantian_id' => $lpj->id,
                    'tanggal'            => $item->tanggal ? $item->tanggal->toDateString() : now()->toDateString(),
                    'tipe_penggunaan'    => $tipe,
                    'nominal'            => $item->nominal,
                    'keterangan'         => $item->keterangan ?? '',
                    'urutan'             => $urutan,
                ]);

                $item->update([
                    'lpj_id'        => $lpj->id,
                    'lpj_detail_id' => $detail->id,
                ]);
            }

            // Item foto tambahan (non-keuangan) tetap ter-link ke LPJ utk lampiran cetak
            foreach ($allItems->where('struk_kategori', 'Tambahan') as $item) {
                $item->update(['lpj_id' => $lpj->id]);
            }

            return $lpj;
        });

        return redirect()->route('requester.lpj.show', $lpj)
            ->with('success', 'LPJ berhasil dibuat dari ' . $submitted->count() . ' laporan harian.');
    }

    /**
     * Keterangan ringkas default per kategori (bisa diedit driver di validasi).
     * Teks OCR mentah disimpan terpisah di kolom ocr_text, bukan di keterangan.
     */
    protected static function defaultKeterangan(string $kategori): string
    {
        return match ($kategori) {
            'BBM'      => 'Pengisian BBM',
            'Tol'      => 'Pembayaran Tol',
            'Parkir'   => 'Biaya Parkir',
            'Tambahan' => 'Dokumentasi Tambahan',
            default    => 'Pengeluaran Lainnya',
        };
    }

    /**
     * Simpan gambar struk ke storage public (kompresi 50%, tanpa resize).
     */
    protected function storeStrukImage($file, string $context): string
    {
        $tmp = $file->getRealPath();
        $jpeg = ImageCompressor::compressJpeg($tmp, 50);

        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = 'S.' . preg_replace('/[^A-Za-z0-9\-]/', '_', $context)
            . '-' . now()->format('dmY-His') . '-' . strtoupper(substr(uniqid(), -6)) . '.' . $ext;

        $path = 'transaction_proofs/struk/' . $filename;

        if ($jpeg !== null) {
            Storage::disk('public')->put($path, $jpeg);
        } else {
            $file->storeAs('transaction_proofs/struk', $filename, 'public');
        }

        return $path;
    }
}
