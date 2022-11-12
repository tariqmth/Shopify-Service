<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->integer('shopify_order_id')->unsigned();
            $table->integer('shopify_payment_gateway_id')->unsigned();
            $table->integer('rex_payment_id')->unsigned()->nullable();
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
        Schema::dropIfExists('shopify_transactions');
    }
}
