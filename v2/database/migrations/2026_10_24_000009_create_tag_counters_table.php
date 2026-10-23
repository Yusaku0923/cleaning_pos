<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 tag_numbers (2022_10_28_022420)。tag_number → value。担当者ごとに1行 (unique)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->unique()->comment('担当者ID')->constrained('managers');
            $table->unsignedBigInteger('value')->comment('シリアルナンバー');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_counters');
    }
};
