<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\ForceJsonResponse::class,
        \App\Http\Middleware\Cors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    //    'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, //COA: Custom made
        //created/added by COA
        'json.response' => \App\Http\Middleware\ForceJsonResponse::class,
        'cors' => \App\Http\Middleware\Cors::class,
        'api.admin' => \App\Http\Middleware\AdminAuth::class,
        'api.superAdmin' => \App\Http\Middleware\SuperAdminAuth::class,
        'sanitize' => \App\Http\Middleware\XssSanitizer::class,
        'log.route' => \App\Http\Middleware\LogRoute::class,
        'agency' => \App\Http\Middleware\Agency::class,
        'individual' => \App\Http\Middleware\Individual::class,
        'enterprise' => \App\Http\Middleware\Enterprise::class,
        'pinop' => \App\Http\Middleware\UsePin::class,
        'delegated' => \App\Http\Middleware\Delegate::class,
        'non.delegate' => \App\Http\Middleware\NonDelegate::class,
        'notFree' => \App\Http\Middleware\LimitExceeded::class,
        'company.admin' => \App\Http\Middleware\CompanyAdmin::class,
    ];
}
/*
  To use the middleware in a given route, simply add it to the route function like this:

    Route::post('route','Controller@method')->middleware('<middleware-name-here>');
    <middleware-name-here> in this case can be api.admin, api.superAdmin, etc.
*/
