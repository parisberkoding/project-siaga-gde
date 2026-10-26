<?php

use App\Models\EmployeeDefault;
use App\Models\Role;
use App\Models\SsoIdentity;
use App\Models\SsoRoleMapping;
use App\Models\SsoSyncLog;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sso\SsoAuthenticator;
use App\Services\Sso\SsoException;
use App\Services\Sso\SsoUserInfo;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RbacSeeder::class);
    $this->seed(\Database\Seeders\UnitSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);

    $this->patuha = Unit::where('code', 'PATUHA')->first();
    $this->pusat = Unit::where('code', 'PUSAT')->first();

    $this->auth = app(SsoAuthenticator::class);
});

/** Bangun data user SSO. */
function ssoInfo(array $override = []): SsoUserInfo
{
    $defaults = [
        'subject' => 'idp-1001',
        'name' => 'Budi Hartono',
        'email' => 'budi.hartono@geodipa.test',
        'unitCode' => 'PATUHA',
        'jabatan' => 'Staff',
        'nik' => '8412',
        'division' => null,
        'raw' => ['sub' => 'idp-1001', 'name' => 'Budi Hartono', 'jabatan' => 'Staff', 'unit' => 'PATUHA'],
        'provider' => 'geodipa',
    ];

    $data = array_merge($defaults, $override);

    return new SsoUserInfo(
        subject: $data['subject'],
        name: $data['name'],
        email: $data['email'],
        unitCode: $data['unitCode'],
        jabatan: $data['jabatan'],
        nik: $data['nik'],
        division: $data['division'],
        raw: $data['raw'],
        provider: $data['provider'],
    );
}

/** Buat mapping jabatan + unit -> role. */
function mapJabatan(string $jabatan, Unit $unit, string $roleCode): SsoRoleMapping
{
    return SsoRoleMapping::create([
        'jabatan' => $jabatan,
        'unit_id' => $unit->id,
        'role_id' => Role::where('code', $roleCode)->first()->id,
        'is_active' => true,
    ]);
}

/* =========================================================
 * JIT provisioning
 * =======================================================*/

it('membuat akun, identitas SSO, dan Master Pekerja saat login pertama', function () {
    mapJabatan('Staff', $this->patuha, 'requester');

    $result = $this->auth->authenticate(ssoInfo());

    expect($result->canLogin())->toBeTrue();

    $user = $result->user;

    expect($user->auth_provider)->toBe('sso');
    expect($user->is_active)->toBeTrue();
    expect($user->unit_id)->toBe($this->patuha->id);
    expect($user->nik)->toBe('8412');

    // identitas SSO tersimpan
    expect(SsoIdentity::where('user_id', $user->id)->where('subject', 'idp-1001')->exists())->toBeTrue();

    // Master Pekerja WAJIB ada — absensi Lunch me-resolve nama ke sini
    $emp = EmployeeDefault::where('user_id', $user->id)->first();
    expect($emp)->not->toBeNull();
    expect($emp->employee_name)->toBe('Budi Hartono');
    expect($emp->nik)->toBe('8412');
});

it('memberi role dari mapping saat provisioning awal', function () {
    mapJabatan('Staff', $this->patuha, 'requester');

    $result = $this->auth->authenticate(ssoInfo());
    $user = $result->user;

    expect($result->status)->toBe('authenticated');
    expect($user->roles()->pluck('code')->all())->toBe(['requester']);
    expect($user->hasPermission('bbm.request'))->toBeTrue();
});

it('memberi nol permission bila jabatan belum dimapping', function () {
    // sengaja tidak membuat mapping

    $result = $this->auth->authenticate(ssoInfo());

    expect($result->canLogin())->toBeTrue();          // akun aktif
    expect($result->status)->toBe('needs_review');
    expect($result->user->getPermissionCodes())->toBe([]);   // tetapi nol permission
    expect($result->user->roles()->count())->toBe(0);

    expect(SsoSyncLog::where('decision', SsoSyncLog::DECISION_UNMAPPED)->exists())->toBeTrue();
});

it('memberi nol permission bila kode unit tidak dikenal', function () {
    mapJabatan('Staff', $this->patuha, 'requester');

    $result = $this->auth->authenticate(ssoInfo([
        'subject' => 'idp-2002',
        'email' => 'asing@geodipa.test',
        'nik' => '9999',
        'unitCode' => 'UNIT-YANG-TIDAK-ADA',
    ]));

    expect($result->status)->toBe('needs_review');
    expect($result->user->unit_id)->toBeNull();
    expect($result->user->getPermissionCodes())->toBe([]);
});

