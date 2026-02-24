<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $schemaSql = file_get_contents(__DIR__.'/../schema/mysql-core.sql');

        if ($schemaSql === false) {
            throw new RuntimeException('Unable to read core schema SQL file.');
        }

        $statements = array_filter(array_map('trim', explode(";\n", $schemaSql)));

        foreach ($statements as $statement) {
            if (str_starts_with($statement, 'CREATE TABLE')) {
                $statement = preg_replace('/^CREATE TABLE\s+`([^`]+)`/m', 'CREATE TABLE IF NOT EXISTS `$1`', $statement) ?? $statement;
            }

            DB::unprepared($statement.';');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $tables = [
            'sale_preparable_items',
            'sale_variation',
            'payments',
            'sales',
            'purchase_order_variation',
            'purchase_orders',
            'stocks',
            'variations',
            'product_attributes',
            'products',
            'images',
            'customers',
            'cash_transactions',
            'transactions',
            'suppliers',
            'categories',
            'brands',
            'attributes',
            'store_settings',
            'units',
            'unit_dimensions',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
