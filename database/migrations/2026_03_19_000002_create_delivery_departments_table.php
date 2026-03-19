<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryDepartmentsTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_departments');
    }
}
