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
        Schema::create('sports_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 40);
            $table->string('odds_api_name', 40)->nullable()->index();
            $table->string('slug', 255)->unique();
            $table->string('icon', 255)->nullable();
            $table->string('regions', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('sports_leagues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('odds_api_sport_key', 255)->nullable()->index();
            $table->unsignedBigInteger('category_id')->index();
            $table->string('name', 100);
            $table->string('short_name', 100);
            $table->string('slug', 255)->unique();
            $table->string('description', 255)->nullable();
            $table->tinyInteger('has_outrights')->default(0);
            $table->string('image', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('api_status')->default(0);
            $table->tinyInteger('manually_added')->default(1);
            $table->timestamps();
        });

        Schema::create('sports_teams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id')->default(0)->index();
            $table->string('slug', 255)->nullable();
            $table->string('name', 255)->nullable()->index();
            $table->string('short_name', 100)->nullable();
            $table->string('image', 255)->nullable();
            $table->tinyInteger('manually_added')->default(1);
            $table->timestamps();
        });

        Schema::create('sports_games', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ods_api_id', 255)->nullable()->index();
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('team_one_id')->default(0)->index();
            $table->unsignedBigInteger('team_two_id')->default(0)->index();
            $table->unsignedBigInteger('league_id')->index();
            $table->string('slug', 255)->nullable();
            $table->dateTime('start_time');
            $table->dateTime('bet_start_time')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('manually_added')->default(1);
            $table->tinyInteger('is_outright')->default(0);
            $table->timestamps();
        });

        Schema::create('sports_game_team', function (Blueprint $table) {
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('team_id');
            $table->primary(['game_id', 'team_id']);
        });

        Schema::create('sports_markets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_id')->index();
            $table->string('market_type', 255)->nullable()->index();
            $table->tinyInteger('outcome_type')->default(1);
            $table->tinyInteger('player_props')->default(0);
            $table->tinyInteger('game_period_market')->default(0);
            $table->string('title', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('locked')->default(0);
            $table->tinyInteger('result_declared')->default(0);
            $table->unsignedBigInteger('win_outcome_id')->default(0);
            $table->timestamp('market_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sports_outcomes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->index();
            $table->string('name', 255);
            $table->decimal('odds', 28, 8);
            $table->decimal('point', 5, 2)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('locked')->default(0);
            $table->tinyInteger('winner')->default(0);
            $table->timestamps();
        });

        Schema::create('sports_bets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bet_number', 40)->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->tinyInteger('type')->default(1)->comment('1: Single, 2: Multi');
            $table->decimal('stake_amount', 28, 8)->default('0.00000000');
            $table->decimal('return_amount', 28, 8)->default('0.00000000');
            $table->tinyInteger('status')->default(2)->comment('1: Win, 2: Pending, 3: Loss, 4: Refunded');
            $table->tinyInteger('is_settled')->default(0);
            $table->dateTime('result_time')->nullable();
            $table->timestamps();
        });

        Schema::create('sports_bet_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bet_id')->index();
            $table->unsignedBigInteger('market_id')->index();
            $table->unsignedBigInteger('outcome_id')->index();
            $table->decimal('odds', 28, 8)->default('0.00000000');
            $table->tinyInteger('status')->default(2)->comment('1: Win, 2: Pending, 3: Loss, 4: Refunded');
            $table->timestamps();
        });

        Schema::create('sports_cron_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job_alias', 100)->index();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->integer('duration')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports_cron_logs');
        Schema::dropIfExists('sports_bet_items');
        Schema::dropIfExists('sports_bets');
        Schema::dropIfExists('sports_outcomes');
        Schema::dropIfExists('sports_markets');
        Schema::dropIfExists('sports_game_team');
        Schema::dropIfExists('sports_games');
        Schema::dropIfExists('sports_teams');
        Schema::dropIfExists('sports_leagues');
        Schema::dropIfExists('sports_categories');
    }
};
