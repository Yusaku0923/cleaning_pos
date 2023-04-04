<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnCarriedOverAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('carry_over_id')->nullable()->default(null)->after('has_carried_over')->comment('繰越先ID');
            $table->dropColumn('carried_over_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('carry_over_id');
            $table->unsignedBigInteger('carried_over_amount')->default(0)->after('has_carried_over')->comment('繰り越し金額');
        });
    }
}
