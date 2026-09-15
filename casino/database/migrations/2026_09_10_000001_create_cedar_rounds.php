<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cedar_states', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('game', 32);
            $table->text('state');
            $table->unique(['user_id', 'game']);
        });
        Schema::create('cedar_rounds', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('game', 32);
            $table->string('request_id', 64);
            $table->string('status', 16);
            $table->decimal('wager', 20, 2);
            $table->decimal('win', 20, 2)->default(0);
            $table->longText('data');
            $table->timestamps();
            $table->unique(['user_id', 'game', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cedar_rounds');
        Schema::dropIfExists('cedar_states');
    }
};