it('TIDAK menebak unit Pusat saat kode unit tidak dikenal', function () {
    // Menebak ke Pusat akan memberi bypass gate modul — ini lubang eskalasi.
    mapJabatan('Staff', $this->pusat, 'superadmin');

    $result = $this->auth->authenticate(ssoInfo([
        'subject' => 'idp-3003',
        'email' => 'tanpaunit@geodipa.test',
        'nik' => '7777',
        'unitCode' => 'TIDAK-DIKENAL',
    ]));

    expect($result->user->unit_id)->not->toBe($this->pusat->id);
    expect($result->user->unit_id)->toBeNull();
    expect($result->user->unitIsCentral())->toBeFalse();
});

it('tidak membuat Master Pekerja ganda untuk NIK yang sama', function () {
    // Baris Master Pekerja manual (outsourcing), belum tertaut ke akun
    $manual = EmployeeDefault::create([
        'employee_name' => 'Budi Hartono',
        'nik' => '8412',
        'position' => 'Operator',
        'default_site_id' => $this->patuha->sites()->value('sites.id'),
        'is_active' => true,
    ]);

    mapJabatan('Staff', $this->patuha, 'requester');

    $result = $this->auth->authenticate(ssoInfo());

    expect(EmployeeDefault::count())->toBe(1);
    expect($manual->fresh()->user_id)->toBe($result->user->id);
});

/* =========================================================
 * Deaktivasi & konflik
 * =======================================================*/

it('menolak login user yang dinonaktifkan lokal dan mencatat konflik', function () {
    mapJabatan('Staff', $this->patuha, 'requester');

    $first = $this->auth->authenticate(ssoInfo());
    $user = $first->user;

    $user->deactivateAccount('Kontrak berakhir');

    expect(fn () => $this->auth->authenticate(ssoInfo()))
        ->toThrow(SsoException::class);

    // status TIDAK diubah otomatis — Pusat yang memutuskan
    expect($user->fresh()->is_active)->toBeFalse();
    expect(SsoSyncLog::where('decision', SsoSyncLog::DECISION_CONFLICT)->exists())->toBeTrue();
});

it('JIT tidak menghidupkan kembali akun yang dinonaktifkan', function () {
    $user = User::factory()->create([
        'role' => 'requester',
        'auth_provider' => 'local',
        'is_active' => false,
        'nik' => '5555',
        'email' => 'lama@geodipa.test',
    ]);

    mapJabatan('Staff', $this->patuha, 'requester');

    expect(fn () => $this->auth->authenticate(ssoInfo([
        'subject' => 'idp-5005',
        'email' => 'lama@geodipa.test',
        'nik' => '5555',
    ])))->toThrow(SsoException::class);

    expect($user->fresh()->is_active)->toBeFalse();
});

/* =========================================================
 * Kebijakan perubahan role (asimetris)
 * =======================================================*/

it('menerapkan downgrade role secara otomatis', function () {
    // Mapping awal: manager (banyak permission)
    mapJabatan('Manager', $this->patuha, 'manager');
    $first = $this->auth->authenticate(ssoInfo(['jabatan' => 'Manager']));
    $user = $first->user;

    expect($user->roles()->pluck('code')->all())->toBe(['manager']);

    // Jabatan turun -> view_only (lebih sedikit permission)
    mapJabatan('Staff', $this->patuha, 'view_only');
    $second = $this->auth->authenticate(ssoInfo(['jabatan' => 'Staff', 'subject' => 'idp-1001']));

    expect($second->status)->toBe('authenticated');
    expect($user->fresh()->roles()->pluck('code')->all())->toBe(['view_only']);
});

it('menahan upgrade role untuk direview Pusat', function () {
    mapJabatan('Staff', $this->patuha, 'view_only');
    $first = $this->auth->authenticate(ssoInfo());
    $user = $first->user;

    expect($user->roles()->pluck('code')->all())->toBe(['view_only']);

    // Jabatan naik -> manager (lebih banyak permission)
    mapJabatan('Manager', $this->patuha, 'manager');
    $second = $this->auth->authenticate(ssoInfo(['jabatan' => 'Manager']));

    expect($second->status)->toBe('needs_review');

    // Role lama DIPERTAHANKAN sampai Pusat menyetujui
    expect($user->fresh()->roles()->pluck('code')->all())->toBe(['view_only']);

    $log = SsoSyncLog::where('decision', SsoSyncLog::DECISION_PENDING_REVIEW)->first();
    expect($log)->not->toBeNull();
    expect($log->mapped_role_id)->toBe(Role::where('code', 'manager')->first()->id);
});

