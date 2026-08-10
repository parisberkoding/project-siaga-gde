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
    public function index()
    {
        $user = Auth::user();

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
            $surat->can_finalize = $surat->tanggal_kembali->isPast()
                && $surat->daily_submitted_count > 0;
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
            'activePermohonan'
        ));
    }

    /**
     * POST /pelaporan/analyze-struk — AJAX OCR.
     * Mendukung 1 file (image) atau banyak file (images[]).
     */
    public function analyzeStruk(Request $request)
    {
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

            $results = [];
            foreach ($request->file('images') as $index => $file) {
                $tmpPath = $file->store('temp/struk-ocr', 'local');
                $absolute = Storage::disk('local')->path($tmpPath);
                $result = $this->analysis->analyzeImage($absolute);
                Storage::disk('local')->delete($tmpPath);

                $results[] = [
                    'index'    => $index,
                    'kategori' => $result['kategori'],
                    'metadata' => $result['metadata'],
                    'text'     => $result['text'],
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results,
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
            $tmpPath = $file->store('temp/struk-ocr', 'local');

            $absolute = Storage::disk('local')->path($tmpPath);
            $result = $this->analysis->analyzeImage($absolute);
            Storage::disk('local')->delete($tmpPath);

            return response()->json([
                'success'  => true,
                'kategori' => $result['kategori'],
                'metadata' => $result['metadata'],
                'text'     => $result['text'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menganalisis struk: ' . $e->getMessage(),
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
            'items.*.struk_kategori'  => 'required|in:BBM,Tol,Parkir,Lainnya,Tambahan',
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

        $totalPengeluaran = collect($request->items)->sum('nominal');
        // Budget kini opsional — over budget hanya dihitung bila budget diisi
        $isOverBudget = $totalBudgetFromTable > 0 && $totalPengeluaran > $totalBudgetFromTable;
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
                'budget_per_hari'   => $totalBudgetFromTable > 0
                    ? round($totalBudgetFromTable / max($budgets->count(), 1))
                    : 0,
                'catatan'           => $request->catatan ?? '',
                'status'            => 'pending',
                'is_late'           => $isOverdue,
                'is_over_budget'    => $isOverBudget,
            ]);

            // 2. Detail + struk_items per item
            foreach ($request->items as $index => $item) {
                $kategori = $item['struk_kategori'];
                $tipePenggunaan = $kategori === 'Lainnya' ? 'Lain-lain' : $kategori;

                $detail = LpjPenggantianDetail::create([
                    'lpj_penggantian_id' => $lpj->id,
                    'tanggal'            => $item['tanggal'],
                    'tipe_penggunaan'    => $tipePenggunaan,
                    'nominal'            => $item['nominal'],
                    'keterangan'         => $item['keterangan'] ?? '',
                    'urutan'             => $index + 1,
                ]);

                // Simpan gambar struk: kompres 50% tanpa resize → storage → path di DB
                $path = $this->storeStrukImage($request->file("items.{$index}.struk"), $surat->nomor_surat);

                $itemCode = StrukItem::generateItemCode($kategori, $surat->nomor_surat, $item['tanggal']);

                StrukItem::create([
                    'spd_id'           => $surat->id,
                    'nomor_surat_spd'  => $surat->nomor_surat,
                    'lpj_id'           => $lpj->id,
                    'lpj_detail_id'    => $detail->id,
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
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
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

        try {
            DB::beginTransaction();

            $requestNumber = $permohonan->request_number;
            $approvedRequest = $permohonan->approvedRequest;
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
            $paths = [];

            foreach ($allFiles as $idx => $file) {
                $kategori = $allKategoris[$idx] ?? 'Lainnya';
                $nominal = isset($allNominals[$idx]) ? (float) $allNominals[$idx] : 0;
                $keterangan = $allKeterangan[$idx] ?? '';
                $metadataRaw = $allMetadata[$idx] ?? '';
                $metadata = ! empty($metadataRaw) ? json_decode($metadataRaw, true) : null;

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
                    'kategori_source'  => 'ai',
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
            return back()->withInput()->with('error', 'Gagal mengirim laporan: ' . $e->getMessage());
        }
    }

    /**
     * Simpan gambar struk ke storage dengan kompresi 50% (tanpa resize).
     * Hanya path yang disimpan ke DB — tidak ada base64.
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

    /**
     * Simpan file lain (tanpa kompresi) ke disk public.
     */
    protected function storeUploaded($file, string $dir, string $filename): string
    {
        return $file->storeAs($dir, $filename, 'public');
    }
}
