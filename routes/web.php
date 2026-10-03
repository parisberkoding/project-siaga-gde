<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AuthentiactionController;
use App\Http\Controllers\BodyCheckingController;
use App\Http\Controllers\CateringGuestController;
use App\Http\Controllers\DailyCheckingController;
use App\Http\Controllers\DispatcherAdministrationController;
use App\Http\Controllers\Lunch\EmployeeDefaultController;
use App\Http\Controllers\Lunch\LunchAnalyticsController;
use App\Http\Controllers\Lunch\LunchDashboardController;
use App\Http\Controllers\Lunch\LunchDistributionController;
use App\Http\Controllers\Lunch\LunchForecastController;
use App\Http\Controllers\Lunch\LunchTransactionController;
use App\Http\Controllers\Lunch\PublicAttendanceController;
use App\Http\Controllers\Lunch\SelfBookingController;
use App\Http\Controllers\Lunch\VendorLunchController;
use App\Http\Controllers\MasterGasolineController;
use App\Http\Controllers\MonthlyCheckingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\RequestManagementController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SpdController;
use App\Http\Controllers\SpdPencairanController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SpdDailyReportController;
use App\Http\Controllers\SpdPrintController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\SsoRoleMappingController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\VehicleCheckingSuperadminController;
use App\Http\Controllers\VehicleHealthHistoryController;
use App\Http\Controllers\VehicleRepairmentDriverController;
use App\Http\Controllers\VehicleSparepartRequestRepairController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WeeklyCheckingController;
use Illuminate\Support\Facades\Route;

// File barcode/QR disajikan untuk sesi terautentikasi (admin/dispatcher).
// Auth wajib: file ini bisa berisi data sensitif (struk, QR surat, barcode).
Route::middleware(['auth'])->group(function () {
    Route::get('/files/storage/{path}', [StorageFileController::class, 'serve'])
        ->where('path', '.*')
        ->name('storage.serve');
});

Route::get('/tentang', function () {
    return view('pages.about');
});

Route::get('/panduan', function () {
    return view('pages.panduan');
});

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', [AuthentiactionController::class, 'index'])->name('login');
Route::post('/login-process', [AuthentiactionController::class, 'authenticate'])->name('login-process');

// ── SSO Geodipa (provider-agnostic) ──
// Publik seperti /login: memang harus dapat diakses SEBELUM login.
// Selama SSO_ENABLED=false, kedua route menolak dengan pesan yang jelas dan
// tidak mengubah perilaku login lokal yang sudah berjalan sama sekali.
Route::prefix('auth/sso')->name('sso.')->group(function () {
    Route::get('/redirect', [SsoController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [SsoController::class, 'callback'])->name('callback');
});

// Logout: dukung GET & POST, tanpa batasan role (semua user authenticated boleh logout)
Route::middleware(['auth'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthentiactionController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'permission:spd.view,spd.manage'])->prefix('spd')->name('spd.')->group(function () {
    Route::get('/surat/{surat}/print', [SpdController::class, 'printSurat'])->name('surat.print');
    Route::get('/laporan/{laporan}/print', [SpdController::class, 'printLaporan'])->name('laporan.print');

    // Cetak dokumen gabungan Surat SPD + LPJ (tersedia untuk semua role SPD)
    Route::get('/laporan/{laporan}/export', [SpdPrintController::class, 'exportLpj'])->name('laporan.export');

    // Unduh terpisah: Lembar Lampiran Pemberian Dana Perjalanan Dinas.
    // HANYA sisi General Affairs (permission lpj.pencairan) — tidak ikut bundle
    // dokumen gabungan milik role lain.
    Route::get('/laporan/{laporan}/pemberian-dana', [SpdPrintController::class, 'exportPemberianDana'])
        ->middleware('permission:lpj.pencairan')
        ->name('laporan.pemberian-dana');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
});
// ────────────────────────────────────────────────────────────────────────
// SINGLE PAGE PELAPORAN BBM OPERASIONAL & SPD (ReportingController)
// Di bawah permission pelaporan baru. Menangani upload struk + AI/OCR,
// mode SPD & mode operasional dalam satu halaman.
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:pelaporan.view,pelaporan.create'])->prefix('pelaporan')->name('pelaporan.')->group(function () {
    Route::get('/', [ReportingController::class, 'index'])->name('index');

    // Semua aksi tulis / halaman input butuh pelaporan.create (view-only tidak bisa).
    Route::middleware('permission:pelaporan.create')->group(function () {
    Route::post('/analyze-struk', [ReportingController::class, 'analyzeStruk'])
        ->middleware(['throttle:10,1', 'permission:pelaporan.analyze'])->name('analyze-struk');
    Route::post('/vehicle-budget', [ReportingController::class, 'vehicleBudget'])->name('vehicle-budget');
    Route::post('/spd', [ReportingController::class, 'storeSpd'])->name('spd.store');
    Route::post('/operational', [ReportingController::class, 'storeOperational'])->name('operational.store');

    // ═════════════════════════════════════════════════════════════════════════
    //  PELAPORAN SPD — FLOW BARU: SATU HALAMAN VALIDASI (/validasi) + SEGMENTASI.
    // ═════════════════════════════════════════════════════════════════════════
    //
    //  FLOW LAMA (dihide): driver wajib input struk PER HARI di halaman
    //  /spd/{surat}/daily (+ /create /{daily}/edit), lalu submit per hari
    //  (status submitted) dan baru bisa divalidasi menjadi LPJ di akhir periode.
    //
    //  FLOW BARU: laporan dipecah menjadi 2 SEGMENTASI —
    //    ▶ DRAFT   : input struk selama periode SPD berjalan, ATAU bulk input
    //                setelah SPD selesai tapi driver belum mau finalisasi.
    //                Semua item tersimpan otomatis server-side dalam status draft.
    //    ▶ FINAL   : setelah tanggal_kembali lewat, driver buka SATU halaman
    //                validasi, bulk-upload (maks 5 gambar per kirim, bisa 5 lagi
    //                dst), lalu langsung "Submit sebagai LPJ".
    //
    //  VIEW LAMA (/daily*) DIHIDE: controller pada index/create/edit kini
    //  redirect ke pelaporan.spd.validasi. Route & logic lama TIDAK dihapus
    //  (tetap fungsional via store/update/destroy) agar tidak ada yang rusak.
    //
    // ── (HIDE) Laporan harian SPD — digantikan SATU view validasi ──
    // Route GET di bawah dialihkan (redirect) ke spd.validasi — lihat controller.
    // Route POST/PUT/DELETE tetap dipakai sebagai cadangan/legacy.
    // Semua rute memakai route model binding (slug/kode surat) — tanpa ID.
    Route::get('/spd/{surat}/daily', [SpdDailyReportController::class, 'index'])->name('spd.daily.index');
    Route::get('/spd/{surat}/daily/create', [SpdDailyReportController::class, 'create'])->name('spd.daily.create');
    Route::get('/spd/{surat}/daily/{daily}/edit', [SpdDailyReportController::class, 'edit'])->name('spd.daily.edit');
    Route::post('/spd/{surat}/daily', [SpdDailyReportController::class, 'store'])->name('spd.daily.store');
    Route::put('/spd/{surat}/daily/{daily}', [SpdDailyReportController::class, 'update'])->name('spd.daily.update');
    Route::delete('/spd/{surat}/daily/{daily}', [SpdDailyReportController::class, 'destroy'])->name('spd.daily.destroy');

    // AJAX item laporan harian (draft server-side, tahan refresh)
    // upload-item = legacy 1-file; upload-items = FLOW BARU bulk (maks 5/kirim).
    Route::post('/spd/{surat}/daily/item', [SpdDailyReportController::class, 'uploadItem'])->name('spd.daily.upload-item');
    Route::post('/spd/{surat}/daily/items', [SpdDailyReportController::class, 'uploadItems'])->name('spd.daily.upload-items');
    Route::patch('/spd/{surat}/daily/item/{item}', [SpdDailyReportController::class, 'updateItem'])->name('spd.daily.update-item');
    Route::delete('/spd/{surat}/daily/item/{item}', [SpdDailyReportController::class, 'deleteItem'])->name('spd.daily.delete-item');

    // SATU halaman validasi (FLOW BARU) → input struk + segmentasi draft/final → LPJ
    Route::get('/spd/{surat}/validasi', [SpdDailyReportController::class, 'validasi'])->name('spd.validasi');
    Route::post('/spd/{surat}/finalize', [SpdDailyReportController::class, 'finalize'])->name('spd.finalize');
    });
});

