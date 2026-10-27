<?php

use App\Models\EmployeeDefault;
use App\Models\LunchCustomPurchase;
use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchVendor;
use App\Models\LunchVendorInvoice;
use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\Lunch\LunchDistributionService;
use Database\Seeders\RbacSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
    $this->seed(UnitSeeder::class);

    $this->unitPatuha = Unit::where('code', 'PATUHA')->firstOrFail();
    $this->unitDieng = Unit::where('code', 'DIENG')->firstOrFail();
    $this->unitPusat = Unit::where('code', 'PUSAT')->firstOrFail();

    $this->sitePth = Site::where('code', 'PTH')->firstOrFail();
    $this->sitePthBc = Site::where('code', 'PTH-BC')->firstOrFail();
    $this->siteDieng = Site::where('code', 'Dieng')->firstOrFail();
    $this->sitePusat = Site::where('code', 'Pusat')->firstOrFail();

    $this->gaPatuha = User::factory()->create([
        'role' => 'general_affairs',
        'unit_id' => $this->unitPatuha->id,
    ]);
    $this->gaPatuha->roles()->attach(
        Role::where('code', 'general_affairs')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->gaDieng = User::factory()->create([
        'role' => 'general_affairs',
        'unit_id' => $this->unitDieng->id,
    ]);
    $this->gaDieng->roles()->attach(
        Role::where('code', 'general_affairs')->firstOrFail()->id,
        ['is_primary' => true]
    );

    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'unit_id' => $this->unitPusat->id,
    ]);
    $this->superadmin->roles()->attach(
        Role::where('code', 'superadmin')->firstOrFail()->id,
        ['is_primary' => true]
    );
});

it('merender 4 halaman utama lunch dan sub-halaman Data Forecast tanpa error', function () {
    $this->actingAs($this->gaPatuha)->get(route('lunch.dashboard'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.forecasts.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.vendor-payments.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.custom-purchases.index'))->assertOk();

    $this->actingAs($this->gaPatuha)->get(route('lunch.employee-defaults.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.guests.calendar'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.distributions.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.qr-codes'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.transactions.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.analytics.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.self-booking.index'))->assertOk();
    $this->actingAs($this->gaPatuha)->get(route('lunch.export-form'))->assertOk();
});

it('sidebar admin GA hanya menampilkan 4 menu utama lunch', function () {
    $this->actingAs($this->gaPatuha)
        ->get(route('lunch.dashboard'))
        ->assertOk()
        ->assertSee('Data Forecast')
        ->assertSee('Data Pengeluaran & Pembayaran Vendor')
        ->assertSee('Custom Purchase')
        ->assertDontSee('Employee Default')
        ->assertDontSee('Guest Reservation')
        ->assertDontSee('Lunch Forecast')
        ->assertDontSee('Lunch Distribution')
        ->assertDontSee('Lunch Transaction');
});

it('membatasi master karyawan dan override ke unit user', function () {
    $employeePatuha = EmployeeDefault::create([
        'employee_name' => 'Pegawai Patuha',
        'default_site_id' => $this->sitePth->id,
        'unit_id' => $this->unitPatuha->id,
        'is_active' => true,
        'verification_status' => 'verified',
    ]);
    $employeeDieng = EmployeeDefault::create([
        'employee_name' => 'Pegawai Dieng',
        'default_site_id' => $this->siteDieng->id,
        'unit_id' => $this->unitDieng->id,
        'is_active' => true,
        'verification_status' => 'verified',
    ]);

    $this->actingAs($this->gaPatuha)
        ->get(route('lunch.employee-defaults.index'))
        ->assertOk()
        ->assertSee('Pegawai Patuha')
        ->assertDontSee('Pegawai Dieng');

    $date = now()->addDay()->toDateString();

    // Override ke site unit lain harus ditolak.
    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.forecasts.override'), [
            'slug' => $employeePatuha->slug,
            'date' => $date,
            'site_id' => $this->siteDieng->id,
        ])
        ->assertSessionHas('error');

    expect(LunchForecast::where('employee_default_id', $employeePatuha->id)->exists())->toBeFalse();

    // Override ke site unit sendiri berhasil.
    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.forecasts.override'), [
            'slug' => $employeePatuha->slug,
            'date' => $date,
            'site_id' => $this->sitePthBc->id,
        ])
        ->assertSessionHas('success');

    expect(LunchForecast::where('employee_default_id', $employeePatuha->id)
        ->whereDate('lunch_date', $date)
        ->value('predicted_site_id'))->toBe($this->sitePthBc->id);

    $this->actingAs($this->gaPatuha)
        ->get(route('lunch.forecasts.index', ['date' => $date]))
        ->assertOk()
        ->assertSee('Pegawai Patuha')
        ->assertDontSee('Pegawai Dieng');

    // Admin unit lain tidak boleh mengubah karyawan Patuha.
    $this->actingAs($this->gaDieng)
        ->post(route('lunch.forecasts.override'), [
            'slug' => $employeePatuha->slug,
            'date' => $date,
            'site_id' => $this->siteDieng->id,
        ])
        ->assertForbidden();
});

