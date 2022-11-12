<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyProductFieldMappingForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_product_field_mappings', function(Blueprint $table) {
			$table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->foreign(
                    'shopify_product_field_id',
                    'shopify_product_field_mappings_field_id_foreign'
                )
                ->references('id')
                ->on('shopify_product_fields')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique(
                array('shopify_store_id','shopify_product_field_id'),
                'shopify_product_field_mappings_store_field_unique'
            );
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
