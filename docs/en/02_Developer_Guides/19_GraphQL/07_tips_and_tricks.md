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


## Debugging the generated code

By default, the generated PHP code is put into obfuscated classnames and filenames to prevent poisoning the search
tools within IDEs. Without this, you can search for something like "Page" in your IDE and get both a generated GraphQL type (probably not what you want) and a DataObject (more likely what you want) in the results and have no easy way of differentiating between the two.

When debugging, however, it's much easier if these classnames are human-readable. To turn on debug mode, add `DEBUG_SCHEMA=1` to your environment file and the classnames and filenames in the generated code directory will match their type names.

[warning]
Take care not to use `DEBUG_SCHEMA=1` as an inline environment variable to your build command, e.g. `DEBUG_SCHEMA=1 vendor/bin/sake dev/graphql/build` because any activity that happens at run time, e.g. querying the schema will fail, since the environment variable is no longer set.
[/warning]

In live mode, full obfuscation kicks in and the filenames become unreadable. You can only determine the type they map
to by looking at the generated classes and finding the `// @type:<typename>` inline comment, e.g. `// @type:Page`.

This obfuscation is handled by the `NameObfuscator` interface. See the `config.yml` file in the GraphQL module for
the various implementations, which include:

* `NaiveNameObfuscator`: Filename/Classname === Type name
* `HybridNameObfuscator`: Filename/Classname is a mix of the typename and a hash (default).
* `HashNameObfuscator`: Filename/Classname is a md5 hash of the type name (non-dev only).

## Getting the type name for a model class

Often times, you'll need to know the name of the type given a class name. There's a bit of context to this.


### Getting the type name from within your app

If you need the type name during normal execution of your app, e.g. to display in your UI, you can rely
on the cached typenames, which are persisted alongside your generated schema code.

```php
SchemaBuilder::singleton()->read('default')->getTypeNameForClass($className);
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


## Schema introspection {#schema-introspection}

Some GraphQL clients such as [Apollo](http://apollographql.com) require some level of introspection
into the schema. The `SchemaTranscriber` class will persist this data to a static file in an event 
that is fired on completion of the schema build. This file can then be consumed by a client side library
like Apollo. The `silverstripe/admin` module is built to consume this data and expects it to be in a
web-accessible location.


```json
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
By default, the file will be stored in `public/_graphql`. Files are only generated for the `silverstripe/admin` module.

If you need these types for your own uses, add a new handler:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\EventDispatcher\Dispatch\Dispatcher:
    properties:
      handlers:
        graphqlTranscribe:
          on: [ graphqlSchemaBuild.mySchema ]
          handler: '%$SilverStripe\GraphQL\Schema\Services\SchemaTranscribeHandler'
```

This handler will only apply to events fired in the `mySchema` context.
