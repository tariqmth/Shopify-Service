<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateShopifyProductFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_product_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->string('label');
            $table->timestamps();
        });

        DB::table('shopify_product_fields')->insert([
            'name'  => 'title',
            'label' => 'Product Title'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_product_fields');
    }
}
