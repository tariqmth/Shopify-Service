<?php

namespace App\Models\Product;

class RexProductGroup extends Product
{
    protected $fillable = array('rex_sales_channel_id', 'code');

    /**
	 * Get store that product belongs to
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function rexSalesChannel()
    {
        return $this->belongsTo('App\Models\Store\RexSalesChannel', 'rex_sales_channel_id');
    }

    public function rexProducts()
	{
		return $this->hasMany('App\Models\Product\RexProduct');
	}

	public function shopifyProduct()
	{
		return $this->hasOne('App\Models\Product\ShopifyProduct');
	}

    public function getRexProductByExternalId($externalId)
    {
        foreach ($this->rexProducts as $rexProduct) {
            if ($rexProduct->external_id === $externalId) {
                return $rexProduct;
            }
        }
    }

    public function hasRexProducts()
    {
        return count($this->rexProducts) > 0;
    }

    public function isAssociatedWith(ShopifyProduct $shopifyProduct)
    {
        return isset($this->shopifyProduct) && $this->shopifyProduct->is($shopifyProduct);
    }
}
