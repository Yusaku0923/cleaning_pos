<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2026_03_19_000003
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('unit_price');
            $table->decimal('tax_rate', 4, 2)->default(0.10);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_products');
    }
};
