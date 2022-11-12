<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('shopify_product_id')->unsigned();
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->bigInteger('rex_product_id')->unsigned()->nullable();
            $table->boolean('deleted')->nullable();
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
        Schema::dropIfExists('shopify_product_variants');
    }
}
