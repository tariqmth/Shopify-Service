<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AttributeForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_attributes', function(Blueprint $table) {
			$table->foreign('client_id')
						->references('id')
						->on('clients')
						->onDelete('cascade')
						->onUpdate('restrict');
		});
        Schema::table('rex_attribute_options', function(Blueprint $table) {
			$table->foreign('rex_attribute_id')
						->references('id')
						->on('rex_attributes')
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
        Schema::table('rex_attributes', function(Blueprint $table) {
			$table->dropForeign('rex_attributes_client_id_foreign');
		});
        Schema::table('rex_attribute_options', function(Blueprint $table) {
			$table->dropForeign('rex_attribute_options_rex_attribute_id_foreign');
		});
    }
}
