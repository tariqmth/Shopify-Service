<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rex_order_id')->unsigned()->index();
            $table->integer('rex_payment_method_external_id')->unsigned()->index();
            $table->bigInteger('external_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rex_payments');
    }
}
