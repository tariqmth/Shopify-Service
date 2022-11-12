<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUniqueOnShopifyProductVariants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_product_variants', function (Blueprint $table) {
            $table->dropUnique('shopify_product_variants_external_id_unique');
            $table->unique(['external_id', 'deleted']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_product_variants', function (Blueprint $table) {
            $table->dropUnique('shopify_product_variants_external_id_deleted_unique');
            $table->unique('external_id');
        });
    }
}
