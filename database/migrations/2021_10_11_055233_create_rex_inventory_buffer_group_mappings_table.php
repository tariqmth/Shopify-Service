<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexInventoryBufferGroupMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_inventory_buffer_group_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id')->unsigned();
            $table->index('group_id');
            $table->foreign('group_id')->references('id')->on('rex_inventory_buffer_groups')->onDelete('cascade');
            $table->integer('rex_product_type_id')->unique()->index(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rex_inventory_buffer_group_mappings');
    }
}