// Requester Routes
Route::middleware(['auth'])->group(function () {
    // ── Permohonan BBM (requester) ──
    Route::middleware('permission:bbm.request,bbm.view,bbm.report')->group(function () {
        // Halaman form permohonan baru → butuh izin membuat permohonan
        Route::get('/requester/view-request-bbm', [RequestManagementController::class, 'requester_index'])
            ->middleware('permission:bbm.request')->name('requester-index');
        Route::post('/requester/create-request-bbm', [RequestManagementController::class, 'requester_create'])
            ->middleware('permission:bbm.request')->name('requester-create');
        Route::put('/requester/cancel/{id}', [RequestManagementController::class, 'requester_cancel_request'])
            ->middleware('permission:bbm.request')->name('requester-cancel');

        // Riwayat & detail (read-only)
        Route::get('/requester/bbm-history', [RequestManagementController::class, 'requester_history'])->name('requester-history');
        Route::get('/requester/request/{id}', [RequestManagementController::class, 'requester_show'])->name('requester-show');

        // Pelaporan bukti pembelian → butuh izin report
        Route::post('/requester/request/{id}/upload-proof', [RequestManagementController::class, 'requestper_upload_proof'])
            ->middleware('permission:bbm.report')->name('requester-upload-proof');

        Route::post('/requester/request/{id}/submit-report', [RequestManagementController::class, 'requester_submit_report'])
            ->middleware('permission:bbm.report')->name('requester.submit-report');

        Route::put('/requester/cancel-request/{id}', [RequestManagementController::class, 'requester_cancel_request'])
            ->middleware('permission:bbm.request')->name('requester.cancel-request');

        // Thermer Print Routes
        Route::get('voucher/{id}/thermer/response', [VoucherController::class, 'thermerJsonResponse'])
            ->name('voucher.thermer.response');
    });

    // ────────────────────────────────────────────────────────────────────────
    // SURAT SPD
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('surat_requester')->name('requester.surat.')->group(function () {
        Route::get('/main-surat', [SpdController::class, 'indexSuratRequester'])->name('index')->middleware('permission:spd.view');
        Route::get('/create', [SpdController::class, 'createSurat'])->name('create')->middleware('permission:spd.create');
        Route::post('/store', [SpdController::class, 'storeSurat'])->name('store')->middleware('permission:spd.create');
        Route::get('/{surat}', [SpdController::class, 'showSuratRequester'])->name('show')->middleware('permission:spd.view');
    });

    // ────────────────────────────────────────────────────────────────────────
    // SPD BERJALAN — daftar surat yang masih harus dilaporkan (driver-friendly)
    // ────────────────────────────────────────────────────────────────────────
    Route::get('/spd-berjalan', [SpdController::class, 'indexSpdBerjalan'])
        ->name('requester.spd.berjalan')
        ->middleware('permission:lpj.create,spd.view');

    // ────────────────────────────────────────────────────────────────────────
    // AUTOCOMPLETE PEMAKAI/PENUMPANG SPD (nama + user_id)
    // ────────────────────────────────────────────────────────────────────────
    Route::get('/spd/passenger-lookup', [SpdController::class, 'passengerLookup'])
        ->name('spd.passenger.lookup')
        ->middleware('permission:spd.create,spd.manage');

    // ────────────────────────────────────────────────────────────────────────
    // PERPANJANGAN
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('perpanjangan')->name('requester.perpanjangan.')->middleware('permission:spd.create,spd.view')->group(function () {
        Route::get('/main-perpanjangan', [SpdController::class, 'indexPerpanjanganRequester'])->name('index');
        Route::get('/create', [SpdController::class, 'createPerpanjangan'])->middleware('permission:spd.create')->name('create');
        Route::post('/store', [SpdController::class, 'storePerpanjangan'])->middleware('permission:spd.create')->name('store');
    });

    // ────────────────────────────────────────────────────────────────────────
    // LPJ (Laporan Pertanggungjawaban)
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('lpj')->name('requester.lpj.')->middleware('permission:lpj.create,lpj.view')->group(function () {
        Route::get('/main-lpj', [SpdController::class, 'indexLaporanRequester'])->name('index');
        Route::get('/create/{surat}', [SpdController::class, 'createLaporan'])->middleware('permission:lpj.create')->name('create');
        Route::post('/store', [SpdController::class, 'storeLaporan'])->middleware('permission:lpj.create')->name('store');
        Route::get('/show/{laporan}', [SpdController::class, 'showLaporanRequester'])->name('show');
        Route::get('/{laporan}/edit', [SpdController::class, 'editLaporanRequester'])->middleware('permission:lpj.update')->name('edit');
        Route::put('/{laporan}', [SpdController::class, 'updateLaporanRequester'])->middleware('permission:lpj.update')->name('update');
    });

    // ────────────────────────────────────────────────────────────────────────
    // AJAX / API Endpoints
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('ajax')->name('requester.ajax.')->middleware('permission:spd.view,bbm.view')->group(function () {
        Route::get('/surat/{surat}', [SpdController::class, 'getSuratDetail'])->name('surat.detail');
        Route::get('/surat-list', [SpdController::class, 'getSuratListForDropdown'])->name('surat.list');
        Route::get('/dashboard-stats', [SpdController::class, 'getDashboardStats'])->name('dashboard.stats');
    });

    // ────────────────────────────────────────────────────────────────────────
    // VEHICLE CHECKING - Driver Acknowledgement & Service Transaction
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('vehicle-check')->name('requester.')->middleware('permission:vehicle.acknowledge')->group(function () {
        // Acknowledge checking dari dispatcher
        Route::get('/acknowledgement', [VehicleRepairmentDriverController::class, 'acknowledgementIndex'])
            ->name('checking-acknowledgement.index');
        Route::get('/acknowledgement/{checkingDetailCode}', [VehicleRepairmentDriverController::class, 'acknowledgementShow'])
            ->name('checking-acknowledgement.show');
        Route::put('/acknowledgement/{checkingDetailCode}/ack', [VehicleRepairmentDriverController::class, 'acknowledge'])
            ->name('checking-acknowledgement.acknowledge');

        // Jadwal replacement yang di-assign ke driver
        Route::get('/replacement-schedule', [VehicleRepairmentDriverController::class, 'replacementScheduleIndex'])
            ->name('replacement-schedule.index');

        // Lapor transaksi service
        Route::get('/service-transaction', [VehicleRepairmentDriverController::class, 'transactionIndex'])
            ->name('service-transaction.index');
        Route::get('/service-transaction/create/{vprCode}', [VehicleRepairmentDriverController::class, 'transactionCreate'])
            ->name('service-transaction.create');
        Route::post('/service-transaction/{vprCode}', [VehicleRepairmentDriverController::class, 'transactionStore'])
            ->name('service-transaction.store');
    });
});

