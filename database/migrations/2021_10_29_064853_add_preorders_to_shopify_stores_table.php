<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPreordersToShopifyStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->tinyInteger('preorders')
                ->default('1')
                ->comment('accepts an id indicating current setting: 1: Disabled 2: Sell On Order stock for Pre-Order Products 3: Accept Pre-Orders for Pre-Order Products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->dropColumn('preorders');
        });
    }
}
