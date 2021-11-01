---
title: The resolver discovery pattern
summary: How you can opt out of mapping fields to resolvers by adhering to naming conventions
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

## The resolver discovery pattern

When you define a query mutation, or any other field on a type, you can opt out of providing
an explicit resolver and allow the system to discover one for you based on naming convention.

Let's start by registering a resolver class(es) where we can define a bunch of these functions.

**app/_graphql/config.yml**
```yaml
resolvers:
  - MyProject\Resolvers\MyResolvers
```

What we're registering here is a generic class that should contain one or more static functions that resolve one
or many fields. How those functions will be discovered relies on the _resolver strategy_.

### Resolver strategy

Each schema config accepts a `resolverStrategy` property. This should map to a callable that will return
a method name given a class name, type name, and `Field` instance.

```php
public static function getResolverMethod(string $className, ?string $typeName = null, ?Field $field = null): ?string;
```

#### The default resolver strategy

By default, all schemas use `SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy::getResolerMethod`
to discover resolver functions. The logic works like this:

* Does `resolve<TypeName><FieldName>` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolve<TypeName>` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolve<FieldName>` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolve` exist?
  * Yes? Invoke
  * No? Return null. This resolver cannot be discovered


Let's look at our query again:

```graphql
query {
  readCountries {
    name
  }
}
```

Imagine we have two classes registered under `resolvers` -- `ClassA` and `ClassB`

The logic will go like this:

* `ClassA::resolveCountryName`
* `ClassA::resolveCountry`
* `ClassA::resolveName`
* `ClassA::resolve`
* `ClassB::resolveCountryName`
* `ClassB::resolveCountry`
* `ClassB::resolveName`
* `ClassB::resolve`
* Return null.

You can implement whatever strategy you like in your schema. Just register it to `resolverStrategy` in the config.

**app/_graphql/config.yml**
```yaml
resolverStrategy: [ 'MyApp\Resolvers\Strategy', 'getResolverMethod' ]
```

Let's add a resolver method to our resolver provider:

**app/src/Resolvers/MyResolvers.php**
```php

class MyResolvers
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

**app/_graphql/schema.yml**
```yml
  queries:
    readCountries: '[Country]'
```

Re-run the schema build, with a flush, and let's go!

`$ vendor/bin/sake dev/graphql/build schema=default flush=1`


### Field resolvers

A less magical approach to resolver discovery is defining a `fieldResolver` property on your
types. This is a generic handler for all fields on a given type and can be a nice middle
ground between the rigor of hard coding everything and the opacity of discovery logic.

**app/_graphql/schema.yml**
```yml
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

### Further reading

[CHILDREN]