it('self booking menolak site di luar unit karyawan', function () {
    $employee = EmployeeDefault::create([
        'employee_name' => 'Pegawai Patuha',
        'default_site_id' => $this->sitePth->id,
        'unit_id' => $this->unitPatuha->id,
        'is_active' => true,
    ]);

    $date = now()->addDay()->toDateString();

    $this->actingAs($this->gaPatuha)
        ->post(route('my.lunch-plan.store'), [
            'slug' => $employee->slug,
            'bookings' => [
                ['date' => $date, 'site_id' => $this->siteDieng->id],
            ],
        ])
        ->assertRedirect();

    expect(LunchForecast::where('employee_default_id', $employee->id)
        ->whereDate('lunch_date', $date)
        ->exists())->toBeFalse();

    $this->actingAs($this->gaPatuha)
        ->post(route('my.lunch-plan.store'), [
            'slug' => $employee->slug,
            'bookings' => [
                ['date' => $date, 'site_id' => $this->sitePth->id],
            ],
        ])
        ->assertRedirect();

    expect(LunchForecast::where('employee_default_id', $employee->id)
        ->whereDate('lunch_date', $date)
        ->where('prediction_source', 'self_booking')
        ->value('predicted_site_id'))->toBe($this->sitePth->id);
});

it('scan pegawai terdaftar hanya menawarkan site unitnya dan menyimpan forecast besok', function () {
    $this->get(route('lunch.attendance.form', $this->sitePth->code))
        ->assertOk()
        ->assertSee('Absen Makan Siang');

    $employee = EmployeeDefault::create([
        'employee_name' => 'Pegawai Patuha Scan',
        'default_site_id' => $this->sitePth->id,
        'unit_id' => $this->unitPatuha->id,
        'is_active' => true,
    ]);

    $response = $this->postJson(route('lunch.attendance.submit'), [
        'site_code' => $this->sitePth->code,
        'name' => $employee->employee_name,
        'employee_id' => $employee->id,
    ])->assertOk();

    $tomorrowSiteIds = collect($response->json('tomorrow_sites'))->pluck('id')->all();

    expect($tomorrowSiteIds)->toContain($this->sitePth->id)
        ->not->toContain($this->siteDieng->id);

    $transactionId = $response->json('transaction_id');
    $token = $response->json('token');

    $this->postJson(url("lunch-attendance/{$transactionId}/tomorrow"), [
        'token' => $token,
        'site_id' => $this->sitePthBc->id,
    ])->assertOk();

    expect(LunchForecast::where('employee_default_id', $employee->id)
        ->whereDate('lunch_date', now()->addDay()->toDateString())
        ->where('prediction_source', 'confirmation')
        ->value('predicted_site_id'))->toBe($this->sitePthBc->id);

    // Site luar unit ditolak.
    $this->postJson(url("lunch-attendance/{$transactionId}/tomorrow"), [
        'token' => $token,
        'site_id' => $this->siteDieng->id,
    ])->assertStatus(422);
});

it('scan pegawai non-SSO mendaftarkan akun pending + employee pending', function () {
    $response = $this->postJson(route('lunch.attendance.submit'), [
        'site_code' => $this->sitePth->code,
        'name' => 'Pegawai Baru Non SSO',
        'division' => 'Operasi',
        'unit_id' => $this->unitPatuha->id,
    ])->assertOk();

    $employee = EmployeeDefault::where('employee_name', 'Pegawai Baru Non SSO')->firstOrFail();

    expect($employee->unit_id)->toBe($this->unitPatuha->id)
        ->and($employee->verification_status)->toBe('pending')
        ->and($employee->source)->toBe('scan')
        ->and($employee->user_id)->not->toBeNull();

    $user = $employee->user;
    expect($user->is_active)->toBeFalse()
        ->and($user->registration_source)->toBe('scan')
        ->and($user->hasOperationalCredentials())->toBeFalse()
        ->and($response->json('account_pending'))->toBeTrue();
});

