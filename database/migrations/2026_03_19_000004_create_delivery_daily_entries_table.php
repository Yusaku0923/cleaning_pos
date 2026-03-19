<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryDailyEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('delivery_note_id')->nullable();
            $table->date('date');
            $table->integer('quantity')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['delivery_product_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_daily_entries');
    }
}
