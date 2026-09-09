<?php

namespace App\Models;

use Database\Factories\ReturnItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    /** @use HasFactory<ReturnItemFactory> */
    use HasFactory;

    protected $fillable = [
        'product_return_id',
        'order_item_id',
        'quantity',
        'reason',
        'condition',
        'resolution',
        'refund_amount',
        'restock',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'refund_amount' => 'decimal:2',
            'restock' => 'boolean',
        ];
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
