<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $dropTables = [
            'w_shops_os',
            'w_shops_devices',
            'w_shops_countries',
            'w_shop_categories',
            'w_shops_user',
            'w_quick_shops',
        ];

        foreach ($dropTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }

    public function down()
    {
        // no-op; these tables are intentionally removed in lite mode
    }
};
