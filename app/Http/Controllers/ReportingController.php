<?php

namespace App\Http\Controllers;

use App\Models\LpjPenggantian;
use App\Models\LpjPenggantianDetail;
use App\Models\Permohonan;
use App\Models\StrukItem;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\Transaction_Proofs;
use App\Models\VehicleAndToolsConsumers;
use App\Services\ImageAnalysisService;
use App\Services\ImageCompressor;
use App\Support\MediaStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * ReportingController — SINGLE PAGE PELAPORAN BBM OPERASIONAL & SPD.
 *
 * Satu halaman (/pelaporan) untuk seluruh pelaporan:
 *  - Mode SPD      : muncul selama user punya surat SPD approved & belum ada LPJ.
 *                    Setiap item pengeluaran di-upload struk → AI baca &
 *                    kategorikan → driver edit → submit (ke lpj_penggantian_details
 *                    + struk_items).
 *  - Mode Operasional: form laporan BBM (seperti requester_submit_report) dengan
 *                    prefill hasil OCR. Backend tetap menulis ke transaction_proofs.
 *
 * Tanpa pengecekan tanggal berangkat/kembali — hanya status approved & belum LPJ.
 */
class ReportingController extends Controller
{
    protected ImageAnalysisService $analysis;

    public function __construct(ImageAnalysisService $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * GET /pelaporan — single page, branching dua mode.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Saat user SUDAH memilih mode (lewat kartu modul, link voucher
        // "Lapor", atau ?mode=), deretan kartu pemilih mode disembunyikan:
        // di halaman pelaporan BBM tidak boleh muncul lagi kartu SPD/BBM.
        $modeLocked = $request->filled('mode') || $request->filled('permohonan_id');

        // ── Mode SPD: surat approved & belum ada LPJ (tanpa cek tanggal) ──
        $suratList = SuratSpd::forUser($user->id)
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with([
                'vehicle',
                'parentSurat',
                'budgets',
                'dailyReports.strukItems',
            ])
            ->orderBy('tanggal_kembali', 'desc')
            ->get();

        $spdMode = $suratList->isNotEmpty();

        // Informasi laporan harian per surat untuk panel SPD
        $suratList = $suratList->map(function ($surat) {
            $submitted = $surat->dailyReports->where('status', 'submitted');
            $surat->daily_submitted_count = $submitted->count();
            $surat->daily_item_count = (int) $submitted->sum(fn ($r) => $r->strukItems->count());
            $surat->has_draft_daily = $surat->dailyReports->contains('status', 'draft');
            // FLOW BARU: halaman validasi adalah satu-satunya entry pelaporan SPD,
            // sehingga tombol tersedia selama surat belum punya LPJ (draft, submitted,
            // maupun periode yang masih berjalan).
            $surat->can_finalize = $surat->has_draft_daily
                || $surat->daily_submitted_count > 0
                || ! $surat->tanggal_kembali->isPast();
            $surat->daily_reports_available = $surat->dailyReports->isNotEmpty();
            return $surat;
        });

        $budgetsPerSurat = [];
        $suratForDropdown = [];

        // ── Mode Operasional: permohonan approved & belum ada laporan ──
        $activePermohonan = Permohonan::with(['vehicle.detail'])
            ->where('requester_id', $user->id)
            ->where('status', 'approved')
            ->whereDoesntHave('transactionProof')
            ->orderBy('request_date', 'desc')
            ->get();

        return view('pelaporan.index', compact(
            'spdMode',
            'suratList',
            'activePermohonan',
            'modeLocked'
        ));
    }

    /**
     * POST /pelaporan/analyze-struk — AJAX OCR.
     * Mendukung 1 file (image) atau banyak file (images[]).
     * Batasan keamanan: max 10 file x 10MB, total agregat 30MB,
     * dan verifikasi konten gambar asli (bukan sekadar MIME header).
     */
    public function analyzeStruk(Request $request)
    {
        // Permohonan acuan validasi (opsional; hanya mode operasional).
        $permohonan = null;
        if ($request->filled('permohonan_id')) {
            $permohonan = Permohonan::with('vehicle')->find($request->input('permohonan_id'));
        }

        // Multi-file: images[]
        if ($request->hasFile('images')) {
            $validator = Validator::make($request->all(), [
                'images'   => 'required|array|min:1|max:10',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            ], [
                'images.required'     => 'Minimal 1 gambar wajib diupload',
                'images.*.required'   => 'Setiap gambar wajib diupload',
                'images.*.image'      => 'File harus berupa gambar',
                'images.*.mimes'      => 'Format gambar harus jpeg, png, jpg, atau webp',
                'images.*.max'        => 'Ukuran gambar maksimal 10MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Batasan ukuran agregat: cegah 10 x 10MB = 100MB masuk ke
            // memori/kompresi dalam satu request (DoS).
            $totalBytes = collect($request->file('images'))->sum(fn ($f) => $f->getSize());
            if ($totalBytes > 30 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total ukuran seluruh gambar maksimal 30MB.',
                ], 422);
            }

            $results = [];
            $statuses = [];
            foreach ($request->file('images') as $index => $file) {
                // Verifikasi konten gambar asli via GD (bukan sekadar MIME header).
                $info = @getimagesize($file->getRealPath());
                if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File ke-' . ($index + 1) . ' bukan gambar yang valid.',
                    ], 422);
                }

                $tmpPath = $file->store('temp/struk-ocr', 'local');
                $absolute = Storage::disk('local')->path($tmpPath);
                $result = $this->analysis->analyzeImage($absolute);
                Storage::disk('local')->delete($tmpPath);

                $statuses[] = $result['status'];

                $results[] = [
                    'index'    => $index,
                    'status'   => $result['status'],
                    'kategori' => $result['kategori'],
                    'metadata' => $result['metadata'],
                    'text'     => $result['text'],
                ];
            }

