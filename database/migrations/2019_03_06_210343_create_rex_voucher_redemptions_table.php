<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRexVoucherRedemptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rex_voucher_redemptions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rex_voucher_id')->unsigned();
            $table->decimal('amount', 12, 4);
            $table->bigInteger('rex_payment_external_id');
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
        Schema::dropIfExists('rex_voucher_redemptions');
    }
}
