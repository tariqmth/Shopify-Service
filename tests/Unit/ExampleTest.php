<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setting\ClickAndCollectSetting; 
use imelgrat\GoogleMapsTimeZone\GoogleMapsTimeZone;
use DateTime;
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    //define('API_KEY', env('GOOGLE_MAPS_TIMEZONE_API_KEY'));

    // Initialize GoogleMapsTimeZone object (New York City coordinates)
   // $timezone_object = new GoogleMapsTimeZone(40.730610, -73.935242, 0, GoogleMapsTimeZone::FORMAT_JSON);
    
    // Set Google API key
   // $timezone_object->setApiKey(API_KEY);
    
    // Perform query 
    //$timezone_data = $timezone_object->queryTimeZone();
    
    echo '<pre>';
    print_r($this->getPHPtimezone(40.730610, -73.935242));
    echo '</pre>';


        // Record found with google api key value
        $clickAndCollectSetting = ClickAndCollectSetting::where('shopify_store_id', 725)->first();
        echo $clickAndCollectSetting->google_api_key;
        $this->assertNotNull($clickAndCollectSetting->google_api_key);
        // Record found with google api key empty value
        $clickAndCollectSetting = ClickAndCollectSetting::where('shopify_store_id', 748)->first();
        echo $clickAndCollectSetting->google_api_key;
        $this->assertEmpty($clickAndCollectSetting->google_api_key);
        // Record Not found with google api key empty value
        $clickAndCollectSetting = ClickAndCollectSetting::where('shopify_store_id', 999)->first();
        
        if ($clickAndCollectSetting){
        	echo $clickAndCollectSetting->google_api_key;
    	}else{
    		echo 'not found';
    	}

//        $this->assert($clickAndCollectSetting->google_api_key);

    }

    /**
     * Attempts to find the closest timezone ID by coordinates using only PHP
     * 
     * @param $latitude Latitude decimal
     * @param $longitude Longitude decimal
     * @return string $timezone The time zone identifier, same as IANA/Olson zone name
     * 
     */
    function getPHPtimezone($latitude, $longitude) {
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
