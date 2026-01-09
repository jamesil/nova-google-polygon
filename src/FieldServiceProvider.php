<?php

namespace Jamesil\NovaGooglePolygon;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova-google-polygon.php', 'nova-google-polygon');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nova-google-polygon.php' => config_path('nova-google-polygon.php'),
            ], 'nova-google-polygon-config');
        }

        Nova::serving(function (ServingNova $event) {
            Nova::script('google-polygon', __DIR__ . '/../dist/js/field.js');

            Nova::provideToScript([
                'googlePolygon' => [
                    'key' => config('nova-google-polygon.api_key'),
                    'center' => config('nova-google-polygon.center'),
                ],
            ]);
        });
    }
}