// Admin Routes — Approval BBM (permission-based)
Route::middleware(['auth', 'permission:bbm.approve'])->group(function () {
    Route::get('/admin/dashboard', [RequestManagementController::class, 'admin_index'])->name('admin-dashboard');
    Route::get('/admin/approval', [RequestManagementController::class, 'admin_approval'])->name('admin-approval');
    Route::post('/admin/approval/{id}/process', [RequestManagementController::class, 'admin_process_request'])->name('admin-process');
    Route::post('/admin/approval/bulk-process', [RequestManagementController::class, 'admin_bulk_process'])->name('admin-bulk-process');

    Route::get('/transaction-photo/{reportId}/{type}', [RequestManagementController::class, 'getTransactionPhoto'])
        ->name('transaction.getPhoto');

    // ────────────────────────────────────────────────────────────────────────
    // VOUCHER — list realtime (AJAX) + bulk export Excel
    // ────────────────────────────────────────────────────────────────────────
    Route::get('/vouchers/list', [RequestManagementController::class, 'voucherList'])->name('admin.vouchers.list');
    Route::post('/vouchers/export', [RequestManagementController::class, 'voucherExport'])->name('admin.vouchers.export');
});

// Admin Routes — Vehicle Service & Sparepart (permission-based)
Route::middleware(['auth', 'permission:vehicle.sparepart'])->group(function () {
    Route::get('/admin/service-scheduling', [VehicleSparepartRequestRepairController::class, 'schedulingIndex'])
        ->name('admin.service-scheduling.index');

    Route::prefix('admin/replacement-approval')->name('admin.replacement-approval.')->group(function () {
        Route::get('/', [VehicleSparepartRequestRepairController::class, 'index'])
            ->name('index');
        Route::get('/{vprCode}', [VehicleSparepartRequestRepairController::class, 'show'])
            ->name('show');
        Route::post('/{vprCode}/approve', [VehicleSparepartRequestRepairController::class, 'approve'])
            ->name('approve');
        Route::post('/{vprCode}/reject', [VehicleSparepartRequestRepairController::class, 'reject'])
            ->name('reject');
        Route::post('/{vprCode}/schedule', [VehicleSparepartRequestRepairController::class, 'schedule'])
            ->name('schedule');
    });

    Route::get('/admin/transaction-monitoring', [VehicleSparepartRequestRepairController::class, 'transactionMonitoringIndex'])
        ->name('admin.transaction-monitoring.index');
});

// MASTER HARGA BBM (Master Gasoline) — settings-based access
Route::middleware(['auth', 'permission:settings.view'])->prefix('master-gasoline')->name('master-gasoline.')->group(function () {
    Route::get('/', [MasterGasolineController::class, 'index'])->name('index');
    Route::post('/sync', [MasterGasolineController::class, 'sync'])->name('sync');
});

// ────────────────────────────────────────────────────────────────────────
// SPAREPART REQUEST REPAIR - Permission-based (vehicle.sparepart)
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:vehicle.sparepart'])->prefix('admin/sparepart-request')->name('admin.sparepart-request.')->group(function () {
    Route::get('/', [VehicleSparepartRequestRepairController::class, 'index'])->name('index');
    Route::get('/{vprCode}', [VehicleSparepartRequestRepairController::class, 'show'])->name('show');
    Route::post('/{vprCode}/approve', [VehicleSparepartRequestRepairController::class, 'approve'])->name('approve');
    Route::post('/{vprCode}/reject', [VehicleSparepartRequestRepairController::class, 'reject'])->name('reject');
    Route::post('/{vprCode}/schedule', [VehicleSparepartRequestRepairController::class, 'schedule'])->name('schedule');
    Route::post('/{vprCode}/complete', [VehicleSparepartRequestRepairController::class, 'markCompleted'])->name('complete');
});

