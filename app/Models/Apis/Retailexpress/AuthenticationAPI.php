<?php
namespace App\Models\Apis\Retailexpress;

/**
 * New Retailexpress API
 * call the Authentication API to retrieve an access token
 * /auth/authtoken endpoint
 * x-api-key = internal key
 * x-client-id = external_id from clients table
 */

use Illuminate\Database\Eloquent\Model;
use App\Models\CLient\CLient as RexClient;
use App\Models\Store\RexSalesChannel;
use GuzzleHttp\Client;
use App\Models\Apis\Api;

class AuthenticationAPI extends Api
{
	protected $client_key;
	protected $rex_api_access_token;	
	protected $rex_api_access_token_expiry;	
	protected $rex_sales_channel_id;
	function __construct($rex_sales_channel_id)
	{
		// find or fail sales channel id
		$rex_sales_channel = RexSalesChannel::findOrFail($rex_sales_channel_id);
		
		// find or fail client id
		$client = RexClient::findOrFail($rex_sales_channel->client_id);
		parent::__construct();
		$this->client_id = $client->id;
        $this->rex_sales_channel_id = $rex_sales_channel_id;
		$this->client_key = $client->external_id;
		$this->rex_api_access_token = $client->rex_api_access_token;	
		$this->rex_api_access_token_expiry = $client->rex_api_access_token_expiry;
	}

	public function get_access_token()
	{
		if ($this->is_access_token_expired() === true)
		{
			$this->rex_api_access_token = $this->get_auth_token();
		}
		return $this->rex_api_access_token;
	}
	private function get_auth_token()
	{
		$client = new \GuzzleHttp\Client();
		$header =  [	
			'headers'=>
				[
					'x-api-key' => $this->ApiInternalKey,
					'x-client-id' => $this->client_key
				]
			];

		$api_url = Api::get_rex_auth_api_url();

		$response = $client->get($api_url, 
			$header
		);
		$code = $response->getStatusCode(); // 200
		$reason = $response->getReasonPhrase(); // OK
		if ($code === 200)
		{
			$content = json_decode($response->getBody());
			$client = RexClient::findOrFail($this->client_id);
			$client->rex_api_access_token = $content->access_token;
			$client->rex_api_access_token_expiry = $content->expires_on;
			$client->save();
			$this->rex_api_access_token = $client->rex_api_access_token;
			return $this->rex_api_access_token;
		}
	}
	private function is_access_token_expired()
	{
		//if null or no value
		if (empty($this->rex_api_access_token_expiry) ||
		empty($this->rex_api_access_token) )
		{
			return true;
		}
		// if date time passed and token expired
		if ($this->rex_api_access_token_expiry)
		{
			// conversaion from gmt to utc and validation
			$token_create_date = date_create($this->rex_api_access_token_expiry);
			$token_create_date->setTimezone(new \DateTimeZone("UTC"));
			$date_utc = new \DateTime("now", new \DateTimeZone("UTC"));
			$date_utc = $date_utc->getTimestamp();
			$token_create_date = $token_create_date->getTimestamp();
			if ($token_create_date < $date_utc)
			{
				//echo " expired token date: ".$token_create_date." current date: ".$date_utc;

				return true;				
			}
			else
			{
				//echo "not expired token date: ".$token_create_date." current date: ".$date_utc;
				return false; // return valid token
			}
		}
	}

}
