<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('店舗ID');
            $table->unsignedBigInteger('manager_id')->comment('担当者ID');
            $table->unsignedBigInteger('customer_id')->comment('顧客ID');
            $table->unsignedBigInteger('invoice_id')->nullable()->default(null)->comment('請求書ID');
            $table->bigInteger('amount')->comment('合計金額');
            $table->bigInteger('reduction')->comment('値引き金額');
            $table->bigInteger('discount')->default(0)->comment('値引き割合');
            $table->bigInteger('payment')->comment('お支払い金額');
            $table->dateTime('paid_at')->nullable()->default(null)->comment('支払い日時');
            $table->dateTime('handed_at')->nullable()->default(null)->comment('お渡し日時');
            $table->text('note')->nullable()->comment('特記事項');
            $table->softDeletes();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('作成日時');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新日時');

            $table->foreign('store_id')->references('id')->on('stores');
            $table->foreign('manager_id')->references('id')->on('managers');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('invoice_id')->references('id')->on('invoices');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
