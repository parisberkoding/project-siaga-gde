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
 * SpdDailyReportController — PELAPORAN SPD (FLOW BARU: SATU HALAMAN VALIDASI).
 *
 * ═════════════ FLOW LAMA (di-hide, dikomentari di masing-masing method) ═════════════
 *  1. Driver wajib input struk PER HARI (halaman /daily → form /daily/create,
 *     status draft → submitted), item di-upload satu-per-satu via AJAX.
 *  2. Setelah SPD selesai, driver buka halaman validasi, meninjau SEMUA file
 *     yang sudah dikirim.
 *  3. Tombol "Submit sebagai LPJ" menggabungkan seluruh laporan harian menjadi
 *     satu LpjPenggantian (fixed report) status pending → direview admin GA.
 *
 * ═════════════ FLOW BARU (aktif — diimplementasikan di method bawah) ═════════════
 *  View sehari-hari DIHIDE (index/create/edit redirect ke /validasi).
 *  Satu view validasi sekarang dipakai untuk SEMUA pelaporan SPD, dipecah
 *  menjadi 2 SEGMENTASI:
 *    ▶ DRAFT : input struk saat periode SPD masih berjalan, ATAU bulk input
 *              setelah SPD selesai tapi driver belum mau finalisasi. Item
 *              tersimpan otomatis server-side dalam status draft.
 *    ▶ FINAL : setelah tanggal_kembali lewat, driver bulk-upload dari halaman
 *              validasi (maks 5 gambar per kirim, bisa lanjut 5 lagi dst) lalu
 *              langsung "Submit sebagai LPJ".
 *
 *  uploadItems() = bulk upload (maks 5/kirim) → FLOW BARU.
 *  uploadItem()  = legacy upload 1 file (tetap berfungsi, tidak dihapus).
 *
 * Informasi keuangan (uang_dari / jumlah_diberikan) TIDAK diisi driver —
 * diisi oleh admin GA / keuangan saat review LPJ.
 */
class SpdDailyReportController extends Controller
{
    /**
     * Batas maksimum jumlah gambar per sekali kirim bulk-upload (FLOW BARU).
     * Driver boleh kirim berulang (5 lagi, 5 lagi, dst) untuk meringankan beban
     * AI VLM saat memproses satu batch.
     */
    public const MAX_BULK_UPLOAD = 5;

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
     *
     * ⚠️ DI-HIDE / TIDAK DIPAKAI (FLOW BARU).
     * FLOW LAMA: menampilkan daftar laporan harian yang diinput satu-per-satu.
     * PENGGANTI : SATU halaman validasi (pelaporan.spd.validasi) menampung input
     *             struk + segmentasi draft/final. Redirect agar driver selalu
     *             diarahkan ke satu view tersebut.
     */
    public function index(SuratSpd $surat)
    {
        return redirect()->route('pelaporan.spd.validasi', $surat)
            ->with('info', 'Pelaporan SPD kini cukup lewat satu halaman validasi (input struk & finalisasi).');
    }

    /**
     * GET /pelaporan/spd/{surat}/daily/create — form laporan harian baru.
     *
     * ⚠️ DI-HIDE / TIDAK DIPAKAI (FLOW BARU).
     * FLOW LAMA: form input per hari dengan upload 1 file + tombol kirim per hari.
     * PENGGANTI : halaman validasi menggabungkan input struk (bulk, maks 5/kirim)
     *             & finalisasi. Redirect demi konsistensi SATU view pelaporan.
     */
    public function create(SuratSpd $surat)
    {
        return redirect()->route('pelaporan.spd.validasi', $surat)
            ->with('info', 'Input struk kini dilakukan dari satu halaman validasi.');
    }

    /**
     * GET /pelaporan/spd/{surat}/daily/{daily}/edit — edit draft laporan harian.
     *
     * ⚠️ DI-HIDE / TIDAK DIPAKAI (FLOW BARU).
     * FLOW LAMA: edit draft harian via form terpisah.
     * PENGGANTI : halaman validasi langsung menampilkan semua item (draft & final)
     *             yang bisa diedit/hapus. Redirect demi SATU view pelaporan.
     */
    public function edit(SuratSpd $surat, SpdDailyReport $daily)
    {
        return redirect()->route('pelaporan.spd.validasi', $surat)
            ->with('info', 'Edit & hapus item kini dilakukan dari satu halaman validasi.');
    }