// export laporan transaksi bbm (admin/superadmin — bbm.export)
Route::middleware(['auth', 'permission:bbm.export'])->group(function () {
    // ==================== REPORTS & EXPORTS ====================
    Route::get('/print-report', [SuperadminController::class, 'printReport'])->name('superadmin.print-report');
    Route::get('/print-report/form', [SuperadminController::class, 'printReportPage'])->name('superadmin.print-report.form');
});
Route::middleware(['auth', 'permission:ai.view'])->group(function () {
    Route::get('/ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant.index');
    Route::post('/ai-assistant/send', [AiAssistantController::class, 'send'])->name('ai-assistant.send');
    Route::delete('/ai-assistant/sessions/{session}', [AiAssistantController::class, 'destroySession'])->name('ai-assistant.sessions.destroy');
});

// ── SUPERADMIN DASHBOARD & API (tetap superadmin-only) ──
Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/sa/dashboard', [SuperadminController::class, 'index'])->name('superadmin-dashboard');
    Route::get('/dashboard/stats', [SuperadminController::class, 'getDashboardStats'])->name('dashboard.stats');
    Route::get('/dashboard/driver-stats', [SuperadminController::class, 'getDetailedDriverStats'])->name('dashboard.driver-stats');

    Route::prefix('api')->name('superadmin.')->group(function () {
        Route::get('/dashboard/stats', [SuperadminController::class, 'getDashboardStats'])->name('dashboard.stats');
        Route::get('/dashboard/driver-statistics', [SuperadminController::class, 'getDriverStatistics'])->name('driver.statistics');
        Route::get('/dashboard/detailed-driver-stats', [SuperadminController::class, 'getDetailedDriverStats'])->name('dashboard.detailed-driver-stats');
        Route::get('/dashboard/vehicle-fuel-consumption', [SuperadminController::class, 'getVehicleFuelConsumption'])->name('dashboard.vehicle-fuel');
        Route::get('/dashboard/vehicle-km-performance', [SuperadminController::class, 'getVehicleKmPerformance'])->name('dashboard.vehicle-km');
        Route::get('/dashboard/pivot-table', [SuperadminController::class, 'getCompletePivotTable'])->name('pivot.data');
    });

    Route::middleware(['auth', 'role:superadmin'])->prefix('api/data-mart')->name('api.datamart.')->group(function () {
        Route::get('/{unitId}', [App\Http\Controllers\Api\DataMartContextController::class, 'show'])->name('context');
        Route::get('/{unitId}/refresh', [App\Http\Controllers\Api\DataMartContextController::class, 'refresh'])->name('refresh');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/fuel-consumption', [SuperadminController::class, 'getVehicleFuelConsumption'])->name('fuel-consumption');
        Route::get('/km-performance', [SuperadminController::class, 'getVehicleKmPerformance'])->name('km-performance');
        Route::get('/pivot-table', [SuperadminController::class, 'getCompletePivotTable'])->name('pivot-table');
    });

    Route::get('/activity-log', [SuperadminController::class, 'getActivityLog'])->name('activity-log');
});

// ── PERMISSION-BASED ROUTES (ex-superadmin group) ──

// User Management (split per aksi agar tidak bocor ke role view-only)
Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:users.view')->group(function () {
        Route::get('users', [SuperadminController::class, 'indexUsers'])->name('users');
    });
    Route::middleware('permission:users.create')->group(function () {
        Route::get('users/create', [SuperadminController::class, 'createUser'])->name('users.create');
        Route::post('users/store', [SuperadminController::class, 'storeUser'])->name('users.store');
    });
    Route::middleware('permission:users.update')->group(function () {
        Route::post('users/{id}/update', [SuperadminController::class, 'updateUser'])->name('users.update');
        Route::post('users/{id}/reset-password', [SuperadminController::class, 'resetPassword'])->name('users.reset-password');
    });
    Route::middleware('permission:users.delete')->group(function () {
        Route::post('users/{id}/delete', [SuperadminController::class, 'deleteUser'])->name('users.delete');
        Route::post('users/{id}/restore', [SuperadminController::class, 'restoreUser'])->name('users.restore');
    });
});

// Vehicle Management (split per aksi; admin/dispatcher hanya view + detail)
Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:vehicle.view')->group(function () {
        Route::get('vehicles', [SuperadminController::class, 'indexVehicles'])->name('vehicles');
        Route::get('/vehicles/{id}/detail', [SuperadminController::class, 'showVehicleDetail'])->name('superadmin.vehicle.detail');
    });
    Route::middleware('permission:vehicle.create')->group(function () {
        Route::get('vehicles/create', [SuperadminController::class, 'createVehicle'])->name('vehicles.create');
        Route::post('vehicles/store', [SuperadminController::class, 'storeVehicle'])->name('vehicles.store');
    });
    Route::middleware('permission:vehicle.update')->group(function () {
        Route::get('vehicles/{id}/edit', [SuperadminController::class, 'editVehicle'])->name('vehicles.edit');
        Route::post('vehicles/{id}/update', [SuperadminController::class, 'updateVehicle'])->name('vehicles.update');
        Route::get('/vehicles/{id}/detail/edit', [SuperadminController::class, 'editVehicleDetail'])->name('superadmin.vehicle.detail.edit');
        Route::post('/vehicles/{id}/detail/update', [SuperadminController::class, 'updateVehicleDetail'])->name('superadmin.vehicle.detail.update');
    });
    Route::middleware('permission:vehicle.delete')->group(function () {
        Route::post('vehicles/{id}/delete', [SuperadminController::class, 'deleteVehicle'])->name('vehicles.delete');
        Route::post('vehicles/{id}/restore', [SuperadminController::class, 'restoreVehicle'])->name('vehicles.restore');
    });
    Route::middleware('permission:vehicle.approve')->group(function () {
        Route::post('/vehicles/{id}/approve', [SuperadminController::class, 'approveVehicle'])->name('superadmin.vehicle.approve');
        Route::post('/vehicles/{id}/reject', [SuperadminController::class, 'rejectVehicle'])->name('superadmin.vehicle.reject');
        Route::get('/vehicle-activation', [SuperadminController::class, 'vehicleActivation'])->name('superadmin.vehicle-activation');
        Route::post('/vehicle/{id}/activate', [SuperadminController::class, 'activateVehicle'])->name('superadmin.vehicle.activate');
        Route::post('/vehicle/{id}/deactivate', [SuperadminController::class, 'deactivateVehicle'])->name('superadmin.vehicle.deactivate');
        Route::post('/vehicle/bulk-activate', [SuperadminController::class, 'bulkActivateVehicles'])->name('superadmin.vehicle.bulk-activate');
        Route::post('/vehicle/bulk-deactivate', [SuperadminController::class, 'bulkDeactivateVehicles'])->name('superadmin.vehicle.bulk-deactivate');
    });
});

