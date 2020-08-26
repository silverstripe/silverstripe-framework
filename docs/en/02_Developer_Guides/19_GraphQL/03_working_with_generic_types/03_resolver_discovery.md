---
title: The resolver discovery pattern
summary: How you can opt out of mapping fields to resolvers by adhering to naming conventions
---

# The resolver discovery pattern

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

To recap, the `DefaultResolverProvider` will follow this workflow to locate a resolver
for this query:

* `resolveQueryReadCountries()` (<typeName><fieldName>)
* `resolveQuery()` (<typeName>)
* `resolveReadCountries()` (<fieldName>)
* `resolve` (catch-all)


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


## Field resolvers

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
