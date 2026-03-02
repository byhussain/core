<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Enums\CategoryStatus;
use SmartTill\Core\Traits\HasStoreScopedReference;
use SmartTill\Core\Traits\TracksUserActivity;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, HasStoreScopedReference, SoftDeletes, TracksUserActivity;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
