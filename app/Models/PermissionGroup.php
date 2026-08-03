<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    protected $table = 'permission_groups';

    protected $fillable = ['code', 'name', 'icon', 'order'];

    protected $casts = ['order' => 'integer'];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'permission_group_id');
    }
}
