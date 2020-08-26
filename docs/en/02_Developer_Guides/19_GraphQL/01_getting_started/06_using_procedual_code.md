---
title: Building a schema with procedural code
summary: Use PHP code to build your schema
---

# Building a schema with procedural code

Sometimes you need access to dynamic information to populate your schema. For instance, you
may have an enum containing a list of all the languages that are configured for the website. It
wouldn't make sense to build this statically. It makes more sense to have a single source
of truth.

Internally, model-driven types that conform to the shapes of their models must use procedural code to add fields, create operations, and more, because the entire premise of model-driven
types is that they're dynamic. So the procedural API for schemas has to be pretty robust.

Lastly, if you just prefer writing PHP to writing YAML, this is a good option, too.

[notice]
One thing you cannot do with the procedural API, though it may be tempting, is define resolvers
on the fly as closures. Resolvers must be static methods on a class.
[/notice]

## Adding a schema builder

We can use the `builders` section of the config to add an implementation of `SchemaUpdater`.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      builders:
        - 'MyProject\MySchema'
```

Now just implement the `SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater` interface.

**app/src/MySchema.php**
```php
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;

class MySchema implements SchemaUpdater
{
    public static function updateSchema(Schema $schema): void
    {
        // update here
    }
}
```

## Example code

Most the API should be self-documenting, and a good IDE should autocomplete everything you
need, but the key methods map directly to their configuration counterparts:

* types (`->addType(Type $type)`)
* models (`->addModel(ModelType $type)`)
* queries (`->addQuery(Query $query)`)
* mutations (`->addMutation(Mutation $mutation)`)
* enums (`->addEnum(Enum $type)`)
* interfaces (`->addInterface(InterfaceType $type)`)
* unions (`->addUnion(UnionType $type)`)


```php
    public static function updateSchema(Schema $schema): void
    {
        $myType = Type::create('Country')
            ->addField('name', 'String')
            ->addField('code', 'String');
        $schema->addType($myType);

        $myQuery = Query::create('readCountries', '[Country]')
            ->addArg('limit', 'Int');

        $myModel = ModelType::create(MyDataObject::class)
            ->addAllFields()
            ->addAllOperations();
        $schema->addModel($myModel);

    }
```

### Fluent setters

To make your code chainable, when adding fields and arguments, you can invoke a callback
to update it on the fly.

```php
$myType = Type::create('Country')
    ->addField('name', 'String', function (Field $field) {

        // Must be a callable. No inline closures allowed!
        $field->setResolver([MyClass::class, 'myResolver'])
            ->addArg('myArg', 'String!');
    })
    ->addField('code', 'String');
$schema->addType($myType);

$myQuery = Query::create('readCountries', '[Country]')
    ->addArg('limit', 'Int', function (Argument $arg) {
        $arg->setDefaultValue(20);
     });
```
