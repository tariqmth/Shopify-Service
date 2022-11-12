<?php

namespace App\Http\Middleware;

use App\Models\ApiAuth;
use App\Models\License\AddonLicense;
use App\Models\Source\Source;
use App\Models\Store\ShopifyStore;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\App;

class VerifyClickAndCollectAddon
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        if (!$request->route('subdomain')) {
            return response('Shopify subdomain required.', 422);
        }

        $shopifyStore = ShopifyStore::where('subdomain', $request->route('subdomain'))->first();

        if (!isset($shopifyStore)) {
            return response('Shopify store not found.', 404);
        }

        $addonLicense = AddonLicense
            ::where('client_id', $shopifyStore->rexSalesChannel->client_id)
            ->where('name', 'click_and_collect')
            ->first();

        if ($addonLicense === null) {
            return response('Shopify store not licensed for Click and Collect.', 422);
        }

        return $next($request);
    }
}
