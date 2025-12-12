<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->enum('direction', ['add', 'deduct', 'payment'])->index();
            $table->decimal('amount', 16, 2);
            $table->decimal('balance_before', 16, 2)->nullable();
            $table->decimal('balance_after', 16, 2)->nullable();
            $table->string('source', 100)->default('manual')->index(); // e.g., manual, payment_gateway:coinbase
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
