<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAvailableStockToRexProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_products', function (Blueprint $table) {
            $table->integer('available_stock')->default(null)->nullable(true);
            $table->integer('rex_product_type_id')->default(null)->nullable(true)->index();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_products', function (Blueprint $table) {
            $table->dropColumn('available_stock');
            $table->dropColumn('rex_product_type_id');
        });
    }
}
