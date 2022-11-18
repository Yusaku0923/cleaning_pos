<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->comment('担当者ID');
            $table->unsignedBigInteger('customer_id')->comment('顧客ID');
            $table->unsignedBigInteger('amount')->default(0)->comment('請求金額');
            $table->date('period_start')->comment('請求対象開始日');
            $table->date('period_end')->comment('請求対象終了日');
            $table->date('paid_at')->nullable()->default(null)->comment('支払い確認日');
            $table->boolean('has_carried_over')->default(false)->comment('繰り越しフラグ');
            $table->unsignedBigInteger('carried_over_amount')->default(0)->comment('繰り越し金額');
            $table->softDeletes();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('作成日時');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新日時');

            $table->foreign('manager_id')->references('id')->on('managers');
            $table->foreign('customer_id')->references('id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
