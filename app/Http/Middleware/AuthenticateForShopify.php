<?php

namespace App\Http\Middleware;

use App\Models\ApiAuth;
use App\Models\Source\Source;
use App\Models\Store\ShopifyStore;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\App;

class AuthenticateForShopify
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

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
    public function handle($request, Closure $next, ...$guards)
    {
        if (App::environment('local')) {
            return $next($request);
        }

        try {
            $source = Source::where('code', 'shopify')->firstOrFail();
            $apiAuth = ApiAuth::where('source_id', $source->id)->firstOrFail();
            $sharedSecret = $apiAuth->api_token;
            $requestedHmac = $request->header('X-Shopify-Hmac-Sha256');
            $input = file_get_contents('php://input');
            $calculatedHmac = base64_encode(hash_hmac('sha256', $input, $sharedSecret, true));
            if (!isset($requestedHmac) || !hash_equals($requestedHmac, $calculatedHmac)) {
                throw new \Exception('Incorrect HMAC in Shopify request.');
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('Unauthenticated.', $guards);
        }

        return $next($request);
    }
}
