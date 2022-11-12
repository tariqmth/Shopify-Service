<?php

namespace App\Http\Controllers;

use App\Models\Client\Client;
use App\Models\License\AddonLicense;
use App\Models\Setting\ClickAndCollectSetting;
use App\Models\Store\ShopifyStore;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Resources\ClickAndCollectSetting as ClickAndCollectSettingResource;
use Illuminate\Support\Facades\Validator;

class ClickAndCollectSettingController extends Controller
{
    public function get($clientId, $shopifySubdomain)
    {
        try {
            $client = Client::where('external_id', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }

        try {
            $license = AddonLicense::where('client_id', $client->id)->where('name', 'click_and_collect')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client is not licensed for Click and Collect.', 400);
        }

        try {
            $shopifyStore = ShopifyStore::where('subdomain', $shopifySubdomain)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Shopify store not found.', 404);
        }

        $clickAndCollectSetting = ClickAndCollectSetting
            ::where('addon_license_id', $license->id)
            ->where('shopify_store_id', $shopifyStore->id)
            ->first();

        if (isset($clickAndCollectSetting)) {
            return new ClickAndCollectSettingResource($clickAndCollectSetting);
        } else {
            return response('Click and Collect setting not found.', 404);
        }
    }

    public function put(Request $request, $clientId, $shopifySubdomain)
    {
        try {
            $client = Client::where('external_id', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }

        try {
            $license = AddonLicense::where('client_id', $client->id)->where('name', 'click_and_collect')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client is not licensed for Click and Collect.', 400);
        }

        try {
            $shopifyStore = ShopifyStore::where('subdomain', $shopifySubdomain)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Shopify store not found.', 404);
        }

        $clickAndCollectSettingData = json_decode($request->getContent(), true);

        $validator = Validator::make($clickAndCollectSettingData, [
            'click_and_collect_setting' => 'required',
            'click_and_collect_setting.map_enabled' => 'boolean',
            'click_and_collect_setting.google_api_key' => 'string|max:255'
        ]);

        if (!isset($clickAndCollectSettingData) || $validator->fails()) {
            return response($validator->errors(), 400);
        }

        $objectData = $clickAndCollectSettingData['click_and_collect_setting'];

        $clickAndCollectSetting = ClickAndCollectSetting
            ::where('addon_license_id', $license->id)
            ->where('shopify_store_id', $shopifyStore->id)
            ->first();

        if (!isset($clickAndCollectSetting)) {
            $clickAndCollectSetting = new ClickAndCollectSetting;
            $clickAndCollectSetting->addonLicense()->associate($license);
            $clickAndCollectSetting->shopifyStore()->associate($shopifyStore);
        }

        $map_enabled = array_get(
            $objectData,
            'map_enabled',
            1
        );

        if (!$map_enabled) {
            $clickAndCollectSetting->google_api_key = '';    
        } else {
            $clickAndCollectSetting->google_api_key = array_get(
                $objectData,
                'google_api_key',
                $clickAndCollectSetting->google_api_key
            );
        }       

        $clickAndCollectSetting->save();

        return new ClickAndCollectSettingResource($clickAndCollectSetting);
    }
}
