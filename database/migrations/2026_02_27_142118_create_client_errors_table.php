<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientErrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_errors', function (Blueprint $table) {
            $table->id();
            $table->string('error_type', 20); // error, warn, unhandled
            $table->text('message');
            $table->text('stack')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();

            $table->index('error_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_errors');
    }
}
