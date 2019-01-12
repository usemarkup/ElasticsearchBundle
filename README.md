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
- `endpoint_closure` Although it's an extremely brittle extension point, the Elasticsearch SDK allows definition of an endpoint closure for providing different logic for resolving endpoints. For details and caveats, see the [documentation for this](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_set_the_endpoint_closure). A configuration value should be a service that is a `callable` - typically an object with a defined `__invoke` method.

### Connection pools

You can define connection pools on a per-client basis, either using the `ConnectionPoolInterface` implementations from the Elasticsearch SDK, or a custom connection pool service.

The [built-in connection pools](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_connection_pool.html) are `static_no_ping` (the default), `static`, `simple` and `sniffing`.

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

### Connection selectors

You can define [connection selectors](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_selectors.html) on a per-client basis, either using the `SelectorInterface` implementations from the Elasticsearch SDK, or a custom connection selector service.

The built-in connection selectors are `round_robin` (default), `sticky_round_robin` and `random`.

```yaml
markup_elasticsearch:
    clients:
        my_selector_client:
            connection_selector: sticky_round_robin
```

The above configuration will define a client `my_selector_client` which uses the [in-built sticky round-robin](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_selectors.html#_stickyroundrobinselector) connection selector implementation.

```yaml
markup_elasticsearch:
    clients:
        my_custom_selector_client:
            connection_selector: coin_toss
    custom_connection_selectors:
        coin_toss: 'acme.coin_toss_selector'
```

The above configuration will define a client `my_custom_selector_client` which uses a custom connection selector service `acme.coin_toss_selector`.

### Serializers

It is not expected that one would need to configure this, but provided for the sake of completeness:

You can define [serializers](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_serializers.html) on a per-client basis, either using the `SerializerInterface` implementations from the Elasticsearch SDK, or a custom serializer service implementing that interface.

The built-in serializers are `smart` (default), `array_to_json` and `everything_to_json`.

```yaml
markup_elasticsearch:
    clients:
        my_serializer_client:
            serializer: everything_to_json
```

The above configuration will define a client `my_serializer_client` which uses the [in-built everything to JSON](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_serializers.html#_everythingtojsonserializer) serializer implementation.

```yaml
markup_elasticsearch:
    clients:
        my_custom_serializer_client:
            serializer: mangled
    custom_serializers:
        mangle: 'acme.mangled_serializer'
```

The above configuration will define a client `my_custom_serializer_client` which uses a custom serializer service `acme.mangled_serializer`.

### HTTP Handlers (RingPHP)

It is not expected that one would need to configure this, but provided for the sake of completeness:

The Elasticsearch SDK uses [RingPHP](https://github.com/guzzle/RingPHP/) HTTP handlers under the hood. Generally the default handler is fine for most cases, but there may be small performance gains etc to be had using a different, or even custom, handler. [The Elasticsearch SDK docs on handlers](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_configure_the_http_handler) has more information.

The built-in RingPHP-compatible handlers are `default` (default), `single` and `multi`.

```yaml
markup_elasticsearch:
    clients:
        my_handler_client:
            handler: multi
```

The above configuration will define a client `my_handler_client` which uses an in-built handler able to make multiple calls concurrently. (The default handler also does this, but has some logic to determine when to use it.)

```yaml
markup_elasticsearch:
    clients:
        my_custom_handler_client:
            handler: edison
    custom_handlers:
        edison: 'acme.edison_handler'
```

The above configuration will define a client `my_custom_handler_client` which uses a custom RingPHP handler service `acme.edison_handler` that seems to be named after Thomas Edison. For more information about writing a RingPHP HTTP handler, [read the project's documentation on handlers](http://guzzle.readthedocs.org/en/latest/handlers.html).

### Connection Factories

You can define [connection factories](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_setting_a_custom_connectionfactory) on a per-client basis using a custom service that implements `ConnectionFactoryInterface` from the Elasticsearch SDK.

There are no in-built connection factories aside from the default implementation.

```yaml
markup_elasticsearch:
    clients:
        my_custom_connection_factory_client:
            connection_factory: my_super_performant_factory
    custom_connection_factories:
        my_super_performant_factory: 'acme.performant_factory'
```

The above configuration will define a client `my_custom_connection_factory_client` which uses a custom connection factory service `acme.performant_factory`.

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
