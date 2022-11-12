<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyPaymentGatewayMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_payment_gateway_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shopify_store_id')->unsigned();
            $table->integer('shopify_payment_gateway_id')->unsigned();
            $table->integer('rex_payment_method_external_id')->unsigned()->nullable();
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
        Schema::dropIfExists('shopify_payment_gateway_mappings');
    }
}
