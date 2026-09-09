<?php

namespace App\Models;

use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'balance',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reason',
        'metadata',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
