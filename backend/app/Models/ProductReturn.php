<?php

namespace App\Models;

use Database\Factories\ProductReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReturn extends Model
{
    /** @use HasFactory<ProductReturnFactory> */
    use HasFactory;

    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'status',
        'reason',
        'customer_note',
        'admin_note',
        'refund_amount',
        'requested_at',
        'approved_at',
        'received_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',

            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }
}
