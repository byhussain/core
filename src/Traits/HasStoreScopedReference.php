<?php

namespace SmartTill\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait HasStoreScopedReference
{
    public static function bootHasStoreScopedReference(): void
    {
        static::creating(function (Model $model): void {
            $reference = trim((string) ($model->getAttribute('reference') ?? ''));
            if ($reference !== '') {
                return;
            }

            $table = $model->getTable();
            if (! Schema::hasColumn($table, 'reference') || ! Schema::hasColumn($table, 'store_id')) {
                return;
            }

            $storeId = (int) ($model->getAttribute('store_id') ?? 0);
            if ($storeId <= 0) {
                return;
            }

            $nextReference = DB::table($table)
                ->where('store_id', $storeId)
                ->whereNotNull('reference')
                ->count() + 1;

            $model->setAttribute('reference', (string) $nextReference);
        });
    }
}

