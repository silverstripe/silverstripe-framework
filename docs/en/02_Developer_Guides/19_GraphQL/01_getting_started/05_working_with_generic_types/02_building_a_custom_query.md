---
title: Building a custom query
summary: Add a custom query for any type of data
---

# Building a custom query

We've now defined the shape of our data, now we need to build a way to access it. For this,
we'll need a query. Let's add one to the `queries` section of our config.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            code: String!
            name: String!
      queries:
        readCountries: '[Country]'
```

Now we have a query that will return all the countries. In order to make this work, we'll
need a **resolver**. For this, we're going to have to break out of the configuration layer
and write some code.

**app/src/Resolvers/MyResolver.php**
```php
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


```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            code: String!
            name: String!
      queries:
        readCountries:
          type: '[Country]'
          resolver: [ 'MyResolver', 'resolveCountries' ]
```

Now, we just have to build the schema:

`$ vendor/bin/sake dev/tasks/build-schema schema=default`

Let's test this out in our GraphQL IDE. If you have the [graphql-devtools](https://github.com/silverstripe/silverstripe-graphql-devtools) module installed, just open it up and set it to the `/graphql` endpoint.

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

This is great, but as we write more and more queries for types with more and more fields,
it's going to get awfully laborious mapping all these resolvers. Let's clean this up a bit by
adding a bit of convention over configuration, and save ourselves a lot of time to boot. We can do
that using the [resolver discovery pattern](resolver_discovery).


