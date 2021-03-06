# elasticvis

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

## Install

Via Composer

``` bash
$ composer require matchish/elasticvis
```

Добавляем в файле app.php в блок providers
```php
  Matchish\ElasticVis\ElasticVisServiceProvider::class,
```

Публикуем ресурсы
``` bash
$ php artisan vendor:publish --tag=elasticvis
```

Выполняем миграции
``` bash
$ php artisan migrate
```

## Usage

В конфиге elasticvis.php прописываем имя индекса и модели которые нужно индексировать

``` php
    'default_index' => 'project_name',
    'models' => ['Article', 'Product'],
```
В модели которые будут индексироваться добавляем метод
``` php
   public function getCategoryIdAttribute()
    {
        return $this->id_category;
    }
```

В контроллер добавляем методы
``` php
    public function search()
    {
        $query = Request::input('q');
        $searchResults = (new \Matchish\ElasticVis\Search)->search($query, [\Article::class, \Product::class]);
        return view('pages.search', compact('searchResults'));
    }

    public function instantsearch()
    {
        $query = Request::input('q');
        (new \Matchish\ElasticVis\Search)->instantsearch($query, [\Article::class, \Product::class]);
    }
```
И роуты для них

Индексируем модели
``` bash
$ php artisan elasticvis:reindex
```
При обновлении моделей индекс будет обновлятьс автоматически.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email husband.sergey@gmail.com instead of using the issue tracker.

## Credits

- [Sergey Shlyakhov][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/matchish/elasticvis.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/husband/elasticvis/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/husband/elasticvis.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/husband/elasticvis.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/matchish/elasticvis.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/matchish/elasticvis
[link-travis]: https://travis-ci.org/husband/elasticvis
[link-scrutinizer]: https://scrutinizer-ci.com/g/husband/elasticvis/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/husband/elasticvis
[link-downloads]: https://packagist.org/packages/matchish/elasticvis
[link-author]: https://github.com/husband
[link-contributors]: ../../contributors
