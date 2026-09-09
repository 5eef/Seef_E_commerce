<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'base_price',
        'sale_price',
        'cost_price',
        'status',
        'is_featured',
        'published_at',
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
        'seo_title',
        'seo_description',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',

            'is_featured' => 'boolean',

            'published_at' => 'datetime',

            'weight_grams' => 'integer',

            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(
            ProductImage::class
        );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(
            ProductVariant::class
        );
    }

    public function options(): HasMany
    {
        return $this->hasMany(
            ProductOption::class
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class
        );
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class
        );
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(
            WishlistItem::class
        );
    }
}
