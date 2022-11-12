<?php
namespace App\Models\Apis\Shippit;

/**
 * Shippit API
 * Retrieve Quote
 * Submits a request for Quotes from different couriers on Shippit.
 * staging url https://app.staging.shippit.com/api/3/quotes
 * production url https://app.shippit.com/api/3/quotes
 *
 * Documentation https://developer.shippit.com/#tag/Quote
 */

use Illuminate\Database\Eloquent\Model;
use App\Models\CLient\CLient as RexClient;
use App\Models\Store\RexSalesChannel;
use GuzzleHttp\Client;
use App\Models\Apis\Api;
use DateTime;
use DateTimeZone;
use DateInterval;
use Illuminate\Support\Facades\Log;

class QuotesAPI extends Api
{
	protected $shippit_api_key;
	protected $dropoff_postcode;
	protected $dropoff_state;
	protected $dropoff_suburb;	
	protected $return_all_quotes = true;	
	protected $parcel_qty = 1;
	protected $cost_cutoff = 0.00;
	protected $deliver_within  = 0;
	protected $priority_quotes=[];
	protected $longitude;
	protected $latitude;
	function __construct($shippit_api_key,$quote_request)
	{

		$this->shippit_api_key = $shippit_api_key;
		$this->dropoff_postcode = $quote_request['dropoff_postcode'];
		$this->dropoff_state = $quote_request['dropoff_state'];
		$this->dropoff_suburb = $quote_request['dropoff_suburb'];
		$parcel = $quote_request['parcel_attributes'];
		$this->parcel_qty = $parcel['qty'];
		$this->latitude = $quote_request['latitude'];
		$this->longitude = $quote_request['longitude'];		
	}

	private function call_shippit_api()
	{
		$client = new \GuzzleHttp\Client();
		$delivery_time_zone = $this->getPHPtimezone($this->latitude,$this->longitude);	
		$order_date = new DateTime("now", new DateTimeZone($delivery_time_zone));
		 			
		$quote['quote'] = 
		[
		    "dropoff_postcode"=> $this->dropoff_postcode,
    		"dropoff_state"=> $this->dropoff_state,
    		"dropoff_suburb"=> $this->dropoff_suburb,
    		"order_date"=> $order_date->format('Y-m-d'),
    		"return_all_quotes"=> true,
    		"parcel_attributes"=> [["qty"=> $this->parcel_qty]]
		];
		
		$header =  [	
			'headers'=>
				[
					'Authorization' => "Bearer {$this->shippit_api_key}",
					'Content-Type' => 'application/json'
				],
			'body'=>json_encode($quote)
			];
		
		//Log::debug('Request: ' . json_encode($quote));

		$content ='';
		$api_url = Api::get_shippit_api_url(); 
		$response = $client->post($api_url, 
			$header
		);

		//Log::debug('Response Body: ' . $response->getBody());

		$code = $response->getStatusCode(); // 200
		$reason = $response->getReasonPhrase(); // OK
		if ($code === 200)
		{
			$content = json_decode($response->getBody());
		}		
		return $content;
	}
	public function get_quotes()
	{		

		if (empty($this->priority_quotes))
		{
			$content = $this->call_shippit_api();
			$response = $content->response; 
			if ($content->count > 0)
			{
				$this->filter_response($response);
			}			
		}

		if ($this->cost_cutoff > 0)
		{
			$this->validate_cost_cutoff();
		}
		if ($this->deliver_within > 0)
		{
			$this->validate_deliver_within();
		}

		return $this->priority_quotes;
	}
	public function filter_response($response)
	{
		if (empty($response))
		{
			return false;
		}
		foreach ($response as $r=>$q) {

			$q_val = $q->quotes;
			if ($q->service_level === 'priority' && 
				$q->courier_type === 'Priority' && 
				$q->success === true &&
				!empty($q->quotes)
			)
			{
				$this->priority_quotes[$q->service_level.'_'.$q->courier_type] = $q_val;
			}					
		}
	}

