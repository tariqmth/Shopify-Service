<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsPrimaryToShopifyLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_locations', function (Blueprint $table) {
            $table->integer('shopify_fulfillment_service_id')->nullable()->unsigned()->change();
            $table->boolean('is_primary')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_locations', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
}
