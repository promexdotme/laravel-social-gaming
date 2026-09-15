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
        // 1. Sports Fixtures Table
        if (!Schema::hasTable('sports_matches')) {
            Schema::create('sports_matches', function (Blueprint $table) {
                $table->id();
                $table->string('sport_key')->index(); // e.g. soccer_epl, basketball_nba
                $table->string('sport_title')->nullable();
                $table->string('match_id')->unique(); // Unique API Match ID
                $table->string('home_team');
                $table->string('away_team');
                $table->timestamp('start_time')->index(); // UTC Kickoff time
                $table->decimal('odds_home', 8, 2)->default(1.90);
                $table->decimal('odds_draw', 8, 2)->nullable()->default(3.20);
                $table->decimal('odds_away', 8, 2)->default(1.90);
                $table->string('status', 20)->default('upcoming')->index(); // 'upcoming', 'completed'
                $table->string('winner', 20)->nullable(); // 'home', 'draw', 'away'
                $table->integer('home_score')->nullable();
                $table->integer('away_score')->nullable();
                $table->timestamps();
            });
        }

        // 2. User Sports Wagers Table (Singles & Multi-Leg Parlays)
        if (!Schema::hasTable('sports_bets')) {
            Schema::create('sports_bets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('type', 20)->default('single'); // 'single', 'parlay'
                $table->json('legs_json'); // Array of selected match legs
                $table->decimal('total_odds', 10, 2)->default(1.00);
                $table->decimal('stake', 14, 2);
                $table->decimal('potential_win', 14, 2);
                $table->string('status', 20)->default('pending')->index(); // 'pending', 'won', 'lost'
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
        Schema::dropIfExists('sports_bets');
        Schema::dropIfExists('sports_matches');
    }
};