it('generate invoice vendor menghitung porsi dan pembayaran mengubah status', function () {
    $employee = EmployeeDefault::create([
        'employee_name' => 'Pegawai Patuha',
        'default_site_id' => $this->sitePth->id,
        'unit_id' => $this->unitPatuha->id,
        'is_active' => true,
    ]);

    $date = now()->toDateString();

    LunchForecast::create([
        'employee_default_id' => $employee->id,
        'lunch_date' => $date,
        'predicted_site_id' => $this->sitePth->id,
        'prediction_source' => 'manual',
        'confidence' => 100,
    ]);

    app(LunchDistributionService::class)->buildForDate($date, $this->gaPatuha, [$this->sitePth->id]);

    LunchDistribution::where('site_id', $this->sitePth->id)
        ->whereDate('distribution_date', $date)
        ->update(['total_count' => 20]);

    $vendor = LunchVendor::create([
        'unit_id' => $this->unitPatuha->id,
        'name' => 'Vendor Patuha',
        'price_per_portion' => 25000,
        'is_active' => true,
    ]);

    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.vendor-payments.generate'), [
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'site_id' => $this->sitePth->id,
        ])
        ->assertSessionHas('success');

    $invoice = LunchVendorInvoice::where('vendor_id', $vendor->id)->firstOrFail();

    expect($invoice->total_portion)->toBe(20)
        ->and((float) $invoice->total_amount)->toBe(500000.0);

    // Pembayaran sebagian.
    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.vendor-payments.payments.store', $invoice), [
            'amount' => 200000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ])->assertSessionHas('success');

    expect($invoice->fresh()->status)->toBe(LunchVendorInvoice::STATUS_PARTIAL);

    // Pelunasan.
    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.vendor-payments.payments.store', $invoice), [
            'amount' => 300000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ])->assertSessionHas('success');

    expect($invoice->fresh()->status)->toBe(LunchVendorInvoice::STATUS_PAID);

    $this->actingAs($this->gaPatuha)
        ->get(route('lunch.vendor-payments.index'))
        ->assertOk()
        ->assertSee('Vendor Patuha');

    $this->actingAs($this->gaPatuha)
        ->get(route('lunch.vendor-payments.show', $invoice))
        ->assertOk()
        ->assertSee('Vendor Patuha');

    // Unit lain tidak boleh melihat invoice Patuha.
    $this->actingAs($this->gaDieng)
        ->get(route('lunch.vendor-payments.show', $invoice))
        ->assertForbidden();
});

it('custom purchase menggantikan porsi katering lalu kembali saat dibatalkan', function () {
    $employee = EmployeeDefault::create([
        'employee_name' => 'Pegawai Patuha',
        'default_site_id' => $this->sitePth->id,
        'unit_id' => $this->unitPatuha->id,
        'is_active' => true,
    ]);

    $date = now()->addDay()->toDateString();

    LunchForecast::create([
        'employee_default_id' => $employee->id,
        'lunch_date' => $date,
        'predicted_site_id' => $this->sitePth->id,
        'prediction_source' => 'manual',
        'confidence' => 100,
    ]);

    app(LunchDistributionService::class)->buildForDate($date, $this->gaPatuha, [$this->sitePth->id]);

    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.custom-purchases.store'), [
            'site_id' => $this->sitePth->id,
            'purchase_date' => $date,
            'title' => 'Apresiasi Pegawai',
            'replaces_catering' => 1,
            'items' => [
                ['item_name' => 'Paket Makan', 'qty' => 10, 'unit_price' => 50000],
            ],
        ])
        ->assertSessionHas('success');

    $purchase = LunchCustomPurchase::where('title', 'Apresiasi Pegawai')->firstOrFail();

    expect((float) $purchase->total_amount)->toBe(500000.0);

    $this->actingAs($this->gaPatuha)
        ->get(route('lunch.custom-purchases.index'))
        ->assertOk()
        ->assertSee('Apresiasi Pegawai');

    $distribution = LunchDistribution::where('site_id', $this->sitePth->id)
        ->whereDate('distribution_date', $date)
        ->firstOrFail();

    expect($distribution->is_custom_purchase)->toBeTrue()
        ->and($distribution->total_count)->toBe(0);

    $this->actingAs($this->gaPatuha)
        ->post(route('lunch.custom-purchases.cancel', $purchase))
        ->assertSessionHas('success');

    $distribution = $distribution->fresh();

    expect($distribution->is_custom_purchase)->toBeFalse()
        ->and($distribution->total_count)->toBe(1);
});


it('superadmin unit bisa menyimpan dan membuka kredensial user unitnya', function () {
    $user = User::factory()->create([
        'role' => 'pegawai',
        'unit_id' => $this->unitPatuha->id,
        'is_active' => false,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('users.credentials.update', $user->id), [
            'password' => 'rahasia123',
        ])
        ->assertRedirect();

    expect($user->fresh()->hasOperationalCredentials())->toBeTrue();

    $response = $this->actingAs($this->superadmin)
        ->getJson(route('users.credentials.reveal', $user->id))
        ->assertOk();

    expect($response->json('password'))->toBe('rahasia123');
    expect($user->fresh()->credentials_reveal_count)->toBe(1);
});