// Permohonan & Laporan (approval)
Route::middleware(['auth', 'permission:bbm.approve'])->group(function () {
    Route::get('/kelola-permohonan', [SuperadminController::class, 'indexPermohonan'])->name('superadmin.permohonan');
    Route::post('/permohonan/{id}/update', [SuperadminController::class, 'updatePermohonan'])->name('superadmin.permohonan.update');
    Route::post('/permohonan/{id}/cancel', [SuperadminController::class, 'cancelPermohonan'])->name('superadmin.permohonan.cancel');
    Route::post('/permohonan/{id}/delete', [SuperadminController::class, 'deletePermohonan'])->name('superadmin.permohonan.delete');
    Route::post('/kelola-permohonan/bulk-process', [SuperadminController::class, 'bulkProcessPermohonan'])->name('superadmin.permohonan.bulk-process');

    Route::post('/laporan/{id}/update', [SuperadminController::class, 'updateLaporan'])->name('superadmin.laporan.update');
    Route::post('/laporan/{id}/delete', [SuperadminController::class, 'deleteLaporan'])->name('superadmin.laporan.delete');
});

// Trip Analysis & Validation (superadmin only)
Route::middleware(['auth', 'permission:vehicle.report'])->group(function () {
    Route::prefix('trip-analysis')->name('superadmin.trip-analysis.')->group(function () {
        Route::get('/', [SuperadminController::class, 'indexTripAnalysis'])->name('index');
        Route::post('/recalculate-all', [SuperadminController::class, 'recalculateAllTripMetrics'])->name('superadmin.trip-validations.recalculate-all');
    });

    Route::post('/trip-validations/{id}/validate', [SuperadminController::class, 'validateTrip'])
        ->name('superadmin.trip-validations.validate');
    Route::post('/trip-validations/bulk-validate', [SuperadminController::class, 'bulkValidateTrips'])
        ->name('superadmin.trip-validations.bulk-validate');
});

// SPD Superadmin
Route::middleware(['auth', 'permission:spd.manage'])->prefix('surat')->name('surat.superadmin.')->group(function () {
    Route::get('/', [SpdController::class, 'indexSuratSuperadmin'])->name('index');
    Route::get('/create-shortcut', [SpdController::class, 'createSuratSuperadmin'])->name('create-shortcut');
    Route::post('/store-shortcut', [SpdController::class, 'storeSuratSuperadmin'])->name('store-shortcut');
    Route::get('/{surat}', [SpdController::class, 'showSuratSuperadmin'])->name('show');
    Route::post('/{surat}/approve', [SpdController::class, 'approveSurat'])->name('approve');
    Route::post('/{surat}/reject', [SpdController::class, 'rejectSurat'])->name('reject');
    Route::get('/{surat}/print', [SpdController::class, 'printSurat'])->name('print');
    Route::post('/{surat}/save-mendadak-budget', [SpdController::class, 'saveMendadakBudget'])->name('save-mendadak-budget');
    Route::post('/{surat}/fund-additions', [SpdController::class, 'storeFundAddition'])->name('fund-additions.store');
    Route::get('/report/spending', [SpdController::class, 'reportSpending'])->name('report');
});

// Perpanjangan Superadmin
Route::middleware(['auth', 'permission:spd.update'])->prefix('perpanjangan')->name('perpanjangan.superadmin.')->group(function () {
    Route::get('/', [SpdController::class, 'indexPerpanjanganSuperadmin'])->name('index');
    Route::get('/validate/{perpanjangan}', [SpdController::class, 'showPerpanjanganSuperadmin'])->name('show');
    Route::post('/{perpanjangan}/approve', [SpdController::class, 'approvePerpanjangan'])->name('approve');
    Route::post('/{perpanjangan}/reject', [SpdController::class, 'rejectPerpanjangan'])->name('reject');
    Route::post('/{perpanjangan}/acknowledge', [SpdController::class, 'acknowledgePerpanjangan'])->name('acknowledge');
});

// LPJ Superadmin
Route::middleware(['auth', 'permission:lpj.validate'])->prefix('lpj')->name('lpj.superadmin.')->group(function () {
    Route::get('/', [SpdController::class, 'indexLaporanSuperadmin'])->name('index');
    Route::get('/{laporan}', [SpdController::class, 'showLaporanSuperadmin'])->name('show');
    Route::post('/{laporan}/approve', [SpdController::class, 'approveLaporan'])->name('approve');
    Route::post('/{laporan}/approve-ga', [SpdController::class, 'approveLaporanDivisiGa'])->name('approve-ga');
    Route::post('/{laporan}/reject', [SpdController::class, 'rejectLaporan'])->name('reject');
    Route::get('/{laporan}/print', [SpdController::class, 'printLaporan'])->name('print');
    Route::put('/{laporan}/keuangan', [SpdController::class, 'updateKeuanganLaporan'])->name('keuangan');
});

// LPJ — Lampiran Pemberian Dana Perjalanan Dinas (pencairan).
// Gerbang terpisah: hanya Divisi General Affairs / superadmin yang punya
// permission `lpj.pencairan` (mapping group: LPJ, modul: Perjalanan Dinas).
Route::middleware(['auth', 'permission:lpj.pencairan'])->prefix('lpj')->name('lpj.superadmin.')->group(function () {
    Route::get('/{laporan}/pencairan', [SpdPencairanController::class, 'index'])->name('pencairan.index');
    Route::post('/{laporan}/pencairan', [SpdPencairanController::class, 'store'])->name('pencairan.store');
    Route::put('/{laporan}/pencairan/{pencairan}', [SpdPencairanController::class, 'update'])->name('pencairan.update');
    Route::delete('/{laporan}/pencairan/{pencairan}', [SpdPencairanController::class, 'destroy'])->name('pencairan.destroy');
});

// LPJ — Validasi kebenaran perjalanan oleh User (penumpang)
Route::middleware(['auth', 'permission:spd.validate_trip'])->prefix('lpj-pengguna')->name('lpj.pengguna.')->group(function () {
    Route::get('/', [SpdController::class, 'indexLaporanPengguna'])->name('index');
    Route::get('/{laporan}', [SpdController::class, 'showValidasiPengguna'])->name('show');
    Route::post('/{laporan}/approve', [SpdController::class, 'approveLaporanPengguna'])->name('approve');
});