	private function validate_deliver_within()
	{
	/*
	* The allowed range is current time + "delivery_within" where delivery_within is a number
	* of hours that are added on to the current time i.e. a delivery driver must be available
	* to delivery the order within 3 hours
	* For example, assume the following "delivery_window": "15:25-18:25"
	* If current time is 14:06 and "delivery_within" is 3, we need to check if 14:06+3 hours, 
	* which is 17:06 <= 18:25. This is true so this is considered to be a valid delivery option
	* Alternatively if current time was 16:55 then we need to check if 16:55 + 3 hours, 
	* which is 19:55 < 18:25. This is false so this isn't considered to be a valid delivery option
	  Sample format
	 			{
                    "delivery_date": "2021-02-24",
                    "delivery_window": "15:35-18:35",
                    "delivery_window_desc": "3PM-6PM",
                    "price": 18.92,
                    "courier_type": "YelloOndemand"
                },
	*/
		if ($this->deliver_within == 0)
		{
			return;
		}

		$valid_quotes = [];
		foreach ($this->priority_quotes as $key => $value) 
		{
				$current_date_with_deliver_within = date("Y-m-d H:i:s", strtotime(sprintf("+%d hours", $this->deliver_within)));
				// Get timezone based on langitude and latitude using Php DateTimeZone Class
				$delivery_time_zone = $this->getPHPtimezone($this->latitude,$this->longitude);	
				$c_date = new DateTime("now", new DateTimeZone($delivery_time_zone));
				$c_date->add(new DateInterval('PT'.$this->deliver_within.'H'));
				foreach ($value as $qv) 
				{
					$quote_value = get_object_vars($qv);
					$delivery_date = isset($quote_value['delivery_date']) ? $quote_value['delivery_date']:false;
					$delivery_window = isset($quote_value['delivery_window']) ? $quote_value['delivery_window']:false;


					if ($delivery_date && $delivery_window)
					{
						list($delivery_window_from,$delivery_window_to) = explode('-',$delivery_window);
						$delivery_date_from = $delivery_date.' '.$delivery_window_from.":00"; 
						$delivery_date_to = $delivery_date.' '.$delivery_window_to.":00"; 
						$delivery_date_to = new DateTime($delivery_date_to, new DateTimeZone($delivery_time_zone));
						// validate number hours with in delivery window based on current date

						// echo "\n current target: ".$c_date->format('Y-m-d H:i:s e')."  deliver to: ".$delivery_date_to->format('Y-m-d H:i:s e');
						// echo "\n target: ".$c_date->getTimestamp()." deliver to: ".$delivery_date_to->getTimestamp() ;

						if ($delivery_date_to->getTimestamp() <= $c_date->getTimestamp())
						{
							// echo "\n found valid delivery window !.";
							$valid_quotes[$key][] = $qv;
						}
					}
			}
		}
		$this->priority_quotes = $valid_quotes;
	}


	private function validate_cost_cutoff()
	{
		// Find any items where the quoted price is < the "cost_cutoff" value in the request
    	// e.g. if "price" for quote is $24.00 and "cost_cutoff"=20.00 then this is not a valid quote

		if ($this->cost_cutoff == 0)
		{
			return;
		}

		$valid_quotes = [];
		foreach ($this->priority_quotes as $key => $value) {
			$quote_value = get_object_vars($value[0]);
			if ($quote_value['price'] < $this->cost_cutoff  )
			{
				$valid_quotes[$key] = $value;
			}
		}
		$this->priority_quotes = $valid_quotes;
	}

	public function set_cost_cutoff($cost_cutoff)
	{
		$this->cost_cutoff = $cost_cutoff;
	}

	public function get_cost_cutoff()
	{
		return $this->cost_cutoff;
	}
	public function set_deliver_within($deliver_within)
	{
		$this->deliver_within  = $deliver_within ;
	}

	public function get_deliver_within()
	{
		return $this->deliver_within;
	}
	/**
	 * Attempts to find the closest timezone ID by coordinates using only PHP
	 * 
	 * @param $latitude Latitude decimal
	 * @param $longitude Longitude decimal
	 * @return string $timezone The time zone identifier, same as IANA/Olson zone name
	 * 
	 */
	public function getPHPtimezone($latitude, $longitude) {
	    $diffs = array();
	    foreach(DateTimeZone::listIdentifiers() as $timezoneID) {
	          $timezone = new DateTimeZone($timezoneID);
	          $location = $timezone->getLocation();
	          $tLat = $location['latitude'];
	          $tLng = $location['longitude'];
	          $diffLat = abs($latitude - $tLat);
	          $diffLng = abs($longitude - $tLng);
	          $diff = $diffLat + $diffLng;
	          $diffs[$timezoneID] = $diff;
	    }
	 
	    $timezone = array_keys($diffs, min($diffs));
	    return $timezone[0];
	}
}
