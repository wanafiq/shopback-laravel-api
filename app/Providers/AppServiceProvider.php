<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::macro('loggable', function () {
            return Http::beforeSending(function ($request, $options) {
                if ($request->method() === 'GET') {
                    Log::info('HTTP Request', [
                        'method' => $request->method(),
                        'url' => $request->url(),
                        'headers' => $request->headers()
                    ]);
                } else {
                    Log::info('HTTP Request', [
                        'method' => $request->method(),
                        'url' => $request->url(),
                        'headers' => $request->headers(),
                        'body' => $request->body(),
                    ]);
                }
            })->throw(function ($response, $httpException) {
                Log::info('HTTP Response', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                ]);
            });
        });
    }
}
