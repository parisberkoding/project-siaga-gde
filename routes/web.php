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
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SpdDailyReportController;
use App\Http\Controllers\SpdPrintController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\VehicleCheckingSuperadminController;
use App\Http\Controllers\VehicleHealthHistoryController;
use App\Http\Controllers\VehicleRepairmentDriverController;
use App\Http\Controllers\VehicleSparepartRequestRepairController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WeeklyCheckingController;
use Illuminate\Support\Facades\Route;

Route::get('/files/storage/{path}', [StorageFileController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

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

// Logout: dukung GET & POST, tanpa batasan role (semua user authenticated boleh logout)
Route::middleware(['auth'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthentiactionController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:requester,admin,superadmin,manager,dispatcher'])->prefix('spd')->name('spd.')->group(function () {
    Route::get('/surat/{surat}/print', [SpdController::class, 'printSurat'])->name('surat.print');
    Route::get('/laporan/{laporan}/print', [SpdController::class, 'printLaporan'])->name('laporan.print');

    // Cetak dokumen gabungan Surat SPD + LPJ (tersedia untuk semua role SPD)
    Route::get('/laporan/{laporan}/export', [SpdPrintController::class, 'exportLpj'])->name('laporan.export');
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
Route::middleware(['auth', 'role:requester,general_affairs,dispatcher,manager'])->prefix('pelaporan')->name('pelaporan.')->group(function () {
    Route::get('/', [ReportingController::class, 'index'])->name('index');
    Route::post('/analyze-struk', [ReportingController::class, 'analyzeStruk'])->name('analyze-struk');
    Route::post('/vehicle-budget', [ReportingController::class, 'vehicleBudget'])->name('vehicle-budget');
    Route::post('/spd', [ReportingController::class, 'storeSpd'])->name('spd.store');
    Route::post('/operational', [ReportingController::class, 'storeOperational'])->name('operational.store');

    // ── Laporan harian SPD (pre-fixed report) ──
    // Semua rute pakai route model binding (slug/kode surat) — tanpa ID.
    Route::get('/spd/{surat}/daily', [SpdDailyReportController::class, 'index'])->name('spd.daily.index');
    Route::get('/spd/{surat}/daily/create', [SpdDailyReportController::class, 'create'])->name('spd.daily.create');
    Route::get('/spd/{surat}/daily/{daily}/edit', [SpdDailyReportController::class, 'edit'])->name('spd.daily.edit');
    Route::post('/spd/{surat}/daily', [SpdDailyReportController::class, 'store'])->name('spd.daily.store');
    Route::put('/spd/{surat}/daily/{daily}', [SpdDailyReportController::class, 'update'])->name('spd.daily.update');
    Route::delete('/spd/{surat}/daily/{daily}', [SpdDailyReportController::class, 'destroy'])->name('spd.daily.destroy');

    // AJAX item laporan harian (draft server-side, tahan refresh)
    Route::post('/spd/{surat}/daily/item', [SpdDailyReportController::class, 'uploadItem'])->name('spd.daily.upload-item');
    Route::patch('/spd/{surat}/daily/item/{item}', [SpdDailyReportController::class, 'updateItem'])->name('spd.daily.update-item');
    Route::delete('/spd/{surat}/daily/item/{item}', [SpdDailyReportController::class, 'deleteItem'])->name('spd.daily.delete-item');

    // Validasi seluruh laporan harian → LPJ final
    Route::get('/spd/{surat}/validasi', [SpdDailyReportController::class, 'validasi'])->name('spd.validasi');
    Route::post('/spd/{surat}/finalize', [SpdDailyReportController::class, 'finalize'])->name('spd.finalize');
});

// Requester Routes
Route::middleware(['auth', 'permission:bbm.request,bbm.view,bbm.report'])->group(function () {
    Route::get('/requester/view-request-bbm', [RequestManagementController::class, 'requester_index'])->name('requester-index');
    Route::post('/requester/create-request-bbm', [RequestManagementController::class, 'requester_create'])->name('requester-create');
    Route::put('/requester/cancel/{id}', [RequestManagementController::class, 'requester_cancel_request'])->name('requester-cancel');
    Route::get('/requester/bbm-history', [RequestManagementController::class, 'requester_history'])->name('requester-history');
    Route::get('/requester/request/{id}', [RequestManagementController::class, 'requester_show'])->name('requester-show');
    Route::post('/requester/request/{id}/upload-proof', [RequestManagementController::class, 'requestper_upload_proof'])->name('requester-upload-proof');

    // Submit Report Route
    Route::post('/requester/request/{id}/submit-report', [RequestManagementController::class, 'requester_submit_report'])->name('requester.submit-report');

    Route::put('/requester/cancel-request/{id}', [RequestManagementController::class, 'requester_cancel_request'])
        ->name('requester.cancel-request');

    // Thermer Print Routes
    Route::get('voucher/{id}/thermer/response', [VoucherController::class, 'thermerJsonResponse'])
        ->name('voucher.thermer.response');

    // ────────────────────────────────────────────────────────────────────────
    // SURAT SPD
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('surat_requester')->name('requester.surat.')->middleware('permission:spd.view')->group(function () {
        Route::get('/main-surat', [SpdController::class, 'indexSuratRequester'])->name('index');
        Route::get('/create', [SpdController::class, 'createSurat'])->name('create');
        Route::post('/store', [SpdController::class, 'storeSurat'])->name('store');
        Route::get('/{surat}', [SpdController::class, 'showSuratRequester'])->name('show');
    });

    // ────────────────────────────────────────────────────────────────────────
    // PERPANJANGAN
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('perpanjangan')->name('requester.perpanjangan.')->middleware('permission:spd.view')->group(function () {
        Route::get('/main-perpanjangan', [SpdController::class, 'indexPerpanjanganRequester'])->name('index');
        Route::get('/create', [SpdController::class, 'createPerpanjangan'])->name('create');
        Route::post('/store', [SpdController::class, 'storePerpanjangan'])->name('store');
    });

    // ────────────────────────────────────────────────────────────────────────
    // LPJ (Laporan Pertanggungjawaban)
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('lpj')->name('requester.lpj.')->middleware('permission:lpj.create,lpj.view')->group(function () {
        Route::get('/main-lpj', [SpdController::class, 'indexLaporanRequester'])->name('index');
        Route::get('/create/{surat}', [SpdController::class, 'createLaporan'])->name('create');
        Route::post('/store', [SpdController::class, 'storeLaporan'])->name('store');
        Route::get('/show/{laporan}', [SpdController::class, 'showLaporanRequester'])->name('show');
        Route::get('/{laporan}/edit', [SpdController::class, 'editLaporanRequester'])->name('edit');
        Route::put('/{laporan}', [SpdController::class, 'updateLaporanRequester'])->name('update');
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
    Route::prefix('vehicle-check')->name('requester.')->group(function () {
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

// Admin Routes
Route::middleware(['auth', 'role:admin,superadmin'])->group(function () {
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

    // ────────────────────────────────────────────────────────────────────────
    // MASTER HARGA BBM (Master Gasoline) - Superadmin & Admin/GA
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('master-gasoline')->name('master-gasoline.')->group(function () {
        Route::get('/', [MasterGasolineController::class, 'index'])->name('index');
        Route::post('/sync', [MasterGasolineController::class, 'sync'])->name('sync');
    });
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

// export laporan transaksi bbm
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    // ==================== REPORTS & EXPORTS ====================
    Route::get('/print-report', [SuperadminController::class, 'printReport'])->name('superadmin.print-report');
    Route::get('/print-report/form', [SuperadminController::class, 'printReportPage'])->name('superadmin.print-report.form');
});

Route::middleware(['auth', 'role:superadmin'])->group(function () {

    // ==================== DASHBOARD ====================
    Route::get('/sa/dashboard', [SuperadminController::class, 'index'])->name('superadmin-dashboard');
    Route::get('/dashboard/stats', [SuperadminController::class, 'getDashboardStats'])->name('dashboard.stats');
    Route::get('/dashboard/driver-stats', [SuperadminController::class, 'getDetailedDriverStats'])->name('dashboard.driver-stats');

    // ==================== AI ASSISTANT (Gemini, masih pengembangan) ====================
    Route::get('/ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant.index');
    Route::post('/ai-assistant/send', [AiAssistantController::class, 'send'])->name('ai-assistant.send');
    Route::delete('/ai-assistant/sessions/{session}', [AiAssistantController::class, 'destroySession'])->name('ai-assistant.sessions.destroy');

    // ==================== USER MANAGEMENT ====================
    Route::get('users', [SuperadminController::class, 'indexUsers'])->name('users');
    Route::get('users/create', [SuperadminController::class, 'createUser'])->name('users.create');
    Route::post('users/store', [SuperadminController::class, 'storeUser'])->name('users.store');
    Route::post('users/{id}/update', [SuperadminController::class, 'updateUser'])->name('users.update');
    Route::post('users/{id}/delete', [SuperadminController::class, 'deleteUser'])->name('users.delete');
    Route::post('users/{id}/reset-password', [SuperadminController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{id}/restore', [SuperadminController::class, 'restoreUser'])->name('users.restore');

    // ==================== VEHICLE MANAGEMENT ====================
    Route::get('vehicles', [SuperadminController::class, 'indexVehicles'])->name('vehicles');
    Route::get('vehicles/create', [SuperadminController::class, 'createVehicle'])->name('vehicles.create');
    Route::get('vehicles/{id}/edit', [SuperadminController::class, 'editVehicle'])->name('vehicles.edit');
    Route::post('vehicles/store', [SuperadminController::class, 'storeVehicle'])->name('vehicles.store');
    Route::post('vehicles/{id}/update', [SuperadminController::class, 'updateVehicle'])->name('vehicles.update');
    Route::post('vehicles/{id}/delete', [SuperadminController::class, 'deleteVehicle'])->name('vehicles.delete');
    Route::post('vehicles/{id}/restore', [SuperadminController::class, 'restoreVehicle'])->name('vehicles.restore');

    // Vehicle Approval — DISEMBUNYIKAN: route dibuat inactive (tidak diakses siapa pun).
    // Route::get('/approval', [SuperadminController::class, 'approvalDashboard'])->name('superadmin.approval');
    Route::post('/vehicles/{id}/approve', [SuperadminController::class, 'approveVehicle'])->name('superadmin.vehicle.approve');
    Route::post('/vehicles/{id}/reject', [SuperadminController::class, 'rejectVehicle'])->name('superadmin.vehicle.reject');

    // Vehicle Activation
    Route::get('/vehicle-activation', [SuperadminController::class, 'vehicleActivation'])->name('superadmin.vehicle-activation');
    Route::post('/vehicle/{id}/activate', [SuperadminController::class, 'activateVehicle'])->name('superadmin.vehicle.activate');
    Route::post('/vehicle/{id}/deactivate', [SuperadminController::class, 'deactivateVehicle'])->name('superadmin.vehicle.deactivate');
    Route::post('/vehicle/bulk-activate', [SuperadminController::class, 'bulkActivateVehicles'])->name('superadmin.vehicle.bulk-activate');
    Route::post('/vehicle/bulk-deactivate', [SuperadminController::class, 'bulkDeactivateVehicles'])->name('superadmin.vehicle.bulk-deactivate');

    // ==================== VEHICLE DETAIL (DOKUMENTASI) ====================
    Route::get('/vehicles/{id}/detail', [SuperadminController::class, 'showVehicleDetail'])->name('superadmin.vehicle.detail');
    Route::get('/vehicles/{id}/detail/edit', [SuperadminController::class, 'editVehicleDetail'])->name('superadmin.vehicle.detail.edit');
    Route::post('/vehicles/{id}/detail/update', [SuperadminController::class, 'updateVehicleDetail'])->name('superadmin.vehicle.detail.update');

    // ==================== PERMOHONAN & LAPORAN ====================
    Route::get('/kelola-permohonan', [SuperadminController::class, 'indexPermohonan'])->name('superadmin.permohonan');
    Route::post('/permohonan/{id}/update', [SuperadminController::class, 'updatePermohonan'])->name('superadmin.permohonan.update');
    Route::post('/permohonan/{id}/cancel', [SuperadminController::class, 'cancelPermohonan'])->name('superadmin.permohonan.cancel');
    Route::post('/permohonan/{id}/delete', [SuperadminController::class, 'deletePermohonan'])->name('superadmin.permohonan.delete');
    Route::post('/kelola-permohonan/bulk-process', [SuperadminController::class, 'bulkProcessPermohonan'])->name('superadmin.permohonan.bulk-process');

    Route::post('/laporan/{id}/update', [SuperadminController::class, 'updateLaporan'])->name('superadmin.laporan.update');
    Route::post('/laporan/{id}/delete', [SuperadminController::class, 'deleteLaporan'])->name('superadmin.laporan.delete');

    // ==================== TRIP ANALYSIS & VALIDATION ====================
    Route::prefix('trip-analysis')->name('superadmin.trip-analysis.')->group(function () {
        // Trip Analysis Dashboard (Main View)
        Route::get('/', [SuperadminController::class, 'indexTripAnalysis'])->name('index');

        Route::post('/recalculate-all', [SuperadminController::class, 'recalculateAllTripMetrics'])->name('superadmin.trip-validations.recalculate-all');

    });

    // Trip Validations (di luar prefix karena beda resource)
    Route::post('/trip-validations/{id}/validate', [SuperadminController::class, 'validateTrip'])
        ->name('superadmin.trip-validations.validate');

    Route::post('/trip-validations/bulk-validate', [SuperadminController::class, 'bulkValidateTrips'])
        ->name('superadmin.trip-validations.bulk-validate');

    // ==================== API ENDPOINTS ====================
    Route::prefix('api')->name('superadmin.')->group(function () {
        Route::get('/dashboard/stats', [SuperadminController::class, 'getDashboardStats'])->name('dashboard.stats');
        Route::get('/dashboard/driver-statistics', [SuperadminController::class, 'getDriverStatistics'])->name('driver.statistics');
        Route::get('/dashboard/detailed-driver-stats', [SuperadminController::class, 'getDetailedDriverStats'])->name('dashboard.detailed-driver-stats');
        Route::get('/dashboard/vehicle-fuel-consumption', [SuperadminController::class, 'getVehicleFuelConsumption'])->name('dashboard.vehicle-fuel');
        Route::get('/dashboard/vehicle-km-performance', [SuperadminController::class, 'getVehicleKmPerformance'])->name('dashboard.vehicle-km');
        Route::get('/dashboard/pivot-table', [SuperadminController::class, 'getCompletePivotTable'])->name('pivot.data');
        // Route::get('/activity-log', [SuperadminController::class, 'getActivityLog'])->name('activity.log');
    });

    // ==================== DATA MART CONTEXT API ====================
    // Endpoint: GET /api/data-mart/{unitId}
    // Menghasilkan JSON konteks per Unit untuk AI (overview/sub_overview/detailing).
    // Cache harian 86400 detik via DataMartContextBuilder.
    Route::middleware(['auth', 'role:superadmin'])->prefix('api/data-mart')->name('api.datamart.')->group(function () {
        Route::get('/{unitId}', [App\Http\Controllers\Api\DataMartContextController::class, 'show'])
            ->name('context');
        Route::get('/{unitId}/refresh', [App\Http\Controllers\Api\DataMartContextController::class, 'refresh'])
            ->name('refresh');
    });

    // ==================== ANALYTICS ====================
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/fuel-consumption', [SuperadminController::class, 'getVehicleFuelConsumption'])->name('fuel-consumption');
        Route::get('/km-performance', [SuperadminController::class, 'getVehicleKmPerformance'])->name('km-performance');
        Route::get('/pivot-table', [SuperadminController::class, 'getCompletePivotTable'])->name('pivot-table');
        // Route::get('/request-analysis', [SuperadminController::class, 'getRequestAnalysis'])->name('request-analysis');
        // Route::get('/latest-odo', [SuperadminController::class, 'getLatestOdoPerVehicle'])->name('latest-odo');
        // Route::get('/monthly-summary', [SuperadminController::class, 'getMonthlyReportSummary'])->name('monthly-summary');
    });

    // ────────────────────────────────────────────────────────────────────────
    // SURAT SPD - Validasi & Shortcut Create
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('surat')->name('surat.superadmin.')->group(function () {
        Route::get('/', [SpdController::class, 'indexSuratSuperadmin'])->name('index');
        Route::get('/create-shortcut', [SpdController::class, 'createSuratSuperadmin'])->name('create-shortcut');
        Route::post('/store-shortcut', [SpdController::class, 'storeSuratSuperadmin'])->name('store-shortcut');
        Route::get('/{surat}', [SpdController::class, 'showSuratSuperadmin'])->name('show');
        Route::post('/{surat}/approve', [SpdController::class, 'approveSurat'])->name('approve');
        Route::post('/{surat}/reject', [SpdController::class, 'rejectSurat'])->name('reject');
        Route::get('/{surat}/print', [SpdController::class, 'printSurat'])->name('print');
        Route::post('/{surat}/save-mendadak-budget', [SpdController::class, 'saveMendadakBudget'])->name('save-mendadak-budget');
        Route::get('/report/spending', [SpdController::class, 'reportSpending'])->name('report');
    });

    // ────────────────────────────────────────────────────────────────────────
    // PERPANJANGAN - Validasi & Acknowledge
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('perpanjangan')->name('perpanjangan.superadmin.')->group(function () {
        Route::get('/', [SpdController::class, 'indexPerpanjanganSuperadmin'])->name('index');
        Route::get('/validate/{perpanjangan}', [SpdController::class, 'showPerpanjanganSuperadmin'])->name('show');
        Route::post('/{perpanjangan}/approve', [SpdController::class, 'approvePerpanjangan'])->name('approve');
        Route::post('/{perpanjangan}/reject', [SpdController::class, 'rejectPerpanjangan'])->name('reject');
        Route::post('/{perpanjangan}/acknowledge', [SpdController::class, 'acknowledgePerpanjangan'])->name('acknowledge');
    });

    // ────────────────────────────────────────────────────────────────────────
    // LPJ - Validasi
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('lpj')->name('lpj.superadmin.')->group(function () {
        Route::get('/', [SpdController::class, 'indexLaporanSuperadmin'])->name('index');
        Route::get('/{laporan}', [SpdController::class, 'showLaporanSuperadmin'])->name('show');
        Route::post('/{laporan}/approve', [SpdController::class, 'approveLaporan'])->name('approve');
        Route::post('/{laporan}/reject', [SpdController::class, 'rejectLaporan'])->name('reject');
        Route::get('/{laporan}/print', [SpdController::class, 'printLaporan'])->name('print');
        Route::put('/{laporan}/keuangan', [SpdController::class, 'updateKeuanganLaporan'])->name('keuangan');
    });

    // ────────────────────────────────────────────────────────────────────────
    // AJAX / API Endpoints
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('ajax')->name('ajax.superadmin.')->group(function () {
        Route::get('/dashboard-stats', [SpdController::class, 'getDashboardStats'])->name('dashboard.stats');
    });

    // ==================== RBAC — ROLE MANAGEMENT ====================
    Route::prefix('sa/rbac')->name('superadmin.rbac.')->middleware('permission:rbac.roles')->group(function () {
        Route::get('/', [RbacController::class, 'index'])->name('index');
        Route::get('/create', [RbacController::class, 'create'])->name('create');
        Route::post('/', [RbacController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [RbacController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [RbacController::class, 'update'])->name('update');
        Route::post('/{id}/delete', [RbacController::class, 'destroy'])->name('delete');
    });

    // ==================== UNIT BISNIS & SITE MANAGEMENT ====================
    Route::prefix('sa/units')->name('superadmin.units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::get('/create', [UnitController::class, 'create'])->name('create');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
        Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
        Route::post('/{unit}/delete', [UnitController::class, 'destroy'])->name('delete');
    });

    Route::prefix('sa/sites')->name('superadmin.sites.')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('index');
        Route::get('/create', [SiteController::class, 'create'])->name('create');
        Route::post('/', [SiteController::class, 'store'])->name('store');
        Route::get('/{site}/edit', [SiteController::class, 'edit'])->name('edit');
        Route::put('/{site}', [SiteController::class, 'update'])->name('update');
        Route::post('/{site}/delete', [SiteController::class, 'destroy'])->name('delete');
    });

    // ==================== ACTIVITY LOG ====================
    Route::get('/activity-log', [SuperadminController::class, 'getActivityLog'])->name('activity-log');

    // ────────────────────────────────────────────────────────────────────────
    // VEHICLE CHECKING SUPERADMIN - Health, Financial, Transaction Reports
    // ────────────────────────────────────────────────────────────────────────
    Route::prefix('vehicle-checking')->name('superadmin.')->group(function () {
        // Vehicle Health Matrix
        Route::get('/vehicle-health', [VehicleCheckingSuperadminController::class, 'vehicleHealthIndex'])
            ->name('vehicle-health.index');
        Route::get('/vehicle-health/{consumerialCode}', [VehicleCheckingSuperadminController::class, 'vehicleHealthShow'])
            ->name('vehicle-health.show');

        // Financial Report
        Route::get('/financial-report', [VehicleCheckingSuperadminController::class, 'financialReportIndex'])
            ->name('financial-report.index');
        Route::get('/financial-report/export', [VehicleCheckingSuperadminController::class, 'financialReportExport'])
            ->name('financial-report.export');

        // Transaction Report
        Route::get('/transaction-report', [VehicleCheckingSuperadminController::class, 'transactionReportIndex'])
            ->name('transaction-report.index');

        // Download barcode satu kendaraan (superadmin)
        Route::get('/barcode/download/{vehicleId}', [VehicleHealthHistoryController::class, 'downloadBarcode'])
            ->name('barcode.download');
        // Download semua barcode sebagai ZIP (superadmin only)
        Route::get('/barcode/download-all-zip', [VehicleHealthHistoryController::class, 'downloadAllBarcodesZip'])
            ->name('barcode.download-zip');
    });
});

Route::middleware(['auth', 'role:manager,superadmin'])->group(function () {
    Route::get('/dashboard-bisnis', [SuperadminController::class, 'dashboardBisnis'])
        ->name('superadmin.dashboard.bisnis');

});

// ────────────────────────────────────────────────────────────────────────
// BARCODE SCAN & RIWAYAT KENDARAAN — Permission-based (vehicle.checking)
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:vehicle.checking'])->prefix('dispatcher')->name('dispatcher.')->group(function () {
    // Scanner riwayat (eksisting) — scan untuk lihat riwayat pengecekan
    Route::get('/scan-vehicle', [VehicleHealthHistoryController::class, 'scanView'])
        ->name('scan-vehicle');
    Route::get('/scan-vehicle/result', [VehicleHealthHistoryController::class, 'vehicleHistory'])
        ->name('scan-vehicle.result');

    // Dispatcher Scanner (baru) — scan untuk memulai pemeriksaan
    Route::get('/dispatcher-scanner', [VehicleHealthHistoryController::class, 'dispatcherScanView'])
        ->name('dispatcher-scanner');
    Route::get('/dispatcher-scanner/result', [VehicleHealthHistoryController::class, 'dispatcherScanResult'])
        ->name('dispatcher-scanner.result');

    // ────────────────────────────────────────────────────────────────────────
    // BARCODE KENDARAAN - Download
    // ────────────────────────────────────────────────────────────────────────
    // Download barcode satu kendaraan (superadmin + dispatcher — dicek di controller)
    Route::get('/barcode/download/{vehicleId}', [VehicleHealthHistoryController::class, 'downloadBarcode'])
        ->name('barcode.download');
});

// Role Baru, Dispatcher Routes
Route::middleware(['auth', 'role:dispatcher,superadmin'])->prefix('dispatcher')->name('dispatcher.')->group(function () {
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

Route::middleware(['auth', 'role:dispatcher,superadmin'])->prefix('dispatcher')->name('dispatcher.')->group(function () {

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
Route::middleware(['auth', 'role:admin,superadmin'])->prefix('lunch')->name('lunch.')->group(function () {
    // Dashboard
    Route::get('/', [LunchDashboardController::class, 'index'])->name('dashboard');

    // Forecast (generate H+1, manual override)
    Route::get('/forecasts', [LunchForecastController::class, 'index'])->name('forecasts.index');
    Route::post('/forecasts/generate', [LunchForecastController::class, 'generate'])->name('forecasts.generate');
    Route::post('/forecasts/override', [LunchForecastController::class, 'override'])->name('forecasts.override');

    // Distribution (angka vendor per site) + export Excel
    Route::get('/distributions', [LunchDistributionController::class, 'index'])->name('distributions.index');
    Route::post('/distributions/generate', [LunchDistributionController::class, 'generate'])->name('distributions.generate');
    Route::post('/distributions/lock', [LunchDistributionController::class, 'lock'])->name('distributions.lock');
    Route::get('/export', [LunchDistributionController::class, 'exportForm'])->name('export-form');
    Route::post('/export', [LunchDistributionController::class, 'export'])->name('export');

    // QR lokasi (cetak untuk ditempel di site)
    Route::get('/qr-codes', [LunchDistributionController::class, 'qrCodes'])->name('qr-codes');
    Route::get('/qr-codes/{site}.svg', [LunchDistributionController::class, 'qrImage'])->name('qr-image');

    // Transactions (kehadiran QR / ground truth)
    Route::get('/transactions', [LunchTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/feed', [LunchTransactionController::class, 'feed'])->name('transactions.feed');

    // Analytics
    Route::get('/analytics', [LunchAnalyticsController::class, 'index'])->name('analytics.index');

    // Guest Reservation — kalender & konfirmasi tamu
    Route::get('/guests', [CateringGuestController::class, 'calendar'])->name('guests.calendar');
    Route::post('/guests', [CateringGuestController::class, 'store'])->name('guests.store');
    Route::put('/guests/{cateringGuest}', [CateringGuestController::class, 'update'])->name('guests.update');
    Route::delete('/guests/{cateringGuest}', [CateringGuestController::class, 'destroy'])->name('guests.destroy');
    Route::post('/guests/{cateringGuest}/h2-confirm', [CateringGuestController::class, 'h2Confirm'])->name('guests.h2-confirm');
    Route::post('/guests/{cateringGuest}/h2-cancel', [CateringGuestController::class, 'h2Cancel'])->name('guests.h2-cancel');

    // Self-Booking — booking mandiri makan siang (kalender multi-hari)
    Route::post('/self-booking', [SelfBookingController::class, 'store'])->name('self-booking.store');
    Route::get('/self-booking', [SelfBookingController::class, 'index'])->name('self-booking.index');
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
Route::prefix('my')->name('my.')->group(function () {
    Route::get('/lunch-plan', [SelfBookingController::class, 'index'])->name('lunch-plan');
    Route::post('/lunch-plan/store', [SelfBookingController::class, 'store'])->name('lunch-plan.store');
});

// ────────────────────────────────────────────────────────────────────────
// VENDOR CATERING — akses terbatas: daftar kehadiran lunch harian.
// Role vendor disiapkan; superadmin juga bisa mengakses. Menu sidebar
// disembunyikan sementara hingga siap diaktifkan.
// ────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:vendor,superadmin'])->prefix('vendor/lunch')->name('vendor.lunch.')->group(function () {
    Route::get('/today', [VendorLunchController::class, 'today'])->name('today');
});
