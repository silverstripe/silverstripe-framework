---
title: Tips & Tricks
summary: Miscellaneous useful tips for working with your GraphQL schema
---

# Tips & Tricks


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

All of these implementations can be configured through `Injector`. Note that each schema gets its
own set of persisted queries. In these examples, we're using the `default`schema.

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

Note that if you pass `query` along with `id`, an exception will be thrown.

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
