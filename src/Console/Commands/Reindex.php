<?php
namespace Matchish\ElasticVis\Console\Commands;

use Illuminate\Console\Command;
use Matchish\ElasticVis\Search;

class Reindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticvis:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all models to index';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $currentLocale = \App::getLocale();
        foreach (config('laravellocalization.supportedLocales') as $locale => $config) {
            \App::setLocale($locale);
            $search = new Search();
            if (!$search->indexExists()) {
                $search->createIndex($shards = null, $replicas = null);
            } else {
                $search->deleteIndex();
                $search->createIndex($shards = null, $replicas = null);
            }
            $search->addAllToIndex();
        }
        \App::setLocale($currentLocale);

    }
}