<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2026_03_19_000004 (+ 000005 で足した delivery_note_id の外部キー)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_note_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->integer('quantity')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['delivery_product_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_daily_entries');
    }
};
