<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['auth.rex']], function () {
    Route::put('eds', 'EDSController@put');

    Route::get('clients', 'ClientController@all');
    Route::get('clients/{clientId}', 'ClientController@get');
    Route::post('clients', 'ClientController@post');
    Route::put('clients/{clientId}', 'ClientController@put');
    Route::delete('clients/{clientId}', 'ClientController@delete');

    Route::get('clients/{clientId}/addon_licenses', 'AddonLicenseController@all');
    Route::get('clients/{clientId}/addon_licenses/{name}', 'AddonLicenseController@get');
    Route::post('clients/{clientId}/addon_licenses', 'AddonLicenseController@post');
    Route::delete('clients/{clientId}/addon_licenses/{name}', 'AddonLicenseController@delete');

    Route::get('clients/{clientId}/shopify_stores', 'ShopifyStoreController@all');
    Route::get('clients/{clientId}/shopify_stores/{subdomain}', 'ShopifyStoreController@get');
    Route::get('shopify_stores/{subdomain}', 'ShopifyStoreController@getDirectly');
    Route::post('clients/{clientId}/shopify_stores', 'ShopifyStoreController@post');
    Route::put('clients/{clientId}/shopify_stores/{subdomain}', 'ShopifyStoreController@put');
    Route::delete('clients/{clientId}/shopify_stores/{subdomain}', 'ShopifyStoreController@delete');


    Route::get(
        'clients/{clientId}/shopify_stores/{subdomain}/payment_mappings',
        'ShopifyPaymentGatewayMappingsController@all'
    );
    Route::put(
        'clients/{clientId}/shopify_stores/{subdomain}/payment_mappings',
        'ShopifyPaymentGatewayMappingsController@put'
    );

    Route::get(
        'clients/{clientId}/shopify_stores/{subdomain}/click_and_collect_setting',
        'ClickAndCollectSettingController@get'
    );
    Route::put(
        'clients/{clientId}/shopify_stores/{subdomain}/click_and_collect_setting',
        'ClickAndCollectSettingController@put'
    );

    Route::get('clients/{clientId}/shopify_stores/{subdomain}/product_variants', 'ShopifyVariantController@all');

    Route::get(
        'clients/{clientId}/shopify_stores/{subdomain}/shopify_product_field_mappings',
        'ShopifyProductFieldMappingsController@all'
    );
    Route::put(
        'clients/{clientId}/shopify_stores/{subdomain}/shopify_product_field_mappings',
        'ShopifyProductFieldMappingsController@put'
    );

    Route::get(
        'clients/{clientId}/shopify_stores/{subdomain}/shopify_order_attribute_mappings',
        'ShopifyOrderAttributeMappingsController@all'
    );
    Route::put(
        'clients/{clientId}/shopify_stores/{subdomain}/shopify_order_attribute_mappings',
        'ShopifyOrderAttributeMappingsController@put'
    );

    Route::get('health/heartbeat', 'HealthController@get');

    Route::get('history', 'SyncJobsHistoryController@allForApi');
    Route::get('history/{uniqueId}', 'SyncJobsHistoryController@getForApi');

    Route::get('clients/{clientId}/shopify_stores/{subdomain}/inventory_buffers', 'RexInventoryBufferGroupController@all');
    Route::post('clients/{clientId}/shopify_stores/{subdomain}/inventory_buffers', 'RexInventoryBufferGroupController@post');
    Route::put('clients/{clientId}/shopify_stores/{subdomain}/inventory_buffers/{id}', 'RexInventoryBufferGroupController@put');
    Route::delete('clients/{clientId}/shopify_stores/{subdomain}/inventory_buffers/{id}', 'RexInventoryBufferGroupController@delete');

});

Route::group(['middleware' => ['auth.shopify']], function () {
    Route::post('shopify_webhook_notifications', 'ShopifyWebhookNotificationController@post');
});

Route::group(['middleware' => ['addons.click_and_collect']], function () {
    Route::get('shopify_stores/{subdomain}/outlets', 'ShopifyOutletController@get');
});
