<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2026_02_27_142118。旧のデータは移さない
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_errors', function (Blueprint $table) {
            $table->id();
            $table->string('error_type', 20); // error, warn, unhandled
            $table->text('message');
            $table->text('stack')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();

            $table->index('error_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_errors');
    }
};
