<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexInventoryBufferGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_inventory_buffer_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',100)->unique();//    Max 100 characters
            $table->integer('rex_sales_channel_id');
            $table->integer('quantity');//    must be 0 or a positive integer
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
        Schema::dropIfExists('rex_inventory_buffer_groups');
    }
}
