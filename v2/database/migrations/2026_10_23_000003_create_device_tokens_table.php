<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 店の端末の自動ログイン用トークン。平文は保存せず SHA-256 のみ。
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete()->comment('ログインする店舗');
            $table->string('name')->comment('端末名');
            $table->char('token_hash', 64)->unique()->comment('トークンの SHA-256');
            $table->timestamp('last_used_at')->nullable()->comment('最終使用日時');
            $table->timestamp('revoked_at')->nullable()->comment('失効日時');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
