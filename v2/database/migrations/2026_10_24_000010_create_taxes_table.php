<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 旧 2022_10_28_022838 (旧と同じく外部キーなし)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index()->comment('店舗ID');
            $table->string('tax')->comment('税率');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
