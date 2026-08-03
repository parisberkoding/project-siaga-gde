<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAlias extends Model
{
    protected $fillable = [
        'employee_default_id',
        'alias',
        'source',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeDefault::class, 'employee_default_id');
    }

    public function scopeLearned($query)
    {
        return $query->where('source', 'learned');
    }
}
