<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddonLicensesForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('addon_licenses', function(Blueprint $table) {
			$table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('client_id', 'name'));
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('addon_licenses', function(Blueprint $table) {
			$table->dropForeign('addon_licenses_client_id_foreign');
			$table->dropUnique('addon_licenses_client_id_name_unique');
		});
    }
}
