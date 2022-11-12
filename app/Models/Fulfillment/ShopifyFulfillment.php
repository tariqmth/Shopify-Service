<?php

namespace App\Models\Fulfillment;

use App\Models\Syncable;

class ShopifyFulfillment extends Syncable
{
    public function shopifyOrder()
	{
		return $this->belongsTo('App\Models\Order\ShopifyOrder');
	}

	public function rexFulfillmentBatch()
	{
		return $this->belongsTo('App\Models\Fulfillment\RexFulfillmentBatch');
	}

	public function shopifyVoucherProduct()
	{
		return $this->belongsTo('App\Models\Product\ShopifyProduct', 'shopify_voucher_product_id');
	}

	public function shopifyFulfillmentItems()
    {
        return $this->hasMany('App\Models\Fulfillment\ShopifyFulfillmentItem');
    }

    public function isFromForeignSource()
    {
        return !$this->rex_fulfillment_batch_id && !$this->shopify_voucher_product_id;
    }
}