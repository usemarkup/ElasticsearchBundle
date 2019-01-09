# A simple Elasticsearch Symfony bundle, by Markup

[![Build Status](https://travis-ci.org/usemarkup/ElasticsearchBundle.svg)](https://travis-ci.org/usemarkup/ElasticsearchBundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/markup/elasticsearch-bundle.svg)](https://packagist.org/packages/markup/elasticsearch-bundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A Symfony bundle providing simple integration with the [Elasticsearch PHP SDK](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html), also providing web profiler information.

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

## Configuration

The configuration options for individual Elasticsearch client services are determined by the [extended configuration](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_extended_host_configuration) defined by the Elasticsearch PHP SDK. No validation is performed at compile time.

### Sample YAML configuration

In the simplest case, a client service (in this case, `markup_elasticsearch.client.simple`) can be declared by just declaring a client name.

```yaml
markup_elasticsearch:
    clients:
        simple: ~
```

This will set up one connection node for that client, at the default location of `http://localhost:9200/`.

For a more complex case with explicit defined node(s), these can be defined explicitly (creating here a service called `markup_elasticsearch.client.complex`):

```yaml
markup_elasticsearch:
    clients:
        complex:
            nodes:
                - host: 8.8.8.8
                  port: 9201
                  scheme: https
                  user: i_am_a_user
                  pass: i_am_a_super_secret_password
                - host: 10.0.3.4
                  scheme: https
                  user: i_am_another_user
                  pass: pss_dont_tell_i_am_a_password
```

This will define a client with two nodes, one at `https://i_am_a_user:i_am_a_super_secret_password@8.8.8.8:9201/` and one at `https://i_am_another_user:pss_dont_tell_i_am_a_password@10.0.3.4:9200/`.

```yaml
markup_elasticsearch:
    clients:
        simple: ~
    logger: my_logger_service_id
    kibana:
        host: https://my-kibana:5601
        should_link_from_profiler: true
```

This will set up a default client as above, with the logger defined as the provided Symfony logger service ID `my_logger_service_id` (defaulting to `logger`), and the Kibana location (for running queries in [Kibana's Dev Tools interface](https://www.elastic.co/guide/en/kibana/current/devtools-kibana.html) from the Symfony web profiler) set as `https://kibana-host:5601` (and defaulting to `http://localhost:5061`). The link to Kibana within the Symfony web profiler is switched on by setting `should_link_from_profiler` to `true`.

### General settings

```yaml
markup_elasticsearch:
    retries: 2
```

- `retries` You can set the number of retries that the client will make against an Elasticsearch instance. If this number is not specified, the default behaviour is use the number of nodes in the cluster that a client is connecting to.

### Connection pools

You can define connection pools on a per-client basis, either using the `ConnectionPoolInterface` implementations from the Elasticsearch SDK, or a custom connection pool service.

The built-in connection pools are `staticNoPing` (the default), `static`, `simple` and `sniffing`.

```yaml
markup_elasticsearch:
    clients:
        my_client:
            connection_pool: sniffing
```

The above configuration will define a client `my_client` which uses the [in-built sniffing connection pool](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_connection_pool.html#_sniffingconnectionpool).

```yaml
markup_elasticsearch:
    clients:
        my_custom_client:
            connection_pool: my_custom_pool
    custom_connection_pools:
        my_custom_pool: 'acme.my_custom_pool'
```

The above configuration will define a client `my_custom_client` which uses a custom connection pool service `acme.my_custom_pool`.

## Usage

Clients as defined above are provided as instances of \Elasticsearch\Client. Usage from that point is as per the [Elasticsearch PHP SDK documentation](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html).

For example, to inject a configured Elasticsearch client into a service at `My\SearchService`, sample YAML configuration might look like:

```yaml
My\SearchService:
    arguments:
        - '@markup_elasticsearch.client.my_client'
```

The client services are defined as private, and therefore require to be injected into e.g. controllers and other services.

## Links

* [Elasticsearch PHP SDK on GitHub](https://github.com/elastic/elasticsearch-php)
* [Elasticsearch PHP SDK on elastic.co](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)
* [Elasticsearch PHP SDK on Packagist](https://packagist.org/packages/elasticsearch/elasticsearch)
* [License (MIT)](https://opensource.org/licenses/MIT)
* [Symfony website](http://symfony.com/)
