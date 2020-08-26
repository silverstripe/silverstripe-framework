---
title: Working with generic types
summary: Break away from the magic of DataObject model and build types and queries from scratch.
---

In this section of the documentation, we cover the fundamentals that are behind a lot of the magic that goes
into making DataObject types work. We'll create some types that are not based on DataObjects at all, and we'll
write some custom queries from the ground up.

[info]
Just because we won't be using DataObjects in this example doesn't mean you can't do it. You will lose a lot
of the benefits of the DataObject model, but this lower level API may suit your needs for really specific use
cases.
[/info]


[CHILDREN]

#### A more realistic example


## Defining queries

We've now defined the shape of our data, now we need to build a way to access it. For this,
we'll need a query.

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
adding a bit of convention over configuration, and save ourselves a lot of time to boot.

### The resolver discovery pattern

When you define a query mutation, or any other field on a type, you can opt out of providing
an explicit resolver and allow the system to discover one for you based on naming convention.

Let's start by registering a resolver class(es) where we can define a bunch of these functions.

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\Schema\Registry\ResolverRegistry:
    constructor:
      myResolver: '%$MyProject\Resolvers\MyResolvers'
```

What we're registering here is called a `ResolverProvider`, and it must implement that interface.
The only thing this class is obliged to do is return a method name for a resolver given a type name and
`Field` object. If the class does not contain a resolver for that combination, it may return null and
defer to other resolver providers, or ultimately fallback on the global default resolver.

```php
public static function getResolverMethod(?string $typeName = null, ?Field $field = null): ?string;
```

Let's look at our query again:

```graphql
query {
  readCountries {
    name
  }
}
```

An example implementation of this method for our example might be:

* Does `resolveCountryName` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolveCountry` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolveName` exist?
  * Yes? Invoke
  * No? Continue
* Return null. Maybe someone else knows how to deal with this.

You can implement whatever logic you like to help the resolver provider discover which of its methods
it appropriate for the given type/field combination, but since the above pattern seems like a pretty common
implementation, the module ships an abstract `DefaultResolverProvider` that applies this logic. You can just
write the resolver methods!

Let's add a resolver method to our resolver provider:

**app/src/Resolvers/MyResolvers.php**
```php
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverProvider;

class MyResolvers extends DefaultResolverProvider
{
    public static function resolveReadCountries()
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

Now that we're using logic to discover our resolver, we can clean up the config a bit.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      # ...
      queries:
        readCountries: '[Country]'
```

Re-run the schema build, with a flush, and let's go!

`$ vendor/bin/sake dev/tasks/build-schema schema=default flush=1`


### Field resolvers

A less magical approach to resolver discovery is defining a `fieldResolver` property on your
types. This is a generic handler for all fields on a given type and can be a nice middle
ground between the rigor of hard coding everything and the opacity of discovery logic.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            name: String
            code: String
          fieldResolver: [ 'MyProject\MyResolver', 'resolveCountryField' ]
```

You'll need to do explicit checks for the `fieldName` in your resolver to make this work.

```php
public static function resolveCountryField($obj, $args, $context, ResolveInfo $info)
{
    $fieldName = $info->fieldName;
    if ($fieldName === 'image') {
        return $obj->getImage()->getURL();
    }
    // .. etc
}
```

## Adding arguments

As stated above, fields can have arguments, and queries are just fields, so let's add a simple
way of influencing the query response:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      # ...
      queries:
        'readCountries(limit: Int!)': '[Country]'
```

We've provided the required argument `limit` to the query, which will allow us to truncate the results.
Let's update the resolver accordingly.

```php
    public static function resolveReadCountries($obj, array $args = [])
    {
        $limit = $args['limit'];
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        $countries = array_slice($countries, 0, $limit);
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $code,
                'name' => $name
            ];
        }

        return $results;
    }

```

Now let's try our query again. This time, notice that the IDE is telling us we're missing a required argument.

```graphql
query {
  readCountries(limit: 5) {
    name
    code
  }
}
```

This works pretty well, but maybe it's a bit over the top to *require* the `limit` argument. We want to optimise
performance, but we also don't want to burden the developer with tedium like this. Let's give it a default value.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      # ...
      queries:
        'readCountries(limit: Int = 20)': '[Country]'
```

Rebuild the schema, and notice that the IDE is no longer yelling at you for a `limit` argument.

## Adding descriptions

One of the great features of a schema-backed API is that it is self-documenting. Many
API developers choose to maximise the benefit of this by adding descriptions to some or
all of the components of their schema.

The trade-off for using descriptions is that the YAML configuration becomes a bit more verbose.

Let's add some descriptions to our types and fields.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          description: A record that describes one of the world's sovereign nations
          fields:
            code:
              type: String!
              description: The unique two-letter country code
            name:
              type: String!
              description: The canonical name of the country, in English
```

We can also add descriptions to our query arguments. We'll have to remove the inline argument
definition to do that.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      queries:
        readCountries:
          type: '[Country]'
          description: Get all the countries in the world
          args:
            limit:
              type: Int = 20
              description: The limit that is applied to the result set
```
## Enum types

Enum types are simply a list of string values that are possible for a given field. They are
often used in arguments to queries, such as `{sort: DESC }`.

It's very easy to add enum types to your schema. Just use the `enums` section of the config.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      enums:
        SortDirection:
          DESC: Descending order
          ASC: Ascending order
```
