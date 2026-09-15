<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('withdraw_funds', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_funds', 'coin_amount')) {
                $table->decimal('coin_amount', 16, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('withdraw_funds', 'fiat_amount')) {
                $table->decimal('fiat_amount', 10, 2)->nullable()->after('coin_amount');
            }
            if (!Schema::hasColumn('withdraw_funds', 'method')) {
                $table->string('method', 100)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('withdraw_funds', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdraw_funds', function (Blueprint $table) {
            $table->dropColumn(['coin_amount', 'fiat_amount', 'method', 'admin_note']);
        });
    }
};
