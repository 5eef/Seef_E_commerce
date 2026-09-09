<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'price',
        'sale_price',
        'cost_price',
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',

            'weight_grams' => 'integer',

            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',

            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(
            CartItem::class,
            'product_variant_id'
        );
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'product_variant_id'
        );
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class
        );
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }
}
