<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'is_active',
        'sort_order',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'is_active',
            true
        );
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            Category::class,
            'parent_id'
        );
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class
        );
    }
}
