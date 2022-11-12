<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToVoucherAdjustments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_voucher_redemptions', function (Blueprint $table) {
            $table->foreign('rex_voucher_id')
                ->references('id')
                ->on('rex_vouchers')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique(['rex_voucher_id', 'rex_payment_external_id'], 'rex_voucher_redemptions_payment_unique');
        });

        Schema::table('shopify_voucher_adjustments', function (Blueprint $table) {
            $table->foreign('shopify_voucher_id')
                ->references('id')
                ->on('shopify_vouchers')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->foreign('rex_voucher_redemption_id')
                ->references('id')
                ->on('rex_voucher_redemptions')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique('external_id');
            $table->unique('rex_voucher_redemption_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_voucher_redemptions', function (Blueprint $table) {
            $table->dropForeign('rex_voucher_redemptions_rex_voucher_id_foreign');
            $table->dropUnique('rex_voucher_redemptions_payment_unique');
        });

        Schema::table('shopify_voucher_adjustments', function (Blueprint $table) {
            $table->dropForeign('shopify_voucher_adjustments_shopify_voucher_id_foreign');
            $table->dropForeign('shopify_voucher_adjustments_rex_voucher_redemption_id_foreign');
            $table->dropUnique('shopify_voucher_adjustments_external_id_unique');
            $table->dropUnique('shopify_voucher_adjustments_rex_voucher_redemption_id_unique');
        });
    }
}