    /**
     * POST /pelaporan/spd/{surat}/daily — kirim laporan harian baru.
     *
     * ⚠️ LEGACY (flow lama) — TIDAK dihapus, tetap berfungsi.
     * FLOW BARU menggunakan uploadItems() + validasi(); namun method ini ditinggal
     * agar tidak ada logic/migration yang terhapus dan test lama tetap hijau.
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
     *
     * ⚠️ LEGACY (flow lama) — TIDAK dihapus, tetap berfungsi.
     * Digantikan oleh segmentasi draft/final di satu halaman validasi.
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

    /**
     * Submit laporan harian (status draft → submitted).
     *
     * ⚠️ SEGMENTASI FLOW BARU: pada alur baru, status per-hari tidak lagi
     * diharuskan. Item yang di-upload (uploadItem/uploadItems) tetap berstatus
     * DRAFT sampai driver menekan "Submit sebagai LPJ" (finalize) pada halaman
     * validasi yang mengumpulkan draft + submitted. Method ini hanya dipakai
     * alur legacy.
     */
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
     *
     * ⚠️ LEGACY (flow lama) — upload SATU file per request.
     * FLOW BARU memakai uploadItems() (bulk, maks 5 gambar/kirim) pada halaman
     * validasi. Method ini ditinggal agar logic lama tidak terhapus.
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

        // Analisis gagal (rate-limit/error): jangan buat draft dgn data kosong.
        $status = $result['status'];
        if ($status !== ImageAnalysisService::STATUS_OK) {
            return response()->json([
                'success'       => false,
                'reason'        => $status,
                'has_api_limit' => $status === ImageAnalysisService::STATUS_API_LIMIT,
                'message'       => $status === ImageAnalysisService::STATUS_API_LIMIT
                    ? 'Kuota AI (Gemini) habis sementara — silakan coba lagi nanti atau isi manual.'
                    : 'Analisis AI gagal — silakan coba lagi atau isi manual.',
            ], 422);
        }

        $kategori = StrukItem::normalizeKategori($result['kategori']);
        $metadata = $result['metadata'];
        $text = $result['text'];
        $nominal = (float) ($metadata['nominal'] ?? 0);

        // Validasi tambahan: cocokkan plat struk dengan plat kendaraan SPD dari DB.
        $metadata = array_merge($metadata, $this->comparePlateWithVehicle($surat, $metadata));

