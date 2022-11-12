<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRexOrderIdForeignKeyToRexVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_vouchers', function (Blueprint $table) {
            $table->foreign('rex_order_id')
                ->references('id')
                ->on('rex_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_vouchers', function (Blueprint $table) {
            $table->dropForeign('rex_vouchers_rex_order_id_foreign');
        });
    }
}
