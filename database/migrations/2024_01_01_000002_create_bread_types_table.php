<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bread_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->decimal('price', 10, 5)->default(0);
            $table->decimal('old_price', 10, 5)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('available_for_daily')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bread_types');
    }
}; 