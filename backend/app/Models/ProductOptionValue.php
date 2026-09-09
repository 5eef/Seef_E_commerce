<?php

namespace App\Models;

use Database\Factories\ProductOptionValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductOptionValue extends Model
{
    /** @use HasFactory<ProductOptionValueFactory> */
    use HasFactory;

    protected $fillable = [
        'product_option_id',
        'value',
        'metadata',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(
            ProductOption::class,
            'product_option_id'
        );
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class);
    }
}