/* =========================================================
 * Pencocokan & normalisasi
 * =======================================================*/

it('menautkan akun SIAGA yang sudah ada lewat NIK', function () {
    $existing = User::factory()->create([
        'role' => 'requester',
        'auth_provider' => 'local',
        'nik' => '8412',
        'email' => 'budi.hartono@geodipa.test',
        'unit_id' => $this->patuha->id,
    ]);

    mapJabatan('Staff', $this->patuha, 'requester');

    $result = $this->auth->authenticate(ssoInfo());

    expect($result->user->id)->toBe($existing->id);
    expect($result->user->auth_provider)->toBe('sso');
    expect(User::count())->toBe(1);
});

it('mencocokkan jabatan tanpa peduli besar-kecil huruf dan spasi', function () {
    mapJabatan('Asisten Manager', $this->patuha, 'manager');

    $result = $this->auth->authenticate(ssoInfo(['jabatan' => '  asisten   manager ']));

    expect($result->status)->toBe('authenticated');
    expect($result->user->roles()->pluck('code')->all())->toBe(['manager']);
});

it('menolak klaim SSO tanpa identitas unik', function () {
    expect(fn () => $this->auth->authenticate(ssoInfo(['subject' => ''])))
        ->toThrow(SsoException::class);

    expect(SsoSyncLog::where('decision', SsoSyncLog::DECISION_REJECTED)->exists())->toBeTrue();
});

it('mencatat unit berpindah di audit', function () {
    mapJabatan('Staff', $this->patuha, 'requester');
    mapJabatan('Staff', $this->pusat, 'requester');

    $this->auth->authenticate(ssoInfo());
    $this->auth->authenticate(ssoInfo(['unitCode' => 'PUSAT']));

    expect(User::where('nik', '8412')->first()->unit_id)->toBe($this->pusat->id);

    $log = SsoSyncLog::where('note', 'like', 'Unit berpindah%')->first();
    expect($log)->not->toBeNull();
    expect($log->note)->toContain('PATUHA');
    expect($log->note)->toContain('PUSAT');
});

it('menyimpan klaim mentah untuk penelusuran', function () {
    mapJabatan('Staff', $this->patuha, 'requester');

    $this->auth->authenticate(ssoInfo());

    $identity = SsoIdentity::where('subject', 'idp-1001')->first();
    expect($identity->claims)->toBeArray();
    expect($identity->claims['jabatan'])->toBe('Staff');
});

/* =========================================================
 * Login lokal
 * =======================================================*/

it('menolak user SSO login lewat form lokal', function () {
    mapJabatan('Staff', $this->patuha, 'requester');
    $this->auth->authenticate(ssoInfo());

    $user = User::where('nik', '8412')->first();

    $this->post(route('login-process'), [
        'username' => $user->username,
        'password' => 'apa-saja',
    ])->assertSessionHasErrors('username');

    expect(auth()->check())->toBeFalse();
});

