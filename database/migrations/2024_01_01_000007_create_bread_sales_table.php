<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bread_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('bread_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->date('transaction_date');
            $table->integer('sold_amount')->default(0);
            $table->integer('returned_amount')->default(0);
            $table->integer('returned_amount_1')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('old_bread_sold')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bread_sales');
    }
}; 