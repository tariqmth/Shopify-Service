<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setting\ClickAndCollectSetting; 
use App\Models\Apis\Retailexpress\AuthenticationAPI;
use App\Models\Apis\Retailexpress\FulfilmentAPI;
use App\Models\Apis\Shippit\QuotesAPI;
use App\Models\Location\RexOutlet;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class QuoteAPITest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
//  // a5c522ec-defa-4dfb-8904-0fa62dc47e94 client key dev-tariq
        $rex_outlet_id = 115;
        $rex_outlet = RexOutlet::findOrFail($rex_outlet_id);
        // get api key shippit_api_key

        $fulfilment_api = new \App\Models\Apis\Retailexpress\FulfilmentAPI($rex_outlet_id); // outlet id
        echo '==============rex get_access_token =============';
        echo $fulfilment_api->get_retailexpress_access_token();
        echo "\n\n\n".'==============shippit api key =============';
        $shippit_api_key = $fulfilment_api->get_shippit_api_key();

        echo $rex_outlet->shippit_api_key;
        
      //  $rex_outlet->shippit_api_key = $shippit_api_key;
    //    $rex_outlet->save();

        $quote= 
        [
            "dropoff_postcode"=> "4558",
            "dropoff_state"=> "QLD",
            "dropoff_suburb"=> "Maroochydore",
            "latitude"=>"-26.6520957",
            "longitude"=> "153.0826248",            
            "return_all_quotes"=> true,
            "parcel_attributes"=> ["qty"=> 1],
        ];
        
        $quote_api = new QuotesAPI($rex_outlet->shippit_api_key,$quote);
        // fake response
       // print_r($this->sample_data()->response);
        $quote_api->filter_response($this->sample_data()->response);
 //       print_r($quote_api->get_quotes());

       $quote_api->set_cost_cutoff(20.00);
        $quote_api->set_deliver_within(8);

        print_r($quote_api->get_quotes());

        $subdomain = 'dev-tariq';
        // perform test on controller
//        $response = $this->json('GET','shopify_stores/'.$subdomain.'/outlets',$quote)->assertStatus(200)
 //           ->assertJsonStructure(['error']);;
    
   //    var_dump($response);
        echo " ** FINISHED ** ";
    }

    public function sample_data()
    {
        $current_date = date("Y-m-d");
        return json_decode('{
    "response": [
        {
            "success": true,
            "courier_type": "CouriersPlease",
            "service_level": "standard",
            "quotes": [
                {
                    "price": 7.6,
                    "estimated_transit_time": "3 business days"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "Fastway",
            "service_level": "standard",
            "quotes": [
                {
                    "price": 17.18,
                    "estimated_transit_time": "1 business day"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "Tnt",
            "service_level": "standard",
            "quotes": [
                {
                    "price": 44.59,
                    "estimated_transit_time": "4 business days"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "AlliedExpressOvernight",
            "service_level": "standard",
            "quotes": [
                {
                    "price": 75.57,
                    "estimated_transit_time": "4 business days"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "Priority",
            "service_level": "priority",
            "quotes": [
                {
                    "delivery_date": "2021-03-23",
                    "delivery_window": "13:00-16:00",
                    "delivery_window_desc": "1PM-4PM",
                    "price": 6.04,
                    "courier_type": "AlliedExpressSameday"
                },
                {
                    "delivery_date": "2021-03-23",
                    "delivery_window": "16:00-19:00",
                    "delivery_window_desc": "4PM-7PM",
                    "price": 14.63,
                    "courier_type": "AlliedExpressSameday"
                },
                {
                    "delivery_date": "2021-03-23",
                    "delivery_window": "19:00-22:00",
                    "delivery_window_desc": "7PM-10PM",
                    "price": 18.92,
                    "courier_type": "YelloOndemand"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "InXpress",
            "service_level": "express",
            "quotes": [
                {
                    "price": 20.05,
                    "estimated_transit_time": "2 business days"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "TntOvernightExpress",
            "service_level": "express",
            "quotes": [
                {
                    "price": 20.09,
                    "estimated_transit_time": "1 business day"
                }
            ]
        },
        {
            "success": true,
            "courier_type": "ClickAndCollect",
            "service_level": "click_and_collect",
            "quotes": [
                {
                    "price": 0,
                    "estimated_transit_time": "0 business days"
                }
            ]
        }
    ],
    "count": 8
}');
    }

}
