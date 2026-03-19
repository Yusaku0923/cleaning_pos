<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryEmailLogsTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_customer_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('sent_to');
            $table->timestamp('sent_at');
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_email_logs');
    }
}
