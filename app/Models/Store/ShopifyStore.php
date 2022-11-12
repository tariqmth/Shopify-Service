<?php

namespace App\Models\Store;

class ShopifyStore extends Store
{
    const SETUP_STATUS_NEW = 'new';
    const SETUP_STATUS_PENDING = 'pending';
    const SETUP_STATUS_LOADING = 'loading';
    const SETUP_STATUS_CONFIRMATION = 'confirmation';
    const SETUP_STATUS_COMPLETE = 'complete';
    const SETUP_STATUS_RECONNECT = 'reconnect';

    protected $fillable = array('client_id', 'rex_sales_channel_id');

    protected $table = 'shopify_stores';

    /**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function products()
	{
		return $this->hasMany('App\Models\Product\ShopifyProduct');
	}

	public function shopifyCustomers()
	{
		return $this->hasMany('App\Models\Customer\ShopifyCustomer');
	}

	public function shopifyLocations()
	{
		return $this->hasMany('App\Models\Location\ShopifyLocation');
	}

	public function shopifyOrders()
	{
		return $this->hasMany('App\Models\Order\ShopifyOrder');
	}

	public function shopifyVouchers()
	{
		return $this->hasMany('App\Models\Voucher\ShopifyVoucher');
	}

	public function rexSalesChannel()
    {
        return $this->belongsTo('App\Models\Store\RexSalesChannel');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client\Client');
    }

    public function shopifyFulFillmentService()
    {
        return $this->hasOne('App\Models\Location\ShopifyFulfillmentService');
    }

    public function shopifyPaymentGatewayMappings()
	{
		return $this->hasMany('App\Models\Payment\ShopifyPaymentGatewayMapping');
	}

	public function shopifyProductFieldMappings()
	{
		return $this->hasMany('App\Models\ProductFields\ShopifyProductFieldMapping');
	}

	public function shopifyWebhooks()
	{
		return $this->hasMany('App\Models\Notification\ShopifyWebhook');
	}

	public function clickAndCollectSetting()
    {
        return $this->hasOne('App\Models\Setting\ClickAndCollectSetting');
    }

    public function clearCredentials()
    {
        $this->access_token = null;
        $this->access_code = null;
        $this->api_key = null;
        $this->password = null;
    }

    public function deleteAllChildren()
    {
        $this->products()->delete();
        $this->shopifyFulFillmentService()->delete();
        $this->shopifyPaymentGatewayMappings()->delete();
        $this->shopifyProductFieldMappings()->delete();
        $this->shopifyWebhooks()->delete();
        $this->shopifyCustomers()->delete();
        $this->shopifyLocations()->delete();
        $this->shopifyOrders()->delete();
        $this->shopifyVouchers()->delete();
    }
}
