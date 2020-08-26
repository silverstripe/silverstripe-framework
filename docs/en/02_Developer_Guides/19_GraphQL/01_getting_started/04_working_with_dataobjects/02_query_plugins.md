---
title: DataObject query plugins
summary: Learn about some of the useful goodies that come pre-packaged with DataObject queries
---

# DataObject query plugins

This module has a [plugin system](plugins.md) that affords extensibility to queries, mutations,
types, fields, and just about every other thread of the schema. Model types can define default
plugins to include, and for DataObject queries, these include:

* filter
* sort
* paginateList
* inheritance
* canViewItem (readOne)
* canViewList (read)

Other modules, such as `silverstripe-versioned` may augment that list with even more.

## The pagination plugin

The pagination plugin augments your queries in two main ways:

* Adding `limit` and `offset` arguments
* Wrapping the return type in a "connection" type with the following fields:
  * `nodes: '[YourType]'`
  * `edges: '[{ node: YourType }]'`
  * `pageInfo: '{ hasNextPage: Boolean, hasPrevPage: Boolean: totalCount: Int }'`

Let's test it out:

```graphql
query {
  readSiteTrees(limit: 10, offset: 20) {
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
```

If you're not familiar with the jargon of `edges` and `node`, don't worry too much about it
for now. It's just a pretty well-established convention for pagination in GraphQL, mostly owing
to its frequent use with [cursor-based pagination](https://graphql.org/learn/pagination/), which
isn't something we do in Silverstripe CMS.

### Disabling pagination

Just set it to `false` in the configuration.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        MyProject\Models\ProductCategory:
          operations:
            read:
              plugins:
                paginateList: false
```


## The filter plugin

The filter plugin (`SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter`) adds a
special `filter` argument to the `read` and `readOne` operations.

```yaml
query {
  readSiteTrees(
    filter: { title: { eq: "Blog" } }
  ) {
  nodes {
    title
    created
  }
}
```

In the above example, the `eq` is known as a *comparator*. There are several of these
included with the the module, including:

* eq (exact match)
* ne (not equal)
* contains (fuzzy match)
* gt (greater than)
* lt (less than)
* gte (greater than or equal)
* lte (less than or equal)
* in (in a given list)
* startswith (starts with)
* endswith (ends with)

Example:
```yaml
query {
  readSiteTrees(
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
```

**NB**: While it is possible to filter using multiple comparators, segmenting them into
disjunctive groups (e.g. "OR" and "AND" clauses) is not yet supported.

Nested fields are supported by default:

```yaml
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
```


### Customising the filter fields

By default, all fields on the dataobject, including relationships, are included. To customise
this, just add a `fields` config to the plugin definition:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
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

### Disabling the filter plugin

Just set it to `false` in the configuration.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        MyProject\Models\ProductCategory:
          operations:
            read:
              plugins:
                filter: false
```

## The sort plugin

The sort plugin (`SilverStripe\GraphQL\Schema\DataObject\Plugin\QuerySort`) adds a
special `sort` argument to the `read` and `readOne` operations.

```yaml
query {
  readSiteTrees(
    sort: { created: DESC }
  ) {
  nodes {
    title
    created
  }
}
```

Nested fields are supported by default, but only for linear relationships (e.g has_one):

```yaml
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
```


### Customising the sort fields

By default, all fields on the dataobject, including `has_one` relationships, are included.
To customise this, just add a `fields` config to the plugin definition:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
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

### Disabling the sort plugin

Just set it to `false` in the configuration.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        MyProject\Models\ProductCategory:
          operations:
            read:
              plugins:
                sort: false
```
