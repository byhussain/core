<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Casts\PriceCast;
use SmartTill\Core\Observers\TransactionObserver;
use SmartTill\Core\Traits\HasStoreScopedReference;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    use HasFactory, HasStoreScopedReference, SoftDeletes;

    protected $fillable = [
        'store_id',
        'transactionable_type',
        'transactionable_id',
        'referenceable_type',
        'referenceable_id',
        'type',
        'amount',
        'amount_balance',
        'quantity',
        'quantity_balance',
        'note',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'amount_balance' => PriceCast::class,
            'quantity' => 'decimal:6',
            'quantity_balance' => 'decimal:6',
            'meta' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo('referenceable');
    }
}
