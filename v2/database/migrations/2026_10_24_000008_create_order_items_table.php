<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 order_clothes (2022_10_14_042928) をテーブル名だけ変えたもの。tag は旧の文字列のまま (0-000 の行もある)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->comment('注文ID')->constrained('orders');
            $table->foreignId('clothes_id')->comment('商品ID')->constrained('clothes');
            $table->string('tag')->comment('タグ番号');
            $table->dateTime('handed_at')->nullable()->comment('お渡し日時');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
