<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->comment('担当者ID');
            $table->string('name')->comment('顧客名');
            $table->string('name_kana')->comment('顧客名カナ');
            $table->date('birth_day')->nullable()->comment('生年月日');
            $table->unsignedTinyInteger('sex')->nullable()->comment('性別');
            $table->unsignedBigInteger('point')->nullable()->comment('ポイント');
            $table->date('cutoff_date')->nullable()->default(Null)->comment('締め日');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('作成日時');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新日時');

            $table->foreign('manager_id')->references('id')->on('managers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
