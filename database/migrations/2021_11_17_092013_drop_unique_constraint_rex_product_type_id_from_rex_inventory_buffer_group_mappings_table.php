<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropUniqueConstraintRexProductTypeIdFromRexInventoryBufferGroupMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_inventory_buffer_group_mappings', function (Blueprint $table) {
            $table->dropUnique('rex_inventory_buffer_group_mappings_rex_product_type_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_inventory_buffer_group_mappings', function (Blueprint $table) {
            //Put the index back when the migration is rolled back
            $table->unique('rex_product_type_id');
        });
    }
}

