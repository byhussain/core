<?php

namespace SmartTill\Core\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Marks a model row as needing a cloud push.
 *
 * Background: a lot of business logic in the core package uses
 * `saveQuietly()` (and to a lesser extent `Sale::withoutEvents()`) to update
 * rows without firing the cash/stock observers that would otherwise recurse.
 * The side-effect is that the POS-side `DispatchCloudSyncObserver` — which
 * lives on the same Eloquent event hooks — also doesn't fire, so the row's
 * `sync_state` stays at `'synced'` and the push job never picks the change
 * up. The server never sees the edit.
 *
 * This helper is the explicit, surgical alternative: after any silent save
 * call `CloudSyncFlagger::flag($model)` and the row goes back into the
 * pending bucket so the next push job ships it.
 *
 * Safe to call from any context:
 *   - On the SaaS server (where `sales` etc. have no `sync_state` column),
 *     every branch short-circuits.
 *   - When the POS job class isn't loaded (running migrations, etc.), the
 *     dispatch is skipped.
 *   - When the model's PK isn't numeric yet, it's a no-op.
 */
class CloudSyncFlagger
{
    /**
     * Tables we know are sale-related, so a sale row + its children can be
     * marked together with a single call.
     */
    private const SALE_CHILD_TABLES = ['sale_variation', 'sale_preparable_items'];

    /**
     * Tables we know are purchase-order-related.
     */
    private const PO_CHILD_TABLES = ['purchase_order_products'];

    public static function flag(Model $model): void
    {
        try {
            $table = $model->getTable();
            $key = $model->getKey();

            if (! is_numeric($key) || (int) $key <= 0) {
                return;
            }

            if (! Schema::hasColumn($table, 'sync_state')) {
                return;
            }

            self::markRowPending($table, (int) $key);

            $model->setAttribute('sync_state', 'pending');
            if (Schema::hasColumn($table, 'sync_error')) {
                $model->setAttribute('sync_error', null);
            }

            self::dispatchPushJob(self::resolveStoreId($model, $table), self::resolveModule($table));
        } catch (Throwable $throwable) {
            // Defensive: sync bookkeeping must NEVER abort the user's
            // business transaction. If something here goes wrong (stale
            // schema cache, unexpected driver behaviour, etc.) we log and
            // continue. The next /sync-status poll's backlog detector
            // will still find any rows that didn't get flagged.
            self::warn('flag', $throwable);
        }
    }

    /**
     * Flag a sale row AND its child line items. Use this when the change to
     * the sale could have come from a transaction service that updated
     * stocks, totals, or transactions without going through the form.
     */
    public static function flagSale(int $saleId): void
    {
        try {
            if ($saleId <= 0 || ! Schema::hasTable('sales') || ! Schema::hasColumn('sales', 'sync_state')) {
                return;
            }

            self::markRowPending('sales', $saleId);

            foreach (self::SALE_CHILD_TABLES as $childTable) {
                if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'sync_state')) {
                    continue;
                }

                $updates = ['sync_state' => 'pending'];
                if (Schema::hasColumn($childTable, 'sync_error')) {
                    $updates['sync_error'] = null;
                }

                DB::table($childTable)->where('sale_id', $saleId)->update($updates);
            }

            $storeId = (int) DB::table('sales')->where('id', $saleId)->value('store_id');
            self::dispatchPushJob($storeId, 'sales');
        } catch (Throwable $throwable) {
            self::warn('flagSale', $throwable);
        }
    }

    /**
     * Flag a purchase order row AND its line items.
     */
    public static function flagPurchaseOrder(int $purchaseOrderId): void
    {
        try {
            if ($purchaseOrderId <= 0 || ! Schema::hasTable('purchase_orders') || ! Schema::hasColumn('purchase_orders', 'sync_state')) {
                return;
            }

            self::markRowPending('purchase_orders', $purchaseOrderId);

            foreach (self::PO_CHILD_TABLES as $childTable) {
                if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'sync_state')) {
                    continue;
                }

                $updates = ['sync_state' => 'pending'];
                if (Schema::hasColumn($childTable, 'sync_error')) {
                    $updates['sync_error'] = null;
                }

                DB::table($childTable)->where('purchase_order_id', $purchaseOrderId)->update($updates);
            }

            $storeId = (int) DB::table('purchase_orders')->where('id', $purchaseOrderId)->value('store_id');
            self::dispatchPushJob($storeId, 'purchase_orders');
        } catch (Throwable $throwable) {
            self::warn('flagPurchaseOrder', $throwable);
        }
    }

    private static function warn(string $method, Throwable $throwable): void
    {
        // Single place that logs all CloudSyncFlagger failures. Keeps the
        // sale/PO save itself succeeding — sync bookkeeping is best-effort,
        // and the auto-sync poller's backlog detector will still find
        // anything that wasn't flagged here.
        Log::warning("CloudSyncFlagger::{$method} failed: ".$throwable->getMessage(), [
            'exception' => $throwable,
        ]);
    }

    private static function markRowPending(string $table, int $id): void
    {
        $updates = ['sync_state' => 'pending'];
        if (Schema::hasColumn($table, 'sync_error')) {
            $updates['sync_error'] = null;
        }

        DB::table($table)->where('id', $id)->update($updates);
    }

    private static function resolveStoreId(Model $model, string $table): int
    {
        if (Schema::hasColumn($table, 'store_id')) {
            return (int) ($model->getAttribute('store_id') ?? 0);
        }

        // Children that don't carry store_id directly — walk to the parent.
        if (in_array($table, ['sale_variation', 'sale_preparable_items'], true)) {
            $saleId = (int) ($model->getAttribute('sale_id') ?? 0);
            if ($saleId > 0) {
                return (int) DB::table('sales')->where('id', $saleId)->value('store_id');
            }
        }

        if ($table === 'purchase_order_products') {
            $poId = (int) ($model->getAttribute('purchase_order_id') ?? 0);
            if ($poId > 0) {
                return (int) DB::table('purchase_orders')->where('id', $poId)->value('store_id');
            }
        }

        return 0;
    }

    private static function resolveModule(string $table): ?string
    {
        return match ($table) {
            'sales', 'sale_variation', 'sale_preparable_items' => 'sales',
            'customers' => 'customers',
            'payments' => 'payments',
            'products', 'product_attributes', 'variations', 'stocks' => 'products',
            'purchase_orders', 'purchase_order_products' => 'purchase_orders',
            'suppliers' => 'suppliers',
            default => null,
        };
    }

    private static function dispatchPushJob(int $storeId, ?string $module): void
    {
        if ($storeId <= 0) {
            return;
        }

        $jobClass = '\\App\\Jobs\\SyncCloudStoreData';
        if (! class_exists($jobClass)) {
            return; // not running inside the POS — nothing to do.
        }

        try {
            $jobClass::dispatch($storeId, 'push', $module);
        } catch (Throwable $throwable) {
            // Dispatch failure is non-fatal: the next /sync-status poll's
            // backlog detector (`hasPendingLocalRowsForStore`) will queue
            // its own push within ~15s.
            Log::warning('CloudSyncFlagger dispatch failed: '.$throwable->getMessage());
        }
    }
}
