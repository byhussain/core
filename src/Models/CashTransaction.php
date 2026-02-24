<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use SmartTill\Core\Casts\PriceCast;

class CashTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'type',
        'amount',
        'cash_balance',
        'referenceable_type',
        'referenceable_id',
        'note',
        'collected_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'cash_balance' => PriceCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
