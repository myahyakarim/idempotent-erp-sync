<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorBill extends Model
{
    protected $fillable = [
        'order_id',
        'bill_type',
        'currency',
        'amount_cents',
        'line_items',
        'remote_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'line_items' => 'array',
            'amount_cents' => 'integer',
        ];
    }
}
