<?php

namespace App\Models\Syncer;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Location\RexOutlet;
use App\Models\Store\RexSalesChannel;
use App\Models\Setting\ClickAndCollectSetting; 
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\SyncAllRexOutletsOut;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api as RexClient;
use RetailExpress\SkyLink\Sdk\Outlets\V2OutletRepository as RexOutletClient;
use RetailExpress\SkyLink\Sdk\ValueObjects\SalesChannelId;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Geocoder\Query\GeocodeQuery;
use App\Models\Client\Client;
use App\Models\Apis\Retailexpress\FulfilmentAPI;

class RexOutletSyncer extends RexSyncer
{
    protected $skylinkSdkFactory;

    public function __construct(SkylinkSdkFactory $skylinkSdkFactory)
    {
        $this->skylinkSdkFactory = $skylinkSdkFactory;
    }

    public function syncAllOut(RexSalesChannel $rexSalesChannel)
    {
        SyncAllRexOutletsOut::dispatch($rexSalesChannel)
            ->onConnection('database_sync')
            ->onQueue('outlet');
    }

    public function performSyncAllOut($rexSalesChannelId)
    {
        $rexSalesChannel = RexSalesChannel::findOrFail($rexSalesChannelId);
        $shopifyStore = $rexSalesChannel->shopifyStore;
        $clickAndCollectSetting = ClickAndCollectSetting::where('shopify_store_id',$shopifyStore->id)->first();
        $googleApiKey = $clickAndCollectSetting ? $clickAndCollectSetting->google_api_key : null;        
        $client = $rexSalesChannel->client;

        $this->limitApiCalls($client);

        $rexOutletsData = $this->fetchRexOutletsData($rexSalesChannel);

        $existingOutlets = RexOutlet
            ::where('rex_sales_channel_id', $rexSalesChannel->id)
            ->get();

        foreach ($rexOutletsData as $rexOutletData) {
            $outletExternalId = $rexOutletData->getId()->toNative();
            $rexOutlet = $existingOutlets
                ->where('external_id', $outletExternalId)
                ->first();
            if (!isset($rexOutlet)) {
                $rexOutlet = new RexOutlet;
                $rexOutlet->rex_sales_channel_id = $rexSalesChannel->id;
                $rexOutlet->external_id = $outletExternalId;
                $rexOutlet->save();
            }

            $rexOutlet->name = $rexOutletData->getName() ? $rexOutletData->getName()->toNative() : null;
            $rexOutlet->phone = $rexOutletData->getPhoneNumber() ? $rexOutletData->getPhoneNumber()->toNative() : null;
            $rexOutlet->email = $rexOutletData->getEmail() ? $rexOutletData->getEmail()->toNative() : null;

            $address = $rexOutletData->getAddress();

            if ($address !== null) {
                $rexOutlet->address1 = $address->getLine1() ? $address->getLine1()->toNative() : null;
                $rexOutlet->address2 = $address->getLine2() ? $address->getLine2()->toNative() : null;
                $rexOutlet->address3 = $address->getLine3() ? $address->getLine3()->toNative() : null;
                $rexOutlet->suburb = $address->getCity() ? $address->getCity()->toNative() : null;
                $rexOutlet->state = $address->getState() ? $address->getState()->toNative() : null;
                $rexOutlet->postcode = $address->getPostcode() ? $address->getPostcode()->toNative() : null;
                $rexOutlet->country = $address->getCountry() ? $address->getCountry()->getName()->toNative() : null;
            }

            $rexOutlet->click_and_collect = $rexOutletData->isClickAndCollect();

            $rexOutlet->priority_shipping = $rexOutletData->isPriorityShipping();

            if (isset($googleApiKey) && !empty($googleApiKey)) {
                $httpClient = new GuzzleClient();
                $provider = new GoogleMaps($httpClient, null, $googleApiKey);
                $geocoder = new StatefulGeocoder($provider, 'en');

                $query = $rexOutlet->address1 . ', '
                    . $rexOutlet->address2 . ', '
                    . $rexOutlet->address3 . ', '
                    . $rexOutlet->suburb . ', '
                    . $rexOutlet->state . ', '
                    . $rexOutlet->postcode . ', '
                    . $rexOutlet->country;

                $result = $geocoder->geocodeQuery(GeocodeQuery::create($query));

                if ($result->count()) {
                    $rexOutlet->latitude = $result->first()->getCoordinates()->getLatitude();
                    $rexOutlet->longitude = $result->first()->getCoordinates()->getLongitude();
                }
            }
             else
            {
                $rexOutlet->latitude = null;
                $rexOutlet->longitude = null;
            }
            $rexOutlet->save();

         // Call fulfulment api to get Shippit API key if available
            $old_shippit_api_key = $rexOutlet->shippit_api_key;

            $FulfilmentAPI = new FulfilmentAPI($rexOutlet->id);
            $shippit_api_key = $FulfilmentAPI->get_shippit_api_key();
            $rexOutlet->shippit_api_key = $shippit_api_key; 
            //update only if values mismatch (api key changed or new api key)
            if ($old_shippit_api_key !== $shippit_api_key)
            {
                $rexOutlet->save();
            }                
        }

        foreach ($existingOutlets as $existingOutlet) {
            if ($this->getOutletDataFromArray($existingOutlet->external_id, $rexOutletsData) === null) {
                $existingOutlet->rexInventory()->delete();
                $existingOutlet->delete();
            }
        }
    }

    private function getRexOutletClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        return new RexOutletClient($api);
    }

    private function fetchRexOutletsData(RexSalesChannel $rexSalesChannel)
    {
        $client = $rexSalesChannel->client;
        $outletClient = $this->getRexOutletClient($client);
        $salesChannelData = new SalesChannelId($rexSalesChannel->external_id);
        $rexOutletsData = $outletClient->all($salesChannelData);

        if (!isset($rexOutletsData)) {
            throw new \Exception('Could not retrieve outlets for Rex sales channel ' . $rexSalesChannel->id);
        }

        return $rexOutletsData;
    }

    private function getOutletDataFromArray($externalId, $rexOutletsData)
    {
        foreach ($rexOutletsData as $rexOutletData) {
            if ($rexOutletData->getId()->toNative() === $externalId) {
                return $rexOutletData;
            }
        }
    }
}
