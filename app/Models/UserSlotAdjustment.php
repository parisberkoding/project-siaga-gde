<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSlotAdjustment extends Model
{

    protected $table = 'user_slot_adjustments';

    protected $fillable = [
        'user_id',
        'type',
        'reason',
        'related_permohonan_id',
        'effective_from',
        'effective_until',
        'is_active'
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'related_permohonan_id');
    }
}
