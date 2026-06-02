<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('shops_user')) {
            Schema::create('shops_user', function (Blueprint $table) {
                $table->id();
                $table->integer('shop_id')->index();
                $table->integer('user_id')->index();
            });

            // Populate it with existing users to be consistent
            $users = DB::table('users')->get(['id', 'shop_id']);
            $inserts = [];
            foreach ($users as $user) {
                $inserts[] = [
                    'shop_id' => $user->shop_id ?? 1,
                    'user_id' => $user->id,
                ];
            }
            if (!empty($inserts)) {
                DB::table('shops_user')->insert($inserts);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops_user');
    }
};
