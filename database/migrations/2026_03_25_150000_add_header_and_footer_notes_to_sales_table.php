<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'header_note')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->text('header_note')->nullable();
            });
        }

        if (! Schema::hasColumn('sales', 'footer_note')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->text('footer_note')->nullable();
            });
        }

        DB::table('sales')
            ->whereNotNull('note')
            ->whereNull('header_note')
            ->update([
                'header_note' => DB::raw('note'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'header_note') || Schema::hasColumn('sales', 'footer_note')) {
            Schema::table('sales', function (Blueprint $table): void {
                $columnsToDrop = array_values(array_filter([
                    Schema::hasColumn('sales', 'header_note') ? 'header_note' : null,
                    Schema::hasColumn('sales', 'footer_note') ? 'footer_note' : null,
                ]));

                if ($columnsToDrop !== []) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
