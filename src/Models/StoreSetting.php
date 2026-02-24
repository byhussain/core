<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SmartTill\Core\Enums\StoreSettingType;

class StoreSetting extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'key',
        'value',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => StoreSettingType::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
