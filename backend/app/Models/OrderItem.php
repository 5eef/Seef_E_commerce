<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',

        'product_name',
        'variant_name',
        'sku',
        'option_values',

        'unit_price',
        'quantity',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'option_values' => 'array',

            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
