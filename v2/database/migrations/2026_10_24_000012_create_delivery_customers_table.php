<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2026_03_19_000001。旧は店舗を持たないので store_id を足す (移行時は旧の最小 id の店舗)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->comment('店舗ID')->constrained('stores');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('registration_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_customers');
    }
};
