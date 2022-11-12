<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shopify_store_id')->unsigned();
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->integer('rex_voucher_id')->unsigned()->nullable();
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
        Schema::dropIfExists('shopify_vouchers');
    }
}
