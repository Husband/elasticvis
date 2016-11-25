<?php
namespace Vis\Elasticquent\Models;
use Matchish\ElasticVis\Search;

/**
 * Created by PhpStorm.
 * User: serg
 * Date: 28.09.16
 * Time: 9:07
 */
abstract class Base
{
    public function addAllToIndex()
    {
        $all = $this->getEloquentInstance()->newQuery()->get(array('*'));
        $this->addToIndex($all);
    }

    abstract public function getEloquentInstance();

    /**
     * Add To Index
     *
     * Add all documents in this collection to to the Elasticsearch document index.
     *
     * @return null|array
     */
    public function addToIndex($collection)
    {
        if ($collection->isEmpty()) {
            return null;
        }

        $params = array();

        foreach ($collection as $item) {
            $params['body'][] = array(
                'index' => array(
                    '_id' => $item->getKey(),
                    '_type' => $this->getTypeName(),
                    '_index' => $this->getIndexName(),
                ),
            );

            $params['body'][] = $this->getIndexDocumentData($item);
        }
        return $this->getElasticSearchClient()->bulk($params);
    }

    /**
     * Remove From Search Index
     *
     * @return array
     */
    public function removeFromIndex($instance)
    {
        $params = array(
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
        );
        $params['id'] = $instance->getKey();

        return $this->getElasticSearchClient()->delete($params);
    }

    /**
     * Partial Update to Indexed Document
     *
     * @return array
     */
    public function updateIndex($instance)
    {
        $params = array(
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
        );
        $params['id'] = $instance->getKey();
        $params['body']['doc'] = $this->getIndexDocumentData($instance);

        return $this->getElasticSearchClient()->update($params);
    }

    /**
     * Get Type Name
     *
     * @return string
     */
    protected function getTypeName($instance)
    {
        return $this->getEloquentInstance()->getTable();
    }

    protected function getIndexName()
    {
        return (new Search)->getIndexName();
    }

    /**
     * Get Index Document Data
     *
     * Get the data that Elasticsearch will
     * index for this particular document.
     *
     * @param $instance
     * @return array
     */
    protected function getIndexDocumentData($instance)
    {
        $allowed = $this->getIndexDocumentFields($instance);

        $data = [];;
        if ($allowed) {
            foreach ($allowed as $docField => $modelProperty) {
                $data[$docField] = $instance->$modelProperty;
            }
        }
        return $data;
    }

    private function getElasticSearchClient()
    {
        return (new Search)->getElasticSearchClient();
    }

    //ElassticSearch document fields
    protected function getIndexDocumentFields($instance)
    {
        $properties = [];
        $locale = \App::getLocale();
        if ($this->searchable) {
            foreach ($this->searchable as $field) {
                $postfix = '';
                if ($locale != config('app.fallback_locale')) {
                    $postfix = '_' . $locale;
                }
                $properties[$field] = $field . $postfix;
            }
        }
        return $properties;
    }

    public function observe()
    {
        $this->getEloquentInstance($this->getObserver());
    }

    abstract function getObserver();
}