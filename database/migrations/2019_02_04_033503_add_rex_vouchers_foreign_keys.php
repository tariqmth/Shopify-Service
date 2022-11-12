<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRexVouchersForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_vouchers', function(Blueprint $table) {
			$table->foreign('rex_sales_channel_id')
                ->references('id')
                ->on('rex_sales_channels')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_sales_channel_id', 'external_id'));
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_vouchers', function(Blueprint $table) {
			$table->dropForeign('rex_vouchers_rex_sales_channel_id_foreign');
			$table->dropUnique('rex_vouchers_rex_sales_channel_id_external_id_unique');
		});
    }
}