// LPJ — Validasi final oleh Management (supervisor tertinggi)
Route::middleware(['auth', 'permission:lpj.validate_management'])->prefix('lpj-management')->name('lpj.management.')->group(function () {
    Route::get('/', [SpdController::class, 'indexLaporanManagement'])->name('index');
    Route::get('/{laporan}', [SpdController::class, 'showLaporanSuperadmin'])->name('show');
    Route::post('/{laporan}/approve', [SpdController::class, 'approveLaporanManagement'])->name('approve');
});

// AJAX Superadmin (SPD dashboard stats)
Route::middleware(['auth', 'permission:spd.manage'])->prefix('ajax')->name('ajax.superadmin.')->group(function () {
    Route::get('/dashboard-stats', [SpdController::class, 'getDashboardStats'])->name('dashboard.stats');
});

// RBAC — Role Management (sudah punya inner permission:rbac.roles)
Route::middleware(['auth'])->prefix('sa/rbac')->name('superadmin.rbac.')->middleware('permission:rbac.roles')->group(function () {
    Route::get('/', [RbacController::class, 'index'])->name('index');
    Route::get('/create', [RbacController::class, 'create'])->name('create');
    Route::post('/', [RbacController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [RbacController::class, 'edit'])->name('edit');
    Route::post('/{id}/update', [RbacController::class, 'update'])->name('update');
    Route::post('/{id}/delete', [RbacController::class, 'destroy'])->name('delete');
});

// Unit Bisnis & Site Management (superadmin only — settings.manage)
Route::middleware(['auth', 'permission:settings.manage'])->prefix('sa')->name('superadmin.')->group(function () {
    Route::prefix('units')->name('units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::get('/create', [UnitController::class, 'create'])->name('create');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
        Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
        Route::post('/{unit}/delete', [UnitController::class, 'destroy'])->name('delete');
    });

    Route::prefix('sites')->name('sites.')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('index');
        Route::get('/create', [SiteController::class, 'create'])->name('create');
        Route::post('/', [SiteController::class, 'store'])->name('store');
        Route::get('/{site}/edit', [SiteController::class, 'edit'])->name('edit');
        Route::put('/{site}', [SiteController::class, 'update'])->name('update');
        Route::post('/{site}/delete', [SiteController::class, 'destroy'])->name('delete');
    });
});

// Pemetaan Jabatan SSO -> role (khusus superadmin unit PUSAT)
// `central` menegakkan syarat unitnya, `permission:settings.sso_mapping`
// menegakkan haknya — keduanya wajib, karena mengubah pemetaan berarti
// mengubah hak akses seluruh orang dengan jabatan + unit tersebut.
Route::middleware(['auth', 'permission:settings.sso_mapping', 'central'])
    ->prefix('sa/sso')->name('superadmin.sso.')
    ->group(function () {
        Route::prefix('mappings')->name('mappings.')->group(function () {
            Route::get('/', [SsoRoleMappingController::class, 'index'])->name('index');
            Route::post('/', [SsoRoleMappingController::class, 'store'])->name('store');
            Route::post('/{mapping}/update', [SsoRoleMappingController::class, 'update'])->name('update');
            Route::post('/{mapping}/delete', [SsoRoleMappingController::class, 'destroy'])->name('delete');
        });
    });

// Tahap Fitur (development/production) — role developers
// TIDAK memakai `central`: aktivitas naik/turun tahap fitur adalah milik
// role `developers`, bukan superadmin Pusat. Siapa pun yang memegang
// permission features.manage boleh mengelolanya.
Route::middleware(['auth', 'permission:features.manage'])
    ->prefix('sa/features')->name('superadmin.features.')
    ->group(function () {
        Route::get('/', [FeatureController::class, 'index'])->name('index');
        Route::post('/', [FeatureController::class, 'store'])->name('store');
        Route::post('/{feature}/activate', [FeatureController::class, 'activate'])->name('activate');
        Route::post('/{feature}/deactivate', [FeatureController::class, 'deactivate'])->name('deactivate');
    });

// Vehicle Checking Superadmin — Health, Financial, Transaction Reports (superadmin only)
Route::middleware(['auth', 'permission:vehicle.report'])->prefix('vehicle-checking')->name('superadmin.')->group(function () {
    Route::get('/vehicle-health', [VehicleCheckingSuperadminController::class, 'vehicleHealthIndex'])
        ->name('vehicle-health.index');
    Route::get('/vehicle-health/{consumerialCode}', [VehicleCheckingSuperadminController::class, 'vehicleHealthShow'])
        ->name('vehicle-health.show');

    Route::get('/financial-report', [VehicleCheckingSuperadminController::class, 'financialReportIndex'])
        ->name('financial-report.index');
    Route::get('/financial-report/export', [VehicleCheckingSuperadminController::class, 'financialReportExport'])
        ->name('financial-report.export');

    Route::get('/transaction-report', [VehicleCheckingSuperadminController::class, 'transactionReportIndex'])
        ->name('transaction-report.index');

    Route::get('/barcode/download/{vehicleId}', [VehicleHealthHistoryController::class, 'downloadBarcode'])
        ->name('barcode.download');
    Route::get('/barcode/download-all-zip', [VehicleHealthHistoryController::class, 'downloadAllBarcodesZip'])
        ->name('barcode.download-zip');
});

Route::middleware(['auth', 'permission:dashboard.view'])->group(function () {
    Route::get('/dashboard-bisnis', [SuperadminController::class, 'dashboardBisnis'])
        ->name('superadmin.dashboard.bisnis');

});

// ────────────────────────────────────────────────────────────────────────
// BARCODE SCAN & RIWAYAT KENDARAAN — Permission-based (vehicle.scan / vehicle.checking)
// Admin boleh scan riwayat (read-only); dispatcher boleh scan riwayat & mulai periksa.
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:vehicle.scan,vehicle.checking'])->prefix('dispatcher')->name('dispatcher.')->group(function () {
    // Scanner riwayat (eksisting) — scan untuk lihat riwayat pengecekan
    Route::get('/scan-vehicle', [VehicleHealthHistoryController::class, 'scanView'])
        ->name('scan-vehicle');
    Route::get('/scan-vehicle/result', [VehicleHealthHistoryController::class, 'vehicleHistory'])
        ->name('scan-vehicle.result');

    // Dispatcher Scanner (baru) — scan untuk memulai pemeriksaan (butuh vehicle.checking)
    Route::get('/dispatcher-scanner', [VehicleHealthHistoryController::class, 'dispatcherScanView'])
        ->middleware('permission:vehicle.checking')
        ->name('dispatcher-scanner');
    Route::get('/dispatcher-scanner/result', [VehicleHealthHistoryController::class, 'dispatcherScanResult'])
        ->middleware('permission:vehicle.checking')
        ->name('dispatcher-scanner.result');

    // ────────────────────────────────────────────────────────────────────────
    // BARCODE KENDARAAN - Download
    // ────────────────────────────────────────────────────────────────────────
    // Download barcode satu kendaraan (superadmin + dispatcher — dicek di controller)
    Route::get('/barcode/download/{vehicleId}', [VehicleHealthHistoryController::class, 'downloadBarcode'])
        ->name('barcode.download');
});

