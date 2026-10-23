<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2022_10_14_042856 + 2023_01_31_143448 (amount 削除) + 2023_03_02_172019 (carried_over_amount 削除, carry_over_id 追加)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->comment('担当者ID')->constrained('managers');
            $table->foreignId('customer_id')->comment('顧客ID')->constrained('customers');
            $table->date('period_start')->comment('請求対象開始日');
            $table->date('period_end')->comment('請求対象終了日');
            $table->date('paid_at')->nullable()->comment('支払い確認日');
            $table->date('issued_at')->nullable()->comment('PDF出力日');
            $table->boolean('has_carried_over')->default(false)->comment('繰り越しフラグ');
            $table->unsignedBigInteger('carry_over_id')->nullable()->comment('繰越先ID');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
