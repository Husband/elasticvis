<?php
/**
 * Created by PhpStorm.
 * User: serg
 * Date: 01.11.16
 * Time: 11:39
 */

namespace Matchish\ElasticVis;


use Matchish\ElasticVis\Models\SearchQuery;

class Search
{
    /**
     * Create a elacticquent result collection of models from plain elasticsearch result.
     *
     * @param  array $result
     * @return \Elasticquent\ElasticquentResultCollection
     */
    public function hydrateElasticsearchResult(array $result)
    {
        $items = $result['hits']['hits'];
        $groupedByType = array_reduce($items, function ($carry, $item) {
            $carry[$item['_type']][] = $item;
            return $carry;
        }, []);
        array_walk($groupedByType, function (&$group, $type) {
            $ids = array_column($group, '_id');
            $model = $this->getModelNameByType($type);
            $collection = $model::whereIn('id', $ids)->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')->get();
            $group = $collection;
        });
        $items = array_map(function ($item) use ($groupedByType) {
            $group = $groupedByType[$item['_type']];
            $instance = $group->keyBy('id')->get($item['_id']);
            if (array_key_exists('highlight', $item)) {
                $instance->highlight = $item['highlight'];
            }
            return $instance;
        }, $items);
        return $items;
    }

    public function indexExists()
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getElasticSearchClient()->indices()->exists($params);
    }

    /**
     * Get Index Name
     *
     * @return string
     */
    public function getIndexName()
    {
        // The first thing we check is if there is an elasticquent
        // config file and if there is a default index.
        $index_name = config('matchish.elasticvis.default_index');

        if (!empty($index_name)) {
            return $index_name . '_' . \App::getLocale();
        }

        // Otherwise we will just go with 'default'
        return 'default' . '_' . \App::getLocale();
    }

    /**
     * Get ElasticSearch Client
     *
     * @return \Elasticsearch\Client
     */
    public function getElasticSearchClient()
    {
        $config = config('matchish.elasticvis.config');
        // elasticsearch v2.0 using builder
        if (class_exists('\Elasticsearch\ClientBuilder')) {
            return \Elasticsearch\ClientBuilder::fromConfig($config);
        }

        // elasticsearch v1
        return new \Elasticsearch\Client($config);
    }

    /**
     * Create Index
     *
     * @param int $shards
     * @param int $replicas
     *
     * @return array
     */
    public function createIndex($shards = null, $replicas = null)
    {
        $client = $this->getElasticSearchClient();

        $index = array(
            'index' => $this->getIndexName(),
        );

        $settings = config('matchish.elasticvis.settings');
        if (!is_null($settings)) {
            $index['body']['settings'] = $settings;
        }

        if (!is_null($shards)) {
            $index['body']['settings']['number_of_shards'] = $shards;
        }

        if (!is_null($replicas)) {
            $index['body']['settings']['number_of_replicas'] = $replicas;
        }

        $mappingProperties = $this->getMappingProperties();
        if (!is_null($mappingProperties)) {
            $index['body']['mappings'] = $mappingProperties;
        }

        return $client->indices()->create($index);
    }

    /**
     * Delete Index
     *
     * @return array
     */
    public function deleteIndex()
    {
        $client = $this->getElasticSearchClient();

        $index = array(
            'index' => $this->getIndexName(),
        );

        return $client->indices()->delete($index);
    }

    /**
     * Index Documents
     *
     * Index all documents in an Eloquent model.
     *
     * @return array
     */
    public function addAllToIndex()
    {
        $models = config('matchish.elasticvis.models');
        array_map(function ($model) {
            (new $model)->addAllToIndex();
        }, $models);

    }

    private function getMappingProperties()
    {
        return config('matchish.elasticvis.mappings');
    }

    public function instantsearch($term = '', $models)
    {
        $params = ['index' => $this->getIndexName()];
        if (!$models) {
            $models = config('matchish.elasticvis.models');
        }
        $types = array_map(function ($model) {
            $instance = new $model;
            return $instance->getTypeName();
        }, $models);
        $params['type'] = implode(',', $types);
        $split = explode(" ", $term);
        $lastWord = array_pop($split);
        if (count($split)) {
            $startTerm = implode(' ', $split);
        }
        $query = [];
        $query['query']['bool']['must'][] = [
            'multi_match' => [
                'query' => $lastWord,
                'fields' => ['*.instantsearch'],
                'analyzer' => 'larasearch_word_search'
            ]
        ];
        if (isset($startTerm)) {
            $query['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $startTerm,
                    'fields' => ['*.analyzed'],
                    'analyzer' => 'larasearch_search',
                    "operator" => "and"
                ]
            ];
        }
        $query['size'] = config('matchish.elasticvis.instantsearch.limit');
        $params['body'][] = new \StdClass;
        $params['body'][] = $query;

        $suggestions = SearchQuery::where('text', 'LIKE', mb_strtolower($term) . '%')->limit(20)->get();
        foreach ($suggestions as $suggestion) {
            $params['body'][] = new \StdClass;
            $query = [];
            $query['query']['multi_match'] = [
                'query' => $term,
                'fields' => ['*.analyzed'],
                'analyzer' => 'larasearch_search'
            ];
            $query['aggs'] = [
                "types" => [
                    "terms" => [
                        "field" => "_type"
                    ],
                    "aggs" => [
                        "categories" => [
                            "terms" => [
                                "field" => "category_id",
                            ]
                        ]
                    ]
                ],
            ];
            $query['size'] = 0;
            $params['body'][] = $query;
        }
        $result = $this->getElasticSearchClient()->msearch($params);
        $responses = $result['responses'];
        $response = [];
        $hits = array_shift($responses);
        $response['hits'] = $this->hydrateElasticsearchResult($hits);
        foreach ($suggestions as $suggestion) {
            $aggs = array_shift($responses);
            if ($aggs['hits']['total']) {
                $suggestion->aggregations = array_shift($responses);
            } else {
                $suggestion->delete();
            }
        }
        $response['suggestions'] = $suggestions;

        return $response;
    }

    public function search($term = '', $models)
    {

        $params = ['index' => static::getIndexName()];
        if (!$models) {
            $models = config('matchish.elasticvis.models');
        }
        $types = array_map(function ($model) {
            $instance = new $model;
            return $this->getTypeName($instance);
        }, $models);
        $params['type'] = implode(',', $types);

        $fuzziness = 3;
        if (mb_strlen($term) <= 4) {
            $fuzziness = 1;
        }

        $params['body']['query']['multi_match'] = [
            'query' => $term,
            'fields' => ['*.analyzed'],
            "fuzziness" => $fuzziness,
            'analyzer' => 'larasearch_search',
            "operator" => "and",
        ];

        $perPage = 5;
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        $params['from'] = ($paginator->currentPage() - 1) * $perPage;
        $params['size'] = $perPage;

        $params['body']['highlight'] = [
            "number_of_fragments" => 0,
            "pre_tags" => ["<span class='highlight'>"],
            "post_tags" => ["</span>"],
            "fields" => [
                "*.analyzed" => new \StdClass(),
            ]
        ];

        $params['body']['aggs'] = [
            "types" => [
                "terms" => [
                    "field" => "_type"
                ],
                "aggs" => [
                    "categories" => [
                        "terms" => [
                            "field" => "category_id",
                        ]
                    ]
                ]
            ],
        ];

        $result = $this->getElasticSearchClient()->search($params);
        $response = [];

        $response['hits'] = $this->hydrateElasticsearchResult($result);
        $response['aggregations'] = $this->hydrateElasticsearchResult($result);

        if (!$result['hits']['total']) {
            $corrector = new Text_LangCorrect;
            $correctedTerm = $corrector->parse($term, $corrector::KEYBOARD_LAYOUT);
            if ($correctedTerm !== $term) {
                $response['corrected_term'] = $correctedTerm;
            }
        } else {
            $query = SearchQuery::firstOrCreate(['text' => mb_strtolower($term)]);
            $query->frequency = $query->frequency + 1;
            $query->update();
        }

        return $response;
    }

    protected function getModelNameByType($type)
    {
        $models = config('matchish.elasticvis.models');
        foreach ($models as $model) {
            $instanse = new $model;
            if ($type === $instanse->getTypeName()) {
                return $model;
            }
        }
    }


}