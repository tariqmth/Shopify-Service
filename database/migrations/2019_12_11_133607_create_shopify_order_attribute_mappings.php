<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateShopifyOrderAttributeMappings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_order_attribute_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shopify_store_id')->unsigned()->nullable()->index();
            $table->string('shopify_order_attribute')->index();
            $table->string('rex_order_field');
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
        Schema::dropIfExists('shopify_order_attribute_mappings');
    }
}
