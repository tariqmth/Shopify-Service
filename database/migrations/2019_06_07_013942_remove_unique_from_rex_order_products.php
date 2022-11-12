<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUniqueFromRexOrderProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_order_products', function (Blueprint $table) {
            $table->index('rex_order_id');
            $table->index('rex_product_id');
            $table->dropUnique(['rex_order_id', 'rex_product_id', 'price']);
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
            $table->unique(['rex_order_id', 'rex_product_id', 'price']);
            $table->dropIndex('rex_order_products_rex_order_id_index');
            $table->dropIndex('rex_order_products_rex_product_id_index');
        });
    }
}
