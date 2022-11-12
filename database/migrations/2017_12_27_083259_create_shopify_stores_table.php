<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_stores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->unsigned()->index();
            $table->integer('rex_sales_channel_id')->unsigned()->nullable()->index();
            $table->string('subdomain')->nullable();
            $table->string('access_token')->nullable();
            $table->string('access_code')->nullable();
            $table->string('api_key')->nullable();
            $table->string('password')->nullable();
            $table->decimal('api_delay', 16, 4)->nullable()->index();
            $table->boolean('enabled');
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
        Schema::dropIfExists('shopify_stores');
    }
}
