<?php
namespace App\Models\Apis;

/**
 * Api General functionality
 */

use Illuminate\Database\Eloquent\Model;

class Api extends Model
{
	// RetailExpress Fulfilment API Configurations
	protected $ApiInternalKey;
	protected $RetailexpressFulfilmentApiUrl;
	protected $RetailexpressFulfilmentApiHostName;
	protected $RetailexpressFulfilmentApiVersion;

	// RetailExpress Auth API Configurations
	protected $RetailexpressAuthApiUrl;
	protected $RetailexpressAuthApiHostName;
	protected $RetailexpressAuthApiVersion;

	// Shippit Quotes API Configurations
	protected $ShippitApiUrl;
	protected $ShippitApiHostName;
	protected $ShippitApiVersion;

	function __construct()
	{
		$this->ApiInternalKey= env('API_INTERNAL_KEY'); //'c112306b42be4341a480c0d970feee42'; // should be from config;
	}
	public function get_rex_auth_api_url()
	{
		// RetailExpress Auth API Configurations
		$this->RetailexpressAuthApiUrl = env('RETAILEXPRESS_AUTH_API_URL'); //"https://devappasvaut001.azurewebsites.net/";
		$this->RetailexpressAuthApiHostName	= env('RETAILEXPRESS_AUTH_API_HOST_NAME'); //'/auth/token';
		$this->RetailexpressAuthApiVersion 	= env('RETAILEXPRESS_AUTH_API_VERSION'); //'v1';

		return $this->RetailexpressAuthApiUrl.$this->RetailexpressAuthApiVersion.$this->RetailexpressAuthApiHostName;
	}

	public function get_rex_fulfilment_api_url()
	{
		// RetailExpress Auth API Configurations
		$this->RetailexpressFulfilmentApiUrl = env('RETAILEXPRESS_FULFILMENT_API_URL'); //"https://devappasvfsv001.azurewebsites.net/";
		$this->RetailexpressFulfilmentApiHostName	= env('RETAILEXPRESS_FULFILMENT_API_HOST_NAME') ;//'/fulfilment/serviceoutlets/shippit';
		$this->RetailexpressFulfilmentApiVersion 	= env('RETAILEXPRESS_FULFILMENT_API_VERSION') ;//'v2';

		return $this->RetailexpressFulfilmentApiUrl.$this->RetailexpressFulfilmentApiVersion.$this->RetailexpressFulfilmentApiHostName;
	}
	public function get_shippit_api_url()
	{
		// Shippit Quotes API Configurations
		$this->ShippitApiUrl = env('SHIPPIT_API_URL'); //"https://app.staging.shippit.com/api/";
		$this->ShippitApiHostName	=env('SHIPPIT_API_HOST_NAME'); // '/quotes';
		$this->ShippitApiVersion 	= env('SHIPPIT_API_VERSION'); //'3';
		return $this->ShippitApiUrl.$this->ShippitApiVersion.$this->ShippitApiHostName;
	}
}