// Role Baru, Dispatcher Routes — Assign
Route::middleware(['auth', 'permission:vehicle.checking'])->prefix('dispatcher')->name('dispatcher.')->group(function () {
    // ────────────────────────────────────────────────────────────────────────
    // ASSIGN DISPATCHER - Assign PIC ke kendaraan
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('assign')->name('assign.')->group(function () {
        Route::get('/', [DispatcherAdministrationController::class, 'assignIndex'])
            ->name('index');
        Route::put('/', [DispatcherAdministrationController::class, 'assignUpdate'])
            ->name('update');
    });
});

Route::middleware(['auth', 'permission:vehicle.checking'])->prefix('dispatcher')->name('dispatcher.')->group(function () {

    // ────────────────────────────────────────────────────────────────────────
    // DASHBOARD - Landing Page
    // ────────────────────────────────────────────────────────────────────────
    Route::get('/dashboard', [DispatcherAdministrationController::class, 'dashboard'])
        ->name('dispatcher-dashboard');

    // ────────────────────────────────────────────────────────────────────────
    // GENERATE PERIODE - Tombol generate header bulanan
    // ────────────────────────────────────────────────────────────────────────
    Route::post('/generate-period', [DispatcherAdministrationController::class, 'generatePeriod'])
        ->name('generate-period');

    // ────────────────────────────────────────────────────────────────────────
    // DAILY CHECKING - Pengecekan Harian (tanpa tracking jadwal)
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('daily-check')->name('daily-check.')->group(function () {
        Route::get('/', [DailyCheckingController::class, 'index'])->name('index');
        Route::get('/create', [DailyCheckingController::class, 'create'])->name('create');
        Route::post('/', [DailyCheckingController::class, 'store'])->name('store');
        Route::get('/{checkingDetailCode}', [DailyCheckingController::class, 'show'])->name('show');
    });

    // ────────────────────────────────────────────────────────────────────────
    // WEEKLY CHECKING - Pengecekan Mingguan (dengan tracking jadwal)
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('weekly-check')->name('weekly-check.')->group(function () {
        Route::get('/', [WeeklyCheckingController::class, 'index'])->name('index');
        Route::get('/create', [WeeklyCheckingController::class, 'create'])->name('create');
        Route::post('/', [WeeklyCheckingController::class, 'store'])->name('store');
        Route::get('/{checkingDetailCode}', [WeeklyCheckingController::class, 'show'])->name('show');
    });

    // ────────────────────────────────────────────────────────────────────────
    // MONTHLY CHECKING - Pengecekan Bulanan (dengan tracking jadwal)
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('monthly-check')->name('monthly-check.')->group(function () {
        Route::get('/', [MonthlyCheckingController::class, 'index'])->name('index');
        Route::get('/create', [MonthlyCheckingController::class, 'create'])->name('create');
        Route::post('/', [MonthlyCheckingController::class, 'store'])->name('store');
        Route::get('/{checkingDetailCode}', [MonthlyCheckingController::class, 'show'])->name('show');
    });

    // ────────────────────────────────────────────────────────────────────────
    // BODY CHECKING - Pengecekan Body Kendaraan
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('body-check')->name('body-check.')->group(function () {
        Route::get('/', [BodyCheckingController::class, 'index'])
            ->name('index');
        Route::get('/create', [BodyCheckingController::class, 'create'])
            ->name('create');
        Route::post('/', [BodyCheckingController::class, 'store'])
            ->name('store');
        Route::get('/{checkingDetailCode}', [BodyCheckingController::class, 'show'])
            ->name('show');
    });

    // ────────────────────────────────────────────────────────────────────────
    // RIWAYAT - History Pengecekan (All types)
    // ────────────────────────────────────────────────────────────────────────
    Route::get('/history', [DispatcherAdministrationController::class, 'history'])
        ->name('history');

    // ────────────────────────────────────────────────────────────────────────
    // EXPORT - Download laporan PDF
    // ────────────────────────────────────────────────────────────────────────
    Route::post('/export/vehicle-checklist', [DispatcherAdministrationController::class, 'exportVehicleChecklist'])
        ->name('export.vehicle-checklist');
    Route::post('/export/routine-check', [DispatcherAdministrationController::class, 'exportRoutineCheck'])
        ->name('export.routine-check');
    Route::post('/export/body-check', [DispatcherAdministrationController::class, 'exportBodyCheck'])
        ->name('export.body-check');

    // ────────────────────────────────────────────────────────────────────────
    // PERMOHONAN PENGGANTIAN SPAREPART (VPR)
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('replacement')->name('replacement.')->group(function () {
        Route::get('/', [DispatcherAdministrationController::class, 'replacementIndex'])
            ->name('index');
        Route::get('/create/{checkingDetailCode}', [DispatcherAdministrationController::class, 'replacementCreate'])
            ->name('create');
        Route::post('/store/{checkingDetailCode}', [DispatcherAdministrationController::class, 'replacementStore'])
            ->name('store');
        Route::post('/auto-create/{checkingDetailCode}', [DispatcherAdministrationController::class, 'replacementAutoCreate'])
            ->name('auto-create');
        Route::get('/{vprCode}', [DispatcherAdministrationController::class, 'replacementShow'])
            ->name('show');
    });
});

// ────────────────────────────────────────────────────────────────────────
// EXPORT EXCEL - FORM PENGECEKAN DISPATCHER
// Bisa diakses oleh 3 role: Superadmin, Dispatcher, dan Driver (requester).
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:vehicle.export'])->prefix('dispatcher')->name('dispatcher.')->group(function () {
    Route::get('/export-checking', [DispatcherAdministrationController::class, 'exportCheckingIndex'])
        ->name('export-checking.index');
    Route::post('/export-checking', [DispatcherAdministrationController::class, 'exportCheckingDownload'])
        ->name('export-checking.download');

    // Export Body Checking Excel
    Route::get('/export-body-checking', [DispatcherAdministrationController::class, 'exportBodyCheckingIndex'])
        ->name('export.body-checking-index');
    Route::post('/export-body-checking', [DispatcherAdministrationController::class, 'exportBodyCheckingDownload'])
        ->name('export.body-checking-download');
});

