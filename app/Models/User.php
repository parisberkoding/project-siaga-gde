<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, softDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_code',
        'username',
        'password',
        'nama_lengkap',
        'role',
        'jabatan',
        'default_site_id',
        'email',
        'added_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
     * Kumpulan kode permission (cached) untuk pengecekan cepat.
     */
    protected ?array $permissionCodesCache = null;

    public function getPermissionCodes(): array
    {
        if ($this->permissionCodesCache === null) {
            $this->permissionCodesCache = $this->roles()
                ->with('permissions')
                ->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('code'))
                ->unique()
                ->values()
                ->toArray();
        }

        return $this->permissionCodesCache;
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

}
