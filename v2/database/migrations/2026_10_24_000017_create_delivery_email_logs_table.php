<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2026_03_19_000006
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_customer_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('sent_to');
            $table->timestamp('sent_at')->useCurrent()->useCurrentOnUpdate();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_email_logs');
    }
};
