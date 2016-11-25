<?php

namespace Matchish\ElasticVis;

use Illuminate\Support\ServiceProvider;
use Vis\Elasticquent\Observers\ElasticquentObserver;

class ElasticVisServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__
            . '/config' => config_path('matchish'),
        ], 'elasticvis');

        $this->publishes([
            __DIR__
            . '/database/migrations' => database_path('migrations'),
        ], 'elasticvis');

        $models = config('elasticvis.models');
        if ($models) {
            foreach ($models as $model) {
//                $model::observe();
            }
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Console\Commands\Reindex::class
        ]);
    }
}