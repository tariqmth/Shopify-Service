<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToRexInventory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_inventory', function (Blueprint $table) {
            $table->foreign('rex_product_id')
                ->references('id')
                ->on('rex_products')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->foreign('rex_outlet_id')
                ->references('id')
                ->on('rex_outlets')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique(['rex_product_id', 'rex_outlet_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_inventory', function (Blueprint $table) {
            $table->dropForeign('rex_inventory_rex_product_id_foreign');
            $table->dropForeign('rex_inventory_rex_outlet_id_foreign');
            $table->dropUnique('rex_inventory_rex_product_id_rex_outlet_id_unique');
        });
    }
}
