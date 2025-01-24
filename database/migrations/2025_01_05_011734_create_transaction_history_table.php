<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionHistoryTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('transaction_history')) {
            Schema::create('transaction_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id');
                $table->unsignedBigInteger('user_id');
                $table->string('action');
                $table->json('old_values')->nullable();
                $table->json('new_values');
                $table->string('ip_address');
                $table->timestamps();

                $table->foreign('transaction_id')
                    ->references('id')
                    ->on('daily_transactions')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('transaction_history');
    }
}
