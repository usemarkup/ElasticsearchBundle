# A simple Elasticsearch Symfony bundle, by Markup

[![Build Status](https://travis-ci.org/usemarkup/ElasticsearchBundle.svg)](https://travis-ci.org/usemarkup/ElasticsearchBundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/markup/elasticsearch-bundle.svg)](https://packagist.org/packages/markup/elasticsearch-bundle)

A Symfony bundle providing simple integration with the Elasticsearch SDK, also providing web profiler information.

## Installation

The Markup Elasticsearch bundle can be installed via [Composer](http://getcomposer.org) by 
requiring the`markup/elasticsearch-bundle` package in your project's `composer.json`:

```json
{
    "require": {
        "markup/elasticsearch-bundle": "~0.2"
    }
}
```

and adding an instance of `Markup\ElasticsearchBundle\MarkupElasticsearchBundle` to your application's kernel:

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return [
            ...
            new \Markup\ElasticsearchBundle\MarkupElasticsearchBundle(),
        ];
    }
    ...
}
```
