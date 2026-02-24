<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use SmartTill\Core\Casts\PriceCast;
use SmartTill\Core\Enums\PaymentMethod;
use SmartTill\Core\Observers\PaymentObserver;

#[ObservedBy([PaymentObserver::class])]
class Payment extends Model
{
    protected $fillable = [
        'store_id',
        'payable_type',
        'payable_id',
        'amount',
        'payment_method',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
