<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Enums\SupplierStatus;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'email',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupplierStatus::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