            return response()->json([
                'success'      => true,
                'results'      => $results,
                'validation'   => $this->buildStrukValidation($permohonan, $results),
                'has_api_limit' => in_array(ImageAnalysisService::STATUS_API_LIMIT, $statuses, true),
            ]);
        }

        // Single-file fallback: image
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'image.required' => 'Gambar struk wajib diupload',
            'image.image'    => 'File harus berupa gambar',
            'image.mimes'    => 'Format gambar harus jpeg, png, jpg, atau webp',
            'image.max'      => 'Ukuran gambar maksimal 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $file = $request->file('image');

            // Verifikasi konten gambar asli (anti poliglot/webp-bomb).
            $info = @getimagesize($file->getRealPath());
            if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File bukan gambar yang valid.',
                ], 422);
            }

            $tmpPath = $file->store('temp/struk-ocr', 'local');

            $absolute = Storage::disk('local')->path($tmpPath);
            $result = $this->analysis->analyzeImage($absolute);
            Storage::disk('local')->delete($tmpPath);

            return response()->json([
                'success'  => true,
                'status'   => $result['status'],
                'kategori' => $result['kategori'],
                'metadata' => $result['metadata'],
                'text'     => $result['text'],
                'validation' => $this->buildStrukValidation($permohonan, [[
                    'kategori' => $result['kategori'],
                    'metadata' => $result['metadata'],
                ]]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => safe_exception_message($e, 'Gagal menganalisis struk. Silakan coba lagi atau hubungi admin.'),
            ], 500);
        }
    }

    /**
     * POST /pelaporan/vehicle-budget — auto-retrieve nominal_full_tank dari master vehicle.
     */
    public function vehicleBudget(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
        ]);

        $vehicle = VehicleAndToolsConsumers::with('detail')->find($request->vehicle_id);

        return response()->json([
            'success'          => true,
            'nominal_full_tank' => $vehicle?->detail?->nominal_full_tank,
            'kapasitas_bbm_tahunan' => $vehicle?->detail?->kapasitas_bbm_tahunan,
        ]);
    }

    /**
     * POST /pelaporan/spd — submit LPJ SPD berbasis struk per item.
     */
    public function storeSpd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'surat_spd_id' => 'required|exists:surat_spds,id',
            'nama'         => 'required|string|max:255',
            'jabatan'      => 'required|string|max:255',
            'uraian'       => 'required|string',
            'uang_dari'    => 'nullable|string|max:255',
            'jumlah_diberikan' => 'nullable|numeric|min:0',
            'catatan'      => 'nullable|string',
            'odo_before'   => 'nullable|numeric|min:0',
            'odo_after'    => 'nullable|numeric|min:0',
            'kebutuhan_bbm_liter' => 'nullable|numeric|min:0',
            'items'        => 'required|array|min:1',
            'items.*.tanggal'         => 'required|date',
            'items.*.struk_kategori'  => 'required|in:' . \App\Models\StrukItem::validationList(),
            'items.*.nominal'         => 'required|numeric|min:0',
            'items.*.keterangan'      => 'nullable|string',
            'items.*.kategori_source' => 'required|in:ai,manual',
            'items.*.struk'           => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'items.required'              => 'Minimal harus ada 1 item pengeluaran.',
            'items.*.struk.required'      => 'Setiap item wajib upload gambar struk.',
            'items.*.struk.image'         => 'File item harus berupa gambar.',
            'items.*.struk_kategori.in'   => 'Kategori tidak valid.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Surat milik user, approved, dan belum ada LPJ
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with('vehicle')
            ->findOrFail($request->surat_spd_id);

        $budgets = SuratSpdBudget::where('surat_spd_id', $surat->id)
            ->pluck('nominal', 'tanggal');
        $totalBudgetFromTable = $budgets->sum();

        // 'Tambahan' hanya dokumentasi (bukan pengeluaran) → tidak dihitung.
        $totalPengeluaran = collect($request->items)
            ->where('struk_kategori', '!=', 'Tambahan')
            ->sum('nominal');

        // BUG-LPJ-001: plafon harian = total dana / lama hari perjalanan,
        // bukan dibagi jumlah baris pencairan.
        $totalDana = (float) $totalBudgetFromTable > 0
            ? (float) $totalBudgetFromTable
            : (float) ($surat->total_budget ?? 0);
        $budgetPerHari = LpjPenggantian::hitungBudgetPerHari($surat, $totalDana);

        // Budget kini opsional — over budget hanya dihitung bila budget diisi
        $isOverBudget = $totalDana > 0 && $totalPengeluaran > $totalDana;
        $isOverdue = $surat->isLpjDeadlineOverdue();

        DB::beginTransaction();
        try {
            // 1. Header LPJ (mengikuti pola SpdController::storeLaporan)
            $lpj = LpjPenggantian::create([
                'surat_spd_id'      => $surat->id,
                'user_id'           => Auth::id(),
                'nama'              => $request->nama,
                'jabatan'           => $request->jabatan,
                'vehicle_id'        => $surat->vehicle_id,
                'nomor_polisi'      => $surat->vehicle?->consumerial_code ?? '-',
                'odo_before'        => $request->odo_before,
                'odo_after'         => $request->odo_after,
                'kebutuhan_bbm_liter' => $request->kebutuhan_bbm_liter,
                'uraian'            => $request->uraian,
                'uang_dari'         => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan'  => $request->jumlah_diberikan ?? 0,
                'total_pengeluaran' => $totalPengeluaran,
                'budget_per_hari'   => $budgetPerHari,
                'catatan'           => $request->catatan ?? '',
                'status'            => 'pending',
                'is_late'           => $isOverdue,
                'is_over_budget'    => $isOverBudget,
            ]);

            // 2. Detail + struk_items per item.
            //    'Tambahan' = dokumentasi (bukan pengeluaran) → hanya struk_item
            //    yang dicatat, TIDAK membuat baris detail/keuangan.
            foreach ($request->items as $index => $item) {
                $kategori = $item['struk_kategori'];
                $isTambahan = $kategori === 'Tambahan';

                // Simpan gambar struk: kompres 50% tanpa resize → storage → path di DB
                $path = $this->storeStrukImage($request->file("items.{$index}.struk"), $surat->nomor_surat);

                $itemCode = StrukItem::generateItemCode($kategori, $surat->nomor_surat, $item['tanggal']);

                $detailId = null;

                if (! $isTambahan) {
                    $tipePenggunaan = $kategori === 'Lainnya' ? 'Lain-lain' : $kategori;

                    $detail = LpjPenggantianDetail::create([
                        'lpj_penggantian_id' => $lpj->id,
                        'tanggal'            => $item['tanggal'],
                        'tipe_penggunaan'    => $tipePenggunaan,
                        'nominal'            => $item['nominal'],
                        'keterangan'         => $item['keterangan'] ?? '',
                        'urutan'             => $index + 1,
                    ]);

                    $detailId = $detail->id;
                }

                StrukItem::create([
                    'spd_id'           => $surat->id,
                    'nomor_surat_spd'  => $surat->nomor_surat,
                    'lpj_id'           => $lpj->id,
                    'lpj_detail_id'    => $detailId,
                    'item_code'        => $itemCode,
                    'path_image'       => $path,
                    'struk_kategori'   => $kategori,
                    'kategori_source'  => $item['kategori_source'],
                    'metadata_json'    => ! empty($item['metadata_json']) ? $item['metadata_json'] : null,
                    'nominal'          => $item['nominal'],
                    'tanggal'          => $item['tanggal'],
                    'keterangan'       => $item['keterangan'] ?? '',
                    'urutan'           => $index + 1,
                ]);
            }

            DB::commit();

            $message = 'Laporan Pertanggungjawaban berhasil diajukan!';
            if ($isOverdue) {
                $message .= ' Pengajuan melewati deadline.';
            }
            if ($isOverBudget) {
                $message .= ' Over budget Rp ' . number_format($totalPengeluaran - $totalBudgetFromTable, 0, ',', '.') . '.';
            }

            return redirect()->route('requester.lpj.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', safe_exception_message($e, 'Terjadi kesalahan. Silakan coba lagi atau hubungi admin.'))
                ->withInput();
        }
    }

    /**
     * POST /pelaporan/operational — submit laporan BBM operasional.
     * Mendukung multi-file upload: 1 struk utama → transaction_proofs,
     * semua file (struk + tambahan) → struk_items.
     */
    public function storeOperational(Request $request)
    {
        $permohonan = Permohonan::with('vehicle')
            ->where('requester_id', Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('transactionProof')
            ->findOrFail($request->permohonan_id);

        // ✅ BUG-BBM-001: tolak laporan bila voucher sudah kedaluwarsa, atau
        // bila permohonan yang sudah punya jejak approval tidak punya voucher.
        // Ditolak SEBELUM ada file/data yang disimpan.
        $approvedRequest = $permohonan->approvedRequest;

        if (! $approvedRequest) {
            // Permohonan lama/tanpa jejak approval (data transisi) tetap dapat
            // dilaporkan agar data historis tidak terkunci. Permohonan yang
            // benar-benar disetujui admin wajib memiliki voucher.
            if ($permohonan->authorizer_id !== null || $permohonan->authorization_date !== null) {
                return back()
                    ->withInput()
                    ->with('error', 'Voucher tidak ditemukan untuk permohonan ini. Laporan tidak dapat dikirim. Silakan ajukan permohonan baru.');
            }
        } elseif (! $approvedRequest->valid_until || Carbon::parse($approvedRequest->valid_until)->lt(now())) {
            return back()
                ->withInput()
                ->with('error', 'Voucher sudah kedaluwarsa (masa berlaku 50 jam). Laporan tidak dapat dikirim. Silakan ajukan permohonan baru.');
        }

        $rules = [
            'permohonan_id'    => 'required|exists:permohonans,id',
            'purchase_date'    => 'required|date|before_or_equal:today',
            'volume_bbm'       => 'required|numeric|min:0.01',
            'nominal'          => 'nullable|numeric|min:0',
            'foto_struk'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'foto_files'       => 'nullable|array|max:10',
            'foto_files.*'     => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'struk_kategoris'  => 'nullable|array',
            'struk_kategoris.*' => 'nullable|string',
            'kategori_sources' => 'nullable|array',
            'kategori_sources.*' => 'nullable|in:ai,manual',
            'nominals'         => 'nullable|array',
            'nominals.*'       => 'nullable|numeric|min:0',
            'keterangan_s'     => 'nullable|array',
            'keterangan_s.*'   => 'nullable|string',
            'metadata_s'       => 'nullable|array',
            'metadata_s.*'     => 'nullable|string',
        ];

        $messages = [
            'purchase_date.required' => 'Tanggal pengisian harus diisi',
            'volume_bbm.required'    => 'Volume pengisian harus diisi',
            'volume_bbm.min'         => 'Volume minimal 0.01 liter',
        ];

        $validated = $request->validate($rules, $messages);

        // Pastikan minimal ada 1 foto
        $hasStruk = $request->hasFile('foto_struk');
        $hasFiles = $request->hasFile('foto_files');
        if (! $hasStruk && ! $hasFiles) {
            return back()->withInput()->with('error', 'Minimal upload 1 foto struk/tambahan.');
        }

        // ── Validasi kecocokan struk dengan permohonan (server-side guard) ──
        // Nominal BBM total vs bill_amounts (toleransi 1%); plat nomor opsional.
        $expectedNominal = (float) ($permohonan->bill_amounts ?? 0);
        $expectedPlateRaw = $permohonan->vehicle?->consumerial_code;
        $expectedPlate = ImageAnalysisService::normalizePlate($expectedPlateRaw);

        $totalBbmNominal = 0.0;
        $detectedPlates = [];
        foreach ($request->input('struk_kategoris', []) as $idx => $kat) {
            if ($kat !== 'BBM') {
                continue;
            }

            $nominal = $request->input("nominals.{$idx}");
            if ($nominal !== null && $nominal !== '') {
                $totalBbmNominal += (float) $nominal;
            }

            $raw = $request->input("metadata_s.{$idx}");
            $meta = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
            $plate = ImageAnalysisService::normalizePlate($meta['metadata']['plat_nomor'] ?? ($meta['plat_nomor'] ?? null));
            if ($plate) {
                $detectedPlates[] = $plate;
            }
        }

        // ✅ BUG-BBM-002 (server hardening): payload basi/parsial dari form
        // (hidden input belum tersinkron) bisa tidak membawa nominal BBM sama
        // sekali. Bila field nominal utama terisi, pakai nilai itu sebagai
        // nominal BBM untuk validasi kecocokan — bukan untuk menutupi nominal
        // yang memang berbeda (nominal > 0 tetap divalidasi apa adanya).
        if ($totalBbmNominal <= 0) {
            $mainNominal = $request->input('nominal');
            if ($mainNominal !== null && $mainNominal !== '' && is_numeric($mainNominal)) {
                $totalBbmNominal = (float) $mainNominal;
            }
        }

        $tolerance = $expectedNominal > 0 ? $expectedNominal * 0.01 : 0;
        if ($expectedNominal > 0 && ($totalBbmNominal <= 0 || abs($totalBbmNominal - $expectedNominal) > $tolerance)) {
            return back()->withInput()->with('error',
                'Struk berbeda — nominal struk BBM Rp ' . number_format($totalBbmNominal, 0, ',', '.')
                . ' tidak sesuai dengan nominal permohonan Rp ' . number_format($expectedNominal, 0, ',', '.')
                . '. Harap upload struk yang sebenarnya.');
        }

        if ($expectedPlate) {
            $mismatch = array_values(array_filter(array_unique($detectedPlates), fn ($p) => $p !== $expectedPlate));
            if (! empty($mismatch)) {
                return back()->withInput()->with('error',
                    'Struk berbeda — plat nomor pada struk (' . implode(', ', $mismatch)
                    . ') tidak sesuai dengan kendaraan permohonan (' . $expectedPlate
                    . '). Harap upload struk yang sebenarnya.');
            }
        }

        try {
            DB::beginTransaction();

            $requestNumber = $permohonan->request_number;
            $purchaseDatetime = $validated['purchase_date'] . ' ' . now()->format('H:i:s');

            // 1. Kumpulkan semua foto (struk utama + tambahan)
            $allFiles = [];
            if ($hasStruk) {
                $allFiles[] = $request->file('foto_struk');
            }
            if ($hasFiles) {
                foreach ($request->file('foto_files') as $f) {
                    $allFiles[] = $f;
                }
            }

            // 2. Simpan tiap foto → struk_items; path file pertama dipakai sbg struk utama
            $allKategoris = $request->input('struk_kategoris', []);
            $allNominals = $request->input('nominals', []);
            $allKeterangan = $request->input('keterangan_s', []);
            $allMetadata = $request->input('metadata_s', []);
            $allSources = $request->input('kategori_sources', []);
            $paths = [];

            foreach ($allFiles as $idx => $file) {
                $kategori = $allKategoris[$idx] ?? 'Lainnya';
                $nominal = isset($allNominals[$idx]) ? (float) $allNominals[$idx] : 0;
                $keterangan = $allKeterangan[$idx] ?? '';
                $metadataRaw = $allMetadata[$idx] ?? '';
                $metadata = ! empty($metadataRaw) ? json_decode($metadataRaw, true) : null;
                $source = ($allSources[$idx] ?? 'ai') === 'manual' ? 'manual' : 'ai';

                // Inject plat nomor dari data permohonan bila AI tidak membacanya
                // (opsional: hanya sebagai pelengkap metadata).
                if ($kategori === 'BBM') {
                    $metadata = is_array($metadata) ? $metadata : [];
                    if (empty($metadata['metadata']) || ! is_array($metadata['metadata'])) {
                        $metadata['metadata'] = [];
                    }
                    if (empty($metadata['metadata']['plat_nomor']) && $expectedPlateRaw) {
                        $metadata['metadata']['plat_nomor'] = $expectedPlateRaw;
                    }
                }

                $path = $this->storeStrukImage($file, 'OPRS-' . $requestNumber);
                $paths[] = $path;
                $itemCode = StrukItem::generateItemCode($kategori, $requestNumber, $validated['purchase_date']);

                StrukItem::create([
                    'spd_id'           => null,
                    'nomor_surat_spd'  => null,
                    'lpj_id'           => null,
                    'lpj_detail_id'    => null,
                    'item_code'        => $itemCode,
                    'path_image'       => $path,
                    'struk_kategori'   => $kategori,
                    'kategori_source'  => $source,
                    'metadata_json'    => $metadata,
                    'nominal'          => $nominal,
                    'tanggal'          => $validated['purchase_date'],
                    'keterangan'       => $keterangan,
                    'urutan'           => $idx + 1,
                ]);
            }

            // 3. Simpan struk utama → transaction_proofs
            $transactionId = 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));
            Transaction_Proofs::create([
                'transaction_id'      => $transactionId,
                'req_id'              => $permohonan->id,
                'requester_id'        => Auth::id(),
                'voucher_number'      => $approvedRequest?->voucher_code,
                'purchase_datetime'   => $purchaseDatetime,
                'fuel_volume'         => $validated['volume_bbm'],
                'km_terakhir'         => $validated['km_akhir'] ?? 0,
                'struk_bbm_path'      => $paths[0] ?? null,
            ]);

            $permohonan->update(['status' => 'completed']);
            if ($approvedRequest) {
                $approvedRequest->update(['status' => 'completed']);
            }

            DB::commit();

            return redirect()->route('pelaporan.index')
                ->with('success', '✅ Laporan BBM operasional berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', safe_exception_message($e, 'Gagal mengirim laporan. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Bandingkan hasil OCR struk dengan data permohonan:
     *  - Nominal BBM (total) vs Permohonan::bill_amounts, toleransi 1%.
     *  - Plat nomor yang terbaca vs kendaraan permohonan (opsional: hanya bila
     *    plat berhasil terbaca; bila tidak terbaca tidak memblokir).
     *
     * @param  array<int, array{kategori?: ?string, metadata?: array}>  $results
     * @return array<string, mixed>|null
     */
    protected function buildStrukValidation(?Permohonan $permohonan, array $results): ?array
    {
        if (! $permohonan) {
            return null;
        }

        $expectedNominal = (float) ($permohonan->bill_amounts ?? 0);
        $expectedPlate = ImageAnalysisService::normalizePlate(
            $permohonan->vehicle?->consumerial_code
        );

        $actualNominal = 0.0;
        $detectedPlates = [];

        foreach ($results as $result) {
            if (($result['kategori'] ?? null) !== 'BBM') {
                continue;
            }

            $nominal = $result['metadata']['nominal'] ?? null;
            if ($nominal !== null && $nominal !== '') {
                $actualNominal += (float) $nominal;
            }

            $plate = ImageAnalysisService::normalizePlate($result['metadata']['plat_nomor'] ?? null);
            if ($plate) {
                $detectedPlates[] = $plate;
            }
        }

        $tolerance = $expectedNominal > 0 ? $expectedNominal * 0.01 : 0;
        $nominalChecked = $expectedNominal > 0 && $actualNominal > 0;
        $nominalDiff = $actualNominal - $expectedNominal;
        $nominalOk = ! $nominalChecked || abs($nominalDiff) <= $tolerance;

        $mismatchedPlates = $expectedPlate
            ? array_values(array_filter($detectedPlates, fn ($p) => $p !== $expectedPlate))
            : [];
        $plateOk = empty($mismatchedPlates);

        $valid = $nominalOk && $plateOk;
        $message = '';

        if (! $valid) {
            $parts = [];
            if (! $nominalOk) {
                $parts[] = 'Nominal struk BBM Rp ' . number_format($actualNominal, 0, ',', '.')
                    . ' tidak sesuai dengan nominal permohonan Rp ' . number_format($expectedNominal, 0, ',', '.') . '.';
            }
            if (! $plateOk) {
                $parts[] = 'Plat nomor pada struk (' . implode(', ', array_unique($mismatchedPlates))
                    . ') berbeda dengan kendaraan permohonan (' . $expectedPlate . ').';
            }
            $message = implode(' ', $parts) . ' Struk berbeda — harap upload struk yang sebenarnya.';
        }

        return [
            'expected_nominal' => $expectedNominal,
            'expected_plate'   => $expectedPlate,
            'actual_nominal'   => $actualNominal,
            'detected_plates'  => array_values(array_unique($detectedPlates)),
            'nominal_checked'  => $nominalChecked,
            'nominal_ok'       => $nominalOk,
            'nominal_diff'     => $nominalDiff,
            'tolerance'        => $tolerance,
            'plate_ok'         => $plateOk,
            'valid'            => $valid,
            'message'          => $message,
        ];
    }

    /**
     * Simpan gambar struk ke disk privat dengan kompresi 50% (tanpa resize).
     * Hanya path yang disimpan ke DB — tidak ada base64.
     *
     * BUG-SEC-003: struk berisi data sensitif (lokasi SPBU, plat nomor) →
     * disk `local` (storage/app/private), bukan disk publik.
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
            MediaStorage::put($path, $jpeg);
        } else {
            MediaStorage::storeUploaded($file, 'transaction_proofs/struk', $filename);
        }

        return $path;
    }

    /**
     * Simpan file lain (tanpa kompresi) ke disk privat.
     */
    protected function storeUploaded($file, string $dir, string $filename): string
    {
        return MediaStorage::storeUploaded($file, $dir, $filename);
    }
}
