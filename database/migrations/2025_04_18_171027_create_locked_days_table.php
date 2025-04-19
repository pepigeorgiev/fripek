<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLockedDaysTable extends Migration
{
    public function up()
    {
        Schema::create('locked_days', function (Blueprint $table) {
            $table->id();
            $table->date('locked_date');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('locked_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // When user_id is NULL, it means the day is locked for ALL users
            $table->unique(['locked_date', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('locked_days');
    }
}