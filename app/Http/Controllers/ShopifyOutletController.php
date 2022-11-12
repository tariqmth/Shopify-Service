<?php

namespace App\Http\Controllers;

use App\Models\Inventory\RexInventory;
use App\Models\Location\RexOutlet;
use App\Models\Product\RexProductRepository;
use App\Models\Product\ShopifyProductVariant;
use App\Models\Store\ShopifyStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Apis\Retailexpress\AuthenticationAPI;
use App\Models\Apis\Retailexpress\FulfilmentAPI;
use App\Models\Apis\Shippit\QuotesAPI;
use Illuminate\Support\Facades\Log;

class ShopifyOutletController extends Controller
{
    const CNC_SEARCH_RADIUS = 100;

    const PS_SEARCH_RADIUS = 15;

    private $rexProductRepository;

    public function __construct(
        RexProductRepository $rexProductRepository
    ) {
        $this->rexProductRepository = $rexProductRepository;
    }

    public function get(Request $request, $subdomain)
    {
        Validator::make($request->all(), [
            'latitude' => 'numeric|between:-90,90',
            'longitude' => 'numeric|between:-180,180'
        ])->validate();

        $shopifyStore = ShopifyStore::where('subdomain', $subdomain)->firstOrFail();

        if ($request->get('variant_ids') !== null) {
            $shopifyVariantIds = explode(',', $request->get('variant_ids'));
        } else {
            $shopifyVariantIds = [];
        }

        $quantities = [];
        foreach ($shopifyVariantIds as $shopifyVariantId) {
            $shopifyVariantId = (int) $shopifyVariantId;
            if ($request->get('qty-' . $shopifyVariantId) !== null) {
                $quantity = (int) $request->get('qty-' . $shopifyVariantId);
                $quantities[$shopifyVariantId] = $quantity;
            }
        }

        if ($request->get('variant_ids') !== null) {
            $shopifyVariantIds = explode(',', $request->get('variant_ids'));
        } else {
            $shopifyVariantIds = [];
        }

        // Setting default value of type to cnc
        // when no parameter type available it will be cnc (click and collect) by default
        $type = 'cnc';

        $priority_shipping_outlet = null;

        if ($request->exists('type') && !empty($request->get('type')))
        {
            $type = $request->get('type');
        }

        $rexOutlets = [];

        if ($type === "cnc")
        {
            if ($request->get('latitude') !== null && $request->get('longitude') !== null) {
                $parameters = [$request->get('latitude'), $request->get('longitude'), $request->get('latitude')];
                $rexOutlets = $shopifyStore->rexSalesChannel->rexOutlets()
                    ->select('rex_outlets.*')
                    ->selectRaw('( 6371 * acos( cos( radians(?) ) *
                                       cos( radians( latitude ) )
                                       * cos( radians( longitude ) - radians(?)
                                       ) + sin( radians(?) ) *
                                       sin( radians( latitude ) ) )
                                     ) AS distance', $parameters)
                    ->where('click_and_collect', true)
                    ->havingRaw('distance < ?', [self::CNC_SEARCH_RADIUS])
                    ->orderBy('distance')
                    ->get();
            } else {
                $rexOutlets = $shopifyStore->rexSalesChannel->rexOutlets()->where('click_and_collect', true)->get();
            }
        }
// Priority Shipping
        elseif ($type === "ps") {
            if ($request->get('latitude') !== null && $request->get('longitude') !== null) {
                $parameters = [$request->get('latitude'), $request->get('longitude'), $request->get('latitude')];
                $distance = self::PS_SEARCH_RADIUS;
                if ($request->exists('radius') && $request->get('radius') !== null)
                {
                    $distance = $request->get('radius');
                }
                $rexOutlets = $shopifyStore->rexSalesChannel->rexOutlets()
                    ->select('rex_outlets.*')
                    ->selectRaw('( 6371 * acos( cos( radians(?) ) *
                                       cos( radians( latitude ) )
                                       * cos( radians( longitude ) - radians(?)
                                       ) + sin( radians(?) ) *
                                       sin( radians( latitude ) ) )
                                     ) AS distance', $parameters)
                    ->where('priority_shipping', true)
                    ->havingRaw('distance < ?', [$distance])
                    ->orderBy('distance')
                    ->get();


                // Shippit Driver availability check
                // If check_availability=true AND suburb, state, postcode, and quantity are provided/not blank
                if (!empty($request->get('check_availability')) && 
                    !empty($request->get('suburb')) && 
                    !empty($request->get('state')) && 
                    !empty($request->get('postcode')) && 
                    !empty($request->get('quantity')) &&
                    // If there are one or more outlets returned by the existing query that retrieves $rexOutlets
                    count($rexOutlets) > 0)
                {
                    foreach ($rexOutlets as $rexOutlet) {
                        $shippit_api_key = $rexOutlet->shippit_api_key; 
                        // check if requested stock available in outlet
                        $stock = $this->isStockAvailable($rexOutlet, $shopifyVariantIds,$quantities);

                        // Call Shippit API only if stock available for this outlet
                        if (!empty($shippit_api_key) && $stock !== false )
                            {

                            $quote= 
                            [
                                "dropoff_postcode"=> $request->get('postcode'),
                                "dropoff_state"=> $request->get('state'),
                                "dropoff_suburb"=> $request->get('suburb'),
                                "latitude"=>$request->get('latitude'),
                                "longitude"=> $request->get('longitude'),
                                "return_all_quotes"=> true,
                                "parcel_attributes"=> ["qty"=> $request->get('quantity')]
                            ];
                            
                            $quote_api = new QuotesAPI($shippit_api_key,$quote);
                            if (!empty($request->get('cost_cutoff')) && 
                                $request->get('cost_cutoff') > 0){
                                $quote_api->set_cost_cutoff($request->get('cost_cutoff'));
                            }
                            if (!empty($request->get('deliver_within')) && 
                                $request->get('deliver_within')> 0){
                                $quote_api->set_deliver_within($request->get('deliver_within'));
                            }
                            $priority_shipping_quotes = $quote_api->get_quotes() ;
                            
                            // if outlet has priority shipping available quit from loop and return
                            if(count($priority_shipping_quotes) > 0){
                                $priority_shipping_outlet = $rexOutlet;
                                break;
                            }
                        }
                    }

                    unset($rexOutlets);
                    $rexOutlets[] = $priority_shipping_outlet;                    
                 } 
            }
        }

