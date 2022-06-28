---
title: DataObject query plugins
summary: Learn about some of the useful goodies that come pre-packaged with DataObject queries
---

# Working with DataObjects

[CHILDREN asList]

[info]
You are viewing docs for silverstripe/graphql 4.x.
If you are using 3.x, documentation can be found
[in the github repository](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/info]

## `DataObject` query plugins

This module has a [plugin system](../plugins) that affords extensibility to queries, mutations,
types, fields, and just about every other thread of the schema. Model types can define default
plugins to include, and for `DataObject` queries, these include:

* `filter`
* `sort`
* `dbFieldArgs`
* `paginateList`
* `inheritance`
* `canView` (read, readOne)
* `firstResult` (readOne)

When the `silverstripe/cms` module is installed, a plugin known as `getByLink` is also added.
Other modules, such as `silverstripe/versioned` may augment that list with even more.

### The pagination plugin

The pagination plugin augments your queries in two main ways:

* Adding `limit` and `offset` arguments
* Wrapping the return type in a "connection" type with the following fields:
  * `nodes: '[YourType]'`
  * `edges: '[{ node: YourType }]'`
  * `pageInfo: '{ hasNextPage: Boolean, hasPreviousPage: Boolean: totalCount: Int }'`

Let's test it out:

```graphql
query {
  readPages(limit: 10, offset: 20) {
    nodes {
      title
    }
    edges {
        node {
            title
        }
    }
    pageInfo {
        totalCount
        hasNextPage
        hasPrevPage
    }
  }
}
```

