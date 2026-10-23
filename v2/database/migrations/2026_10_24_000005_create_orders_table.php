<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2022_10_14_042857 + 2023_02_16_143136 (is_invoice)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->comment('店舗ID')->constrained('stores');
            $table->foreignId('manager_id')->comment('担当者ID')->constrained('managers');
            $table->foreignId('customer_id')->comment('顧客ID')->constrained('customers');
            $table->foreignId('invoice_id')->nullable()->comment('請求書ID')->constrained('invoices');
            $table->boolean('is_invoice')->default(false)->comment('請求書フラグ');
            $table->bigInteger('amount')->comment('合計金額');
            $table->bigInteger('reduction')->comment('値引き金額');
            $table->bigInteger('discount')->default(0)->comment('値引き割合');
            $table->bigInteger('payment')->comment('お支払い金額');
            $table->dateTime('paid_at')->nullable()->comment('支払い日時');
            $table->dateTime('handed_at')->nullable()->comment('お渡し日時');
            $table->text('note')->nullable()->comment('特記事項');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
