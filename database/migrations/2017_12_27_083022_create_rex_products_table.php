<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('rex_sales_channel_id')->unsigned();
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->bigInteger('rex_product_group_id')->unsigned()->nullable();
            $table->boolean('has_size')->nullable();
            $table->boolean('has_colour')->nullable();
            $table->timestamp('latest_version')->nullable();
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
        Schema::dropIfExists('rex_products');
    }
}
