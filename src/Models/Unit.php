<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Observers\UnitObserver;

#[ObservedBy([UnitObserver::class])]
class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'symbol',
        'store_id',
        'dimension_id',
        'code',
        'to_base_factor',
        'to_base_offset',
    ];

    protected function casts(): array
    {
        return [
            'to_base_factor' => 'float',
            'to_base_offset' => 'float',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(UnitDimension::class);
    }

    public function scopeForStoreOrGlobal(Builder $query, ?int $storeId): Builder
    {
        return $query->where(function (Builder $inner) use ($storeId) {
            $inner->whereNull('store_id');

            if ($storeId) {
                $inner->orWhere('store_id', $storeId);
            }
        });
    }

    public static function convertQuantity(float $quantity, Unit $from, Unit $to): float
    {
        if ($from->id === $to->id) {
            return $quantity;
        }

        $base = ($quantity * (float) $from->to_base_factor) + (float) $from->to_base_offset;
        $converted = ($base - (float) $to->to_base_offset) / (float) $to->to_base_factor;

        return round($converted, 6);
    }

    public static function convertPrice(float $price, Unit $from, Unit $to): float
    {
        if ($from->id === $to->id) {
            return $price;
        }

        $fromFactor = (float) $from->to_base_factor;
        $toFactor = (float) $to->to_base_factor;

        if ($fromFactor == 0.0 || $toFactor == 0.0) {
            return $price;
        }

        $converted = $price * ($toFactor / $fromFactor);

        return round($converted, 6);
    }
}
