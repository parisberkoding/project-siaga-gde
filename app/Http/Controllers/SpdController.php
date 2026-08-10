<?php


namespace App\Http\Controllers;

use App\Models\SuratSpd;
use App\Models\SuratSpdDetail;
use App\Models\SuratSpdExtension;
use App\Models\SuratSpdBudget;
use App\Models\LpjPenggantian;
use App\Models\LpjPenggantianDetail;
use App\Models\VehicleAndToolsConsumers;
use App\Models\User;
use App\Models\EmployeeDefault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\SpdBarcodeSignature;
use App\Services\ImageCompressor;
use Illuminate\Support\Facades\Storage;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class SpdController extends Controller
{
    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                    SECTION 1: SURAT SPD - REQUESTER                       ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Show form untuk create surat SPD (requester/driver)
     *
     * @return \Illuminate\View\View
     */
    public function createSurat()
    {
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->where('consumerial_type', 'Kendaraan')
            ->orderBy('consumerial_name')
            ->get();

        $pejabat = User::whereIn('role', ['admin', 'superadmin'])->get();

        return view('requester.spd.surat.create', compact('vehicles', 'pejabat'));
    }

    /**
     * Store surat SPD dari requester
     * TANPA budget - budget ditentukan superadmin saat approve
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeSurat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pemberi_tugas_nama' => 'required|string|max:255',
            'pemberi_tugas_jabatan' => 'required|string|max:255',
            'tempat_tujuan' => 'required|string',
            'melayani_bidang' => 'required|string|max:255',
            'jumlah_pemakai' => 'required|integer|min:1|max:7',
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'tanggal_berangkat' => 'required|date|after_or_equal:today',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_berangkat',
            'catatan' => 'nullable|string',
            // BUDGET TIDAK DIPERLUKAN - ditentukan superadmin
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Get vehicle untuk nomor polisi
            $vehicle = VehicleAndToolsConsumers::findOrFail($request->vehicle_id);

            // Create surat SPD TANPA budget
            $surat = SuratSpd::create([
                'pemberi_tugas_nama' => $request->pemberi_tugas_nama,
                'pemberi_tugas_jabatan' => $request->pemberi_tugas_jabatan,
                'user_id' => Auth::id(),
                'pengemudi_nama' => Auth::user()->nama_lengkap ?? Auth::user()->name,
                'tempat_tujuan' => $request->tempat_tujuan,
                'melayani_bidang' => $request->melayani_bidang,
                'jumlah_pemakai' => $request->jumlah_pemakai,
                'vehicle_id' => $request->vehicle_id,
                'tanggal_berangkat' => $request->tanggal_berangkat,
                'tanggal_kembali' => $request->tanggal_kembali,
                'budget_per_hari' => 0, // Default 0, diisi superadmin saat approve
                'catatan' => $request->catatan,
                'is_extension' => false,
                'status' => 'pending',
            ]);

            // Add nama lengkap penumpang (1-7)
            $urutan = 1;
            for ($i = 1; $i <= 7; $i++) {
                $namaLengkap = $request->input("nama_lengkap_{$i}");
                if (!empty($namaLengkap)) {
                    SuratSpdDetail::create([
                        'surat_spd_id' => $surat->id,
                        'nama_lengkap' => $namaLengkap,
                        'urutan' => $urutan++,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('requester.surat.index')
                ->with('success', 'Surat Tugas berhasil diajukan! Nomor: ' . $surat->nomor_surat);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show list surat SPD untuk requester
     *
     * @return \Illuminate\View\View
     */
    public function indexSuratRequester()
    {
        $suratList = SuratSpd::forUser(Auth::id())
            ->with(['vehicle', 'approver', 'lpjPenggantian'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Hitung statistik
        $stats = [
            'total' => $suratList->count(),
            'pending' => $suratList->where('status', 'pending')->count(),
            'approved' => $suratList->where('status', 'approved')->count(),
            'need_lpj' => $suratList->where('status', 'approved')
                ->whereNull('lpjPenggantian')->count(),
        ];

        return view('requester.spd.surat.index', compact('suratList', 'stats'));
    }

    /**
     * Show detail surat SPD untuk requester
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showSuratRequester(SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->with(['details', 'vehicle', 'approver', 'rejector', 'lpjPenggantian', 'childExtensions', 'parentSurat'])
            ->findOrFail($surat->id);

        return view('requester.spd.surat.show', compact('surat'));
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                    SECTION 2: SURAT SPD - SUPERADMIN                      ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Show list surat SPD untuk validasi superadmin
     *
     * @return \Illuminate\View\View
     */
    public function indexSuratSuperadmin()
    {
        $pending_list = SuratSpd::pending()
            ->with(['user', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approved_list = SuratSpd::approved()
            ->with(['user', 'vehicle', 'approver', 'lpjPenggantian'])
            ->orderBy('approved_at', 'desc')
            ->limit(50)
            ->get();

        $rejected_list = SuratSpd::rejected()
            ->with(['user', 'vehicle', 'rejector'])
            ->orderBy('rejected_at', 'desc')
            ->limit(50)
            ->get();

        return view('superadmin.spd.surat.index', compact(
            'pending_list',
            'approved_list',
            'rejected_list'
        ))->with([
            'pending_count' => $pending_list->count(),
            'approved_count' => SuratSpd::approved()->count(),
            'rejected_count' => SuratSpd::rejected()->count(),
        ]);
    }

    /**
     * Show detail surat SPD untuk validasi superadmin
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showSuratSuperadmin(SuratSpd $surat)
    {
        $surat = SuratSpd::with([
            'details',
            'vehicle',
            'user',
            'approver',
            'rejector',
            'parentSurat',
            'childExtensions',
            'lpjPenggantian'
        ])->findOrFail($surat->id);

        return view('superadmin.spd.surat.detail', compact('surat'));
    }

    /**
     * Approve surat SPD
     * Set status approved, budget per hari, dan calculate lpj_deadline
     *
     * @param Request $request
     * @param \App\Models\SuratSpd $surat
     * @return \Illuminate\Http\RedirectResponse
     */
public function approveSurat(Request $request, SuratSpd $surat)
{
    if ($surat->status !== 'pending') {
        return redirect()->back()
            ->with('error', 'Surat ini sudah diproses sebelumnya.');
    }

    DB::beginTransaction();
    try {
        // Alur sementara: budgeting per hari di-skip, cukup persetujuan.
        // total_budget dibiarkan 0 (tidak diisi dari input budget).

        // Set approval fields
        $surat->status = 'approved';
        $surat->approved_by = Auth::id();
        $surat->approved_at = Carbon::now();

        // Set LPJ deadline (3 hari setelah tanggal kembali)
        $surat->lpj_deadline = Carbon::parse($surat->tanggal_kembali)->addDays(3);

        $surat->save();

        DB::commit();

        return redirect()->route('surat.superadmin.index')
            ->with('success', 'Surat Tugas berhasil disetujui! Deadline LPJ: ' .
                $surat->lpj_deadline->format('d M Y'));

    } catch (\Exception $e) {
        DB::rollBack();
        // ✅ Tampilkan error untuk debugging
        return redirect()->back()
            ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

    /**
     * Reject surat SPD
     *
     * @param Request $request
     * @param \App\Models\SuratSpd $surat
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectSurat(Request $request, SuratSpd $surat)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        if ($surat->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Surat ini sudah diproses sebelumnya.');
        }

        $surat->reject(Auth::id(), $request->rejection_reason);

        return redirect()->route('surat.superadmin.index')
            ->with('success', 'Surat Tugas berhasil ditolak.');
    }

    /**
     * Show form create surat SPD shortcut untuk superadmin.
     * Langsung dengan budget + approve, tanpa perlu flow validasi GA.
     */
    public function createSuratSuperadmin()
    {
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->where('consumerial_type', 'Kendaraan')
            ->orderBy('consumerial_name')
            ->get();

        $employees = User::whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('role', 'requester')
                  ->orWhere('jabatan', 'like', '%driver%')
                  ->orWhere('jabatan', 'like', '%Driver%')
                  ->orWhere('jabatan', 'like', '%DRIVER%')
                  ->orWhere('jabatan', 'like', '%pengemudi%')
                  ->orWhere('jabatan', 'like', '%Pengemudi%');
            })
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn ($u) => (object) [
                'id'          => 'user_' . $u->id,
                'nama_lengkap' => $u->nama_lengkap,
                'jabatan'     => $u->jabatan ?? 'Karyawan',
            ])
            ->sortBy('nama_lengkap')
            ->values();

        return view('superadmin.spd.surat.create-shortcut', compact('vehicles', 'employees'));
    }

    /**
     * Store surat SPD shortcut — langsung approved + budget.
     * Superadmin create sekaligus approve tanpa perlu validasi GA.
     */
    public function storeSuratSuperadmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'           => 'required|string',
            'tempat_tujuan'         => 'required|string',
            'melayani_bidang'       => 'required|string|max:255',
            'jumlah_pemakai'        => 'required|integer|min:1|max:7',
            'vehicle_id'            => 'required|exists:vehicle_and_tools_consumers,id',
            'tanggal_berangkat'     => 'required|date',
            'tanggal_kembali'       => 'required|date|after_or_equal:tanggal_berangkat',
            'catatan'               => 'nullable|string',
            'type'                  => 'required|in:biasa,mendadak',
            // Budget per hari
            'budgets'               => 'nullable|array',
            'budgets.*.tanggal'     => 'required_with:budgets|date',
            'budgets.*.nominal'     => 'required_with:budgets|numeric|min:0',
            'budgets.*.catatan'     => 'nullable|string|max:255',
        ], [
            'employee_id.required'      => 'Pilih pengemudi yang bertugas.',
            'type.required'             => 'Pilih tipe SPD.',
            'type.in'                   => 'Tipe SPD tidak valid.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $totalBudget = $request->budgets ? collect($request->budgets)->sum('nominal') : 0;

        // Resolve employee_id (format: emp_X atau user_X)
        $userId = null;
        $pegawaiNama = '';
        if (str_starts_with($request->employee_id, 'emp_')) {
            $empId = (int) str_replace('emp_', '', $request->employee_id);
            $emp = EmployeeDefault::with('user')->find($empId);
            $pegawaiNama = $emp?->employee_name ?? 'Unknown';
            $userId = $emp?->user_id;
        } elseif (str_starts_with($request->employee_id, 'user_')) {
            $uid = (int) str_replace('user_', '', $request->employee_id);
            $user = User::find($uid);
            $pegawaiNama = $user?->nama_lengkap ?? 'Unknown';
            $userId = $user?->id;
        }

        if (!$userId) {
            return redirect()->back()
                ->with('error', 'Pegawai tidak ditemukan.')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $surat = SuratSpd::create([
                'pemberi_tugas_nama'    => 'Genny Kusumaningrum',
                'pemberi_tugas_jabatan' => 'HC, GA, & Finance Manager',
                'user_id'               => $userId,
                'pengemudi_nama'        => $pegawaiNama,
                'tempat_tujuan'         => $request->tempat_tujuan,
                'melayani_bidang'       => $request->melayani_bidang,
                'jumlah_pemakai'        => $request->jumlah_pemakai,
                'vehicle_id'            => $request->vehicle_id,
                'tanggal_berangkat'     => $request->tanggal_berangkat,
                'tanggal_kembali'       => $request->tanggal_kembali,
                'catatan'               => $request->catatan,
                'type'                  => $request->type,
                'is_extension'          => false,
                // Langsung approve
                'status'                => 'approved',
                'approved_by'           => Auth::id(),
                'approved_at'           => Carbon::now(),
                'total_budget'          => $totalBudget,
                'lpj_deadline'          => Carbon::parse($request->tanggal_kembali)->addDays(3),
            ]);

            // Simpan nama penumpang
            $urutan = 1;
            for ($i = 1; $i <= 7; $i++) {
                $namaLengkap = $request->input("nama_lengkap_{$i}");
                if (!empty($namaLengkap)) {
                    SuratSpdDetail::create([
                        'surat_spd_id' => $surat->id,
                        'nama_lengkap' => $namaLengkap,
                        'urutan'       => $urutan++,
                    ]);
                }
            }

            // Simpan budget per tanggal
            if ($request->budgets) {
                foreach ($request->budgets as $budgetData) {
                    SuratSpdBudget::create([
                        'surat_spd_id' => $surat->id,
                        'tanggal'      => $budgetData['tanggal'],
                        'nominal'      => $budgetData['nominal'],
                        'catatan'      => $budgetData['catatan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('surat.superadmin.show', $surat)
                ->with('success', 'Surat SPD langsung berhasil dibuat & disetujui! Nomor: ' . $surat->nomor_surat);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat surat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Print surat SPD (untuk cetak)
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    /**
     * SPD spending report per surat
     */
    public function reportSpending()
    {
        $suratList = SuratSpd::with(['vehicle', 'user', 'lpjPenggantian', 'budgets'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $totalBudget = SuratSpd::sum('total_budget');
        $totalApproved = SuratSpd::approved()->count();
        $totalLpj = LpjPenggantian::approved()->count();
        $totalSpending = LpjPenggantian::approved()->sum('total_pengeluaran');

        return view('superadmin.spd.surat.report', compact(
            'suratList', 'totalBudget', 'totalApproved', 'totalLpj', 'totalSpending'
        ));
    }

    public function printSurat(SuratSpd $surat)
    {
        $surat = SuratSpd::with(['details', 'vehicle', 'user', 'approver', 'budgets'])
            ->where('status', 'approved')
            ->findOrFail($surat->id);

        $needsBudget = false; // Budget kini modul terpisah — tidak lagi menjadi syarat cetak

        // Check if barcode already exists in DB
        $barcodeRecord = SpdBarcodeSignature::where('spd_id', $surat->id)->first();

        if ($barcodeRecord && file_exists(storage_path('app/public/' . $barcodeRecord->signature_path))) {
            // Barcode sudah ada di DB dan file masih ada
            $qrSvgContent = file_get_contents(storage_path('app/public/' . $barcodeRecord->signature_path));
        } else {
            // Generate QR baru, simpan file, dan simpan ke DB
            $qrSvgContent = $this->generateAndStoreQr($surat);
        }

        return view('templates.template-surat.template-spd', compact('surat', 'qrSvgContent', 'needsBudget'));
    }

    /**
     * Save budget for mendadak SPD at print time
     */
    public function saveMendadakBudget(Request $request, SuratSpd $surat)
    {
        $surat = SuratSpd::with('budgets')->findOrFail($surat->id);

        if ($surat->type !== 'mendadak') {
            return redirect()->back()->with('error', 'Hanya SPD mendadak yang bisa input budget saat cetak.');
        }

        $validator = Validator::make($request->all(), [
            'budgets'               => 'required|array|min:1',
            'budgets.*.tanggal'     => 'required|date',
            'budgets.*.nominal'     => 'required|numeric|min:0',
            'budgets.*.catatan'     => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $totalBudget = 0;
            foreach ($request->budgets as $budgetData) {
                SuratSpdBudget::create([
                    'surat_spd_id' => $surat->id,
                    'tanggal'      => $budgetData['tanggal'],
                    'nominal'      => $budgetData['nominal'],
                    'catatan'      => $budgetData['catatan'] ?? null,
                ]);
                $totalBudget += (float) $budgetData['nominal'];
            }

            $surat->update(['total_budget' => $totalBudget]);

            DB::commit();

            return redirect()->route('surat.superadmin.print', $surat)
                ->with('print_now', true);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan budget: ' . $e->getMessage());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                    SECTION 3: PERPANJANGAN - REQUESTER                    ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Show form untuk ajukan perpanjangan (requester)
     *
     * @return \Illuminate\View\View
     */
    public function createPerpanjangan()
    {
        // Get surat yang approved dan belum completed (masih bisa diperpanjang)
        $suratList = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->with(['vehicle', 'childExtensions'])
            ->orderBy('tanggal_kembali', 'desc')
            ->get();

        return view('requester.spd.perpanjangan.create', compact('suratList'));
    }

    /**
     * Store pengajuan perpanjangan dari requester
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePerpanjangan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'surat_spd_id' => 'required|exists:surat_spds,id',
            'tanggal_kembali_baru' => 'required|date|after:today',
            'alasan_perpanjangan' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate surat milik user ini
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->findOrFail($request->surat_spd_id);

        // Validate tanggal kembali baru harus setelah tanggal kembali sekarang
        $tanggalKembaliBaru = Carbon::parse($request->tanggal_kembali_baru);
        if ($tanggalKembaliBaru->lessThanOrEqualTo($surat->tanggal_kembali)) {
            return redirect()->back()
                ->with('error', 'Tanggal kembali baru harus setelah tanggal kembali sebelumnya (' .
                    $surat->tanggal_kembali->format('d M Y') . ')')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Create extension (status pending karena requester yang ajukan)
            $extensionSurat = $surat->createExtension(
                $request->tanggal_kembali_baru,
                $request->alasan_perpanjangan,
                false // autoApprove = false
            );

            DB::commit();

            return redirect()->route('requester.perpanjangan.index')
                ->with('success', 'Pengajuan perpanjangan berhasil! Nomor surat baru: ' .
                    $extensionSurat->nomor_surat . '. Menunggu persetujuan superadmin.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show list perpanjangan untuk requester
     *
     * @return \Illuminate\View\View
     */
    public function indexPerpanjanganRequester()
    {
        // Get semua extension milik user
        $extensionList = SuratSpdExtension::whereHas('originalSurat', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['originalSurat', 'extensionSurat', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('requester.spd.perpanjangan.index', compact('extensionList'));
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                    SECTION 4: PERPANJANGAN - SUPERADMIN                   ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Show list perpanjangan untuk superadmin
     *
     * @return \Illuminate\View\View
     */
    public function indexPerpanjanganSuperadmin()
    {
        // Pending approval
        $ext_pending = SuratSpdExtension::pending()
            ->with(['originalSurat.user', 'extensionSurat'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Approved tapi belum acknowledged
        $ext_need_acknowledge = SuratSpdExtension::needAcknowledge()
            ->with(['originalSurat.user', 'extensionSurat', 'approver'])
            ->orderBy('approved_at', 'desc')
            ->get();

        // Sudah acknowledged (selesai)
        $ext_acknowledged = SuratSpdExtension::acknowledged()
            ->with(['originalSurat.user', 'extensionSurat', 'approver', 'acknowledger'])
            ->orderBy('acknowledged_at', 'desc')
            ->limit(50)
            ->get();

        // Rejected
        $ext_rejected = SuratSpdExtension::rejected()
            ->with(['originalSurat.user', 'extensionSurat', 'rejector'])
            ->limit(50)
            ->get();

        // Stats
        $stats = [
            'pending' => $ext_pending->count(),
            'need_acknowledge' => $ext_need_acknowledge->count(),
            'acknowledged' => SuratSpdExtension::acknowledged()->count(),
            'rejected' => SuratSpdExtension::rejected()->count(),
            'done' => SuratSpdExtension::acknowledged()->count(),
        ];

        return view('superadmin.spd.perpanjangan.index', compact(
            'ext_pending',
            'ext_need_acknowledge',
            'ext_acknowledged',
            'ext_rejected',
            'stats'
        ))->with('pending_list', $ext_pending->count());
    }

    /**
     * Show detail perpanjangan untuk superadmin
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showPerpanjanganSuperadmin(SuratSpdExtension $perpanjangan)
    {
        $extension = SuratSpdExtension::with([
            'originalSurat.user',
            'originalSurat.vehicle',
            'originalSurat.details',
            'extensionSurat',
            'approver',
            'rejector',
            'acknowledger'
        ])->findOrFail($perpanjangan->id);

        return view('superadmin.spd.perpanjangan.detail', compact('extension'));
    }

    /**
     * Approve perpanjangan
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approvePerpanjangan(Request $request, SuratSpdExtension $perpanjangan)
    {
        $extension = SuratSpdExtension::pending()->findOrFail($perpanjangan->id);

        DB::beginTransaction();
        try {
            // Approve extension (ini juga akan approve surat extension & set lpj_deadline)
            $extension->approve(Auth::id());

            // Update catatan jika ada
            if ($request->filled('catatan')) {
                $extension->update(['catatan' => $request->catatan]);
            }

            DB::commit();

            return redirect()->route('perpanjangan.superadmin.index')
                ->with('success', 'Perpanjangan berhasil disetujui! Surat baru: ' .
                    $extension->nomor_surat_extension);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reject perpanjangan
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectPerpanjangan(Request $request, SuratSpdExtension $perpanjangan)
    {
        $request->validate([
            'alasan' => 'required|string|min:10',
        ]);

        $extension = SuratSpdExtension::pending()->findOrFail($perpanjangan->id);

        $extension->reject(Auth::id(), $request->alasan);

        return redirect()->route('perpanjangan.superadmin.index')
            ->with('success', 'Perpanjangan berhasil ditolak.');
    }

    /**
     * Acknowledge perpanjangan (checklist sudah diproses)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acknowledgePerpanjangan(Request $request, SuratSpdExtension $perpanjangan)
    {
        $extension = SuratSpdExtension::approved()
            ->where('is_acknowledged', false)
            ->findOrFail($perpanjangan->id);

        $extension->acknowledge(Auth::id(), $request->catatan);

        return redirect()->route('perpanjangan.superadmin.index')
            ->with('success', 'Perpanjangan berhasil di-acknowledge.');
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                       SECTION 5: LPJ - REQUESTER                          ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Halaman validasi semua laporan harian SPD lalu generate LPJ (requester).
     * Reuses pelaporan.spd.validasi; route di-key dengan slug nomor surat.
     *
     * @param \App\Models\SuratSpd $surat
     * @return \Illuminate\View\View
     */
    public function createLaporan(SuratSpd $surat)
    {
        return app(\App\Http\Controllers\SpdDailyReportController::class)->validasi($surat);
    }

    /**
     * Store LPJ dari requester
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Store LPJ dari requester
     * Budget diambil dari surat_spd_budgets, BUKAN dari budget_per_hari
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeLaporan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'surat_spd_id' => 'required|exists:surat_spds,id',
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'uraian' => 'required|string',
            'uang_dari' => 'nullable|string|max:255',
            'jumlah_diberikan' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'odo_before' => 'nullable|numeric|min:0',
            'odo_after' => 'nullable|numeric|min:0',
            'kebutuhan_bbm_liter' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.tanggal' => 'required|date',
            // Alur baru: setiap item = 1 gambar struk + kategori (AI/manual).
            // tipe_penggunaan lama tetap dipakai sebagai fallback.
            'items.*.struk_kategori' => 'nullable|in:BBM,Tol,Parkir,Lainnya',
            'items.*.kategori_source' => 'nullable|in:ai,manual',
            'items.*.tipe_penggunaan' => 'nullable|in:BBM,Tol,Parkir,Lain-lain',
            'items.*.nominal' => 'required|numeric|min:0',
            'items.*.keterangan' => 'nullable|string',
            'items.*.struk' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'items.required' => 'Minimal harus ada 1 item pengeluaran.',
            'items.*.struk_kategori.in' => 'Kategori tidak valid.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate surat milik user dan belum ada LPJ
        $surat = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with('vehicle')
            ->findOrFail($request->surat_spd_id);

        // Check deadline
        $isOverdue = $surat->isLpjDeadlineOverdue();

        // ✅ Query budget per tanggal dari surat_spd_budgets
        $budgets = SuratSpdBudget::where('surat_spd_id', $surat->id)
            ->pluck('nominal', 'tanggal'); // ['2026-02-09' => 1400000, ...]

        $totalBudgetFromTable = $budgets->sum();

        // ✅ Hitung total pengeluaran
        $totalPengeluaran = collect($request->items)->sum('nominal');

        // Budget kini opsional — over budget hanya dihitung bila budget diisi
        $isOverBudget = $totalBudgetFromTable > 0 && $totalPengeluaran > $totalBudgetFromTable;

        DB::beginTransaction();
        try {
            // ✅ Create LPJ
            $lpj = LpjPenggantian::create([
                'surat_spd_id'     => $surat->id,
                'user_id'          => Auth::id(),
                'nama'             => $request->nama,
                'jabatan'          => $request->jabatan,
                'vehicle_id'       => $surat->vehicle_id,
                'nomor_polisi'     => $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor ?? '-',
                'odo_before'       => $request->odo_before,
                'odo_after'        => $request->odo_after,
                'kebutuhan_bbm_liter' => $request->kebutuhan_bbm_liter,
                'uraian'           => $request->uraian,
                'uang_dari'        => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan' => $request->jumlah_diberikan ?? 0,
                'total_pengeluaran'=> $totalPengeluaran,
                'budget_per_hari'  => $totalBudgetFromTable > 0
                    ? round($totalBudgetFromTable / max($budgets->count(), 1))
                    : 0,
                'catatan'          => $request->catatan ?? '',
                'status'           => 'pending',
                'is_late'          => $isOverdue,
                'is_over_budget'   => $isOverBudget,
            ]);

            // Create LPJ details (+ struk_items untuk alur baru)
            foreach ($request->items as $index => $item) {
                $kategori = $item['struk_kategori'] ?? null;

                // Normalisasi kategori AI ke 4 kategori valid (Lainnya = Lain-lain di tipe lama)
                if ($kategori) {
                    $kategori = \App\Models\StrukItem::normalizeKategori($kategori);
                    $tipePenggunaan = $kategori === 'Lainnya' ? 'Lain-lain' : $kategori;
                } else {
                    $tipePenggunaan = $item['tipe_penggunaan'];
                }

                $detail = LpjPenggantianDetail::create([
                    'lpj_penggantian_id' => $lpj->id,
                    'tanggal'            => $item['tanggal'],
                    'tipe_penggunaan'    => $tipePenggunaan,
                    'nominal'            => $item['nominal'],
                    'keterangan'         => $item['keterangan'] ?? '',
                    'urutan'             => $index + 1,
                ]);

                // Alur baru: item membawa gambar struk → simpan (kompres 50%, tanpa resize)
                if (! empty($item['struk']) && $item['struk'] instanceof \Illuminate\Http\UploadedFile) {
                    $path = $this->storeStrukImage($item['struk'], $surat->nomor_surat);

                    $itemCode = \App\Models\StrukItem::generateItemCode(
                        $kategori ?: ($tipePenggunaan === 'Lain-lain' ? 'Lainnya' : $tipePenggunaan),
                        $surat->nomor_surat,
                        $item['tanggal']
                    );

                    \App\Models\StrukItem::create([
                        'spd_id'          => $surat->id,
                        'nomor_surat_spd' => $surat->nomor_surat,
                        'lpj_id'          => $lpj->id,
                        'lpj_detail_id'   => $detail->id,
                        'item_code'       => $itemCode,
                        'path_image'      => $path,
                        'struk_kategori'  => $kategori ?: ($tipePenggunaan === 'Lain-lain' ? 'Lainnya' : $tipePenggunaan),
                        'kategori_source' => $item['kategori_source'] ?? 'manual',
                        'metadata_json'   => ! empty($item['metadata_json']) ? $item['metadata_json'] : null,
                        'nominal'         => $item['nominal'],
                        'tanggal'         => $item['tanggal'],
                        'keterangan'      => $item['keterangan'] ?? '',
                        'urutan'          => $index + 1,
                    ]);
                }
            }

            DB::commit();

            // Build success message
            $message = 'Laporan Pertanggungjawaban berhasil diajukan!';
            if ($isOverdue) {
                $message .= ' ⚠️ Pengajuan melewati deadline.';
            }
            if ($isOverBudget) {
                $message .= ' ⚠️ Over budget Rp ' .
                    number_format($totalPengeluaran - $totalBudgetFromTable, 0, ',', '.') . '.';
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
     * Show edit form for rejected LPJ (revision by requester)
     */
    public function editLaporanRequester(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::where('user_id', Auth::id())
            ->where('status', 'rejected')
            ->with(['suratSpd', 'suratSpd.vehicle', 'details.strukItem', 'suratSpd.budgets'])
            ->findOrFail($laporan->id);

        $surat = $laporan->suratSpd;

        $budgetsPerSurat = [];
        if ($surat) {
            $budgetsPerSurat[$surat->id] = SuratSpdBudget::where('surat_spd_id', $surat->id)
                ->orderBy('tanggal')
                ->get()
                ->map(fn ($b) => [
                    'tanggal' => $b->tanggal->format('Y-m-d'),
                    'nominal' => $b->nominal,
                    'catatan' => $b->catatan,
                ]);
        }

        return view('requester.spd.lpj.edit', compact('laporan', 'surat', 'budgetsPerSurat'));
    }

    /**
     * Update rejected LPJ (resubmit by requester)
     */
    public function updateLaporanRequester(Request $request, LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::where('user_id', Auth::id())
            ->where('status', 'rejected')
            ->with('suratSpd')
            ->findOrFail($laporan->id);

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'uraian' => 'required|string',
            'uang_dari' => 'nullable|string|max:255',
            'jumlah_diberikan' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'odo_before' => 'nullable|numeric|min:0',
            'odo_after' => 'nullable|numeric|min:0',
            'kebutuhan_bbm_liter' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.tanggal' => 'required|date',
            'items.*.struk_kategori' => 'nullable|in:BBM,Tol,Parkir,Lainnya',
            'items.*.kategori_source' => 'nullable|in:ai,manual',
            'items.*.tipe_penggunaan' => 'nullable|in:BBM,Tol,Parkir,Lain-lain',
            'items.*.nominal' => 'required|numeric|min:0',
            'items.*.keterangan' => 'nullable|string',
            'items.*.struk' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $surat = $laporan->suratSpd;
        $totalPengeluaran = collect($request->items)->sum('nominal');

        $budgets = SuratSpdBudget::where('surat_spd_id', $surat->id)
            ->pluck('nominal', 'tanggal');

        $totalBudgetFromTable = $budgets->sum();

        $pengeluaranPerTanggal = collect($request->items)
            ->groupBy('tanggal')
            ->map(fn($items) => $items->sum('nominal'));

        $isOverBudget = $totalBudgetFromTable > 0 && $totalPengeluaran > $totalBudgetFromTable;

        DB::beginTransaction();
        try {
            $laporan->update([
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
                'uraian' => $request->uraian,
                'uang_dari' => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan' => $request->jumlah_diberikan ?? 0,
                'odo_before' => $request->odo_before,
                'odo_after' => $request->odo_after,
                'kebutuhan_bbm_liter' => $request->kebutuhan_bbm_liter,
                'total_pengeluaran' => $totalPengeluaran,
                'catatan' => $request->catatan ?? '',
                'status' => 'pending',
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'is_over_budget' => $isOverBudget,
            ]);

            // Simpan struk_items lama (urutannya) supaya bisa di-reuse bila
            // user tidak mengupload ulang gambar pada item yang sama.
            $oldStrukItems = $laporan->strukItems()->orderBy('urutan')->get();

            // Delete old details, recreate (struk_items tetap dipertahankan & di-re-link)
            $laporan->details()->delete();

            $createdDetailIds = [];
            $replacedIndices = []; // index yang upload struk baru (menggantikan yang lama)
            foreach ($request->items as $index => $item) {
                $kategori = $item['struk_kategori'] ?? null;

                if ($kategori) {
                    $kategori = \App\Models\StrukItem::normalizeKategori($kategori);
                    $tipePenggunaan = $kategori === 'Lainnya' ? 'Lain-lain' : $kategori;
                } else {
                    $tipePenggunaan = $item['tipe_penggunaan'];
                }

                $detail = LpjPenggantianDetail::create([
                    'lpj_penggantian_id' => $laporan->id,
                    'tanggal' => $item['tanggal'],
                    'tipe_penggunaan' => $tipePenggunaan,
                    'nominal' => $item['nominal'],
                    'keterangan' => $item['keterangan'] ?? '',
                    'urutan' => $index + 1,
                ]);

                $createdDetailIds[$index] = $detail->id;

                if (! empty($item['struk']) && $item['struk'] instanceof \Illuminate\Http\UploadedFile) {
                    $path = $this->storeStrukImage($item['struk'], $surat->nomor_surat);

                    $itemCode = \App\Models\StrukItem::generateItemCode(
                        $kategori ?: ($tipePenggunaan === 'Lain-lain' ? 'Lainnya' : $tipePenggunaan),
                        $surat->nomor_surat,
                        $item['tanggal']
                    );

                    \App\Models\StrukItem::create([
                        'spd_id'          => $surat->id,
                        'nomor_surat_spd' => $surat->nomor_surat,
                        'lpj_id'          => $laporan->id,
                        'lpj_detail_id'   => $detail->id,
                        'item_code'       => $itemCode,
                        'path_image'      => $path,
                        'struk_kategori'  => $kategori ?: ($tipePenggunaan === 'Lain-lain' ? 'Lainnya' : $tipePenggunaan),
                        'kategori_source' => $item['kategori_source'] ?? 'manual',
                        'metadata_json'   => ! empty($item['metadata_json']) ? $item['metadata_json'] : null,
                        'nominal'         => $item['nominal'],
                        'tanggal'         => $item['tanggal'],
                        'keterangan'      => $item['keterangan'] ?? '',
                        'urutan'          => $index + 1,
                    ]);

                    $replacedIndices[] = $index;
                }
            }

            // Re-link struk_items lama ke detail baru (posisi sama), hapus yang tidak dipakai
            foreach ($oldStrukItems as $index => $old) {
                if (isset($createdDetailIds[$index]) && ! in_array($index, $replacedIndices, true)) {
                    $old->update([
                        'lpj_detail_id'   => $createdDetailIds[$index],
                        'urutan'          => $index + 1,
                        'nominal'         => $request->items[$index]['nominal'] ?? $old->nominal,
                        'tanggal'         => $request->items[$index]['tanggal'] ?? $old->tanggal,
                        'keterangan'      => $request->items[$index]['keterangan'] ?? $old->keterangan,
                    ]);
                } else {
                    $old->delete();
                }
            }

            DB::commit();

            return redirect()->route('requester.lpj.show', $laporan)
                ->with('success', 'LPJ berhasil diperbaiki dan diajukan ulang!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show list LPJ untuk requester
     *
     * @return \Illuminate\View\View
     */
    public function indexLaporanRequester()
    {
        $laporanList = LpjPenggantian::where('user_id', Auth::id())
            ->with(['suratSpd', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Stats
        $stats = [
            'total' => $laporanList->count(),
            'pending' => $laporanList->where('status', 'pending')->count(),
            'approved' => $laporanList->where('status', 'approved')->count(),
            'rejected' => $laporanList->where('status', 'rejected')->count(),
        ];

        return view('requester.spd.lpj.index', compact('laporanList', 'stats'));
    }

    /**
     * Show detail LPJ untuk requester
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showLaporanRequester(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::where('user_id', Auth::id())
            ->with(['suratSpd', 'vehicle', 'details.strukItem', 'approverPengguna', 'approverDivisiGa', 'rejector'])
            ->findOrFail($laporan->id);

        return view('requester.spd.lpj.show', compact('laporan'));
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                       SECTION 6: LPJ - SUPERADMIN                         ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Show list LPJ untuk validasi superadmin
     *
     * @return \Illuminate\View\View
     */
    public function indexLaporanSuperadmin()
    {
        $pending_list = LpjPenggantian::pending()
            ->with(['user', 'suratSpd.vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approved_list = LpjPenggantian::approved()
            ->with(['user', 'suratSpd.vehicle', 'approverPengguna'])
            ->orderBy('approved_pengguna_at', 'desc')
            ->limit(50)
            ->get();

        $rejected_list = LpjPenggantian::rejected()
            ->with(['user', 'suratSpd.vehicle', 'rejector'])
            ->orderBy('rejected_at', 'desc')
            ->limit(50)
            ->get();

        // Stats
        $stats = [
            'pending' => $pending_list->count(),
            'approved' => LpjPenggantian::approved()->count(),
            'rejected' => LpjPenggantian::rejected()->count(),
            'late' => LpjPenggantian::late()->count(),
            'over_budget' => LpjPenggantian::overBudget()->count(),
        ];

        // Total amount bulan ini
        $total_approved_amount = LpjPenggantian::approved()
            ->whereMonth('approved_pengguna_at', Carbon::now()->month)
            ->whereYear('approved_pengguna_at', Carbon::now()->year)
            ->sum('total_pengeluaran');

        return view('superadmin.spd.lpj.index', compact(
            'pending_list',
            'approved_list',
            'rejected_list',
            'stats',
            'total_approved_amount'
        ));
    }

    /**
     * Show detail LPJ untuk validasi superadmin
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showLaporanSuperadmin(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'details.strukItem',
            'suratSpd.parentSurat',
            'suratSpd.vehicle',
            'suratSpd.details',
            'vehicle',
            'user',
            'approverPengguna',
            'approverDivisiGa',
            'rejector'
        ])->findOrFail($laporan->id);

        return view('superadmin.spd.lpj.detail', compact('laporan'));
    }

    /**
     * Approve LPJ
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveLaporan(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);

        DB::beginTransaction();
        try {
            // Simple approve (superadmin = approve both)
            $laporan->approve(Auth::id());

            DB::commit();

            return redirect()->route('lpj.superadmin.index')
                ->with('success', 'Laporan Pertanggungjawaban berhasil disetujui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reject LPJ
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectLaporan(Request $request, LpjPenggantian $laporan)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);

        $laporan->reject(Auth::id(), $request->rejection_reason);

        return redirect()->route('lpj.superadmin.index')
            ->with('success', 'Laporan Pertanggungjawaban berhasil ditolak.');
    }

    /**
     * Update data keuangan LPJ (diisi admin GA / orang keuangan).
     * Driver tidak mengisi data ini saat membuat laporan harian.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateKeuanganLaporan(Request $request, LpjPenggantian $laporan)
    {
        $request->validate([
            'uang_dari'        => 'nullable|string|max:255',
            'jumlah_diberikan' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($laporan, $request) {
            $laporan->update([
                'uang_dari'        => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan' => $request->jumlah_diberikan ?? 0,
            ]);
            $laporan->calculateKeuangan();
        });

        return redirect()->back()
            ->with('success', 'Data keuangan LPJ berhasil diperbarui.');
    }

    /**
     * Print LPJ
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function printLaporan(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'details',
            'suratSpd.parentSurat',
            'suratSpd.details',
            'vehicle',
            'user'
        ])->where('status', 'approved')
          ->findOrFail($laporan->id);

        return view('templates.template-surat.template-lpj', compact('laporan'));
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                       SECTION 7: HELPER / AJAX                            ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Get detail surat untuk AJAX (digunakan di form LPJ)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuratDetail(SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->with(['vehicle', 'details', 'parentSurat'])
            ->findOrFail($surat->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
                'tempat_tujuan' => $surat->tempat_tujuan,
                'tanggal_berangkat' => $surat->tanggal_berangkat->format('Y-m-d'),
                'tanggal_kembali' => $surat->tanggal_kembali->format('Y-m-d'),
                'lama_perjalanan_hari' => $surat->lama_perjalanan_hari,
                'budget_per_hari' => $surat->budget_per_hari,
                'total_budget' => $surat->total_budget,
                'lpj_deadline' => $surat->lpj_deadline ? $surat->lpj_deadline->format('Y-m-d') : null,
                'is_extension' => $surat->is_extension,
                'parent_nomor_surat' => $surat->parentSurat->nomor_surat ?? null,
                'vehicle' => [
                    'id' => $surat->vehicle->id,
                    'nama' => $surat->vehicle->consumerial_name,
                    'plat' => $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor,
                ],
                'penumpang' => $surat->details->pluck('nama_lengkap'),
            ],
        ]);
    }

    /**
     * Get surat list untuk dropdown (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuratListForDropdown(Request $request)
    {
        $query = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->with(['vehicle', 'parentSurat']);

        // Filter: hanya yang belum ada LPJ
        if ($request->get('need_lpj')) {
            $query->whereDoesntHave('lpjPenggantian');
        }

        $suratList = $query->orderBy('tanggal_kembali', 'desc')->get();

        $data = $suratList->map(function ($surat) {
            return [
                'id' => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
                'text' => $surat->is_extension
                    ? '└── ' . $surat->nomor_surat . ' (Perpanjangan)'
                    : $surat->nomor_surat,
                'tujuan' => $surat->tempat_tujuan,
                'tanggal' => $surat->tanggal_berangkat->format('d M') . ' - ' . $surat->tanggal_kembali->format('d M Y'),
                'is_extension' => $surat->is_extension,
                'deadline' => $surat->lpj_deadline ? $surat->lpj_deadline->format('d M Y') : '-',
                'is_overdue' => $surat->isLpjDeadlineOverdue(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Dashboard SPD Stats (untuk dashboard)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardStats()
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->role === 'superadmin' || Auth::user()->role === 'admin';

        if ($isAdmin) {
            // Stats untuk superadmin
            $stats = [
                'surat_pending' => SuratSpd::pending()->count(),
                'perpanjangan_pending' => SuratSpdExtension::pending()->count(),
                'lpj_pending' => LpjPenggantian::pending()->count(),
                'lpj_late' => LpjPenggantian::late()->count(),
                'lpj_over_budget' => LpjPenggantian::overBudget()->count(),
            ];
        } else {
            // Stats untuk requester
            $stats = [
                'surat_total' => SuratSpd::forUser($userId)->count(),
                'surat_approved' => SuratSpd::forUser($userId)->approved()->count(),
                'surat_need_lpj' => SuratSpd::forUser($userId)->needLpj()->count(),
                'lpj_pending' => LpjPenggantian::where('user_id', $userId)->pending()->count(),
                'lpj_approved' => LpjPenggantian::where('user_id', $userId)->approved()->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // Helper untuk generate QR code signature (digunakan di print surat)
    /**
     * Generate QR code, simpan file SVG ke storage, dan simpan path ke DB.
     * Return SVG content string untuk ditampilkan langsung di template.
     */
    private function generateAndStoreQr(SuratSpd $surat): ?string
    {
        try {
            $storageDir = storage_path('app/public/qr-surat');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $filename = 'QR_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $surat->nomor_surat) . '.svg';
            $relativePath = 'qr-surat/' . $filename;
            $fullPath = $storageDir . '/' . $filename;

            // Generate QR SVG
            $qrData = json_encode([
                'id'                => $surat->id,
                'nomor_surat'       => $surat->nomor_surat,
                'authorizer'        => $surat->pemberi_tugas_nama,
                'tanggal_terbit'    => $surat->created_at->format('Y-m-d'),
                'tanggal_disetujui' => $surat->approved_at?->format('Y-m-d'),
            ]);

            $svgContent = $this->generateQrSvgWithLogo($qrData);
            file_put_contents($fullPath, $svgContent);

            // Simpan ke database (update or create)
            SpdBarcodeSignature::updateOrCreate(
                ['spd_id' => $surat->id],
                ['signature_path' => $relativePath]
            );

            return $svgContent;

        } catch (\Throwable $e) {
            \Log::warning('QR signature gagal: ' . $e->getMessage(), [
                'surat_id'    => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
            ]);
            return null;
        }
    }

    /**
     * Generate QR code tanda tangan untuk surat SPD (legacy).
     * - Di-generate on-the-fly saat print
     * - Di-cache ke storage supaya tidak generate ulang jika file sudah ada
     * - Return base64 string siap pakai di <img src="...">
     */
    private function generateSignatureQr(SuratSpd $surat): ?string
{
    try {
        $storageDir = storage_path('app/public/qr-surat');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'QR_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $surat->nomor_surat) . '.svg';
        $fullPath = $storageDir . '/' . $filename;

        // Cache — tidak generate ulang jika sudah ada
        if (!file_exists($fullPath)) {
            $qrData = json_encode([
                'id'                => $surat->id,
                'nomor_surat'       => $surat->nomor_surat,
                'authorizer'        => $surat->pemberi_tugas_nama,
                'tanggal_terbit'    => $surat->created_at->format('Y-m-d'),
                'tanggal_disetujui' => $surat->approved_at?->format('Y-m-d'),
            ]);

            $svgContent = $this->generateQrSvgWithLogo($qrData);
            file_put_contents($fullPath, $svgContent);
        }

        // Return SVG sebagai base64 data URI
        return 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($fullPath));

    } catch (\Throwable $e) {
        \Log::warning('QR signature gagal: ' . $e->getMessage(), [
            'surat_id'    => $surat->id,
            'nomor_surat' => $surat->nomor_surat,
        ]);
        return null;
    }
}

private function generateQrSvgWithLogo(string $data): string
{
    $qrSize  = 300;
    $logoSize = 54;
    $padding  = 4;

    $renderer = new ImageRenderer(
        new RendererStyle($qrSize, 1),
        new SvgImageBackEnd()
    );
    $svg = (new Writer($renderer))->writeString($data);

    // Ambil dimensi viewBox
    preg_match('/viewBox="0 0 (\d+(?:\.\d+)?) (\d+(?:\.\d+)?)"/', $svg, $m);
    $svgW = isset($m[1]) ? (float)$m[1] : $qrSize;
    $svgH = isset($m[2]) ? (float)$m[2] : $qrSize;

    $logoX = ($svgW - $logoSize) / 2;
    $logoY = ($svgH - $logoSize) / 2;

    // Embed logo sebagai base64
    $logoPath = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
    $logoElements = '';

    if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        $rectX = $logoX - $padding;
        $rectY = $logoY - $padding;
        $rectW = $logoSize + ($padding * 2);
        $rectH = $logoSize + ($padding * 2);

        $logoElements = <<<SVG

    <rect x="{$rectX}" y="{$rectY}" width="{$rectW}" height="{$rectH}" fill="white" rx="3" ry="3"/>
    <image href="data:image/png;base64,{$logoBase64}"
           x="{$logoX}" y="{$logoY}"
           width="{$logoSize}" height="{$logoSize}"
           preserveAspectRatio="xMidYMid meet"/>
SVG;
    }

    return str_replace('</svg>', $logoElements . "\n</svg>", $svg);
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
}
