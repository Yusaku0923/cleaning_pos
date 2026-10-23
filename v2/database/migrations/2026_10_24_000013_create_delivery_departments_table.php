<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2026_03_19_000002
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_departments');
    }
};
