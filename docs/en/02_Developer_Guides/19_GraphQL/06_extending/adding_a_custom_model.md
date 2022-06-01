---
title: Adding a custom model
summary: Add a new class-backed type beyond DataObject
---
# Extending the Schema

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## Adding a custom model

The only point of contact the `silverstripe/graphql` schema has with
Silverstripe specifically is through the `DataObjectModel` adapter
and its associated plugins. This is important, because it means you
can plug in any schema-aware class as a model, and it will be afforded
all the same features as DataObjects.

It is, however, hard to imagine a model-driven type that isn't 
related to an ORM, so we'll keep this section simple and just describe
what the requirements are rather than think up an outlandish example
of what a non-DataObject model might be.

### SchemaModelInterface

Models must implement the `SchemaModelInterface`, which has some
hefty requirements. Let's walk through them:

* `getIdentifier(): string`: A unique identifier for this model type,
e.g. 'DataObject'
* `hasField(string $fieldName): bool`: Return true if `$fieldName` exists
on the model
* `getTypeForField(string $fieldName): ?string`: Given a field name,
infer the type. If the field doesn't exist, return `null`
* `getTypeName(): string`: Get the name of the type (i.e. based on
the source class)
* `getDefaultResolver(?array $context = []): ResolverReference`:
Get the generic resolver that should be used for types that are built
with this model. 
* `getSourceClass(): string`: Get the name of the class that builds
the type, e.g. `MyDataObject`
* `getAllFields(): array`: Get all available fields on the object
* `getModelField(string $fieldName): ?ModelType`: For nested fields.
If a field resolves to another model (e.g. has_one), return that
model type.

In addition, models may want to implement:

* `OperationProvider` (if your model creates operations, like
read, create, etc)
* `DefaultFieldsProvider` (if your model provides a default list
of fields, e.g. `id`)
* `DefaultPluginProvider` (if your model can supply a default set
of plugins, e.g. `default_plugins` on `DataObjectModel`)

This is all a lot to take in out of context. A good exercise would be
to look through how `DataObjectModel` implements all these methods.

### SchemaModelCreatorInterface

Given a class name, create an instance of `SchemaModelInterface`. 
This layer of abstraction is necessary because we can't assume that
all `SchemaModelInterfaces` will accept a class name in their 
constructors.

Implementors of this interface just need to be able to report
whether they apply to a given class and create a model given a
class name.

Look at the DataObjectModel implementation:

```php
class ModelCreator implements SchemaModelCreatorInterface
{
    use Injectable;

    /**
     * @param string $class
     * @return bool
     */
    public function appliesTo(string $class): bool
    {
        return is_subclass_of($class, DataObject::class);
    }

    /**
     * @param string $class
     * @return SchemaModelInterface
     */
    public function createModel(string $class): SchemaModelInterface
    {
        return DataObjectModel::create($class);
    }
}

```

### Registering your model creator

Just add it to the registry:

**app/_graphql/config.yml
```yaml
modelCreators:
  - 'SilverStripe\GraphQL\Schema\DataObject\ModelCreator'
```

### Further reading

[CHILDREN]
