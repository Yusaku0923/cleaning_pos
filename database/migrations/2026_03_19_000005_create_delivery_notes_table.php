<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryNotesTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_customer_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('note_number');
            $table->timestamps();
            $table->unique(['delivery_customer_id', 'note_number']);
        });

        // Add FK for delivery_daily_entries.delivery_note_id now that delivery_notes exists
        Schema::table('delivery_daily_entries', function (Blueprint $table) {
            $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('delivery_daily_entries', function (Blueprint $table) {
            $table->dropForeign(['delivery_note_id']);
        });
        Schema::dropIfExists('delivery_notes');
    }
}
