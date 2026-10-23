<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2022_10_14_042858
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->comment('店舗ID')->constrained('stores');
            $table->string('name')->comment('カテゴリ名');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
