<?php

namespace App\Models\Product;

class ShopifyProductVariant extends Product
{
    protected $fillable = array('shopify_store_id', 'external_id', 'shopify_product_id', 'rex_product_id');

    /**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function rexProduct()
	{
		return $this->belongsTo('App\Models\Product\RexProduct');
	}

	public function shopifyProduct()
	{
		return $this->belongsTo('App\Models\Product\ShopifyProduct');
	}

	/**
	 * Get store that product belongs to
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function shopifyStore()
	{
		return $this->shopifyProduct->shopifyStore();
	}

	public function shopifyInventoryItem()
	{
		return $this->hasOne('App\Models\Inventory\ShopifyInventoryItem');
	}
}
