<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('daily_transactions', function (Blueprint $table) {
            $table->index(['company_id', 'transaction_date', 'is_paid']);
            $table->index(['bread_type_id', 'is_paid']);
            $table->index(['transaction_date', 'is_paid']);
        });
        
        Schema::table('bread_sales', function (Blueprint $table) {
            $table->index(['transaction_date', 'company_id']);
            $table->index(['bread_type_id', 'transaction_date']);
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->index(['type']);
        });
    }
    
    public function down()
    {
        Schema::table('daily_transactions', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'transaction_date', 'is_paid']);
            $table->dropIndex(['bread_type_id', 'is_paid']);
            $table->dropIndex(['transaction_date', 'is_paid']);
        });
        
        Schema::table('bread_sales', function (Blueprint $table) {
            $table->dropIndex(['transaction_date', 'company_id']);
            $table->dropIndex(['bread_type_id', 'transaction_date']);
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
