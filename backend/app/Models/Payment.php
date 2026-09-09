<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'provider',
        'provider_reference',
        'status',
        'amount',
        'currency',
        'paid_at',
        'failed_at',
        'refunded_at',
        'failure_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',

            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',

            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
