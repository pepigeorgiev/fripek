<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreadTypeOrderTable extends Migration
{
    public function up()
    {
        Schema::create('bread_type_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bread_type_id')->constrained('bread_types');
            $table->integer('display_order')->default(999);
            $table->timestamps();
            $table->unique('bread_type_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bread_type_order');
    }
}
