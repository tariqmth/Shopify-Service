<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueConstraintNameAndSalesChannelToRexInventoryBufferGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_inventory_buffer_groups', function (Blueprint $table) {
            $table->unique(["name", "rex_sales_channel_id"], 'buffer_group_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_inventory_buffer_groups', function (Blueprint $table) {
            $table->dropUnique('buffer_group_name_unique');
        });
    }
}
