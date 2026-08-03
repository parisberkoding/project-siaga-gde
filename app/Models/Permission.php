<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'code',
        'permission_group_id',
        'module',
        'action',
        'description',
    ];

    /**
     * Relasi ke Role melalui pivot role_permissions.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }

    /**
     * Cek apakah permission ini punya code tertentu.
     */
    public function isCode(string $code): bool
    {
        return $this->code === $code;
    }
}
