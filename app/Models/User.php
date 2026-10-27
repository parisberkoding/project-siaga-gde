<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\ModuleGate;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, softDeletes;

    /**
     * `auth_provider` SENGAJA tidak ada di sini.
     *
     * Nilainya ditentukan oleh jalur login (SSO vs lokal), bukan oleh input
     * form — kalau bisa di-mass-assign, sebuah form bisa "menaikkan" user lokal
     * menjadi user SSO atau sebaliknya dan melewati penolakan login.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_code',
        'nik',
        'username',
        'password',
        'nama_lengkap',
        'role',
        'jabatan',
        'default_site_id',
        'unit_id',
        'email',
        'is_active',
        'added_at',
        'registration_source',
    ];

    /**
     * Nilai default atribut untuk model yang belum dibaca dari database.
     *
     * PENTING: `default(true)` pada migrasi HANYA berlaku saat INSERT — model
     * yang baru dibuat di memori (mis. `User::create()` atau hasil factory)
     * tidak memiliki atribut `is_active` sama sekali, sehingga membacanya
     * menghasilkan null. Sebelum ini null tersebut ter-cast menjadi false dan
     * middleware EnsureAccountIsActive menganggap akunnya nonaktif, sehingga
     * user langsung ter-logout pada request berikutnya.
     *
     * Menegaskan default di sini membuat perilaku model konsisten dengan
     * perilaku database pada semua keadaan.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'auth_provider' => 'local',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'credentials_plain',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'sso_synced_at' => 'datetime',
            'credentials_plain' => 'encrypted',
            'credentials_set_at' => 'datetime',
            'credentials_last_revealed_at' => 'datetime',
        ];
    }

    /**
     * Gunakan user_code sebagai route key agar ID tidak tampil di URL.
     */
    public function getRouteKeyName(): string
    {
        return 'user_code';
    }

    /**
     * Auto-generate user_code saat creating jika belum ada.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->user_code)) {
                $lastId = self::withTrashed()->max('id') ?? 0;
                $user->user_code = 'USR-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function permohonans()
    {
        return $this->hasMany(Permohonan::class);
    }

    /**
     * Relasi ke Permohonan sebagai authorizer
     */
    public function authorizedPermohonans()
    {
        return $this->hasMany(Permohonan::class, 'authorizer_id');
    }

    /**
     * Relasi ke VehicleAndToolsConsumers yang dibuat user
     */
    public function createdVehicles()
    {
        return $this->hasMany(VehicleAndToolsConsumers::class, 'created_by');
    }

    /**
     * Site fallback ketika belum ada manual override atau historical
     * pattern yang berlaku untuk user ini.
     */
    public function defaultSite()
    {
        return $this->belongsTo(Site::class, 'default_site_id');
    }

    /**
     * Unit bisnis (mis. Patuha, Dieng, Pusat). Null = global/tanpa scope.
     * Dipakai untuk membatasi data yang terlihat oleh GA unit.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Apakah unit user adalah unit Pusat (is_central).
     * Unit Pusat TIDAK terkena gate modul.
     */
    public function unitIsCentral(): bool
    {
        if ($this->unitIsCentralCache === null) {
            $this->unitIsCentralCache = (bool) ($this->unit?->is_central);
        }

        return $this->unitIsCentralCache;
    }

    protected ?bool $unitIsCentralCache = null;

    /** @var array<int, string>|null */
    protected ?array $roleCodesCache = null;

    /**
     * Kode role efektif: kolom legacy `role` + role RBAC.
     *
     * @return array<int, string>
     */
    public function roleCodes(): array
    {
        if ($this->roleCodesCache === null) {
            $codes = $this->roles()->pluck('code')->all();

            if (! empty($this->role)) {
                $codes[] = (string) $this->role;
            }

            $this->roleCodesCache = array_values(array_unique($codes));
        }

        return $this->roleCodesCache;
    }

    public function hasRoleCode(string|array $codes): bool
    {
        $codes = is_array($codes) ? $codes : [$codes];

        return count(array_intersect($codes, $this->roleCodes())) > 0;
    }

    /**
     * Apakah user boleh melihat SELURUH unit (tanpa batas data)?
     *
     * Aturan yang disepakati:
     *   1. `unit_id` kosong        -> global (masa transisi backfill);
     *   2. role `developers`       -> global (tim pengembang);
     *   3. role `superadmin` yang unitnya Pusat (is_central) -> global.
     *
     * Role lain yang kebetulan berada di unit Pusat (mis. General Affairs
     * Pusat) TETAP dibatasi ke site unit Pusat. Ini mencegah "Pusat" menjadi
     * jalan pintas untuk melihat data seluruh unit.
     */
    public function hasGlobalUnitScope(): bool
    {
        if ($this->unit_id === null) {
            return true;
        }

        if ($this->hasRoleCode('developers')) {
            return true;
        }

        return $this->unitIsCentral() && $this->hasRoleCode('superadmin');
    }

    /**
     * ID site milik unit user, atau null bila TIDAK dibatasi.
     *
     * null = global (lihat hasGlobalUnitScope()).
     */
    public function unitSiteIds(): ?array
    {
        if ($this->hasGlobalUnitScope()) {
            return null;
        }

        if (! $this->unit_id) {
            return null;
        }

        return $this->unit?->sites()->pluck('sites.id')->all() ?: [];
    }

    /**
     * Kode site milik unit user, atau null bila tidak dibatasi.
     */
    public function unitSiteCodes(): ?array
    {
        $ids = $this->unitSiteIds();

        if ($ids === null) {
            return null;
        }

        return Site::whereIn('id', $ids)->pluck('code')->all();
    }

    /**
     * True bila user dibatasi ke unit tertentu.
     */
    public function hasUnitScope(): bool
    {
        return ! $this->hasGlobalUnitScope();
    }

    /**
     * Entri master karyawan Lunch Management milik user ini (jika ada).
     */
    public function employeeDefault()
    {
        return $this->hasOne(EmployeeDefault::class, 'user_id');
    }

    /**
     * Histori forecast lunch milik user ini (data lama, per akun).
     */
    public function lunchForecasts()
    {
        return $this->hasMany(LunchForecast::class, 'user_id');
    }

    /* =========================================================
     * SSO & STATUS AKUN
     * =======================================================*/

    /**
     * Identitas SSO milik user ini (satu baris per provider).
     */
    public function ssoIdentities(): HasMany
    {
        return $this->hasMany(SsoIdentity::class);
    }

    /**
     * Apakah akun ini login lewat SSO?
     *
     * Dipakai untuk MENOLAK login lewat form lokal. Tanpa penolakan ini,
     * mencabut akses di SSO tidak ada artinya selama password lokalnya
     * masih hidup.
     */
    public function usesSso(): bool
    {
        return $this->auth_provider === 'sso';
    }

    /**
     * Akun boleh login?
     *
     * `is_active` terpisah dari soft-delete: soft-delete dipakai untuk akun
     * yang memang dihapus, sedangkan is_active untuk menonaktifkan sementara
     * tanpa kehilangan histori (permohonan, SPD, transaksi lunch).
     *
     * Nilai null diperlakukan AKTIF. Kolomnya NOT NULL dengan default true,
     * jadi null hanya mungkin muncul dari model yang belum dibaca dari
     * database — dan memperlakukan keadaan itu sebagai "nonaktif" akan
     * mengunci user secara tidak sengaja.
     */
    public function isActiveAccount(): bool
    {
        if ($this->is_active === null) {
            return true;
        }

        return (bool) $this->is_active;
    }

    /**
     * Nonaktifkan akun. Tidak menghapus apa pun.
     */
    public function deactivateAccount(string $reason): void
    {
        $this->is_active = false;
        $this->deactivated_at = now();
        $this->deactivation_reason = $reason;
        $this->save();
    }

    /**
     * Aktifkan kembali akun.
     *
     * PENTING: hanya boleh dipanggil atas keputusan eksplisit admin Pusat.
     * JIT SSO DILARANG memanggil ini — kalau tidak, menonaktifkan user di SIAGA
     * akan dibatalkan begitu orangnya login lagi lewat SSO.
     */
    public function activateAccount(): void
    {
        $this->is_active = true;
        $this->deactivated_at = null;
        $this->deactivation_reason = null;
        $this->save();
    }

    /* =========================================================
     * PHASE 2 - RBAC Authorization Layer
     * =======================================================*/

    /**
     * Role RBAC (many-to-many). Source of truth otorisasi.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Semua permission user (melalui role), di-cache per-instance request.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )->join('user_roles', 'user_roles.role_id', '=', 'role_permissions.role_id')
         ->where('user_roles.user_id', $this->getKey());
    }

    /**
     * Kumpulan kode permission SAH milik user (cached per-instance).
     *
     * SUDAH tersaring gate modul: permission yang modulnya dimatikan untuk unit
     * user akan hilang dari daftar ini. Karena seluruh permukaan otorisasi
     * bermuara ke method ini (middleware CheckPermission, Gate, sidebar, blade),
     * tidak ada jalur yang perlu ditambahi pengecekan modul secara manual.
     */
    protected ?array $permissionCodesCache = null;

    public function getPermissionCodes(): array
    {
        if ($this->permissionCodesCache === null) {
            $rawPermissionCodes = $this->roles()
                ->with('permissions')
                ->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('code'))
                ->unique()
                ->values()
                ->all();

            $this->permissionCodesCache = app(ModuleGate::class)
                ->filterPermissionCodes($rawPermissionCodes, $this->enabledModuleCodes());
        }

        return $this->permissionCodesCache;
    }

    /* =========================================================
     * GATE MODUL PER UNIT
     * =======================================================*/

    protected bool $moduleCodesResolved = false;

    /** @var array<int, string>|null */
    protected ?array $enabledModuleCodesCache = null;

    /**
     * Apakah user melewati gate modul sepenuhnya?
     *
     * true bila:
     *   1. Unit user adalah unit Pusat (is_central) — Pusat mengawasi semua unit.
     *   2. User belum punya unit sama sekali.
     *
     * Poin 2 bersifat TRANSISI dan sengaja fail-open. Saat fitur ini dipasang,
     * seluruh user di database belum memiliki unit_id; fail-closed akan langsung
     * mencabut seluruh akses mereka. Karena semua unit juga masih full-enable
     * (grandfather), poin 2 tidak mengubah perilaku apa pun sampai Pusat
     * mematikan modul tertentu — dan saat itu backfill unit_id harus sudah selesai.
     */
    public function bypassesModuleGate(): bool
    {
        return $this->unit_id === null || $this->unitIsCentral();
    }

    /**
     * Kode modul yang enabled untuk unit user.
     *
     * null = BYPASS (tidak digate) — lihat bypassesModuleGate() dan
     * computeEnabledModuleCodes() untuk alasan lengkapnya.
     *
     * @return array<int, string>|null
     */
    public function enabledModuleCodes(): ?array
    {
        if (! $this->moduleCodesResolved) {
            $this->enabledModuleCodesCache = $this->computeEnabledModuleCodes();
            $this->moduleCodesResolved = true;
        }

        return $this->enabledModuleCodesCache;
    }

    /**
     * Hitung modul yang berlaku bagi user ini.
     *
     * Mengembalikan null (= bypass, semua permission dipertahankan) bila:
     *
     *   1. User di unit Pusat (is_central) atau belum punya unit.
     *   2. Fitur gate modul belum diinisialisasi (tabel `modules` kosong karena
     *      seeder belum dijalankan). Fail-open di sini WAJIB: fail-closed akan
     *      mengunci seluruh pengguna yang sudah punya unit.
     *   3. Unit user belum punya satu pun baris matrix — unit yang belum
     *      dikonfigurasi. Fail-open dipilih karena mengunci seluruh user di
     *      sebuah unit jauh lebih berbahaya daripada memberi akses sementara
     *      yang bisa langsung terlihat dan diperbaiki lewat UI matrix.
     *
     * @return array<int, string>|null
     */
    private function computeEnabledModuleCodes(): ?array
    {
        if ($this->bypassesModuleGate()) {
            return null;
        }

        $gate = app(ModuleGate::class);

        if (! $gate->isInitialized()) {
            return null;
        }

        $codes = $this->unit?->enabledModuleCodes();

        if (empty($codes)) {
            return null;
        }

        return $codes;
    }

    /**
     * Cek akses modul (untuk gating menu, dashboard, dan pesan 403).
     */
    public function hasModuleAccess(string $moduleCode): bool
    {
        if ($this->bypassesModuleGate()) {
            return true;
        }

        return in_array($moduleCode, $this->enabledModuleCodes() ?? [], true);
    }

    /**
     * Cek apakah user punya permission (bisa string tunggal atau array).
     * Jika array & $requireAll=false => cukup satu saja yang match.
     */
    public function hasPermission(string|array $permission, bool $requireAll = false): bool
    {
        $codes = $this->getPermissionCodes();
        $check = is_array($permission) ? $permission : [$permission];

        if ($requireAll) {
            return count(array_intersect($check, $codes)) === count($check);
        }

        return count(array_intersect($check, $codes)) > 0;
    }

    /**
     * Cek berdasarkan module + action (tanpa perlu tahu code utuh).
     */
    public function canAction(string $module, string $action): bool
    {
        return $this->hasPermission("{$module}.{$action}");
    }

    /* =========================================================
     * PENUGASAN ROLE (satu-satunya jalur resmi)
     * =======================================================*/

    /**
     * Berikan tepat satu role RBAC kepada user ini, sambil menegakkan aturan
     * segmentasi unit (item 4).
     *
     * Semua pemanggil WAJIB lewat sini alih-alih `roles()->sync()` langsung,
     * supaya aturan "role ber-unit hanya untuk user di unit itu" tidak bisa
     * dilewati di satu tempat pun.
     *
     * @param  bool  $replace  true = ganti seluruh role (satu user satu role),
     *                         false = tambahkan tanpa menghapus yang ada
     *
     * @throws \InvalidArgumentException bila role tidak berlaku untuk unit user
     */
    public function assignRole(Role|int $role, bool $replace = true): void
    {
        $role = $role instanceof Role ? $role : Role::findOrFail($role);

        if (! $role->isAssignableTo($this->unit_id)) {
            throw new \InvalidArgumentException(
                "Role \"{$role->name}\" khusus untuk unit lain dan tidak dapat diberikan "
                .'kepada user di unit ini.'
            );
        }

        $pivot = [$role->id => ['is_primary' => true]];

        if ($replace) {
            $this->roles()->sync($pivot);
        } else {
            $this->roles()->syncWithoutDetaching($pivot);
        }

        // Kolom legacy `role` masih dipakai beberapa bagian aplikasi (sidebar
        // dan middleware CheckRole), jadi ikut disamakan.
        $this->role = (string) $role->code;
        $this->save();

        $this->forgetAuthorizationCache();
    }

    /**
     * Buang cache otorisasi per-instance.
     *
     * Wajib dipanggil setiap kali role / unit berubah dalam satu request —
     * kalau tidak, pengecekan permission berikutnya masih memakai hasil lama.
     */
    public function forgetAuthorizationCache(): void
    {
        $this->permissionCodesCache = null;
        $this->moduleCodesResolved = false;
        $this->enabledModuleCodesCache = null;
        $this->unitIsCentralCache = null;
        $this->roleCodesCache = null;
        $this->unsetRelation('roles');
        $this->unsetRelation('unit');
    }

    /* =========================================================
     * KREDENSIAL AKUN HASIL SCAN
     * =======================================================*/

    /**
     * Simpan password baru sekaligus salinan terenkripsi untuk kebutuhan
     * operasional (dibagikan offline oleh superadmin unit).
     *
     * `password` tetap hash satu arah untuk login. `credentials_plain`
     * dienkripsi dengan APP_KEY dan hanya bisa dibuka lewat endpoint yang
     * mencatat audit trail.
     */
    public function setOperationalCredentials(string $plainPassword, ?User $actor = null): void
    {
        $this->password = $plainPassword;
        $this->credentials_plain = $plainPassword;
        $this->credentials_set_at = now();
        $this->credentials_set_by = $actor?->id;
        $this->credentials_reveal_count = 0;
        $this->credentials_last_revealed_at = null;
        $this->save();
    }

    public function hasOperationalCredentials(): bool
    {
        return ! empty($this->credentials_plain) && $this->credentials_set_at !== null;
    }

    public function isPendingCredentials(): bool
    {
        return $this->registration_source === 'scan'
            && ! $this->hasOperationalCredentials();
    }

    public function credentialReveals()
    {
        return $this->hasMany(UserCredentialReveal::class);
    }
}
