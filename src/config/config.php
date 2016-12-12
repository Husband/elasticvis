<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Custom Elasticsearch Client Configuration
    |--------------------------------------------------------------------------
    |
    | This array will be passed to the Elasticsearch client.
    | See configuration options here:
    |
    | http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html
    */

    'config' => [
        'hosts' => ['localhost:9200'],
        'retries' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Index Name
    |--------------------------------------------------------------------------
    |
    | This is the index name that Elasticquent will use for all
    | Elasticquent models.
    */

    'default_index' => 'project_name',

    'models' => ['Article', 'News'],

    'settings' => [
        'analysis' => [
            'analyzer' => [
                'default_index' => [
                    'type' => "custom",
                    'tokenizer' => "standard",
                    'filter' => ["standard", "lowercase", "asciifolding", "larasearch_index_shingle", "larasearch_stemmer"]
                ],
                'larasearch_search' => [
                    'type' => "custom",
                    'tokenizer' => "standard",
                    'filter' => ["standard", "lowercase", "asciifolding", "larasearch_stemmer"]
                ],
                'larasearch_word_search' => [
                    'type' => "custom",
                    'tokenizer' => "standard",
                    'filter' => ["lowercase", "asciifolding"]
                ],
                'larasearch_word_start_index' => [
                    'type' => "custom",
                    'tokenizer' => "standard",
                    'filter' => ["lowercase", "asciifolding", "larasearch_edge_ngram"]
                ]
            ],
            'filter' => [
                'larasearch_index_shingle' => [
                    'type' => "shingle",
                    'token_separator' => ""
                ],
                'larasearch_search_shingle' => [
                    'type' => "shingle",
                    'token_separator' => "",
                    'output_unigrams' => false,
                    'output_unigrams_if_no_shingles' => true
                ],
                'larasearch_suggest_shingle' => [
                    'type' => "shingle",
                    'max_shingle_size' => 5
                ],
                'larasearch_edge_ngram' => [
                    'type' => "edgeNGram",
                    'min_gram' => 1,
                    'max_gram' => 50
                ],
                'larasearch_ngram' => [
                    'type' => "nGram",
                    'min_gram' => 1,
                    'max_gram' => 50
                ],
                'larasearch_stemmer' => [
                    'type' => "snowball",
                    'language' => "Russian"
                ],
                "latin_cyrillic" => [
                    "type" => "icu_transform",
                    "id" => "Latin-Cyrillic; NFD; [:Nonspacing Mark:] Remove; NFC"
                ],
                "cyrillic_latin" => [
                    "type" => "icu_transform",
                    "id" => "Cyrillic-Latin; NFD; [:Nonspacing Mark:] Remove; NFC"
                ],
                "any_latin" => [
                    "type" => "icu_transform",
                    "id" => "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC"
                ]
            ],
            'tokenizer' => [
                'larasearch_autocomplete_ngram' => [
                    'type' => "edgeNGram",
                    'min_gram' => 1,
                    'max_gram' => 50
                ]
            ]
        ],
    ],

    'mappings' => [
        '_default_' => [
            # https://gist.github.com/kimchy/2898285
            'dynamic_templates' => [
                [
                    'string_template' => [
                        'match' => '*',
                        'match_mapping_type' => 'string',
                        'mapping' => [
                            # http://www.elasticsearch.org/guide/reference/mapping/multi-field-type/
                            'type' => 'multi_field',
                            'fields' => [
                                # analyzed field must be the default field for include_in_all
                                # http://www.elasticsearch.org/guide/reference/mapping/multi-field-type/
                                # however, we can include the not_analyzed field in _all
                                # and the _all index analyzer will take care of it
                                '{name}' => ['type' => 'string', 'index' => 'not_analyzed'],
                                'analyzed' => ['type' => 'string', 'index' => 'analyzed', 'analyzer' => 'default_index'],
                                'instantsearch' => ['type' => 'string', 'index' => 'analyzed', 'analyzer' => 'larasearch_word_start_index'],
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],

    'instantsearch' => ['limit' => 5]

);
