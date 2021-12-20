---
title: The DataObject model type
summary: An overview of how the DataObject model can influence the creation of types, queries, and mutations
---

# Working with DataObjects

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## The DataObject model type

In Silverstripe CMS projects, our data tends to be contained in dataobjects almost exclusively,
and the silverstripe-graphql schema API is designed to make adding dataobject content fast and simple.

### Using model types

While it is possible to add dataobjects to your schema as generic types under the `types`
section of the configuration, and their associated queries and mutations under `queries` and
`mutations`, this will lead to a lot of boilerplate code and repetition. Unless you have some
really custom needs, a much better approach is to embrace _convention over configuration_
and use the `models` section of the config.

**Model types** are types that rely on external classes to tell them who they are and what
they can and cannot do. The model can define and resolve fields, auto-generate queries
and mutations, and more.

Naturally, this module comes bundled with a model type for subclasses of `DataObject`.

Let's use the `models` config to expose some content.

**app/_graphql/models.yml**
```
Page:
  fields: '*'
  operations: '*'
```

The class `Page` is a subclass of `DataObject`, so the bundled model
type will kick in here and provide a lot of assistance in building out this part of our API.

Case in point, by supplying a value of `*` for `fields` , we're saying that we want _all_ of the fields
on site tree. This includes the first level of relationships, as well, as defined on `has_one`, `has_many`,
or `many_many`.

[notice]
Fields on relationships will not inherit the `*` fields selector, and will only expose their ID by default.
[/notice]

The `*` value on `operations` tells the schema to create all available queries and mutations
 for the dataobject, including:

* `read`
* `readOne`
* `create`
* `update`
* `delete`

Now that we've changed our schema, we need to build it using the `build-schema` task:

`$ vendor/bin/sake dev/graphql/build schema=default`

Now, we can access our schema on the default graphql endpoint, `/graphql`.

Test it out!

A query:
```graphql
query {
  readPages {
    nodes {
      title
      content
      ... on BlogPage {
        date(format: NICE)
        comments {
          nodes {
            comment
            author {
              firstName
            }
          }
        }
      }
    }
}
```