//
        $outletsData['outlets'] = [];
        foreach ($rexOutlets as $rexOutlet) {
            if (!empty($rexOutlet))
            {
                $outlet = [
                    'name' => $rexOutlet->name,
                    'external_id' => $rexOutlet->external_id,
                    'stock' => $this->getStockLevels($rexOutlet, $shopifyVariantIds),
                    'contact' => [
                        'address1' => $rexOutlet->address1,
                        'address2' => $rexOutlet->address2,
                        'address3' => $rexOutlet->address3,
                        'suburb' => $rexOutlet->suburb,
                        'state' => $rexOutlet->state,
                        'postcode' => $rexOutlet->postcode,
                        'phone' => $rexOutlet->phone,
                        'email' => $rexOutlet->email,
                        'latitude' => $rexOutlet->latitude,
                        'longitude' => $rexOutlet->longitude
                    ]
                ];
                if (isset($rexOutlet->distance)) {
                    if ($rexOutlet->distance > 1) {
                        $outlet['distance'] = round($rexOutlet->distance, 0);
                    } else {
                        $outlet['distance'] = round($rexOutlet->distance, 1);
                    }
                }
                $outletsData['outlets'][] = $outlet;
            }
        }
    
        $domainName = !empty($shopifyStore->full_domain) ? $shopifyStore->full_domain : $subdomain . '.myshopify.com';
        $url = 'https://' . $domainName;

        return response()->json($outletsData, 200, [
            'Access-Control-Allow-Origin' => $url
        ]);
    }

    private function getStockLevels(RexOutlet $rexOutlet, array $shopifyVariantIds)
    {
        $stock = [];

        foreach ($shopifyVariantIds as $shopifyVariantId) {
            $shopifyVariant = ShopifyProductVariant::where('external_id', $shopifyVariantId)->first();

            if (!isset($shopifyVariant)) {
                $stock[$shopifyVariantId] = 0;
                continue;
            }

            $rexProductId = $shopifyVariant->rex_product_id;

            if (!isset($rexProductId)) {
                $stock[$shopifyVariantId] = 0;
                continue;
            }

            $rexInventory = RexInventory
                ::where('rex_product_id', $rexProductId)
                ->where('rex_outlet_id', $rexOutlet->id)
                ->first();

            $stock[$shopifyVariantId] = isset($rexInventory->quantity) ? $rexInventory->quantity : 0;
        }

        return $stock;
    }
    private function isStockAvailable(RexOutlet $rexOutlet, array $shopifyVariantIds, array $quantities)
    {

        foreach ($shopifyVariantIds as $shopifyVariantId) {
            $shopifyVariant = ShopifyProductVariant::where('external_id', $shopifyVariantId)->first();

            if (!isset($shopifyVariant)) {
                return false;
            }

            $rexProductId = $shopifyVariant->rex_product_id;

            if (!isset($rexProductId)) {
                return false;
            }
            $rexInventory = RexInventory
                ::where('rex_product_id', $rexProductId)
                ->where('rex_outlet_id', $rexOutlet->id)
                ->first();

            $stock_available = isset($rexInventory->quantity) ? $rexInventory->quantity : 0;
            // validate stock level available or not
            if ($quantities[$shopifyVariantId] > $stock_available){
                return false;
            }
        }
        return true;
    }

}
