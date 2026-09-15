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
        // 1. Configurable Lotto Games
        if (!Schema::hasTable('lotto_games')) {
            Schema::create('lotto_games', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->integer('pick_count')->default(4); // e.g. 4 numbers or 6 numbers
                $table->integer('max_number')->default(20); // e.g. 1 to 20, or 1 to 49
                $table->decimal('entry_fee', 14, 2)->default(500.00);
                $table->decimal('jackpot_pool', 14, 2)->default(100000.00);
                $table->string('draw_interval', 20)->default('daily'); // 'hourly', 'daily', 'weekly'
                $table->string('draw_time', 10)->default('23:55');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. User Lotto Tickets
        if (!Schema::hasTable('lotto_tickets')) {
            Schema::create('lotto_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lotto_game_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->json('numbers_json');
                $table->date('draw_date');
                $table->string('status', 20)->default('pending'); // 'pending', 'won', 'lost'
                $table->integer('matches_count')->default(0);
                $table->decimal('payout_amount', 14, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 3. Lotto Draw Results History
        if (!Schema::hasTable('lotto_draws')) {
            Schema::create('lotto_draws', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lotto_game_id')->index();
                $table->json('winning_numbers_json');
                $table->date('draw_date');
                $table->integer('total_tickets')->default(0);
                $table->integer('total_winners')->default(0);
                $table->decimal('total_paid', 14, 2)->default(0.00);
                $table->timestamp('drawn_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_games');
    }
};