it('menolak login user yang dinonaktifkan', function () {
    $user = User::factory()->create([
        'role' => 'requester',
        'auth_provider' => 'local',
        'is_active' => false,
        'password' => bcrypt('rahasia123'),
    ]);

    $this->post(route('login-process'), [
        'username' => $user->username,
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('username');

    expect(auth()->check())->toBeFalse();
});

it('memutus sesi berjalan begitu akun dinonaktifkan', function () {
    $user = User::factory()->create([
        'role' => 'requester',
        'auth_provider' => 'local',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk();

    $user->deactivateAccount('Kontrak berakhir');

    $this->actingAs($user->fresh())
        ->get(route('profile'))
        ->assertRedirect(route('login'));
});

/* =========================================================
 * Route SSO
 * =======================================================*/

it('menolak alur SSO selama belum diaktifkan', function () {
    config(['sso.enabled' => false]);

    $this->get(route('sso.redirect'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('menolak callback dengan state yang tidak sah', function () {
    config(['sso.enabled' => true]);

    $this->get(route('sso.callback', ['state' => 'palsu', 'code' => 'abc']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect(SsoSyncLog::where('decision', SsoSyncLog::DECISION_REJECTED)->exists())->toBeTrue();
});

/* =========================================================
 * UI pemetaan jabatan -> role
 * =======================================================*/

/** Superadmin uji dengan unit tertentu. */
function superadminDi(?Unit $unit): User
{
    $user = User::factory()->create(['role' => 'superadmin', 'unit_id' => $unit?->id]);
    $user->roles()->attach(Role::where('code', 'superadmin')->first()->id, ['is_primary' => true]);

    return User::find($user->id);
}

it('menampilkan halaman pemetaan jabatan untuk superadmin Pusat', function () {
    $this->actingAs(superadminDi(null))
        ->get(route('superadmin.sso.mappings.index'))
        ->assertOk()
        ->assertSee('Pemetaan Jabatan SSO')
        ->assertSee('Perlu Dipetakan');
});

it('membuat pemetaan jabatan ke role', function () {
    $this->actingAs(superadminDi(null))
        ->post(route('superadmin.sso.mappings.store'), [
            'jabatan' => 'Asisten Manager',
            'unit_id' => $this->patuha->id,
            'role_id' => Role::where('code', 'manager')->first()->id,
        ])
        ->assertRedirect(route('superadmin.sso.mappings.index'));

    $mapping = SsoRoleMapping::first();
    expect($mapping->jabatan)->toBe('Asisten Manager');
    expect($mapping->jabatan_key)->toBe('asisten manager');
    expect($mapping->role->code)->toBe('manager');
});

it('menolak pemetaan ganda untuk jabatan yang hanya beda besar-kecil huruf', function () {
    $admin = superadminDi(null);
    $roleId = Role::where('code', 'requester')->first()->id;

    $this->actingAs($admin)->post(route('superadmin.sso.mappings.store'), [
        'jabatan' => 'Asisten Manager',
        'unit_id' => $this->patuha->id,
        'role_id' => $roleId,
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('superadmin.sso.mappings.store'), [
        'jabatan' => 'asisten   manager',   // sama setelah dinormalisasi
        'unit_id' => $this->patuha->id,
        'role_id' => $roleId,
    ])->assertSessionHas('error');

    expect(SsoRoleMapping::count())->toBe(1);
});

it('menolak superadmin unit non-Pusat membuka pemetaan jabatan', function () {
    $this->actingAs(superadminDi($this->patuha))
        ->get(route('superadmin.sso.mappings.index'))
        ->assertForbidden();
});

it('menampilkan kombinasi yang belum dipetakan di antrian review', function () {
    // User SSO tanpa role: muncul di antrian
    User::factory()->create([
        'role' => '',
        'auth_provider' => 'sso',
        'jabatan' => 'Teknisi',
        'unit_id' => $this->patuha->id,
    ]);

    $this->actingAs(superadminDi(null))
        ->get(route('superadmin.sso.mappings.index'))
        ->assertOk()
        ->assertSee('Teknisi');
});

it('pemetaan yang dibuat langsung berlaku untuk login berikutnya', function () {
    $this->actingAs(superadminDi(null))->post(route('superadmin.sso.mappings.store'), [
        'jabatan' => 'Staff',
        'unit_id' => $this->patuha->id,
        'role_id' => Role::where('code', 'requester')->first()->id,
    ])->assertRedirect();

    $result = $this->auth->authenticate(ssoInfo());

    expect($result->status)->toBe('authenticated');
    expect($result->user->roles()->pluck('code')->all())->toBe(['requester']);
});

it('pemetaan nonaktif tidak dipakai', function () {
    $mapping = mapJabatan('Staff', $this->patuha, 'requester');
    $mapping->update(['is_active' => false]);

    $result = $this->auth->authenticate(ssoInfo());

    expect($result->status)->toBe('needs_review');
    expect($result->user->getPermissionCodes())->toBe([]);
});

it('model user yang baru dibuat di memori sudah dianggap aktif', function () {
    // Regresi: `default(true)` pada migrasi hanya berlaku saat INSERT. Model
    // yang belum dibaca dari database tidak punya atribut is_active, sehingga
    // sebelumnya terbaca null -> cast false -> middleware menganggap akun
    // nonaktif dan memaksa logout. Bug ini sempat melumpuhkan 136 test.
    $user = new User(['username' => 'user.baru', 'role' => 'requester']);

    expect($user->is_active)->toBeTrue();
    expect($user->isActiveAccount())->toBeTrue();
    expect($user->usesSso())->toBeFalse();

    // Begitu pula hasil create() tanpa menyebut is_active.
    $created = User::factory()->create(['role' => 'requester']);
    expect($created->is_active)->toBeTrue();

    $fresh = User::find($created->id);
    expect($fresh->is_active)->toBeTrue();
});
