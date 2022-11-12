<?php

namespace App\Http\Middleware;

use App\Models\ApiAuth;
use App\Models\Source\Source;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\App;

class AuthenticateWithHeader
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
        if (App::environment('testing')) {
            return $next($request);
        }

        try {
            $apiToken = $request->header('x-api-key') ?? $request->input('api_token');
            if (!isset($apiToken)) {
                throw new \Exception('API token is not set.');
            }
            $source = Source::where('code', 'rex')->firstOrFail();
            ApiAuth::where('source_id', $source->id)->where('api_token', $apiToken)->firstOrFail();
        } catch (\Exception $e) {
            throw new AuthenticationException('Unauthenticated.', $guards);
        }

        return $next($request);
    }
}
