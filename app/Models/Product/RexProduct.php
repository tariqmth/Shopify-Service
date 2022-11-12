<?php

namespace App\Models\Product;

class RexProduct extends Product
{
    protected $fillable = array('rex_sales_channel_id', 'external_id');

    /**
	 * Get store that product belongs to
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function rexSalesChannel()
	{
		return $this->belongsTo('App\Models\Store\RexSalesChannel', 'rex_sales_channel_id');
	}

	public function rexProductGroup()
	{
		return $this->belongsTo('App\Models\Product\RexProductGroup', 'rex_product_group_id');
	}

	public function shopifyProduct()
	{
		return $this->hasOne('App\Models\Product\ShopifyProduct');
	}

	public function shopifyProductVariants()
	{
		return $this->hasMany('App\Models\Product\ShopifyProductVariant');
	}

	public function rexInventory()
	{
		return $this->hasMany('App\Models\Inventory\RexInventory');
	}

    public function belongsToGroup()
    {
        return isset($this->rexProductGroup);
    }

    public function isAssociatedWith(ShopifyProduct $shopifyProduct)
    {
        return $this->isDirectlyAssociatedWith($shopifyProduct) || $this->isIndirectlyAssociatedWith($shopifyProduct);
    }

    public function isDirectlyAssociatedWith(ShopifyProduct $shopifyProduct)
    {
        return isset($this->shopifyProduct) && $this->shopifyProduct->is($shopifyProduct);
    }

    public function isIndirectlyAssociatedWith(ShopifyProduct $shopifyProduct)
    {
        return isset($this->rexProductGroup) && $this->rexProductGroup->isAssociatedWith($shopifyProduct);
    }
}
