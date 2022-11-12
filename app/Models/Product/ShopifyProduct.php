<?php

namespace App\Models\Product;

use Shopify\ShopifyClient;

class ShopifyProduct extends Product
{
    protected $fillable = array('shopify_store_id', 'external_id', 'rex_product_id', 'rex_product_group_id');

    /**
	 * Get store that product belongs to
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore', 'shopify_store_id');
	}

	public function rexProduct()
	{
		return $this->belongsTo('App\Models\Product\RexProduct');
	}

	public function rexProductGroup()
	{
		return $this->belongsTo('App\Models\Product\RexProductGroup');
	}

	public function shopifyProductVariants()
	{
		return $this->hasMany('App\Models\Product\ShopifyProductVariant');
	}
}
