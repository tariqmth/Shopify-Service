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
use App\Models\Location\RexOutlet;
use App\Models\Apis\Retailexpress\AuthenticationAPI;
use GuzzleHttp\Client;
use App\Models\Apis\Api;

class FulfilmentAPI extends Api
{
	protected $outlet_id;
	protected $rex_sales_channel_id;
	protected $rex_api_access_token;	
	// a5c522ec-defa-4dfb-8904-0fa62dc47e94 client key dev-tariq
	function __construct($outlet_id)
	{
		// find or fail client id
		$outlet = RexOutlet
            ::findOrFail($outlet_id);
        $this->external_id  = $outlet->external_id ;
        $this->rex_sales_channel_id = $outlet->rex_sales_channel_id;
		$this->rex_api_access_token = $this->get_access_token();	
	}

	private function get_access_token()
	{
		$auth_api = new AuthenticationAPI($this->rex_sales_channel_id) ;
        return $auth_api->get_access_token();
	}
	public function get_retailexpress_access_token()
	{
		return $this->rex_api_access_token;
	}

	public function get_shippit_api_key()
	{

		$client = new \GuzzleHttp\Client();
		$api_url = Api::get_rex_fulfilment_api_url();
		$response = $client->get($api_url, 
			[	
			'headers'=>
				['Authorization' => "Bearer {$this->rex_api_access_token}"],
			'query' => 
				['outlet_id'=>$this->external_id] 				
			]
		);
		$code = $response->getStatusCode(); // 200
		$reason = $response->getReasonPhrase(); // OK
		if ($code === 200)
		{
			$content = json_decode($response->getBody());
			$total_record = $content->total_records;
			$outlet = $content->data;
			if ($total_record > 0 )
			{
				$data = $outlet[0]->outlet_model;
				return !empty($data->api_key) ? $data->api_key: '';
			}
		}
	}

}
