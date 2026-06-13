<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_orders', 'discount_is_percentage')) {
                // true = the value is a percentage of (supplier subtotal + withholding tax),
                // false = the value is a flat amount.
                $table->boolean('discount_is_percentage')->default(false);
            }

            if (! Schema::hasColumn('purchase_orders', 'discount_value')) {
                // The raw input the user entered (a percentage like 2.5 or a flat amount).
                $table->decimal('discount_value', 18, 6)->nullable();
            }

            if (! Schema::hasColumn('purchase_orders', 'discount_amount')) {
                // The computed discount amount (minor units, via PriceCast).
                $table->bigInteger('discount_amount')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('purchase_orders', 'discount_is_percentage') ? 'discount_is_percentage' : null,
                Schema::hasColumn('purchase_orders', 'discount_value') ? 'discount_value' : null,
                Schema::hasColumn('purchase_orders', 'discount_amount') ? 'discount_amount' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
