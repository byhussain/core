<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_orders', 'withholding_tax_is_percentage')) {
                // true = the value is a percentage of the supplier subtotal,
                // false = the value is a flat amount.
                $table->boolean('withholding_tax_is_percentage')->default(false);
            }

            if (! Schema::hasColumn('purchase_orders', 'withholding_tax_value')) {
                // The raw input the user entered (a percentage like 4.5 or a flat amount).
                $table->decimal('withholding_tax_value', 18, 6)->nullable();
            }

            if (! Schema::hasColumn('purchase_orders', 'withholding_tax_amount')) {
                // The computed withholding tax amount (minor units, via PriceCast).
                $table->bigInteger('withholding_tax_amount')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('purchase_orders', 'withholding_tax_is_percentage') ? 'withholding_tax_is_percentage' : null,
                Schema::hasColumn('purchase_orders', 'withholding_tax_value') ? 'withholding_tax_value' : null,
                Schema::hasColumn('purchase_orders', 'withholding_tax_amount') ? 'withholding_tax_amount' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
