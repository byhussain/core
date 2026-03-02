<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Observers\AttributeObserver;
use SmartTill\Core\Traits\HasStoreScopedReference;

#[ObservedBy([AttributeObserver::class])]
class Attribute extends Model
{
    use HasFactory, HasStoreScopedReference, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
    ];

    /**
     * Get the store that owns the attribute.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