// ────────────────────────────────────────────────────────────────────────
// LUNCH MANAGEMENT — QR Attendance (PUBLIK, tanpa login)
// QR lokasi membuka halaman attendance; scan → cari nama → submit →
// popup "Besok makan dimana?". Endpoint dibatasi throttle karena publik.
// ────────────────────────────────────────────────────────────────────────
Route::prefix('lunch-attendance')->name('lunch.attendance.')->group(function () {
    Route::get('/', [PublicAttendanceController::class, 'landing'])->name('scan');
    Route::get('/site/{siteCode}', [PublicAttendanceController::class, 'form'])->name('form');

    Route::get('/lookup', [PublicAttendanceController::class, 'lookup'])
        ->middleware('throttle:60,1')->name('lookup');
    Route::post('/submit', [PublicAttendanceController::class, 'submit'])
        ->middleware('throttle:15,1')->name('submit');
    Route::post('/tomorrow/{transaction}', [PublicAttendanceController::class, 'confirmTomorrow'])
        ->middleware('throttle:15,1')->name('tomorrow');
    Route::post('/guest-submit', [PublicAttendanceController::class, 'guestSubmit'])
        ->middleware('throttle:15,1')->name('guest-submit');
    Route::post('/{transaction}/choose-site', [PublicAttendanceController::class, 'chooseSite'])
        ->middleware('throttle:30,1')->name('choose-site');
    Route::post('/{transaction}/choose-dine-type', [PublicAttendanceController::class, 'chooseDineType'])
        ->middleware('throttle:30,1')->name('choose-dine-type');
});

// ────────────────────────────────────────────────────────────────────────
// LUNCH MANAGEMENT — Admin & Superadmin
// Employee Default → Forecast → Distribution → QR Attendance →
// Transaction → Learning → Forecast H+1
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:lunch.view,lunch.export'])->prefix('lunch')->name('lunch.')->group(function () {
    // ── Halaman read-only (lunch.view / lunch.export) ──
    Route::get('/', [LunchDashboardController::class, 'index'])->name('dashboard');
    Route::get('/forecasts', [LunchForecastController::class, 'index'])->name('forecasts.index');
    Route::get('/distributions', [LunchDistributionController::class, 'index'])->name('distributions.index');
    Route::get('/qr-codes', [LunchDistributionController::class, 'qrCodes'])->name('qr-codes');
    Route::get('/qr-codes/{site}.svg', [LunchDistributionController::class, 'qrImage'])->name('qr-image');
    Route::get('/transactions', [LunchTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/feed', [LunchTransactionController::class, 'feed'])->name('transactions.feed');
    Route::get('/analytics', [LunchAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/guests', [CateringGuestController::class, 'calendar'])->name('guests.calendar');
    Route::get('/self-booking', [SelfBookingController::class, 'index'])->name('self-booking.index');

    // ── Aksi mutasi: butuh lunch.manage (view-only TIDAK boleh) ──
    Route::middleware('permission:lunch.manage')->group(function () {
        Route::post('/forecasts/generate', [LunchForecastController::class, 'generate'])->name('forecasts.generate');
        Route::post('/forecasts/override', [LunchForecastController::class, 'override'])->name('forecasts.override');
        Route::post('/distributions/generate', [LunchDistributionController::class, 'generate'])->name('distributions.generate');
        Route::post('/distributions/lock', [LunchDistributionController::class, 'lock'])->name('distributions.lock');
        Route::post('/guests', [CateringGuestController::class, 'store'])->name('guests.store');
        Route::put('/guests/{cateringGuest}', [CateringGuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{cateringGuest}', [CateringGuestController::class, 'destroy'])->name('guests.destroy');
        Route::post('/guests/{cateringGuest}/h2-confirm', [CateringGuestController::class, 'h2Confirm'])->name('guests.h2-confirm');
        Route::post('/guests/{cateringGuest}/h2-cancel', [CateringGuestController::class, 'h2Cancel'])->name('guests.h2-cancel');
        Route::post('/self-booking', [SelfBookingController::class, 'store'])->name('self-booking.store');
    });

    // ── Export data: butuh lunch.export ──
    Route::middleware('permission:lunch.export')->group(function () {
        Route::get('/export', [LunchDistributionController::class, 'exportForm'])->name('export-form');
        Route::post('/export', [LunchDistributionController::class, 'export'])->name('export');
    });
});

// ────────────────────────────────────────────────────────────────────────
// EMPLOYEE DEFAULTS (Lunch) - Permission-based (lunch.manage)
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:lunch.manage'])->prefix('lunch')->name('lunch.')->group(function () {
    Route::get('/employee-defaults', [EmployeeDefaultController::class, 'index'])->name('employee-defaults.index');
    Route::post('/employee-defaults', [EmployeeDefaultController::class, 'store'])->name('employee-defaults.store');
    Route::put('/employee-defaults/{employeeDefault}', [EmployeeDefaultController::class, 'update'])->name('employee-defaults.update');
    Route::delete('/employee-defaults/{employeeDefault}', [EmployeeDefaultController::class, 'destroy'])->name('employee-defaults.destroy');
    Route::post('/employee-defaults/{employeeDefault}/restore', [EmployeeDefaultController::class, 'restore'])->name('employee-defaults.restore');
    Route::get('/employee-defaults-export', [EmployeeDefaultController::class, 'export'])->name('employee-defaults.export');
    Route::post('/employee-defaults-import', [EmployeeDefaultController::class, 'import'])->name('employee-defaults.import');
});

// Self-Booking untuk semua pegawai (public — cukup masuk tanpa login)
// Endpoint write dibatasi throttle untuk mencegah penyalahgunaan.
Route::prefix('my')->name('my.')->group(function () {
    Route::get('/lunch-plan', [SelfBookingController::class, 'index'])->name('lunch-plan');
    Route::post('/lunch-plan/store', [SelfBookingController::class, 'store'])
        ->middleware('throttle:20,1')->name('lunch-plan.store');
});

// ────────────────────────────────────────────────────────────────────────
// VENDOR CATERING — akses terbatas: daftar kehadiran lunch harian.
// Role vendor disiapkan; superadmin juga bisa mengakses. Menu sidebar
// disembunyikan sementara hingga siap diaktifkan.
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:lunch.view'])->prefix('vendor/lunch')->name('vendor.lunch.')->group(function () {
    Route::get('/today', [VendorLunchController::class, 'today'])->name('today');
});
