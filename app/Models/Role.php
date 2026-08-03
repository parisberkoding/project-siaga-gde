<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Relasi ke Permission melalui pivot role_permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * User pemilik role (many-to-many).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Sync permissions by permission code list.
     */
    public function syncPermissionsByCode(array $codes): void
    {
        $ids = Permission::whereIn('code', $codes)->pluck('id')->toArray();
        $this->permissions()->sync($ids);
    }
}
