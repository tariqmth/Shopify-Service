<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_inventory', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('rex_product_id')->unsigned()->index();
            $table->integer('rex_outlet_id')->unsigned()->index();
            $table->integer('quantity');
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
        Schema::dropIfExists('rex_inventory');
    }
}
