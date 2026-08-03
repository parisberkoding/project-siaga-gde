<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningProfile extends Model
{
    protected $fillable = [
        'employee_default_id',
        'site_id',
        'transaction_count',
        'last_transaction_date',
    ];

    protected $casts = [
        'transaction_count'     => 'integer',
        'last_transaction_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeDefault::class, 'employee_default_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
