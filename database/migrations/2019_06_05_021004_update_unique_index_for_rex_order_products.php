<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUniqueIndexForRexOrderProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_order_products', function (Blueprint $table) {
            $table->unique(['rex_order_id', 'rex_product_id', 'price']);
            $table->dropUnique(['rex_order_id', 'rex_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_order_products', function (Blueprint $table) {
            $table->unique(['rex_order_id', 'rex_product_id']);
            $table->dropUnique(['rex_order_id', 'rex_product_id', 'price']);
        });
    }
}
