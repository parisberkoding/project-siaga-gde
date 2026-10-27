<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LunchVendorPayment extends Model
{
    protected $table = 'lunch_vendor_payments';

    protected $fillable = [
        'lunch_vendor_invoice_id',
        'amount',
        'paid_at',
        'method',
        'reference',
        'proof_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(LunchVendorInvoice::class, 'lunch_vendor_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'transfer' => 'Transfer Bank',
            'cash'     => 'Tunai',
            'giro'     => 'Giro / Cek',
            default    => $this->method,
        };
    }
}
