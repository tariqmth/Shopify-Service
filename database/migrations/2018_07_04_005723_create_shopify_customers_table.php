<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->integer('shopify_store_id')->unsigned()->index();
            $table->string('email')->nullable();
            $table->integer('rex_customer_id')->unsigned()->nullable()->index();
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
        Schema::dropIfExists('shopify_customers');
    }
}