[notice]
If you're not familiar with the jargon of `edges` and `node`, don't worry too much about it
for now. It's just a pretty well-established convention for pagination in GraphQL, mostly owing
to its frequent use with [cursor-based pagination](https://graphql.org/learn/pagination/), which
isn't something we do in Silverstripe CMS. You can ignore `edges.node` and just use `nodes` if
you want to.
[/notice]

#### Disabling pagination

Just set it to `false` in the configuration.

**app/_graphql/models.yml**
```yaml
MyProject\Models\ProductCategory:
  operations:
    read:
      plugins:
        paginateList: false
```

To disable pagination globally, use `modelConfig`:

**app/_graphql/config.yml**
```yaml
modelConfig:
  DataObject:
    operations:
      read:
        plugins:
          paginateList: false
```

### The filter plugin

The filter plugin ([`QueryFilter`](api:SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter)) adds a
special `filter` argument to the `read` and `readOne` operations.

```graphql
query {
  readPages(
    filter: { title: { eq: "Blog" } }
  ) {
    nodes {
      title
      created
    }
  }
}
```

In the above example, the `eq` is known as a "comparator". There are several of these
included with the the module, including:

* `eq` (exact match)
* `ne` (not equal)
* `contains` (fuzzy match)
* `gt` (greater than)
* `lt` (less than)
* `gte` (greater than or equal)
* `lte` (less than or equal)
* `in` (in a given list)
* `startswith` (starts with)
* `endswith` (ends with)

Example:
```graphql
query {
  readPages (
    filter: {
      title: { ne: "Home" },
      created: { gt: "2020-06-01", lte: "2020-09-01" }
    }
  ) {
    nodes {
      title
      created
    }
  }
}
```

[notice]
While it is possible to filter using multiple comparators, segmenting them into
disjunctive groups (e.g. "OR" and "AND" clauses) is not yet supported.
[/notice]

Nested fields are supported by default:

```graphql
query {
  readProductCategories(
    filter: {
      products: {
        reviews: {
          rating: { gt: 3 },
          comment: { contains: "awesome" },
          author: { ne: "Me" }
        }
      }
    }
  ) {
    nodes {
      title
    }
  }
}
```

Filters are only querying against the database by default - it is not possible to filter by
fields with custom resolvers.

#### Customising the filter fields

By default, all fields on the DataObject, including relationships, are included. To customise
this, just add a `fields` config to the plugin definition:

**app/_graphql/models.yml**
```yaml
MyProject\Models\ProductCategory:
  fields:
    title: true
    featured: true
  operations:
    read:
      plugins:
        filter:
          fields:
            title: true
```

#### Disabling the filter plugin

Just set it to `false` in the configuration.

**app/_graphql/models.yml**
```yaml
MyProject\Models\ProductCategory:
  operations:
    read:
      plugins:
        filter: false
```

To disable filtering globally, use `modelConfig`:

**app/_graphql/config.yml**
```yaml
modelConfig:
  DataObject:
    operations:
      read:
        plugins:
          filter: false
```

### The sort plugin

The sort plugin ([`QuerySort`](api:SilverStripe\GraphQL\Schema\DataObject\Plugin\QuerySort)) adds a
special `sort` argument to the `read` and `readOne` operations.

```graphql
query {
  readPages (
    sort: { created: DESC }
  ) {
    nodes {
      title
      created
    }
  }
}
```

Nested fields are supported by default, but only for linear relationships (e.g `has_one`):

```graphql
query {
  readProducts(
    sort: {
      primaryCategory: {
        lastEdited: DESC
      }
    }
  ) {
    nodes {
      title
    }
  }
}
```

#### Customising the sort fields

By default, all fields on the DataObject, including `has_one` relationships, are included.
To customise this, just add a `fields` config to the plugin definition:

**app/_graphql/models.yml**
```yaml
MyProject\Models\ProductCategory:
  fields:
    title: true
    featured: true
  operations:
    read:
      plugins:
        sort:
          fields:
            title: true
```

#### Disabling the sort plugin

Just set it to `false` in the configuration.

**app/_graphql/models.yml**
```yaml
MyProject\Models\ProductCategory:
  operations:
    read:
      plugins:
        sort: false
```

To disable sort globally, use `modelConfig`:

*app/_graphql/config.yml*
```yaml
modelConfig:
  DataObject:
    operations:
      read:
        plugins:
          sort: false
```

### The `DBFieldArgs` plugin  {#dbfieldargs}

When fields are introspected from a model and reference a `DBField` instance,
they get populated with a default set of arguments that map to methods on that
`DBField` class, for instance `$field->Nice()` or `$field->LimitSentences(4)`.

Let's have a look at this query:

```graphql
query {
  readPages {
    nodes {
      content(format: LIMIT_SENTENCES, limit: 4)
      created(format: NICE)
      
      ... on BlogPage {
        introText(format: FIRST_PARAGRAPH)
        publishDate(format: CUSTOM, customFormat: "dd/MM/yyyy")
      }
    }
  }
}
```

The primary field types that are affected by this include:

* `DBText` (including `DBHTMLText`)
* `DBDate` (including `DBDatetime`)
* `DBTime`
* `DBDecimal`
* `DBFloat`

#### All available arguments

##### `DBText`

* `format: CONTEXT_SUMMARY` (optional "limit" arg)
* `format: FIRST_PARAGRAPH`
* `format: LIMIT_SENTENCES` (optional "limit" arg)
* `format: SUMMARY` (optional "limit" arg)
* `parseShortcodes: Boolean` (DBHTMLText only)

##### `DBDate`

* `format: TIMESTAMP`
* `format: NICE`
* `format: DAY_OF_WEEK`
* `format: MONTH`
* `format: YEAR`
* `format: SHORT_MONTH`
* `format: DAY_OF_MONTH`
* `format: SHORT`
* `format: LONG`
* `format: FULL`
* `format: CUSTOM` (requires `customFormat: String` arg)

##### `DBTime`

* `format: TIMESTAMP`
* `format: NICE`
* `format: SHORT`
* `format: CUSTOM` (requires `customFormat: String` arg)

##### `DBDecimal`

* `format: INT`

##### `DBFloat`

* `format: NICE`
* `format: ROUND`
* `format: NICE_ROUND`

#### Enum naming strategy and deduplication

By default, auto-generated Enum types will use as generic a name as possible using the convention `<FieldName>Enum` (e.g.
`OrderStatusEnum`). On occasion, this may collide with other types (e.g. `OptionsEnum` is quite generic and likely to be used already).
In this case, the second enum generated will use `<TypeName><FieldName>Enum` (e.g. `MyTypeOptionsEnum`).

If an enum already exists with the same fields and name, it will be reused. For instance, if `OptionsEnum`
is found and has exactly the same defined values (in the same order) as the Enum being generated,
it will be reused rather than proceeding to the deduplication strategy.

#### Custom enum names

You can specify custom enum names in the plugin config:

**app/_graphql/config.yml**
```yaml
modelConfig:
  DataObject:
    plugins:
      dbFieldTypes:
        enumTypeMapping:
          MyType:
            myEnumField: SomeCustomTypeName
             
```

You can also specify enums to be ignored. (`ClassName` does this on all DataObjects to prevent inheritance
issues)

**app/_graphql/config.yml**
```yaml
modelConfig:
  DataObject:
    plugins:
      dbFieldTypes:
        ignore:
          MyType:
            myEnumField: true
             
```

### The getByLink plugin

When the `silverstripe/cms` module is installed (it is in most cases), a plugin called `getByLink`
will ensure that queries that return a single `DataObject` model (e.g. `readOne`) get a new query argument
called `link` (configurable on the `field_name` property of `LinkablePlugin`).

```graphql
readOneSiteTree(link: "/about-us" ) {
  title
}
```

### Further reading

[CHILDREN]