[info]
Note the use of the default arguments on `date`. Fields created from `DBFields`
generate their own default sets of arguments. For more information, see the
[DBFieldArgs](query_plugins#dbfieldargs) for more information.
[/info]


A mutation:
```graphql
mutation {
  createPage(input: {
    title: "my page"
  }) {
    title
    id
  }
}
```

[info]
Did you get a permissions error? Make sure you're authenticated as someone with appropriate access.
[/info]

### Configuring operations

You may not always want to add _all_ operations with the `*` wildcard. You can allow those you
want by setting them to `true` (or `false` to remove them).

**app/_graphql/models.yml**
```
Page:
  fields: '*'
  operations:
    read: true
    create: true
```

Operations are also configurable, and accept a nested map of config.

**app/_graphql/models.yml**
```
Page:
  fields: '*'
  operations:
    create: true
    read:
      name: getAllThePages
```

#### Customising the input types

The input types, specifically in `create` and `update` can be customised with a
list of fields, which can include explicitly _disallowed_ fields.

**app/_graphql/models.yml**
```
Page:
  fields: '*'
  operations:
    create:
      fields:
        title: true
        content: true
    update:
      fields:
        '*': true
        sensitiveField: false
```

### Adding more fields

Let's add some more dataobjects, but this time, we'll only add a subset of fields and operations.

*app/_graphql/models.yml*
```yaml
Page:
  fields: '*'
  operations: '*'
MyProject\Models\Product:
  fields:
    onSale: true
    title: true
    price: true
  operations:
    delete: true
MyProject\Models\ProductCategory:
  fields:
    title: true
    featured: true
```

[notice]
A couple things to note here:

* By assigning a value of `true` to the field, we defer to the model to infer the type for the field. To override that, we can always add a `type` property:

```yaml
onSale:
  type: Boolean
```

* The mapping of our field names to the DataObject property is case-insensitive. It is a
convention in GraphQL APIs to use lowerCamelCase fields, so this is given by default.

[/notice]

### Customising model fields

You don't have to rely on the model to tell you how fields should resolve. Just like
generic types, you can customise them with arguments and resolvers.

*app/_graphql/models.yml*
```yaml
MyProject\Models\Product:
  fields:
    title:
      type: String
      resolver: [ 'MyProject\Resolver', 'resolveSpecialTitle' ]
    'price(currency: String = "NZD")': true
```

For more information on custom arguments and resolvers, see the [adding arguments](../working_with_generic_types/adding_arguments) and [resolver discovery](../working_with_generic_types/resolver_discovery) documentation.

### Excluding or customising "*" declarations

You can use the `*` as a field or operation, and anything that follows it will override the
all-inclusive collection. This is almost like a spread operator in Javascript:

```js
const newObj = {...oldObj, someProperty: 'custom' }
```

Here's an example:

**app/_graphql/models.yml**
```yaml
Page:
  fields:
    '*': true # Get everything
    sensitiveData: false # hide this field
    'content(summaryLength: Int)': true # add an argument to this field
  operations:
    '*': true
    read:
      plugins:
        paginateList: false # don't paginate the read operation
```

### Blacklisted fields {#blacklisted-fields}

While selecting all fields via `*` is usedful, there are some fields that you
don't want to accidentally expose, especially if you're a module author
and expect models within this code to be used through custom GraphQL endpoints.
For example, a module might add a secret "preview token" to each `SiteTree`.
A custom GraphQL endpoint might have used `fields: '*'` on `SiteTree` to list pages
on the public site, which now includes a sensitive field.

The `graphql_blacklisted_fields` property on `DataObject` allows you to
blacklist fields globally for all GraphQL schemas.
This blacklist applies for all operations (read, update, etc).  

**app/_config/graphql.yml**
```yaml
SilverStripe\CMS\Model\SiteTree:
    graphql_blacklisted_fields:
      myPreviewTokenField: true
```

### Model configuration

There are several settings you can apply to your model class (typically `DataObjectModel`),
but because they can have distinct values _per schema_, the standard `_config` layer is not
an option. Model configuration has to be done within the schema config in the `modelConfig`
subsection.

### Customising the type name

Most DataObject classes are namespaced, so converting them to a type name ends up
being very verbose. As a default, the `DataObjectModel` class will use the "short name"
of your DataObject as its typename (see: `ClassInfo::shortName()`). That is,
`MyProject\Models\Product` becomes `Product`.

Given the brevity of these type names, it's not inconceivable that you could run into naming
collisions, particularly if you use feature-based namespacing. Fortunately, there are
hooks you have available to help influence the typename.

#### The type formatter

The `type_formatter` is a callable that can be set on the `DataObjectModel` config. It takes
the `$className` as a parameter.

Let's turn `MyProject\Models\Product` into the more specific `MyProjectProduct`

*app/_graphql/config.yml*
```yaml
modelConfig:
  DataObject: 
    type_formatter: ['MyProject\Formatters', 'formatType' ]
```

[info]
In the above example, `DataObject` is the result of the `DataObjectModel::getIdentifier()`. Each
model class must declare one of these.
[/info]

Your formatting function could look something like:

```php
public static function formatType(string $className): string
{
    $parts = explode('\\', $className);
    if (count($parts) === 1) {
        return $className;
    }
    $first = reset($parts);
    $last = end($parts);

    return $first . $last;
}
```

#### The type prefix

You can also add prefixes to all your DataObject types. This can be a scalar value or a callable,
using the same signature as `type_formatter`.

*app/_graphql/config.yml*
```yaml
modelConfig:
  DataObject
    type_prefix: 'MyProject'
```

### Further reading

[CHILDREN]
