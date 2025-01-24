<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExportJobsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('export_jobs')) {
            Schema::create('export_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('status');
                $table->string('file_path')->nullable();
                $table->text('error')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('export_jobs');
    }
}
