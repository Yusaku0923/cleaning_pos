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
            $table->unsignedBigInteger('customer_id')->comment('顧客ID');
            $table->bigInteger('amount')->comment('合計金額');
            $table->bigInteger('discount')->comment('値引き金額');
            $table->bigInteger('payment')->comment('お支払い金額');
            $table->boolean('is_registered_as_invoice')->default(false)->comment('請求書フラグ');
            $table->boolean('has_paid')->default(false)->comment('支払い済みフラグ');
            $table->boolean('is_handed_over')->default(false)->comment('お渡し済みフラグ');
            $table->text('note')->nullable()->comment('特記事項');
            $table->softDeletes();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('作成日時');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新日時');

            $table->foreign('store_id')->references('id')->on('stores');
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
        Schema::dropIfExists('orders');
    }
}
