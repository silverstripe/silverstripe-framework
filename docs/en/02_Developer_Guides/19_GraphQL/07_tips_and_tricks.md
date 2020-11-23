---
title: Tips & Tricks
summary: Miscellaneous useful tips for working with your GraphQL schema
---

# Tips & Tricks

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## Getting the type name for a model class

Often times, you'll need to know the name of the type given a class name. There's a bit of context to this.

### Getting the type name at build time

If you need to know the name of the type _during the build_, e.g. creating the name of an operation, field, query, etc,
you should use the `Build::requireActiveBuild()` accessor. This will get you the schema that is currently being built,
and throw if no build is active. A more tolerant method is `getActiveBuild()` which will return null if no schema
is being built.

```php
Build::requireActiveBuild()->findOrMakeModel($className)->getName();
```

### Getting the type name from within your app

If you need the type name during normal execution of your app, e.g. to display in your UI, you can rely
on the cached typenames, which are persisted alongside your generated schema code.

```php
Schema::create('default')->getTypeNameForClass($className);
```

### Why is there a difference?

It is expensive to load all of the schema config. The `getTypeNameForClass` function avoids the need to
load the config, and reads directly from the cache. To be clear, the following is functionally equivalent,
but slow:

```php
Schema::create('default')
  ->loadFromConfig()
  ->findOrMakeModel($className)
  ->getName();
```

## Persisting queries

A common pattern in GraphQL APIs is to store queries on the server by an identifier. This helps save
on bandwidth, as the client need not put a fully expressed query in the request body, but rather a
simple identifier. Also, it allows you to whitelist only specific query IDs, and block all other ad-hoc,
potentially malicious queries, which adds an extra layer of security to your API, particularly if it's public.

To implement persisted queries, you need an implementation of the
`SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider` interface. By default, three are provided,
which cover most use cases:

* `FileProvider`: Store your queries in a flat JSON file on the local filesystem.
* `HTTPProvider`: Store your queries on a remote server and reference a JSON file by URL.
* `JSONStringProvider`: Store your queries as hardcoded JSON

### Configuring query mapping providers

All of these implementations can be configured through `Injector`.

[notice]
Note that each schema gets its own set of persisted queries. In these examples, we're using the `default`schema.
[/notice]

#### FileProvider

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\FileProvider
    properties:
     schemaMapping:
       default: '/var/www/project/query-mapping.json'
```


A flat file in the path `/var/www/project/query-mapping.json` should contain something like:

```json
{"someUniqueID":"query{validateToken{Valid Message Code}}"}
```

[notice]
The file path must be absolute.
[/notice]

#### HTTPProvider

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\HTTPProvider
    properties:
     schemaMapping:
       default: 'http://example.com/myqueries.json'
```

A flat file at the URL `http://example.com/myqueries.json` should contain something like:

```json
{"someUniqueID":"query{readMembers{Name+Email}}"}
```

#### JSONStringProvider

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\HTTPProvider
    properties:
     schemaMapping:
       default: '{"myMutation":"mutation{createComment($comment:String!){Comment}}"}'
```

The queries are hardcoded into the configuration.

### Requesting queries by identifier

To access a persisted query, simply pass an `id` parameter in the request in lieu of `query`.

`GET http://example.com/graphql?id=someID`

[notice]
Note that if you pass `query` along with `id`, an exception will be thrown.
[/notice]

## Query caching (Caution: EXPERIMENTAL)

The `QueryCachingMiddleware` class is an experimental cache layer that persists the results of a GraphQL
query to limit unnecessary calls to the database. The query cache is automatically expired when any 
DataObject that it relies on is modified. The entire cache will be discarded on `?flush` requests.

To implement query caching, add the middleware to your `QueryHandlerInterface`

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface.default:
    class: SilverStripe\GraphQL\QueryHandler\QueryHandler
    properties:
      Middlewares:
        cache: '%$SilverStripe\GraphQL\Middleware\QueryCachingMiddleware'
```

And you will also need to apply an extension to all DataObjects:

```yaml
SilverStripe\ORM\DataObject:
  extensions:
    - SilverStripe\GraphQL\Extensions\QueryRecorderExtension
```

[warning]
This feature is experimental, and has not been thoroughly evaluated for security. Use at your own risk.
[/warning]


## Schema introspection

Some GraphQL clients such as [Apollo](http://apollographql.com) require some level of introspection
into the schema. While introspection is [part of the GraphQL spec](http://graphql.org/learn/introspection/),
this module provides a limited API for fetching it via non-graphql endpoints. By default, the `graphql/`
controller provides a `types` action that will return the type schema (serialised as JSON) dynamically.

*GET http://example.com/graphql/types*
```js
{
   "data":{
      "__schema":{
         "types":[
            {
               "kind":"OBJECT",
               "name":"Query",
               "possibleTypes":null
            }
            // etc ...
         ]
      }
   }

```

As your schema grows, introspecting it dynamically may have a performance hit. Alternatively,
if you have the `silverstripe/assets` module installed (as it is in the default SilverStripe installation),
GraphQL can cache your schema as a flat file in the `assets/` directory. To enable this, simply
set the `cache_types_in_filesystem` setting to `true` on `SilverStripe\GraphQL\Controller`. Once enabled,
a `types.graphql` file will be written to your `assets/` directory on `flush`.

When `cache_types_in_filesystem` is enabled, it is recommended that you remove the extension that
provides the dynamic introspection endpoint.

```php
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Extensions\IntrospectionProvider;

Controller::remove_extension(IntrospectionProvider::class);
```
