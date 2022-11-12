<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ProductFields\ShopifyProductFieldRepository;

class CreateShopifyProductFieldMappings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_product_field_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shopify_store_id')->unsigned()->nullable()->index();
            $table->integer('shopify_product_field_id')->unsigned()->index();
            $table->string('rex_product_field_name');
            $table->timestamps();
        });

        $titleField = DB::table('shopify_product_fields')->where('name', 'title')->first();

        if (isset($titleField)) {
            DB::table('shopify_product_field_mappings')->insert([
                'shopify_product_field_id'  => $titleField->id,
                'rex_product_field_name'    => 'Description'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_product_field_mappings');
    }
}
