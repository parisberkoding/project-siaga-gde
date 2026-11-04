<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDefault;
use App\Models\LpjPenggantian;
use App\Models\LpjPenggantianDetail;
use App\Models\SpdBarcodeSignature;
use App\Models\SuratSpd;
use App\Models\SuratSpdBudget;
use App\Models\SuratSpdDetail;
use App\Models\SuratSpdExtension;
use App\Models\SuratSpdFundAddition;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Services\ImageCompressor;
use App\Services\UnitScope;
use App\Support\MediaStorage;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        // Scope unit: hanya kendaraan milik unit pengguna (user global/Pusat = semua unit).
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->where('consumerial_type', 'Kendaraan')
            ->visibleTo(Auth::user())
            ->orderBy('consumerial_name')
            ->get();

        return view('requester.spd.surat.create', compact('vehicles'));
    }

    /**
     * Store surat SPD dari requester
     * TANPA budget - budget ditentukan superadmin saat approve
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeSurat(Request $request)
    {
        // Aturan pemakai/penumpang: user_id (bila diisi) harus user yang valid.
        $passengerRules = $this->passengerValidationRules();

        $validator = Validator::make($request->all(), array_merge([
            'tempat_tujuan' => 'required|string',
            'melayani_bidang' => 'required|string|max:255',
            'jumlah_pemakai' => 'required|integer|min:1|max:7',
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'tanggal_berangkat' => 'required|date|after_or_equal:today',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_berangkat',
            'catatan' => 'nullable|string',
            'jenis_spd' => 'nullable|in:dalam_kota,luar_kota,lainnya',
            'jenis_spd_lainnya' => 'nullable|string|max:255',
            // BUDGET TIDAK DIPERLUKAN - ditentukan superadmin
        ], $passengerRules));

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Get vehicle untuk nomor polisi
            $vehicle = VehicleAndToolsConsumers::findOrFail($request->vehicle_id);

            // Scope unit: plat nomor hanya boleh dipakai unit pemiliknya.
            if (! $vehicle->isVisibleTo(Auth::user())) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Plat nomor '.$vehicle->consumerial_code.' terdaftar di unit lain ('
                        .($vehicle->unit?->name ?? 'tanpa unit').'). Pilih kendaraan unit Anda.');
            }

            // Create surat SPD TANPA budget & TANPA pemberi tugas
            $surat = SuratSpd::createWithNumberedSurat([
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
                'unit_id' => Auth::user()->unit_id,
                'jenis_spd' => $request->jenis_spd,
                'jenis_spd_lainnya' => $request->jenis_spd === 'lainnya' ? $request->jenis_spd_lainnya : null,
            ]);

            // Add nama lengkap penumpang (1-7)
            $urutan = 1;
            for ($i = 1; $i <= 7; $i++) {
                $namaLengkap = $request->input("nama_lengkap_{$i}");
                if (! empty($namaLengkap)) {
                    SuratSpdDetail::create([
                        'surat_spd_id' => $surat->id,
                        'nama_lengkap' => $namaLengkap,
                        'user_id' => $request->input("user_id_{$i}") ?: null,
                        'urutan' => $urutan++,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('requester.surat.index')
                ->with('success', 'Surat Tugas berhasil diajukan! Nomor: '.$surat->nomor_surat);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal mengajukan surat SPD', [
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.')
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
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showSuratRequester(SuratSpd $surat)
    {
        $surat = SuratSpd::forUser(Auth::id())
            ->with(['details', 'vehicle', 'approver', 'rejector', 'lpjPenggantian', 'childExtensions', 'parentSurat'])
            ->findOrFail($surat->id);

        return view('requester.spd.surat.show', compact('surat'));
    }

    /**
     * SPD Berjalan — daftar surat tugas yang masih harus dilaporkan driver.
     *
     * Hanya surat berstatus approved yang belum punya LPJ. Ditampilkan langsung
     * sebagai daftar (tanpa pemilihan mode) agar driver langsung tahu surat mana
     * yang harus dibuat laporannya.
     *
     * @return \Illuminate\View\View
     */
    public function indexSpdBerjalan()
    {
        $suratList = SuratSpd::forUser(Auth::id())
            ->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian')
            ->with(['vehicle', 'parentSurat', 'budgets', 'dailyReports.strukItems'])
            ->orderBy('tanggal_kembali', 'desc')
            ->get();

        // Informasi laporan harian per surat (draft/submitted) untuk panel driver.
        $suratList = $suratList->map(function ($surat) {
            $submitted = $surat->dailyReports->where('status', 'submitted');
            $surat->daily_submitted_count = $submitted->count();
            $surat->daily_item_count = (int) $submitted->sum(fn ($r) => $r->strukItems->count());
            $surat->has_draft_daily = $surat->dailyReports->contains('status', 'draft');
            $surat->daily_reports_available = $surat->dailyReports->isNotEmpty();

            return $surat;
        });

        return view('requester.spd.berjalan.index', compact('suratList'));
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
        $unitId = UnitScope::for(Auth::user())->unitId();

        $pending_list = SuratSpd::pending()
            ->inUnit($unitId)
            ->with(['user', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approved_list = SuratSpd::approved()
            ->inUnit($unitId)
            ->with(['user', 'vehicle', 'approver', 'lpjPenggantian'])
            ->orderBy('approved_at', 'desc')
            ->limit(50)
            ->get();

        $rejected_list = SuratSpd::rejected()
            ->inUnit($unitId)
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
            'approved_count' => SuratSpd::approved()->inUnit($unitId)->count(),
            'rejected_count' => SuratSpd::rejected()->inUnit($unitId)->count(),
        ]);
    }

    /**
     * Show detail surat SPD untuk validasi superadmin
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showSuratSuperadmin(SuratSpd $surat)
    {
        // BUG-SPD-002: cegah Admin GA unit lain membuka detail surat.
        // Null unit_id hanya lolos untuk user global (allowsUnit).
        UnitScope::for(Auth::user())->ensureUnit($surat->unit_id);

        $surat = SuratSpd::with([
            'details',
            'vehicle',
            'user',
            'approver',
            'rejector',
            'parentSurat',
            'childExtensions',
            'lpjPenggantian',
            'fundAdditions',
            'budgets',
            'pencairans',
        ])->findOrFail($surat->id);

        return view('superadmin.spd.surat.detail', [
            'surat' => $surat,
            // Opsi pemberi tugas yang sama dengan form shortcut, agar nama &
            // jabatan pada surat (dan template cetak) selalu seragam.
            'pejabatOptions' => $this->pejabatOptions(),
        ]);
    }

    /**
     * Approve surat SPD
     * Set status approved, budget per hari, dan calculate lpj_deadline
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveSurat(Request $request, SuratSpd $surat)
    {
        // BUG-SPD-002: validasi unit sebelum status/mutasi apa pun.
        UnitScope::for(Auth::user())->ensureUnit($surat->unit_id);

        if ($surat->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Surat ini sudah diproses sebelumnya.');
        }

        // Pemberi tugas boleh dipilih dari Master Pekerja (sumber yang sama
        // dengan template surat) atau diisi manual bila belum ada di master.
        $request->validate([
            'pemberi_employee_id' => 'nullable|exists:employee_defaults,id',
        ]);

        // Jika pemberi tugas belum diisi dan tidak memilih dari Master Pekerja,
        // nama & jabatan wajib diisi saat approve.
        if (empty($surat->pemberi_tugas_nama) && ! $request->filled('pemberi_employee_id')) {
            $request->validate([
                'pemberi_tugas_nama' => 'required|string|max:255',
                'pemberi_tugas_jabatan' => 'required|string|max:255',
            ]);
        }

        // Pemberian dana (opsional saat approve, tapi bila diisi harus valid).
        $request->validate($this->pencairanRules());

        DB::beginTransaction();
        try {
            // Set pemberi tugas: prioritas data Master Pekerja supaya nama &
            // jabatan yang tercetak pada template surat persis sama.
            if ($request->filled('pemberi_employee_id')) {
                $pejabat = EmployeeDefault::forUnit(UnitScope::for(Auth::user())->unitId())
                    ->find($request->pemberi_employee_id);
                if ($pejabat) {
                    $surat->pemberi_tugas_nama = $pejabat->employee_name;
                    $surat->pemberi_tugas_jabatan = $pejabat->position
                        ?: ($request->pemberi_tugas_jabatan ?: $surat->pemberi_tugas_jabatan);
                }
            } elseif ($request->filled('pemberi_tugas_nama')) {
                $surat->pemberi_tugas_nama = $request->pemberi_tugas_nama;
                $surat->pemberi_tugas_jabatan = $request->pemberi_tugas_jabatan;
            }

            // Set approval fields
            $surat->status = 'approved';
            $surat->approved_by = Auth::id();
            $surat->approved_at = Carbon::now();

            // Kode GL (SAP) & uang diberikan ke driver — diisi saat penerbitan surat.
            if ($request->filled('kode_gl')) {
                $surat->kode_gl = $request->kode_gl;
            }
            if ($request->filled('uang_diberikan')) {
                $surat->uang_diberikan = $request->uang_diberikan;
            }

            // Override jenis SPD oleh admin (permohonan mendadak / perubahan manajemen).
            if ($request->filled('jenis_spd')) {
                $surat->jenis_spd = $request->jenis_spd;
                $surat->jenis_spd_lainnya = $request->jenis_spd === 'lainnya' ? ($request->jenis_spd_lainnya ?? null) : null;
            }

            // Set LPJ deadline: 3 hari setelah tanggal kembali (akhir hari,
            // sehingga submit pada hari deadline masih dihitung tepat waktu).
            $surat->lpj_deadline = Carbon::parse($surat->tanggal_kembali)->addDays(3)->endOfDay();

            $surat->save();

            // Pemberian dana saat validasi: GA boleh mencatat satu/lebih
            // pencairan (Tunai/Transfer) sekaligus saat menyetujui surat.
            $pencairans = $this->extractPencairans($request);
            if ($pencairans !== []) {
                $this->simpanPencairans($surat, $pencairans);
            }

            DB::commit();

            return redirect()->route('surat.superadmin.index')
                ->with('success', 'Surat Tugas berhasil disetujui! Deadline LPJ: '.
                    $surat->lpj_deadline->translatedFormat('d M Y'));

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal menyetujui surat SPD', [
                'surat_id' => $surat->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Reject surat SPD
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectSurat(Request $request, SuratSpd $surat)
    {
        // BUG-SPD-002: Admin GA unit lain tidak boleh menolak surat unit ini.
        UnitScope::for(Auth::user())->ensureUnit($surat->unit_id);

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
        // Scope unit: hanya kendaraan milik unit pengguna (user global/Pusat = semua unit).
        $vehicles = VehicleAndToolsConsumers::where('is_active', true)
            ->where('consumerial_type', 'Kendaraan')
            ->visibleTo(Auth::user())
            ->orderBy('consumerial_name')
            ->get();

        return view('superadmin.spd.surat.create-shortcut', [
            'vehicles' => $vehicles,
            'pengemudiOptions' => $this->pengemudiOptions(),
            'pejabatOptions' => $this->pejabatOptions(),
        ]);
    }

    /**
     * Karyawan Master Pekerja yang boleh muncul sebagai PENGEMUDI.
     *
     * Hanya akun dengan role Driver/Requester. Pegawai non-driver (staff,
     * manager, operator, dsb.) tidak ditampilkan supaya dropdown pengemudi
     * tidak tercampur dengan daftar seluruh karyawan.
     */
    private function pengemudiOptions()
    {
        // Batas unit: superadmin unit hanya melihat Master Pekerja unitnya.
        $unitId = UnitScope::for(Auth::user())->unitId();

        return EmployeeDefault::active()
            ->forUnit($unitId)
            ->with('defaultSite')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['requester', 'driver'])
                    ->orWhereHas('roles', fn ($roles) => $roles->whereIn('code', ['requester', 'driver']));
            })
            ->orderBy('employee_name')
            ->get();
    }

    /**
     * Pejabat pemberi tugas (Assistant Manager / Manager) dari Master Pekerja.
     *
     * Sumber yang sama dipakai form shortcut dan form validasi GA, sehingga
     * nama & jabatan yang tercetak pada template surat selalu identik dengan
     * data Master Pekerja (bukan teks bebas yang bisa berbeda ejaan).
     */
    private function pejabatOptions()
    {
        // Batas unit: pemberi tugas hanya dari Master Pekerja unit user.
        $unitId = UnitScope::for(Auth::user())->unitId();

        return EmployeeDefault::active()
            ->forUnit($unitId)
            ->where(function ($q) {
                $q->where('position', 'like', '%manager%')
                    ->orWhere('position', 'like', '%Manager%')
                    ->orWhere('position', 'like', '%assistant%')
                    ->orWhere('position', 'like', '%Assistant%');
            })
            ->orderBy('employee_name')
            ->get();
    }

    /**
     * Store surat SPD shortcut — langsung approved + budget.
     * Superadmin create sekaligus approve tanpa perlu validasi GA.
     */
    public function storeSuratSuperadmin(Request $request)
    {
        $validator = Validator::make($request->all(), array_merge([
            // Pengemudi: hibrid — pilih dari Master Pekerja atau input manual
            'pengemudi_nama' => 'required|string|max:255',
            'pengemudi_employee_id' => 'nullable|exists:employee_defaults,id',
            // Pemberi Tugas: hibrid — pilih dari Master Pekerja atau input manual
            'pemberi_tugas_level' => 'required|in:manager,assistant_manager',
            'pemberi_tugas_nama' => 'required|string|max:255',
            'pemberi_tugas_jabatan' => 'required|string|max:255',
            'pemberi_employee_id' => 'nullable|exists:employee_defaults,id',
            // Perjalanan
            'tempat_tujuan' => 'required|string',
            'melayani_bidang' => 'required|string|max:255',
            'jumlah_pemakai' => 'required|integer|min:1|max:7',
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_berangkat',
            'catatan' => 'nullable|string',
            'type' => 'required|in:biasa,mendadak',
            'jenis_spd' => 'nullable|in:dalam_kota,luar_kota,lainnya',
            'jenis_spd_lainnya' => 'nullable|string|max:255',
            'kode_gl' => 'nullable|string|max:255',
            'uang_diberikan' => 'nullable|numeric|min:0',
            // Pemberian dana (baris per transfer) + kompatibilitas `budgets` lama
            'budgets' => 'nullable|array',
            'budgets.*.tanggal' => 'required_with:budgets|date',
            'budgets.*.nominal' => 'required_with:budgets|numeric|min:0',
            'budgets.*.catatan' => 'nullable|string|max:255',
        ], $this->pencairanRules(), $this->passengerValidationRules()), [
            'pengemudi_nama.required' => 'Nama pengemudi harus diisi.',
            'pemberi_tugas_nama.required' => 'Nama pemberi tugas harus diisi.',
            'pemberi_tugas_jabatan.required' => 'Jabatan pemberi tugas harus diisi.',
            'pemberi_tugas_level.required' => 'Pilih level pemberi tugas.',
            'pemberi_tugas_level.in' => 'Level pemberi tugas tidak valid.',
            'type.required' => 'Pilih tipe SPD.',
            'type.in' => 'Tipe SPD tidak valid.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Scope unit: superadmin unit hanya memakai plat nomor unitnya.
        $scope = UnitScope::for(Auth::user());
        $vehicle = VehicleAndToolsConsumers::find($request->vehicle_id);
        if ($vehicle && ! $vehicle->isVisibleTo(Auth::user())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Plat nomor '.$vehicle->consumerial_code.' terdaftar di unit lain ('
                    .($vehicle->unit?->name ?? 'tanpa unit').').');
        }

        // Pemberian dana: satu baris = satu transfer/pemberian (bukan per hari).
        $pencairans = $this->extractPencairans($request);
        $totalBudget = collect($pencairans)->sum('nominal');

        // Resolve pengemudi: prioritas Master Pekerja (boleh tanpa akun)
        $pengemudiNama = $request->pengemudi_nama;
        $employeeId = null;
        $userId = null;
        if ($request->filled('pengemudi_employee_id')) {
            $emp = EmployeeDefault::forUnit($scope->unitId())
                ->with('user')
                ->find($request->pengemudi_employee_id);

            if (! $emp) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Pengemudi yang dipilih berada di luar unit Anda.');
            }

            $employeeId = $emp->id;
            $userId = $emp->user_id; // boleh null
            $pengemudiNama = $emp->employee_name;
        } elseif (! empty($pengemudiNama)) {
            // Pengemudi diisi manual: cocokkan dengan Master Pekerja berdasarkan
            // nama (case-insensitive) supaya surat tetap muncul di POV driver
            // yang memiliki akun tanpa admin harus memilih dropdown.
            $empByName = EmployeeDefault::forUnit($scope->unitId())
                ->with('user')
                ->active()
                ->whereRaw('LOWER(employee_name) = LOWER(?)', [trim($pengemudiNama)])
                ->first();

            if ($empByName) {
                $employeeId = $empByName->id;
                $userId = $empByName->user_id;
                $pengemudiNama = $empByName->employee_name;
            }
        }

        // Pemberi tugas: bila dipilih dari Master Pekerja, nama & jabatan
        // diambil dari data master (bukan teks bebas) agar identik dengan
        // template surat yang dicetak.
        $pemberiNama = trim((string) $request->pemberi_tugas_nama);
        $pemberiJabatan = trim((string) $request->pemberi_tugas_jabatan);
        if ($request->filled('pemberi_employee_id')) {
            $pejabat = EmployeeDefault::forUnit($scope->unitId())->find($request->pemberi_employee_id);
            if ($pejabat) {
                $pemberiNama = $pejabat->employee_name;
                $pemberiJabatan = $pejabat->position ?: $pemberiJabatan;
            }
        }

        DB::beginTransaction();
        try {
            $surat = SuratSpd::createWithNumberedSurat([
                'pemberi_tugas_nama' => $pemberiNama,
                'pemberi_tugas_jabatan' => $pemberiJabatan,
                'user_id' => $userId,
                'employee_default_id' => $employeeId,
                'pengemudi_nama' => $pengemudiNama,
                'tempat_tujuan' => $request->tempat_tujuan,
                'melayani_bidang' => $request->melayani_bidang,
                'jumlah_pemakai' => $request->jumlah_pemakai,
                'vehicle_id' => $request->vehicle_id,
                'tanggal_berangkat' => $request->tanggal_berangkat,
                'tanggal_kembali' => $request->tanggal_kembali,
                'catatan' => $request->catatan,
                'type' => $request->type,
                'is_extension' => false,
                // Langsung approve
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => Carbon::now(),
                'total_budget' => $totalBudget,
                // 3 hari setelah tanggal kembali (akhir hari).
                'lpj_deadline' => Carbon::parse($request->tanggal_kembali)->addDays(3)->endOfDay(),
                'unit_id' => Auth::user()->unit_id,
                'jenis_spd' => $request->jenis_spd,
                'jenis_spd_lainnya' => $request->jenis_spd === 'lainnya' ? $request->jenis_spd_lainnya : null,
                'kode_gl' => $request->kode_gl,
                'uang_diberikan' => $request->uang_diberikan,
            ]);

            // Simpan nama penumpang
            $urutan = 1;
            for ($i = 1; $i <= 7; $i++) {
                $namaLengkap = $request->input("nama_lengkap_{$i}");
                if (! empty($namaLengkap)) {
                    SuratSpdDetail::create([
                        'surat_spd_id' => $surat->id,
                        'nama_lengkap' => $namaLengkap,
                        'user_id' => $request->input("user_id_{$i}") ?: null,
                        'urutan' => $urutan++,
                    ]);
                }
            }

            // Simpan pemberian dana (satu baris = satu transfer/pemberian).
            $this->simpanPencairans($surat, $pencairans);

            DB::commit();

            return redirect()
                ->route('surat.superadmin.show', $surat)
                ->with('success', 'Surat SPD langsung berhasil dibuat & disetujui! Nomor: '.$surat->nomor_surat);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal membuat surat SPD shortcut', [
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.')
                ->withInput();
        }
    }

    /**
     * Print surat SPD (untuk cetak)
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    /**
     * SPD spending report per surat
     */
    public function reportSpending()
    {
        $unitId = UnitScope::for(Auth::user())->unitId();

        $suratList = SuratSpd::with(['vehicle', 'user', 'lpjPenggantian', 'budgets'])
            ->inUnit($unitId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $totalBudget = SuratSpd::inUnit($unitId)->sum('total_budget');
        $totalApproved = SuratSpd::approved()->inUnit($unitId)->count();
        $totalLpj = LpjPenggantian::approved()->inUnit($unitId)->count();
        $totalSpending = LpjPenggantian::approved()->inUnit($unitId)->sum('total_pengeluaran');

        // Surat yang sudah disetujui tetapi LPJ-nya belum disubmit driver.
        // Dipisah: masih dalam batas 3 hari vs sudah lewat batas (overdue).
        // Overdue TIDAK menutup form LPJ — hanya penanda agar GA bisa mengejar.
        $suratMenungguLpj = SuratSpd::approved()
            ->inUnit($unitId)
            ->whereDoesntHave('lpjPenggantian')
            ->with(['vehicle', 'user'])
            ->orderBy('tanggal_kembali')
            ->get();

        $totalMenungguLpj = $suratMenungguLpj->count();
        $totalMenungguLpjOverdue = $suratMenungguLpj->filter(fn ($surat) => $surat->isLpjDeadlineOverdue())->count();

        return view('superadmin.spd.surat.report', compact(
            'suratList', 'totalBudget', 'totalApproved', 'totalLpj', 'totalSpending',
            'totalMenungguLpj', 'totalMenungguLpjOverdue'
        ));
    }

    public function printSurat(SuratSpd $surat)
    {
        // BUG-EXP-002: SPD yang LPJ-nya sudah approved berstatus `completed`
        // dan tetap harus bisa dicetak.
        $surat = SuratSpd::with(['details', 'vehicle', 'user.unit', 'user.defaultSite', 'approver', 'budgets'])
            ->whereIn('status', ['approved', 'completed'])
            ->findOrFail($surat->id);

        // BUG-SEC-001: hanya pemilik/penumpang, unit yang sama, atau user global.
        // Record ada tetapi tidak boleh dilihat -> 404 (bukan 403).
        if (! $surat->isVisibleToUser(Auth::user())) {
            abort(404);
        }

        $needsBudget = false; // Budget kini modul terpisah — tidak lagi menjadi syarat cetak

        // Check if barcode already exists in DB
        $barcodeRecord = SpdBarcodeSignature::where('spd_id', $surat->id)->first();

        // QR disimpan di disk privat (BUG-SEC-003); MediaStorage membaca
        // privat dulu lalu fallback ke file lama di disk publik.
        $qrSvgContent = $barcodeRecord && $barcodeRecord->signature_path
            ? MediaStorage::get($barcodeRecord->signature_path)
            : null;

        if ($qrSvgContent === null) {
            // Generate QR baru, simpan file, dan simpan ke DB
            $qrSvgContent = $this->generateAndStoreQr($surat);
        }

        return view('templates.template-surat.template-spd', compact('surat', 'qrSvgContent', 'needsBudget'));
    }

    /**
     * Save budget for mendadak SPD at print time
     */
    /**
     * Ambil baris PEMBERIAN DANA dari request.
     *
     * Satu baris = satu kali pemberian/transfer uang (bukan per hari).
     * Mendukung `pencairans[]` (form baru) dan `budgets[]` (form lama) agar
     * formulir/tombol lawas tetap berfungsi. Baris tanpa tanggal atau
     * bernominal 0 dilewati supaya baris kosong tidak tersimpan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractPencairans(Request $request): array
    {
        $rows = $request->input('pencairans');

        if (! is_array($rows) || $rows === []) {
            $rows = $request->input('budgets');
        }

        if (! is_array($rows)) {
            return [];
        }

        $hasil = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $tanggal = $row['tanggal'] ?? null;
            $nominal = (float) ($row['nominal'] ?? 0);

            if (! $tanggal || $nominal <= 0) {
                continue;
            }

            $bentuk = ($row['bentuk_pencairan'] ?? 'Tunai') === 'Transfer' ? 'Transfer' : 'Tunai';

            $hasil[] = [
                'tanggal'          => $tanggal,
                'nominal'          => $nominal,
                'bentuk_pencairan' => $bentuk,
                'bank_reference'   => $bentuk === 'Transfer' ? ($row['bank_reference'] ?? null) : null,
                'rek_tujuan'       => $bentuk === 'Transfer' ? ($row['rek_tujuan'] ?? null) : null,
                'catatan'          => $row['catatan'] ?? null,
            ];
        }

        return $hasil;
    }

    /**
     * Simpan baris pemberian dana untuk sebuah surat SPD, lalu sinkronkan
     * `total_budget` dan `uang_diberikan` = akumulasi seluruh baris surat itu.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return float  total dana diberikan setelah penyimpanan
     */
    private function simpanPencairans(SuratSpd $surat, array $rows): float
    {
        $urutan = (int) SuratSpdBudget::where('surat_spd_id', $surat->id)->max('urutan');

        foreach ($rows as $row) {
            SuratSpdBudget::create([
                'surat_spd_id'     => $surat->id,
                'tanggal'          => $row['tanggal'],
                'nominal'          => $row['nominal'],
                'bentuk_pencairan' => $row['bentuk_pencairan'],
                'bank_reference'   => $row['bank_reference'],
                'rek_tujuan'       => $row['rek_tujuan'],
                'catatan'          => $row['catatan'],
                'urutan'           => ++$urutan,
                // Sumber dana: awal (saat surat dibuat/validasi), tambahan,
                // atau perpanjangan (diinput Admin GA setelah ETD disetujui).
                'sumber'           => $row['sumber'] ?? SuratSpdBudget::SUMBER_AWAL,
                'surat_spd_extension_id' => $row['surat_spd_extension_id'] ?? null,
            ]);
        }

        $total = (float) SuratSpdBudget::where('surat_spd_id', $surat->id)->sum('nominal');

        if ($total > 0) {
            $surat->update([
                'total_budget'    => $total,
                'uang_diberikan'  => $total,
            ]);
        }

        return $total;
    }

    /**
     * Aturan validasi baris pemberian dana (dipakai beberapa form).
     *
     * @return array<string, string>
     */
    private function pencairanRules(string $prefix = 'pencairans'): array
    {
        return [
            "{$prefix}"                     => 'nullable|array',
            "{$prefix}.*.tanggal"           => 'required|date',
            "{$prefix}.*.nominal"           => 'required|numeric|min:0',
            "{$prefix}.*.bentuk_pencairan"  => 'nullable|in:Tunai,Transfer',
            "{$prefix}.*.bank_reference"    => 'nullable|string|max:255',
            "{$prefix}.*.rek_tujuan"        => 'nullable|string|max:255',
            "{$prefix}.*.catatan"           => 'nullable|string|max:255',
        ];
    }

    public function saveMendadakBudget(Request $request, SuratSpd $surat)
    {
        $surat = SuratSpd::with('budgets')->findOrFail($surat->id);

        // F-03 / BUG-SPD-002: hanya pengelola unit SPD ini (global = semua).
        UnitScope::for(Auth::user())->ensureUnit($surat->unit_id);

        if ($surat->type !== 'mendadak') {
            return redirect()->back()->with('error', 'Hanya SPD mendadak yang bisa input budget saat cetak.');
        }

        $validator = Validator::make($request->all(), array_merge([
            'budgets' => 'required|array|min:1',
            'budgets.*.tanggal' => 'required|date',
            'budgets.*.nominal' => 'required|numeric|min:0',
            'budgets.*.catatan' => 'nullable|string|max:255',
        ], $this->pencairanRules()), [
            'budgets.required' => 'Isi minimal satu baris pemberian dana.',
            'budgets.min'      => 'Isi minimal satu baris pemberian dana.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Catat sebagai baris pemberian dana (bukan budget per hari).
            $totalBudget = $this->simpanPencairans($surat, $this->extractPencairans($request));

            DB::commit();

            return redirect()->route('surat.superadmin.print', $surat)
                ->with('print_now', true);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal menyimpan pemberian dana SPD', [
                'surat_id' => $surat->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.');
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
                ->with('error', 'Tanggal kembali baru harus setelah tanggal kembali sebelumnya ('.
                    $surat->tanggal_kembali->translatedFormat('d M Y').')')
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
                ->with('success', 'Pengajuan perpanjangan berhasil! Nomor surat baru: '.
                    $extensionSurat->nomor_surat.'. Menunggu persetujuan superadmin.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal mengajukan perpanjangan SPD', [
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.')
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
        $unitId = UnitScope::for(Auth::user())->unitId();

        // Pending approval
        $ext_pending = SuratSpdExtension::pending()
            ->inUnit($unitId)
            ->with(['originalSurat.user', 'extensionSurat'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Approved tapi belum acknowledged
        $ext_need_acknowledge = SuratSpdExtension::needAcknowledge()
            ->inUnit($unitId)
            ->with(['originalSurat.user', 'extensionSurat', 'approver'])
            ->orderBy('approved_at', 'desc')
            ->get();

        // Sudah acknowledged (selesai)
        $ext_acknowledged = SuratSpdExtension::acknowledged()
            ->inUnit($unitId)
            ->with(['originalSurat.user', 'extensionSurat', 'approver', 'acknowledger'])
            ->orderBy('acknowledged_at', 'desc')
            ->limit(50)
            ->get();

        // Rejected
        $ext_rejected = SuratSpdExtension::rejected()
            ->inUnit($unitId)
            ->with(['originalSurat.user', 'extensionSurat', 'rejector'])
            ->limit(50)
            ->get();

        // Stats
        $stats = [
            'pending' => $ext_pending->count(),
            'need_acknowledge' => $ext_need_acknowledge->count(),
            'acknowledged' => SuratSpdExtension::acknowledged()->inUnit($unitId)->count(),
            'rejected' => SuratSpdExtension::rejected()->inUnit($unitId)->count(),
            'done' => SuratSpdExtension::acknowledged()->inUnit($unitId)->count(),
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
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showPerpanjanganSuperadmin(SuratSpdExtension $perpanjangan)
    {
        // Scope unit: detail perpanjangan hanya untuk unitnya (global = semua).
        $unitId = UnitScope::for(Auth::user())->unitId();

        $extension = SuratSpdExtension::inUnit($unitId)->with([
            'originalSurat.user',
            'originalSurat.vehicle',
            'originalSurat.details',
            'extensionSurat',
            'extensionSurat.vehicle',
            'extensionSurat.user',
            'extensionSurat.details',
            'extensionSurat.approver',
            'extensionSurat.rejector',
            'extensionSurat.childExtensions',
            'approver',
            'rejector',
            'acknowledger',
        ])->findOrFail($perpanjangan->id);

        $surat = $extension->extensionSurat;

        return view('superadmin.spd.perpanjangan.detail', compact('extension', 'surat'));
    }

    /**
     * Approve perpanjangan
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approvePerpanjangan(Request $request, SuratSpdExtension $perpanjangan)
    {
        // Scope unit: hanya perpanjangan milik unit user (global = tanpa batas).
        $unitId = UnitScope::for(Auth::user())->unitId();

        $extension = SuratSpdExtension::pending()
            ->inUnit($unitId)
            ->findOrFail($perpanjangan->id);

        // Dana perpanjangan OPSIONAL saat approve. Admin GA boleh mencatat
        // kapan & berapa dana diberikan; bila dikosongkan, dana bisa
        // ditambahkan kemudian dari halaman detail surat perpanjangan.
        $request->validate([
            'catatan' => 'nullable|string|max:255',
            'dana_tanggal' => 'nullable|date',
            'dana_nominal' => 'nullable|numeric|min:0',
            'dana_bentuk' => 'nullable|in:Tunai,Transfer',
            'dana_bank_reference' => 'nullable|string|max:255|required_if:dana_bentuk,Transfer',
            'dana_rek_tujuan' => 'nullable|string|max:255|required_if:dana_bentuk,Transfer',
            'dana_catatan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Approve extension (ini juga akan approve surat extension & set lpj_deadline)
            $extension->approve(Auth::id());

            // Update catatan jika ada
            if ($request->filled('catatan')) {
                $extension->update(['catatan' => $request->catatan]);
            }

            // Catat pemberian dana perpanjangan bila Admin GA mengisinya.
            $nominalDana = (float) $request->input('dana_nominal', 0);
            if ($nominalDana > 0 && $extension->extensionSurat) {
                $bentuk = $request->input('dana_bentuk') === 'Transfer' ? 'Transfer' : 'Tunai';

                $this->simpanPencairans($extension->extensionSurat, [[
                    'tanggal' => $request->input('dana_tanggal') ?: now()->toDateString(),
                    'nominal' => $nominalDana,
                    'bentuk_pencairan' => $bentuk,
                    'bank_reference' => $bentuk === 'Transfer' ? $request->input('dana_bank_reference') : null,
                    'rek_tujuan' => $bentuk === 'Transfer' ? $request->input('dana_rek_tujuan') : null,
                    'catatan' => $request->input('dana_catatan') ?: 'Dana perpanjangan',
                    'sumber' => SuratSpdBudget::SUMBER_PERPANJANGAN,
                    'surat_spd_extension_id' => $extension->id,
                ]]);

                $this->syncExtensionBudgetTotal($extension);
            }

            DB::commit();

            return redirect()->route('perpanjangan.superadmin.index')
                ->with('success', 'Perpanjangan berhasil disetujui! Surat baru: '.
                    $extension->nomor_surat_extension);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal menyetujui perpanjangan SPD', [
                'perpanjangan_id' => $perpanjangan->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Reject perpanjangan
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectPerpanjangan(Request $request, SuratSpdExtension $perpanjangan)
    {
        $request->validate([
            'alasan' => 'required|string|min:10',
        ]);

        $extension = SuratSpdExtension::pending()
            ->inUnit(UnitScope::for(Auth::user())->unitId())
            ->findOrFail($perpanjangan->id);

        $extension->reject(Auth::id(), $request->alasan);

        return redirect()->route('perpanjangan.superadmin.index')
            ->with('success', 'Perpanjangan berhasil ditolak.');
    }

    /**
     * Acknowledge perpanjangan (checklist sudah diproses)
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acknowledgePerpanjangan(Request $request, SuratSpdExtension $perpanjangan)
    {
        $extension = SuratSpdExtension::approved()
            ->where('is_acknowledged', false)
            ->inUnit(UnitScope::for(Auth::user())->unitId())
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
     * @return \Illuminate\View\View
     */
    public function createLaporan(SuratSpd $surat)
    {
        return app(\App\Http\Controllers\SpdDailyReportController::class)->validasi($surat);
    }

    /**
     * Store LPJ dari requester
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Store LPJ dari requester
     * Budget diambil dari surat_spd_budgets, BUKAN dari budget_per_hari
     *
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
            'items.*.struk_kategori' => 'nullable|in:' . \App\Models\StrukItem::validationListBiaya(),
            'items.*.kategori_source' => 'nullable|in:ai,manual',
            'items.*.tipe_penggunaan' => 'nullable|in:BBM,Tol,Parkir,Makan,Hotel,Lain-lain',
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

        // BUG-LPJ-001: plafon harian = total dana / LAMA HARI PERJALANAN,
        // bukan dibagi jumlah baris pencairan (surat_spd_budgets).
        $totalDana = (float) $totalBudgetFromTable > 0
            ? (float) $totalBudgetFromTable
            : (float) ($surat->total_budget ?? 0);
        $budgetPerHari = LpjPenggantian::hitungBudgetPerHari($surat, $totalDana);

        // ✅ Hitung total pengeluaran
        $totalPengeluaran = collect($request->items)->sum('nominal');

        // Budget kini opsional — over budget hanya dihitung bila budget diisi
        $isOverBudget = $totalDana > 0 && $totalPengeluaran > $totalDana;

        // BUG-LPJ-002: bila field dikosongkan/0, isi otomatis dari total
        // pencairan dana SPD yang sudah disetujui. Nilai manual tetap dipakai.
        $jumlahDiberikan = (float) ($request->jumlah_diberikan ?? 0);
        if ($jumlahDiberikan <= 0) {
            $jumlahDiberikan = (float) $surat->pencairans()->sum('nominal');
        }

        DB::beginTransaction();
        try {
            // ✅ Create LPJ
            $lpj = LpjPenggantian::create([
                'surat_spd_id' => $surat->id,
                'user_id' => Auth::id(),
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
                'vehicle_id' => $surat->vehicle_id,
                'nomor_polisi' => $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor ?? '-',
                'odo_before' => $request->odo_before,
                'odo_after' => $request->odo_after,
                'kebutuhan_bbm_liter' => $request->kebutuhan_bbm_liter,
                'uraian' => $request->uraian,
                'uang_dari' => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan' => $jumlahDiberikan,
                'total_pengeluaran' => $totalPengeluaran,
                'budget_per_hari' => $budgetPerHari,
                'catatan' => $request->catatan ?? '',
                'status' => 'pending',
                'is_late' => $isOverdue,
                'is_over_budget' => $isOverBudget,
            ]);

            // Selisih pengembalian/kekurangan dihitung sejak awal (BUG-LPJ-002).
            $lpj->calculateKeuangan();

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
                    'tanggal' => $item['tanggal'],
                    'tipe_penggunaan' => $tipePenggunaan,
                    'nominal' => $item['nominal'],
                    'keterangan' => $item['keterangan'] ?? '',
                    'urutan' => $index + 1,
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
                        'spd_id' => $surat->id,
                        'nomor_surat_spd' => $surat->nomor_surat,
                        'lpj_id' => $lpj->id,
                        'lpj_detail_id' => $detail->id,
                        'item_code' => $itemCode,
                        'path_image' => $path,
                        'struk_kategori' => $kategori ?: ($tipePenggunaan === 'Lain-lain' ? 'Lainnya' : $tipePenggunaan),
                        'kategori_source' => $item['kategori_source'] ?? 'manual',
                        'metadata_json' => ! empty($item['metadata_json']) ? $item['metadata_json'] : null,
                        'nominal' => $item['nominal'],
                        'tanggal' => $item['tanggal'],
                        'keterangan' => $item['keterangan'] ?? '',
                        'urutan' => $index + 1,
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
                $message .= ' ⚠️ Over budget Rp '.
                    number_format($totalPengeluaran - $totalDana, 0, ',', '.').'.';
            }

            return redirect()->route('requester.lpj.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal mengajukan LPJ', [
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.')
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
            'items.*.struk_kategori' => 'nullable|in:' . \App\Models\StrukItem::validationListBiaya(),
            'items.*.kategori_source' => 'nullable|in:ai,manual',
            'items.*.tipe_penggunaan' => 'nullable|in:BBM,Tol,Parkir,Makan,Hotel,Lain-lain',
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
            ->map(fn ($items) => $items->sum('nominal'));

        // BUG-LPJ-001: plafon harian = total dana / lama hari perjalanan.
        $totalDana = (float) $totalBudgetFromTable > 0
            ? (float) $totalBudgetFromTable
            : (float) ($surat->total_budget ?? 0);
        $budgetPerHari = LpjPenggantian::hitungBudgetPerHari($surat, $totalDana);

        $isOverBudget = $totalDana > 0 && $totalPengeluaran > $totalDana;

        // BUG-LPJ-002: jangan menimpa nilai tersimpan saat edit LPJ lama bila
        // field tidak dikirim. Nilai awal form sudah memakai total pencairan.
        $jumlahDiberikan = $request->filled('jumlah_diberikan')
            ? (float) $request->jumlah_diberikan
            : (float) $laporan->jumlah_diberikan;

        DB::beginTransaction();
        try {
            $laporan->update([
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
                'uraian' => $request->uraian,
                'uang_dari' => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan' => $jumlahDiberikan,
                'odo_before' => $request->odo_before,
                'odo_after' => $request->odo_after,
                'kebutuhan_bbm_liter' => $request->kebutuhan_bbm_liter,
                'total_pengeluaran' => $totalPengeluaran,
                'budget_per_hari' => $budgetPerHari,
                'catatan' => $request->catatan ?? '',
                'status' => 'pending',
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'is_over_budget' => $isOverBudget,
            ]);

            // Hitung ulang selisih dengan Jumlah Diberikan yang baru.
            $laporan->calculateKeuangan();

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
                        'spd_id' => $surat->id,
                        'nomor_surat_spd' => $surat->nomor_surat,
                        'lpj_id' => $laporan->id,
                        'lpj_detail_id' => $detail->id,
                        'item_code' => $itemCode,
                        'path_image' => $path,
                        'struk_kategori' => $kategori ?: ($tipePenggunaan === 'Lain-lain' ? 'Lainnya' : $tipePenggunaan),
                        'kategori_source' => $item['kategori_source'] ?? 'manual',
                        'metadata_json' => ! empty($item['metadata_json']) ? $item['metadata_json'] : null,
                        'nominal' => $item['nominal'],
                        'tanggal' => $item['tanggal'],
                        'keterangan' => $item['keterangan'] ?? '',
                        'urutan' => $index + 1,
                    ]);

                    $replacedIndices[] = $index;
                }
            }

            // Re-link struk_items lama ke detail baru (posisi sama), hapus yang tidak dipakai
            foreach ($oldStrukItems as $index => $old) {
                if (isset($createdDetailIds[$index]) && ! in_array($index, $replacedIndices, true)) {
                    $old->update([
                        'lpj_detail_id' => $createdDetailIds[$index],
                        'urutan' => $index + 1,
                        'nominal' => $request->items[$index]['nominal'] ?? $old->nominal,
                        'tanggal' => $request->items[$index]['tanggal'] ?? $old->tanggal,
                        'keterangan' => $request->items[$index]['keterangan'] ?? $old->keterangan,
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

            \Log::error('Gagal memperbarui LPJ', [
                'lpj_id' => $laporan->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.')
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
     * @param  int  $id
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
        $unitId = UnitScope::for(Auth::user())->unitId();

        $pending_list = LpjPenggantian::pending()
            ->inUnit($unitId)
            ->with(['user', 'suratSpd.vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approved_list = LpjPenggantian::approved()
            ->inUnit($unitId)
            ->with(['user', 'suratSpd.vehicle', 'approverPengguna'])
            ->orderBy('approved_pengguna_at', 'desc')
            ->limit(50)
            ->get();

        $rejected_list = LpjPenggantian::rejected()
            ->inUnit($unitId)
            ->with(['user', 'suratSpd.vehicle', 'rejector'])
            ->orderBy('rejected_at', 'desc')
            ->limit(50)
            ->get();

        // Stats
        $stats = [
            'pending' => $pending_list->count(),
            'approved' => LpjPenggantian::approved()->inUnit($unitId)->count(),
            'rejected' => LpjPenggantian::rejected()->inUnit($unitId)->count(),
            'late' => LpjPenggantian::late()->inUnit($unitId)->count(),
            'over_budget' => LpjPenggantian::overBudget()->inUnit($unitId)->count(),
        ];

        // Total amount bulan ini
        $total_approved_amount = LpjPenggantian::approved()
            ->inUnit($unitId)
            ->whereMonth('approved_pengguna_at', Carbon::now()->month)
            ->whereYear('approved_pengguna_at', Carbon::now()->year)
            ->sum('total_pengeluaran');

        // Notice GA: surat yang LPJ-nya belum disubmit & sudah lewat batas 3 hari.
        $suratMenungguOverdue = $this->suratMenungguLpjOverdue($unitId);

        return view('superadmin.spd.lpj.index', compact(
            'pending_list',
            'approved_list',
            'rejected_list',
            'stats',
            'total_approved_amount',
            'suratMenungguOverdue'
        ));
    }

    /**
     * Surat SPD yang sudah disetujui, belum dibuatkan LPJ, dan sudah lewat
     * batas 3 hari — dipakai sebagai "notice" untuk GA & User yang berangkat.
     *
     * Overdue hanya PENANDA: form LPJ driver tetap terbuka.
     *
     * @param  int|null  $userId  Batasi ke surat yang melibatkan user ini
     *                            (sebagai pemohon ATAU salah satu pemakai).
     *                            Null = tanpa batasan (sisi GA).
     * @return \Illuminate\Support\Collection<int, \App\Models\SuratSpd>
     */
    private function suratMenungguLpjOverdue(?int $unitId, ?int $userId = null)
    {
        $query = SuratSpd::approved()
            ->inUnit($unitId)
            ->whereDoesntHave('lpjPenggantian')
            ->with(['vehicle', 'user', 'details'])
            ->orderBy('tanggal_kembali');

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('details', fn ($d) => $d->where('user_id', $userId));
            });
        }

        return $query->get()
            ->filter(fn ($surat) => $surat->isLpjDeadlineOverdue())
            ->values();
    }

    /**
     * List LPJ untuk validasi kebenaran perjalanan oleh User (penumpang).
     */
    public function indexLaporanPengguna()
    {
        $unitId = UnitScope::for(Auth::user())->unitId();

        $pending_list = LpjPenggantian::pending()
            ->inUnit($unitId)
            ->whereNull('approved_by_pengguna')
            ->with(['user', 'suratSpd.vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approved_list = collect();
        $rejected_list = collect();

        $stats = [
            'pending' => $pending_list->count(),
            'approved' => 0,
            'rejected' => 0,
            'late' => 0,
            'over_budget' => 0,
        ];

        $total_approved_amount = 0;

        // Notice User yang berangkat: surat yang ia ikuti (sebagai pemohon atau
        // pemakai) dan LPJ-nya belum disubmit driver padahal sudah lewat batas.
        $suratMenungguOverdue = $this->suratMenungguLpjOverdue($unitId, Auth::id());

        return view('superadmin.spd.lpj.index', compact(
            'pending_list',
            'approved_list',
            'rejected_list',
            'stats',
            'total_approved_amount',
            'suratMenungguOverdue'
        ));
    }

    /**
     * List LPJ untuk validasi final oleh Management.
     */
    public function indexLaporanManagement()
    {
        $unitId = UnitScope::for(Auth::user())->unitId();

        $pending_list = LpjPenggantian::pending()
            ->inUnit($unitId)
            ->whereNotNull('approved_by_pengguna')
            ->whereNotNull('approved_by_divisi_ga')
            ->whereNull('approved_by_management')
            ->with(['user', 'suratSpd.vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approved_list = LpjPenggantian::approved()
            ->inUnit($unitId)
            ->with(['user', 'suratSpd.vehicle'])
            ->orderBy('approved_management_at', 'desc')
            ->limit(50)
            ->get();

        $rejected_list = collect();

        $stats = [
            'pending' => $pending_list->count(),
            'approved' => $approved_list->count(),
            'rejected' => 0,
            'late' => 0,
            'over_budget' => 0,
        ];

        $total_approved_amount = 0;

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
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showLaporanSuperadmin(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'details.strukItem',
            'suratSpd.parentSurat',
            'suratSpd.vehicle',
            'suratSpd.details',
            'suratSpd.fundAdditions',
            'vehicle',
            'user',
            'approverPengguna',
            'approverDivisiGa',
            'approverManagement',
            'rejector',
        ])->findOrFail($laporan->id);

        // F-01 / BUG-SEC-001: detail LPJ hanya untuk pemilik/penumpang,
        // pengelola unit yang sama, atau user global. Record ada tetapi tidak
        // boleh dilihat -> 404 (bukan 403) agar keberadaan dokumen tidak bocor.
        if (! $laporan->isVisibleToUser(Auth::user())) {
            abort(404);
        }

        return view('superadmin.spd.lpj.detail', compact('laporan'));
    }

    /**
     * R2 / BUG-SEC-001: aksi tulis LPJ hanya untuk pengelola unitnya.
     * User global tetap boleh; unit lain -> 403 (aksi tulis, bukan baca).
     */
    private function ensureLaporanUnit(LpjPenggantian $laporan): void
    {
        UnitScope::for(Auth::user())->ensureUnit($laporan->suratSpd?->unit_id);
    }

    /**
     * R2 / BUG-SEC-001: validasi pengguna hanya oleh pihak yang berhak atas
     * dokumen (pemilik/penumpang) atau user global. Unit lain -> 403.
     */
    private function ensureLaporanValidator(LpjPenggantian $laporan): void
    {
        if (! $laporan->isVisibleToUser(Auth::user())) {
            abort(403, 'LPJ ini berada di luar kewenangan Anda.');
        }
    }

    /**
     * Approve LPJ
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveLaporan(LpjPenggantian $laporan)
    {
        // BUG-LPJ-003: "setujui semua sekaligus" adalah jalur final. Hanya
        // Management/superadmin (lpj.validate_management) yang boleh memakainya
        // agar validasi Admin GA tidak melompati antrean Manager.
        if (! Auth::user()->hasPermission('lpj.validate_management')) {
            return redirect()->back()
                ->with('error', 'Persetujuan final hanya dapat dilakukan Management. Validasi Divisi GA tidak boleh melompati tahap Manager.');
        }

        // R2 / BUG-SEC-001: Manager unit lain tidak boleh approve-final.
        $this->ensureLaporanUnit($laporan);

        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);

        DB::beginTransaction();
        try {
            // Simple approve (superadmin = approve all)
            $laporan->approve(Auth::id());

            DB::commit();

            return redirect()->route('lpj.superadmin.index')
                ->with('success', 'Laporan Pertanggungjawaban berhasil disetujui!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Gagal menyetujui LPJ', [
                'lpj_id' => $laporan->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->with('error', 'Pengajuan gagal diproses, silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Reject LPJ
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectLaporan(Request $request, LpjPenggantian $laporan)
    {
        // R2 / BUG-SEC-001: unit lain -> 403 sebelum validasi/penolakan.
        $this->ensureLaporanUnit($laporan);

        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);

        $laporan->reject(Auth::id(), $request->rejection_reason);

        return redirect()->route('lpj.superadmin.index')
            ->with('success', 'Laporan Pertanggungjawaban berhasil ditolak.');
    }

    /**
     * Validasi kebenaran perjalanan oleh User (penumpang) — tahap 1 dari 3.
     */
    public function approveLaporanPengguna(Request $request, LpjPenggantian $laporan)
    {
        // R2 / BUG-SEC-001: hanya pemilik/penumpang dokumen (boleh lintas
        // unit), pengelola unit yang sama, atau user global.
        $this->ensureLaporanValidator($laporan);

        $request->validate([
            'pernyataan' => 'accepted',
        ], [
            'pernyataan.accepted' => 'Centang pernyataan bahwa perjalanan dinas ini benar adanya.',
        ]);

        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);
        $laporan->approveByPengguna(Auth::id());

        return redirect()->route('lpj.pengguna.show', $laporan)
            ->with('success', 'Validasi kebenaran perjalanan berhasil. Menunggu validasi Divisi GA.');
    }

    /**
     * Halaman validasi perjalanan oleh User (penumpang) + QR tanda tangan.
     */
    public function showValidasiPengguna(LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with([
            'details.strukItem',
            'suratSpd.vehicle',
            'suratSpd.details',
            'suratSpd.parentSurat',
            'vehicle',
            'user',
            'approverPengguna',
            'approverDivisiGa',
            'approverManagement',
            'rejector',
        ])->findOrFail($laporan->id);

        // F-01 / BUG-SEC-001: halaman validasi hanya untuk pemilik/penumpang,
        // pengelola unit yang sama, atau user global. Lintas unit -> 404.
        if (! $laporan->isVisibleToUser(Auth::user())) {
            abort(404);
        }

        // QR tanda tangan hanya dibuat setelah user (penumpang) memvalidasi.
        $qrSvg = $laporan->approved_by_pengguna ? $this->generateValidationQr($laporan) : null;

        return view('requester.spd.validasi-pengguna.show', compact('laporan', 'qrSvg'));
    }

    /**
     * QR tanda tangan validasi user — berisi pernyataan + rincian perjalanan.
     */
    private function generateValidationQr(LpjPenggantian $laporan): ?string
    {
        try {
            $qrData = json_encode([
                'pernyataan'       => 'Saya mengetahui bahwa perjalanan dinas ini benar adanya dengan rincian sebagaimana terlampir.',
                'lpj_number'       => $laporan->lpj_number,
                'nomor_surat'      => $laporan->suratSpd?->nomor_surat,
                'user'             => $laporan->approverPengguna?->nama_lengkap ?? $laporan->nama,
                'tujuan'           => $laporan->suratSpd?->tempat_tujuan,
                'tanggal_validasi' => optional($laporan->approved_pengguna_at)->format('Y-m-d H:i'),
            ]);

            return $this->generateQrSvgWithLogo($qrData);
        } catch (\Throwable $e) {
            \Log::warning('QR validasi pengguna gagal: '.$e->getMessage(), [
                'lpj_id' => $laporan->id,
            ]);

            return null;
        }
    }

    /**
     * Validasi oleh Divisi GA — tahap 2 dari 3.
     */
    public function approveLaporanDivisiGa(LpjPenggantian $laporan)
    {
        // R2 / BUG-SEC-001: GA unit lain -> 403.
        $this->ensureLaporanUnit($laporan);

        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);

        // Tidak strict: Divisi GA boleh memvalidasi tanpa menunggu tahap User.
        // Kedua tanda tangan tetap dicatat terpisah untuk akuntabilitas.
        $laporan->approveByDivisiGa(Auth::id());

        return redirect()->back()
            ->with('success', 'Validasi Divisi GA berhasil.');
    }

    /**
     * Validasi final oleh Management (supervisor tertinggi) — tahap 3 dari 3.
     */
    public function approveLaporanManagement(LpjPenggantian $laporan)
    {
        // R2 / BUG-SEC-001: Manager unit lain -> 403.
        $this->ensureLaporanUnit($laporan);

        $laporan = LpjPenggantian::pending()->findOrFail($laporan->id);

        if (! $laporan->approved_by_pengguna || ! $laporan->approved_by_divisi_ga) {
            return redirect()->back()
                ->with('error', 'Validasi User & Divisi GA harus selesai terlebih dahulu.');
        }

        $laporan->approveByManagement(Auth::id());

        return redirect()->back()
            ->with('success', 'LPJ disetujui penuh. Dokumen siap dicetak.');
    }

    /**
     * Tambah dana pada surat SPD yang sudah disetujui (penambahan uang).
     */
    public function storeFundAddition(Request $request, SuratSpd $surat)
    {
        // F-03 / BUG-SPD-002: hanya pengelola unit SPD ini (global = semua).
        UnitScope::for(Auth::user())->ensureUnit($surat->unit_id);

        $request->validate([
            'nominal_tambahan' => 'required|numeric|min:0',
            'alasan' => 'required|string|min:5',
            'bank_reference' => 'nullable|string|max:255',
            'bukti_transfer' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if (! in_array($surat->status, ['approved', 'completed'])) {
            return redirect()->back()
                ->with('error', 'Hanya surat yang sudah disetujui yang bisa menerima penambahan dana.');
        }

        $path = null;
        if ($request->hasFile('bukti_transfer')) {
            $path = $request->file('bukti_transfer')->store('spd/fund_additions', 'public');
        }

        SuratSpdFundAddition::create([
            'surat_spd_id' => $surat->id,
            'nominal_tambahan' => $request->nominal_tambahan,
            'alasan' => $request->alasan,
            'bukti_transfer_path' => $path,
            'bank_reference' => $request->bank_reference,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Penambahan dana berhasil dicatat.');
    }

    /**
     * Catat Pemberian Dana (pencairan) untuk surat yang sudah disetujui.
     *
     * Dipakai Admin GA untuk menambahkan dana perpanjangan (ETD) setelah
     * perpanjangan disetujui. Baris diberi sumber `perpanjangan` + relasi ke
     * record perpanjangan, sehingga pada tabel/lembar Pemberian Dana terlihat
     * KAPAN dana diberikan dan bahwa dana itu terkait perpanjangan.
     */
    public function storeSuratPencairan(Request $request, SuratSpd $surat)
    {
        UnitScope::for(Auth::user())->ensureUnit($surat->unit_id);

        if (! in_array($surat->status, ['approved', 'completed'], true)) {
            return redirect()->back()
                ->with('error', 'Pemberian dana hanya dapat dicatat pada surat yang sudah disetujui.');
        }

        // Form baru memakai komponen Pemberian Dana (array `pencairans`);
        // field tunggal tetap diterima untuk kompatibilitas.
        $rows = $this->extractPencairans($request);

        if ($rows === []) {
            $data = $request->validate([
                'tanggal' => 'required|date',
                'nominal' => 'required|numeric|min:1',
                'bentuk_pencairan' => 'required|in:Tunai,Transfer',
                'bank_reference' => 'nullable|string|max:255|required_if:bentuk_pencairan,Transfer',
                'rek_tujuan' => 'nullable|string|max:255|required_if:bentuk_pencairan,Transfer',
                'catatan' => 'nullable|string|max:255',
            ], [
                'tanggal.required' => 'Tanggal pemberian dana wajib diisi.',
                'nominal.required' => 'Nominal pemberian dana wajib diisi.',
                'nominal.min' => 'Nominal pemberian dana harus lebih dari 0.',
                'bentuk_pencairan.required' => 'Bentuk pemberian dana wajib dipilih.',
                'bank_reference.required_if' => 'Nomor referensi bank wajib diisi untuk transfer.',
                'rek_tujuan.required_if' => 'Rekening tujuan wajib diisi untuk transfer.',
            ]);

            $rows = [[
                'tanggal' => $data['tanggal'],
                'nominal' => $data['nominal'],
                'bentuk_pencairan' => $data['bentuk_pencairan'],
                'bank_reference' => $data['bentuk_pencairan'] === 'Transfer' ? ($data['bank_reference'] ?? null) : null,
                'rek_tujuan' => $data['bentuk_pencairan'] === 'Transfer' ? ($data['rek_tujuan'] ?? null) : null,
                'catatan' => $data['catatan'] ?? null,
            ]];
        } else {
            $request->validate($this->pencairanRules());
        }

        // Surat perpanjangan selalu bertanda sumber perpanjangan.
        $extension = $surat->is_extension ? $surat->extensionRecord : null;
        $sumber = $surat->is_extension
            ? SuratSpdBudget::SUMBER_PERPANJANGAN
            : SuratSpdBudget::SUMBER_TAMBAHAN;

        foreach ($rows as &$row) {
            $row['sumber'] = $sumber;
            $row['surat_spd_extension_id'] = $extension?->id;
        }
        unset($row);

        $this->simpanPencairans($surat, $rows);

        if ($extension) {
            $this->syncExtensionBudgetTotal($extension);
        }

        return redirect()->back()->with('success', 'Pemberian dana berhasil dicatat'
            .($extension ? ' sebagai dana perpanjangan.' : '.'));
    }

    /**
     * Samakan `budget_tambahan` record perpanjangan dengan total dana yang
     * benar-benar diberikan untuk perpanjangan tersebut.
     */
    private function syncExtensionBudgetTotal(SuratSpdExtension $extension): void
    {
        $total = (float) SuratSpdBudget::where('surat_spd_extension_id', $extension->id)->sum('nominal');

        $extension->update(['budget_tambahan' => $total]);
    }

    /**
     * Update data keuangan LPJ (diisi admin GA / orang keuangan).
     * Driver tidak mengisi data ini saat membuat laporan harian.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateKeuanganLaporan(Request $request, LpjPenggantian $laporan)
    {
        // R2 / BUG-SEC-001: data keuangan LPJ hanya untuk unit yang benar.
        $this->ensureLaporanUnit($laporan);

        $request->validate([
            'uang_dari' => 'nullable|string|max:255',
            'jumlah_diberikan' => 'nullable|numeric|min:0',
        ]);

        // BUG-LPJ-002: bila field dikosongkan, pakai nilai efektif (nilai
        // tersimpan atau total pencairan dana SPD).
        $jumlahDiberikan = $request->filled('jumlah_diberikan')
            ? (float) $request->jumlah_diberikan
            : (float) $laporan->jumlah_diberikan_efektif;

        DB::transaction(function () use ($laporan, $request, $jumlahDiberikan) {
            $laporan->update([
                'uang_dari' => $request->uang_dari ?? 'KAS',
                'jumlah_diberikan' => $jumlahDiberikan,
            ]);
            $laporan->calculateKeuangan();
        });

        return redirect()->back()
            ->with('success', 'Data keuangan LPJ berhasil diperbarui.');
    }

    /**
     * Print LPJ.
     *
     * Sejak dokumen lampiran LPJ SPD disusun ulang (Lembar Ringkasan Penggunaan,
     * Lembar Persetujuan Penggantian Pertanggungjawaban, Lampiran Penggunaan
     * Dana), route print ini memakai renderer dokumen gabungan yang sama dengan
     * `spd.laporan.export` supaya tidak lagi mengeluarkan template lama
     * (`template-lpj`) yang isinya baris kosong.
     *
     * @return \Illuminate\View\View
     */
    public function printLaporan(Request $request, LpjPenggantian $laporan)
    {
        $laporan = LpjPenggantian::with('suratSpd.details')
            ->where('status', 'approved')
            ->findOrFail($laporan->id);

        // BUG-SEC-001: aturan visibilitas sama dengan cetak SPD — pemilik/
        // penumpang, unit yang sama, atau user global. Record ada tetapi tidak
        // terlihat -> 404.
        $surat = $laporan->suratSpd;
        $user = $request->user();

        $bolehLihat = ($surat !== null && $surat->isVisibleToUser($user))
            || ($user !== null && (int) $laporan->user_id === (int) $user->id);

        if (! $bolehLihat) {
            abort(404);
        }

        return app(SpdPrintController::class)->exportLpj($request, $laporan);
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                                                                           ║
    // ║                       SECTION 7: HELPER / AJAX                            ║
    // ║                                                                           ║
    // ╚═══════════════════════════════════════════════════════════════════════════╝

    /**
     * Aturan validasi kolom pemakai/penumpang (nama + user_id opsional).
     * user_id wajib user yang valid bila diisi — ID dari autocomplete tetap
     * diverifikasi di server agar tidak bisa dipalsukan.
     *
     * @return array<string, string>
     */
    private function passengerValidationRules(): array
    {
        $rules = [];
        for ($i = 1; $i <= 7; $i++) {
            $rules["nama_lengkap_{$i}"] = 'nullable|string|max:255';
            $rules["user_id_{$i}"] = 'nullable|integer|exists:users,id';
        }

        return $rules;
    }

    /**
     * Autocomplete pemakai/penumpang SPD (dari Master Pekerja).
     * Mengembalikan nama + user_id (akun) bila pekerja terhubung ke user SIAGA.
     * user_id boleh null — penumpang tanpa akun tetap bisa dipilih.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function passengerLookup(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $employees = EmployeeDefault::query()
            ->active()
            ->forUnit(UnitScope::for($request->user())->unitId())
            ->where('employee_name', 'like', '%'.$q.'%')
            ->orderBy('employee_name')
            ->limit(10)
            ->get(['id', 'employee_name', 'position', 'division', 'user_id']);

        return response()->json([
            'results' => $employees->map(fn ($e) => [
                'id'       => $e->id,
                'name'     => $e->employee_name,
                'position' => $e->position,
                'division' => $e->division,
                'user_id'  => $e->user_id,
            ])->values(),
        ]);
    }

    /**
     * Get detail surat untuk AJAX (digunakan di form LPJ)
     *
     * @param  int  $id
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
                    ? '└── '.$surat->nomor_surat.' (Perpanjangan)'
                    : $surat->nomor_surat,
                'tujuan' => $surat->tempat_tujuan,
                'tanggal' => $surat->tanggal_berangkat->translatedFormat('d M').' - '.$surat->tanggal_kembali->translatedFormat('d M Y'),
                'is_extension' => $surat->is_extension,
                'deadline' => $surat->lpj_deadline ? $surat->lpj_deadline->translatedFormat('d M Y') : '-',
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
            // Stats untuk superadmin/admin — dibatasi unit user.
            $unitId = UnitScope::for(Auth::user())->unitId();

            $stats = [
                'surat_pending' => SuratSpd::pending()->inUnit($unitId)->count(),
                'perpanjangan_pending' => SuratSpdExtension::pending()->inUnit($unitId)->count(),
                'lpj_pending' => LpjPenggantian::pending()->inUnit($unitId)->count(),
                'lpj_late' => LpjPenggantian::late()->inUnit($unitId)->count(),
                'lpj_over_budget' => LpjPenggantian::overBudget()->inUnit($unitId)->count(),
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
            $filename = 'QR_'.preg_replace('/[^A-Za-z0-9\-]/', '_', $surat->nomor_surat).'.svg';
            $relativePath = 'qr-surat/'.$filename;

            // Generate QR SVG
            $qrData = json_encode([
                'id' => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
                'authorizer' => $surat->pemberi_tugas_nama,
                'tanggal_terbit' => $surat->created_at->format('Y-m-d'),
                'tanggal_disetujui' => $surat->approved_at?->format('Y-m-d'),
            ]);

            $svgContent = $this->generateQrSvgWithLogo($qrData);
            MediaStorage::put($relativePath, $svgContent);

            // Simpan ke database (update or create)
            SpdBarcodeSignature::updateOrCreate(
                ['spd_id' => $surat->id],
                ['signature_path' => $relativePath]
            );

            return $svgContent;

        } catch (\Throwable $e) {
            \Log::warning('QR signature gagal: '.$e->getMessage(), [
                'surat_id' => $surat->id,
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
            $filename = 'QR_'.preg_replace('/[^A-Za-z0-9\-]/', '_', $surat->nomor_surat).'.svg';
            $relativePath = 'qr-surat/'.$filename;

            // Cache — tidak generate ulang jika sudah ada
            if (! MediaStorage::exists($relativePath)) {
                $qrData = json_encode([
                    'id' => $surat->id,
                    'nomor_surat' => $surat->nomor_surat,
                    'authorizer' => $surat->pemberi_tugas_nama,
                    'tanggal_terbit' => $surat->created_at->format('Y-m-d'),
                    'tanggal_disetujui' => $surat->approved_at?->format('Y-m-d'),
                ]);

                $svgContent = $this->generateQrSvgWithLogo($qrData);
                MediaStorage::put($relativePath, $svgContent);
            }

            // Return SVG sebagai base64 data URI
            $svg = MediaStorage::get($relativePath);

            return $svg !== null
                ? 'data:image/svg+xml;base64,'.base64_encode($svg)
                : null;

        } catch (\Throwable $e) {
            \Log::warning('QR signature gagal: '.$e->getMessage(), [
                'surat_id' => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
            ]);

            return null;
        }
    }

    private function generateQrSvgWithLogo(string $data): string
    {
        $qrSize = 300;
        $logoSize = 54;
        $padding = 4;

        $renderer = new ImageRenderer(
            new RendererStyle($qrSize, 1),
            new SvgImageBackEnd
        );
        $svg = (new Writer($renderer))->writeString($data);

        // Ambil dimensi viewBox
        preg_match('/viewBox="0 0 (\d+(?:\.\d+)?) (\d+(?:\.\d+)?)"/', $svg, $m);
        $svgW = isset($m[1]) ? (float) $m[1] : $qrSize;
        $svgH = isset($m[2]) ? (float) $m[2] : $qrSize;

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

        return str_replace('</svg>', $logoElements."\n</svg>", $svg);
    }

    /**
     * Simpan gambar struk ke disk privat dengan kompresi 50% (tanpa resize).
     * Hanya path yang disimpan ke DB — tidak ada base64.
     */
    protected function storeStrukImage($file, string $context): string
    {
        $tmp = $file->getRealPath();
        $jpeg = ImageCompressor::compressJpeg($tmp, 50);

        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = 'S.'.preg_replace('/[^A-Za-z0-9\-]/', '_', $context)
            .'-'.now()->format('dmY-His').'-'.strtoupper(substr(uniqid(), -6)).'.'.$ext;

        $path = 'transaction_proofs/struk/'.$filename;

        if ($jpeg !== null) {
            MediaStorage::put($path, $jpeg);
        } else {
            MediaStorage::storeUploaded($file, 'transaction_proofs/struk', $filename);
        }

        return $path;
    }
}
