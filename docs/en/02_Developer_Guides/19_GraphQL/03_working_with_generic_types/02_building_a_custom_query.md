---
title: Building a custom query
summary: Add a custom query for any type of data
---
# Working with generic types

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## Building a custom query

We've defined the shape of our data, now we need a way to access it. For this,
we'll need a query. Let's add one to the `queries` section of our config.

**app/_graphql/schema.yml**
```yaml
queries:
  readCountries: '[Country]'
```

### Resolving fields

Now we have a query that will return all the countries. In order to make this work, we'll
need a **resolver** to tell the query where to get the data from. For this, we're going to
have to break out of the configuration layer and write some PHP code.

**app/src/Resolvers/MyResolver.php**
```php
namespace MyProject\Resolvers;

class MyResolver
{
    public static function resolveCountries(): array
    {
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $code,
                'name' => $name
            ];
        }

        return $results;
    }
}
```

Resolvers are pretty loosely defined, and don't have to adhere to any specific contract
other than that they **must be static methods**. You'll see why when we add it to the configuration:

**app/_graphql/schema.yml**
```yaml
queries:
  readCountries:
    type: '[Country]'
    resolver: [ 'MyProject\Resolvers\MyResolver', 'resolveCountries' ]
```

[notice]
Note the difference in syntax here between the `type` and the `resolver` - the type declaration
_must_ have quotes around it, because we are saying "this is a list of `Country` objects". The value
of this must be a yaml _string_. But the resolver must _not_ be surrounded in quotes. It is explicitly
a yaml array, so that PHP recognises it as a `callable`.
[/notice]

Now, we just have to build the schema:

`vendor/bin/sake dev/graphql/build schema=default`

### Testing the query

Let's test this out in our GraphQL IDE. If you have the [silverstripe/graphql-devtools](https://github.com/silverstripe/silverstripe-graphql-devtools)
module installed, just go to `/dev/graphql/ide` in your browser.

As you start typing, it should autocomplete for you.

Here's our query:
```graphql
query {
  readCountries {
    name
    code
  }
}
```

And the expected response:

```json
{
  "data": {
    "readCountries": [
      {
        "name": "Afghanistan",
        "code": "af"
      },
      {
        "name": "Åland Islands",
        "code": "ax"
      },
      "... etc"
    ]
  }
}
```

[notice]
Keep in mind that [plugins](../working_with_DataObjects/query_plugins)
don't apply in this context - at least without updating the resolver
to account for them. Most importantly this means you need to
implement your own `canView()` checks. It also means you need
to add your own filter functionality, such as [pagination](adding_pagination).
[/notice]

## Resolver Method Arguments

A resolver is executed in a particular query context, which is passed into the method as arguments.

* `mixed $value`: An optional value of the parent in your data graph.
  Defaults to `null` on the root level, but can be useful to retrieve the object
  when writing field-specific resolvers (see [Resolver Discovery](resolver_discovery)).
* `array $args`: An array of optional arguments for this field (which is different from the [Query Variables](https://graphql.org/learn/queries/#variables))
* `array $context`: An arbitrary array which holds information shared between resolvers.
  Use implementors of [`ContextProvider`](api:SilverStripe\GraphQL\Schema\Interfaces\ContextProvider) to get and set
  data, rather than relying on the array keys directly.
* [`?ResolveInfo`](api:GraphQL\Type\Definition\ResolveInfo)` $info`: Data structure containing useful information for the resolving process (e.g. the field name).
  See [Fetching Data](http://webonyx.github.io/graphql-php/data-fetching/) in the underlying PHP library for details.

## Using Context Providers

The `$context` array can be useful to get access to the HTTP request,
retrieve the current member, or find out details about the schema.
You can use it through implementors of the `ContextProvider` interface.
In the example below, we'll demonstrate how you could limit viewing the country code to
users with ADMIN permissions.

**app/src/Resolvers/MyResolver.php**
```php
namespace MyProject\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\Security\Permission;

class MyResolver
{
    public static function resolveCountries(mixed $value = null, array $args = [], array $context = [], ?ResolveInfo $info = null): array
    {
        $member = UserContextProvider::get($context);
        $canViewCode = ($member && Permission::checkMember($member, 'ADMIN'));
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $canViewCode ? $code : '',
                'name' => $name
            ];
        }

        return $results;
    }
}
```

## Resolver Discovery

This is great, but as we write more and more queries for types with more and more fields,
it's going to get awfully laborious mapping all these resolvers. Let's clean this up a bit by
adding a bit of convention over configuration, and save ourselves a lot of time to boot. We can do
that using the [resolver discovery pattern](resolver_discovery).

### Further reading

[CHILDREN]
