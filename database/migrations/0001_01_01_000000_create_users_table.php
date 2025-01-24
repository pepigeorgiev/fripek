<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default(User::ROLE_USER);
                $table->rememberToken();
                $table->timestamps();

                // Add indexes
                $table->index('email');
                $table->index('role');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};