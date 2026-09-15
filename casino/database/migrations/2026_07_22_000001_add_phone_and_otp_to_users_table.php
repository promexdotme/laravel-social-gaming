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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->index()->after('email');
            }
            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code', 10)->nullable()->after('phone_verified_at');
            }
            if (!Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            }
            if (!Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id')->nullable()->index()->after('otp_expires_at');
            }
            if (!Schema::hasColumn('users', 'social_provider')) {
                $table->string('social_provider', 30)->nullable()->after('social_id');
            }
            if (!Schema::hasColumn('users', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->default(0)->index()->after('social_provider');
            }
            if (!Schema::hasColumn('users', 'invite_code')) {
                $table->string('invite_code', 20)->nullable()->unique()->after('parent_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'otp_code',
                'otp_expires_at',
                'social_id',
                'social_provider',
                'parent_id',
                'invite_code'
            ]);
        });
    }
};
