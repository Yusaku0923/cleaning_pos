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
            $table->string('phone_number')->nullable()->comment('電話番号');
            $table->date('birth_day')->nullable()->comment('生年月日');
            $table->unsignedTinyInteger('sex')->nullable()->comment('性別');
            $table->unsignedBigInteger('point')->nullable()->comment('ポイント');
            $table->tinyInteger('cutoff_date')->default(99)->comment('締め日');
            $table->boolean('is_invoice')->default(false)->comment('請求書フラグ');
            $table->boolean('needs_payment_confimation')->default(false)->comment('入金確認フラグ');
            $table->unsignedBigInteger('total_sales')->default(0)->comment('累計売上');
            $table->unsignedBigInteger('number_of_visits')->default(0)->comment('来店回数');
            $table->softDeletes();
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
