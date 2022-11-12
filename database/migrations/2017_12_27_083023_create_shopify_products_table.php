<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('shopify_store_id')->unsigned();
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->bigInteger('rex_product_group_id')->unsigned()->nullable();
            $table->bigInteger('rex_product_id')->unsigned()->nullable();
            $table->boolean('active')->nullable();
            $table->timestamp('latest_version')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->integer('sync_count')->unsigned()->nullable();
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
        Schema::dropIfExists('shopify_products');
    }
}
