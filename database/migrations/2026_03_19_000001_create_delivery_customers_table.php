<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('registration_number')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_customers');
    }
}
