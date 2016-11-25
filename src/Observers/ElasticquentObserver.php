<?php
namespace Vis\Elasticquent\Observers;
use Illuminate\Database\Eloquent\Collection;

/**
 * Created by PhpStorm.
 * User: serg
 * Date: 28.09.16
 * Time: 9:07
 */
abstract class ElasticquentObserver
{
    public function created($instance)
    {
        $currentLocale = \App::getLocale();
        foreach (config('laravellocalization.supportedLocales') as $locale => $config) {
            \App::setLocale($locale);
            $model->addToIndex(new Collection([$instance]));
        }
        \App::setLocale($currentLocale);
    }

    public function deleting($instance)
    {
        $currentLocale = \App::getLocale();
        foreach (config('laravellocalization.supportedLocales') as $locale => $config) {
            \App::setLocale($locale);
            $model->deleteFromIndex(new Collection([$instance]));
        }
        \App::setLocale($currentLocale);
    }

    public function updating($model)
    {
        $currentLocale = \App::getLocale();
        foreach (config('laravellocalization.supportedLocales') as $locale => $config) {
            \App::setLocale($locale);
            $model->addToIndex(new Collection([$instance]));
        }
        \App::setLocale($currentLocale);
    }

    abstract function getModel();
}