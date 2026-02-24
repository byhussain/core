<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitDimension extends Model
{
    /** @use HasFactory<\Database\Factories\UnitDimensionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'base_unit_id',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'dimension_id');
    }
}
