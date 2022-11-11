<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderClothesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_clothes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('注文ID');
            $table->unsignedBigInteger('clothes_id')->comment('商品ID');
            $table->string('tag')->comment('タグ番号');
            $table->dateTime('handed_at')->nullable()->default(null)->comment('お渡し日時');
            $table->softDeletes();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('作成日時');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新日時');

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('clothes_id')->references('id')->on('clothes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_clothes');
    }
}
