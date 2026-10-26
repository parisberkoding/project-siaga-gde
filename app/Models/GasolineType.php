<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GasolineType extends Model
{
    protected $table = 'gasoline_types';

    protected $fillable = [
        'gasoline_code',
        'gasoline_name',
        'price_per_liter',
        'notes',
    ];
}
