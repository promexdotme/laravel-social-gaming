<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('driver', 50)->index();
            $table->decimal('amount', 16, 2);
            $table->string('currency', 10)->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, paid, failed, canceled
            $table->string('external_id', 150)->nullable()->index();
            $table->string('payment_url', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
