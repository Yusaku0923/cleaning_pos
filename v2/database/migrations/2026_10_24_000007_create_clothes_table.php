<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2022_10_14_042908 + 2022_12_25_162512 (sort_key)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clothes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->comment('店舗ID')->constrained('stores');
            $table->foreignId('category_id')->comment('カテゴリID')->constrained('categories');
            $table->unsignedBigInteger('sort_key')->default(0)->comment('ソートキー');
            $table->string('name')->comment('商品名');
            $table->string('name_kana')->comment('商品略名');
            $table->unsignedBigInteger('price')->comment('値段');
            $table->unsignedInteger('tag_count')->default(1)->comment('タグ枚数');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clothes');
    }
};
