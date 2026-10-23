<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 店舗 = ログインアカウント。旧 stores の最終形 (2022_10_14_042759 + 2023_02_01_173915 postal_code) と同じカラム。
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('店舗名');
            $table->string('email')->unique()->comment('メールアドレス');
            $table->string('password')->comment('パスワード');
            $table->string('postal_code')->nullable()->comment('郵便番号');
            $table->string('address')->comment('住所');
            $table->string('phone_number')->comment('電話番号');
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
