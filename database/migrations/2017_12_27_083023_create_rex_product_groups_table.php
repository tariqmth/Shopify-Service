<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexProductGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_product_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('rex_sales_channel_id')->unsigned();
            $table->string('code');
            $table->timestamp('latest_version')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->integer('sync_count')->unsigned()->nullable();
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
        Schema::dropIfExists('rex_product_groups');
    }
}
