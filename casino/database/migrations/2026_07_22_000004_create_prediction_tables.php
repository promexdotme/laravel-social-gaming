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
        // 1. Prediction Markets Table
        if (!Schema::hasTable('prediction_markets')) {
            Schema::create('prediction_markets', function (Blueprint $table) {
                $table->id();
                $table->string('market_id')->unique()->index(); // Unique Polymarket ID or custom slug
                $table->unsignedBigInteger('creator_id')->nullable()->index(); // User ID if player-created
                $table->string('category', 50)->default('custom')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('verification_url')->nullable(); // Verification link for admins
                $table->decimal('pool_yes', 14, 2)->default(1000.00);
                $table->decimal('pool_no', 14, 2)->default(1000.00);
                $table->timestamp('end_date')->index(); // Resolution UTC date
                $table->string('status', 20)->default('active')->index(); // 'active', 'muted', 'cancelled', 'resolved'
                $table->string('resolution', 10)->nullable(); // 'yes', 'no'
                $table->timestamps();
            });
        }

        // 2. User Prediction Wagers Table
        if (!Schema::hasTable('prediction_votes')) {
            Schema::create('prediction_votes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('market_id')->index();
                $table->string('choice', 10); // 'yes', 'no'
                $table->decimal('odds', 8, 2)->default(2.00);
                $table->decimal('stake', 14, 2);
                $table->decimal('potential_win', 14, 2);
                $table->string('status', 20)->default('pending')->index(); // 'pending', 'won', 'lost', 'refunded'
                $table->decimal('payout_amount', 14, 2)->default(0.00);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_votes');
        Schema::dropIfExists('prediction_markets');
    }
};
