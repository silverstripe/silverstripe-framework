---
title: Adding pagination
summary: Add the pagination plugin to a generic query
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

## Adding pagination

So far in this section we've created a simple generic query for a `Country` type called `readCountries` that takes a
`limit` argument.

```graphql
query {
  readCountries(limit: 5) {
    name
    code
  }
}
```

Let's take this a step further and paginate it using a plugin.

### The `paginate` plugin

Since pagination is a fairly common task, we can take advantage of some reusable code here and just add a generic
plugin for paginating.

[notice]
If you're paginating a `DataList`, you might want to consider using models with read operations (instead of declaring
them as generic types with generic queries), which paginate by default using the `paginateList` plugin.
You can use generic typing and follow the below instructions too but it requires code that, for `DataObject` models,
you get for free.
[/notice]

Let's add the plugin to our query:

**app/_graphql/schema.yml**
```yaml
  queries:
    readCountries:
      type: '[Country]'
      plugins:
        paginate: {}
```

Right now the plugin will add the necessary arguments to the query, and update the return types. But
we still need to provide this generic plugin a way of actually limiting the result set, so we need a resolver.

**app/_graphql/schema.yml**
```yaml
  queries:
    readCountries:
      type: '[Country]'
      plugins:
        paginate:
          resolver: ['MyProject\Resolvers\Resolver', 'paginateCountries']

```

Let's write that resolver code now:

```php
public static function paginateCountries(array $context): Closure
{
    $maxLimit = $context['maxLimit'];
    return function (array $countries, array $args) use ($maxLimit) {
        $offset = $args['offset'];
        $limit = $args['limit'];
        $total = count($countries);
        if ($limit > $maxLimit) {
            $limit = $maxLimit;
        }

        $limitedList = array_slice($countries, $offset, $limit);

        return PaginationPlugin::createPaginationResult($total, $limitedList, $limit, $offset);
    };
}
```

A couple of things are going on here:

* Notice the new design pattern of a **context-aware resolver**. Since the plugin is configured with a `maxLimit`
parameter, we need to get this information to the function that is ultimately used in the schema. Therefore,
we create a dynamic function in a static method by wrapping it with context. It's kind of like a decorator.
* As long as we can do the work of counting and limiting the array, the [`PaginationPlugin`](api:SilverStripe\GraphQL\Schema\Plugin\PaginationPlugin)
can handle the rest. It will return an array including `edges`, `nodes`, and `pageInfo`.

Rebuild the schema and test it out:

`vendor/bin/sake dev/graphql/build schema=default`

```graphql
query {
  readCountries(limit:3, offset:4) {
    nodes {
      name
    }
  }
} 
```

### Further reading

[CHILDREN]
