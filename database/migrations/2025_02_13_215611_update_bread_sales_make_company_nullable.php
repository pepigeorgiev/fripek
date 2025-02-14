<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bread_sales', function (Blueprint $table) {
            // Make company_id nullable
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('bread_sales', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }
};