        $path = $this->storeStrukImage($request->file('struk'), $surat->nomor_surat);
        // FLOW BARU (seamless): tanggal transaksi diambil dari hasil AI bila terbaca;
        // fallback ke tanggal request, lalu ke tanggal laporan draft.
        $aiTanggal = $this->parseAiTanggal($metadata['tanggal'] ?? null);
        $itemDate = $aiTanggal ?? ($request->tanggal ?? $daily->tanggal->format('Y-m-d'));
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
                'url'        => url('files/storage/' . $item->path_image),
                'keterangan' => $item->keterangan,
                'plat_nomor'     => $metadata['plat_nomor'] ?? null,
                'plat_kendaraan' => $metadata['plat_kendaraan'] ?? null,
                'plat_cocok'     => $metadata['plat_cocok'] ?? null,
            ],
            'daily_report_id' => $daily->id,
        ]);
    }

    /**
     * POST /pelaporan/spd/{surat}/daily/items — FLOW BARU: BULK UPLOAD.
     *
     * Upload beberapa gambar struk sekaligus dari SATU halaman validasi.
     * Maksimal self::MAX_BULK_UPLOAD (5) gambar per kirim — driver boleh kirim
     * berulang (5 lagi, 5 lagi, dst) sesuai kebutuhan lapangan sehingga AI VLM
     * tidak kewalahan saat menganalisis satu batch.
     *
     * SEGMENTASI:
     *  ▶ Bila periode SPD masih BERJALAN (tanggal_kembali belum lewat) → item
     *    disimpan sebagai DRAFT (belum tervalidasi / belum finalisasi).
     *  ▶ Bila SPD sudah selesai & driver mau langsung final → item tetap draft,
     *    kemudian driver menekan "Submit sebagai LPJ" (finalize) yang mengumpulkan
     *    seluruh draft + submitted.
     *
     * Setiap gambar dianalisis AI → kategori & nominal otomatis.
     */
    public function uploadItems(Request $request, SuratSpd $surat)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'   => 'nullable|date',
            'images'    => 'required|array|min:1|max:' . self::MAX_BULK_UPLOAD,
            'images.*'  => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',

            // Data isi-manual per indeks gambar (frontend mengirim ini untuk foto
            // yang gagal dianalisis AI, mis. karena rate-limit) — dilewati OCR.
            'manual_data'              => 'nullable|array',
            'manual_data.*.kategori'   => 'required|in:BBM,Tol,Parkir,Hotel + Makan,Lainnya,Tambahan',
            'manual_data.*.nominal'    => 'nullable|numeric|min:0',
            'manual_data.*.tanggal'    => 'nullable|date',
            'manual_data.*.keterangan' => 'nullable|string|max:500',
        ], [
            'images.required'   => 'Minimal 1 gambar wajib diupload.',
            'images.max'        => 'Maksimal ' . self::MAX_BULK_UPLOAD . ' gambar per kirim. Upload lagi ' . self::MAX_BULK_UPLOAD . ' gambar berikutnya untuk lanjut.',
            'images.*.image'    => 'File harus berupa gambar.',
            'images.*.mimes'    => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'images.*.max'      => 'Ukuran gambar maksimal 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Nominal wajib untuk semua kategori kecuali Tambahan (foto pendukung).
        foreach ((array) $request->input('manual_data', []) as $i => $md) {
            $isTambahan = ($md['kategori'] ?? '') === 'Tambahan';
            if (! $isTambahan && (! isset($md['nominal']) || $md['nominal'] === '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal wajib diisi untuk foto #' . ((int) $i + 1) . ' (kategori ' . $md['kategori'] . ').',
                ], 422);
            }
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

        $created = [];
        $failed = [];
        $hasApiLimit = false;
        $urutan = $daily->strukItems()->count();

        // Setiap gambar dianalisis & disimpan server-side (draft) PER FILE.
        // Bila satu file gagal, file lain dalam batch TETAP diproses — tidak ada
        // data yang hilang; failure dilaporkan terpisah ke frontend beserta alasannya.
        foreach ($request->file('images') as $index => $file) {
            try {
                $urutan++;

                // Isi-manual (mis. setelah API limit): lewati OCR, pakai input user.
                $manual = $request->input("manual_data.{$index}");
                $source = 'ai';

                if (is_array($manual)) {
                    $kategori = StrukItem::normalizeKategori($manual['kategori'] ?? null);
                    $metadata = [
                        'nominal' => isset($manual['nominal']) && $manual['nominal'] !== '' ? (float) $manual['nominal'] : null,
                        'volume'  => null,
                        'tanggal' => $manual['tanggal'] ?? null,
                    ];
                    $text = trim((string) ($manual['keterangan'] ?? ''));
                    $source = 'manual';
                    $itemDate = $manual['tanggal'] ?? ($request->tanggal ?? $daily->tanggal->format('Y-m-d'));
                } else {
                    // AI/OCR — dianalisis per gambar dalam batch
                    $tmpPath = $file->store('temp/struk-ocr', 'local');
                    $absolute = Storage::disk('local')->path($tmpPath);
                    try {
                        $result = $this->analysis->analyzeImage($absolute);
                    } finally {
                        Storage::disk('local')->delete($tmpPath);
                    }

                    $status = $result['status'];

                    // Analisis gagal (rate-limit/error): JANGAN buat draft dengan data kosong.
                    // Laporkan ke frontend supaya foto ini diisi manual.
                    if ($status !== ImageAnalysisService::STATUS_OK) {
                        $hasApiLimit = $hasApiLimit || $status === ImageAnalysisService::STATUS_API_LIMIT;
                        $failed[] = [
                            'index'  => $index + 1,
                            'reason' => $status,
                            'message' => $status === ImageAnalysisService::STATUS_API_LIMIT
                                ? 'Kuota AI (Gemini) habis sementara.'
                                : 'Analisis AI gagal.',
                        ];
                        $urutan--;
                        continue;
                    }

                    $kategori = StrukItem::normalizeKategori($result['kategori']);
                    $metadata = $result['metadata'];
                    $text = $result['text'];

                    // Validasi tambahan: cocokkan plat struk dengan plat kendaraan SPD dari DB.
                    $metadata = array_merge($metadata, $this->comparePlateWithVehicle($surat, $metadata));

                    // FLOW BARU (seamless): tanggal transaksi otomatis dari AI (metadata tanggal),
                    // bukan pilih manual — item dikelompokkan per tanggal struk.
                    $aiTanggal = $this->parseAiTanggal($metadata['tanggal'] ?? null);
                    $itemDate = $aiTanggal ?? ($request->tanggal ?? $daily->tanggal->format('Y-m-d'));
                }

                $nominal = (float) ($metadata['nominal'] ?? 0);

                $path = $this->storeStrukImage($file, $surat->nomor_surat);
                $itemCode = StrukItem::generateItemCode($kategori, $surat->nomor_surat, $itemDate);

                $item = StrukItem::create([
                    'spd_id'              => $surat->id,
                    'spd_daily_report_id' => $daily->id,
                    'nomor_surat_spd'     => $surat->nomor_surat,
                    'item_code'           => $itemCode,
                    'path_image'          => $path,
                    'struk_kategori'      => $kategori,
                    'kategori_source'     => $source,
                    'metadata_json'       => ! empty($metadata) ? $metadata : null,
                    'nominal'             => $nominal,
                    'tanggal'             => $itemDate,
                    // Hanya keterangan yang DIKETIK USER yang disimpan ke field ini.
                    // Hasil OCR AI tidak lagi ditulis ke keterangan (hanya ke ocr_text).
                    'keterangan'          => $source === 'manual' && $text !== ''
                        ? mb_substr($text, 0, 500)
                        : self::defaultKeterangan($kategori),
                    'ocr_text'            => ($source === 'ai' && $text) ? $text : null,
                    'urutan'              => $urutan,
                ]);

                $created[] = [
                    'id'         => $item->id,
                    'slug'       => $item->slug,
                    'item_code'  => $item->item_code,
                    'kategori'   => $item->struk_kategori,
                    'nominal'    => $nominal,
                    'tanggal'    => $item->tanggal ? $item->tanggal->format('Y-m-d') : null,
                    'url'        => url('files/storage/' . $item->path_image),
                    'keterangan' => $item->keterangan,
                    'plat_nomor'     => $metadata['plat_nomor'] ?? null,
                    'plat_kendaraan' => $metadata['plat_kendaraan'] ?? null,
                    'plat_cocok'     => $metadata['plat_cocok'] ?? null,
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('uploadItems: file #' . $index . ' gagal diproses.', [
                    'error' => $e->getMessage(),
                ]);
                $failed[] = [
                    'index'   => $index + 1,
                    'reason'  => ImageAnalysisService::STATUS_API_ERROR,
                    'message' => 'Gagal memproses file: ' . $e->getMessage(),
                ];
            }
        }

        $message = count($created) . ' struk berhasil disimpan sebagai draft.';
        if (! empty($failed)) {
            $message .= ' ' . count($failed) . ' gagal (urutan #' . implode(', #', array_column($failed, 'index')) . ').';
            if ($hasApiLimit) {
                $message .= ' Kuota AI terbatas — silakan isi data struk secara manual.';
            }
        }

        return response()->json([
            'success'           => count($created) > 0 || empty($failed),
            'items'             => $created,
            'failed'            => array_column($failed, 'index'),
            'failed_details'    => $failed,
            'has_api_limit'     => $hasApiLimit,
            'daily_report_id'   => $daily->id,
            'max_bulk_upload'   => self::MAX_BULK_UPLOAD,
            'message'           => $message,
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
            'struk_kategori' => 'required|in:BBM,Tol,Parkir,Hotel + Makan,Lainnya,Tambahan',
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
     * GET /pelaporan/spd/{surat}/validasi — SATU HALAMAN PELAPORAN SPD (FLOW BARU).
     *
     * ============ FLOW BARU / SEGMENTASI ============
     * View ini menjadi satu-satunya tampilan yang dipakai driver:
     *  - Input struk (bulk, maks 5 gambar per kirim → uploadItems route).
     *  - Menampilkan SEMUA item ter-SEGMENTASI:
     *      ▶ DRAFT  : item yang diinput saat periode SPD berjalan, atau bulk input
     *                 yang belum di-finalisasi. Masih bisa diubah/dihapus.
     *      ▶ FINAL  : item dari laporan harian yang sudah dikirim (flow lama,
     *                 status submitted) — menunggu digabung menjadi LPJ.
     *  - Edit nominal/kategori/keterangan & hapus item (update-item/delete-item).
     *  - Form "Submit sebagai LPJ" (finalize) menggabungkan draft + submitted.
     *
     * Sebelumnya (FLOW LAMA): halaman ini hanya menampilkan laporan SUBMITTED
     * (hasil input per hari) untuk divalidasi di akhir periode.
     */
    public function validasi(SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with([
                'vehicle',
                // FLOW BARU: tampilkan SEMUA daily report (draft + submitted),
                // bukan hanya yang submitted — agar draft tetap terlihat & diedit.
                'dailyReports' => fn ($q) => $q->orderBy('tanggal'),
                'dailyReports.strukItems',
            ])
            ->findOrFail($surat->id);

        // Segmentasi draft (belum final) vs submitted (siap finalisasi)
        $drafts = $surat->dailyReports->filter(fn ($r) => $r->isDraft())->values();
        $submitted = $surat->dailyReports->filter(fn ($r) => $r->isSubmitted())->values();

        $draftItems = $drafts->flatMap(fn ($r) => $r->strukItems);
        $submittedItems = $submitted->flatMap(fn ($r) => $r->strukItems);

        // Menandakan apakah periode SPD masih berjalan → finalisasi diblokir.
        $spdActive = ! $surat->tanggal_kembali->isPast();

        return view('pelaporan.spd.validasi', compact(
            'surat', 'drafts', 'submitted', 'draftItems', 'submittedItems', 'spdActive'
        ));
    }

    /**
     * POST /pelaporan/spd/{surat}/finalize — gabung seluruh laporan
     * menjadi satu LPJ (fixed report) berstatus pending.
     *
     * ============ FLOW BARU / SEGMENTASI ============
     * Kini mengumpulkan SELURUH item — baik dari laporan DRAFT maupun SUBMITTED.
     * Alasannya: bulk input pasca-SPD (flow baru) menyimpan item dalam status
     * DRAFT; ketika driver menekan "Submit sebagai LPJ", seluruh draft tersebut
     * ikut digabung sehingga tidak perlu lagi lewat submit per hari.
     */
    public function finalize(Request $request, SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with([
                'vehicle',
                // FLOW BARU: muat SEMUA daily report (draft + submitted).
                'dailyReports' => fn ($q) => $q->orderBy('tanggal'),
                'dailyReports.strukItems',
            ])
            ->findOrFail($surat->id);

        if (! $surat->tanggal_kembali->isPast()) {
            return redirect()->back()
                ->with('error', 'SPD belum selesai — tunggu hingga tanggal kembali lewat sebelum membuat LPJ.');
        }

        $reports = $surat->dailyReports;
        if ($reports->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada laporan yang dikirim.');
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

        // Gabungkan item dari seluruh laporan (draft + submitted) — SEGMENTASI FLOW BARU.
        $allItems = $reports->flatMap(fn ($r) => $r->strukItems);
        $financialItems = $allItems->whereIn('struk_kategori', ['BBM', 'Tol', 'Parkir', 'Hotel + Makan', 'Lainnya']);

        if ($financialItems->isEmpty()) {
            return redirect()->back()
                ->with('error', 'Belum ada item pengeluaran (BBM/Tol/Parkir/Hotel + Makan/Lainnya) pada laporan harian.');
        }

        $totalPengeluaran = (float) $financialItems->sum('nominal');

        $lpj = DB::transaction(function () use ($surat, $allItems, $financialItems, $totalPengeluaran, $request) {
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
            ->with('success', 'LPJ berhasil dibuat dari ' . $reports->count() . ' laporan.');
    }

    /**
     * Normalisasi tanggal transaksi hasil AI (metadata['tanggal']) → 'Y-m-d'.
     *
     * AI bisa mengembalikan beragam format: "2026-08-05", "05/08/2026",
     * "08.05.2026", "5 Agustus 2026", dst. Bila tidak terbaca → null sehingga
     * caller memakai fallback (tanggal request / laporan draft).
     */
    protected function parseAiTanggal(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $raw = trim($value);

        // Terjemahkan nama bulan Indonesia (dan singkatan) ke angka agar bisa di-parse.
        $bulanId = [
            'januari' => '01', 'februari' => '02', 'maret' => '03',
            'april' => '04', 'mei' => '05', 'juni' => '06',
            'juli' => '07', 'agustus' => '08', 'september' => '09',
            'oktober' => '10', 'november' => '11', 'desember' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'mei' => '05', 'jun' => '06', 'jul' => '07', 'agu' => '08', 'ags' => '08',
            'sep' => '09', 'okt' => '10', 'nov' => '11', 'des' => '12',
        ];
        $normalized = preg_replace_callback('/\b(' . implode('|', array_keys($bulanId)) . ')\b/i', function ($m) use ($bulanId) {
            return $bulanId[strtolower($m[1])];
        }, $raw);

        // Tahun pendek ("5-8-26") → lengkapi jadi 2026.
        if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2})\b/', $normalized, $m)) {
            $year = ($m[3] <= 80 ? '20' : '19') . $m[3];
            $normalized = sprintf('%s-%s-%s', $m[1], $m[2], $year);
        }

        $digits = preg_replace('/[\/\.]/', '-', $normalized);
        $digits = preg_replace('/\s+/', ' ', $digits);

        foreach (['Y-m-d', 'd-m-Y', 'j-n-Y', 'n-j-Y'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $digits);
                if ($date && $date->format('Y') > 1970) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // lanjut format berikutnya
            }
        }

        try {
            $ts = strtotime($digits);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        } catch (\Throwable $e) {
            // fallback null
        }

        return null;
    }

    /**
     * Keterangan ringkas default per kategori (bisa diedit driver di validasi).
     * Teks OCR mentah disimpan terpisah di kolom ocr_text, bukan di keterangan.
     */
    protected static function defaultKeterangan(string $kategori): string
    {
        return match ($kategori) {
            'BBM'           => 'Pengisian BBM',
            'Tol'           => 'Pembayaran Tol',
            'Parkir'        => 'Biaya Parkir',
            'Hotel + Makan' => 'Biaya Hotel / Makan',
            'Tambahan'      => 'Dokumentasi Tambahan',
            default         => 'Pengeluaran Lainnya',
        };
    }

    /**
     * Bandingkan nomor polisi yang tertulis di struk (plat_nomor hasil AI)
     * dengan nomor polisi kendaraan SPD dari database (vehicle.consumerial_code).
     *
     * Normalisasi: "D 1077 ALV" → "D1077ALV" (kapital, buang semua non-alfanumerik).
     * Justru berguna sebagai validasi tambahan — bila plat cocok, bukti pengeluaran
     * dianggap terverifikasi untuk kendaraan yang sama.
     *
     * @return array{plat_nomor: ?string, plat_kendaraan: ?string, plat_cocok: ?bool}
     */
    protected function comparePlateWithVehicle(SuratSpd $surat, array $metadata): array
    {
        $platStruk   = ImageAnalysisService::normalizePlate($metadata['plat_nomor'] ?? null);
        $platKendaraan = ImageAnalysisService::normalizePlate($surat->vehicle?->consumerial_code);

        $cocok = null;
        if ($platStruk !== null && $platKendaraan !== null) {
            $cocok = strtoupper($platStruk) === strtoupper($platKendaraan);
        }

        return [
            'plat_nomor'      => $platStruk,
            'plat_kendaraan'  => $platKendaraan,
            'plat_cocok'      => $cocok,
        ];
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
