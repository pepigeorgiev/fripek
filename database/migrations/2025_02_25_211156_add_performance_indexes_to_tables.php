<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_transactions', function (Blueprint $table) {
            $table->index(['company_id', 'transaction_date']);
            $table->index(['is_paid', 'paid_date']);
            $table->index(['bread_type_id']);
        });

        Schema::table('bread_sales', function (Blueprint $table) {
            $table->index(['transaction_date', 'company_id']);
            $table->index(['bread_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_transactions', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'transaction_date']);
            $table->dropIndex(['is_paid', 'paid_date']);
            $table->dropIndex(['bread_type_id']);
        });

        Schema::table('bread_sales', function (Blueprint $table) {
            $table->dropIndex(['transaction_date', 'company_id']);
            $table->dropIndex(['bread_type_id']);
        });
    }
}