<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bread_types', function (Blueprint $table) {
            $table->decimal('price_group_1', 10, 2)->nullable()->after('price');
            $table->decimal('price_group_2', 10, 2)->nullable()->after('price_group_1');
            $table->decimal('price_group_3', 10, 2)->nullable()->after('price_group_2');
            $table->decimal('price_group_4', 10, 2)->nullable()->after('price_group_3');
            $table->decimal('price_group_5', 10, 2)->nullable()->after('price_group_4');
        });
    }

    public function down()
    {
        Schema::table('bread_types', function (Blueprint $table) {
            $table->dropColumn([
                'price_group_1',
                'price_group_2',
                'price_group_3',
                'price_group_4',
                'price_group_5'
            ]);
        });
    }
};