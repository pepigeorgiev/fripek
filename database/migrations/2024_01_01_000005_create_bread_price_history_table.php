<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bread_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bread_type_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 5);
            $table->decimal('old_price', 10, 5);
            $table->date('valid_from');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bread_price_history');
    }
}; 