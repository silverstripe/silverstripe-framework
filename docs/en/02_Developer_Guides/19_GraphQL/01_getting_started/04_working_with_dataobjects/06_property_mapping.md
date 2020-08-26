---
title: Property mapping and dot syntax
summary: Learn how to customise field names, use dot syntax, and use aggregate functions
---
# Property mapping and dot syntax

For the most part, field names are inferred through the DataObject model, but its API affords developers full
control over naming:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        Page:
          fields:
            pageContent:
              type: String
              property: Content
```

**NB**: When using explicit property mapping, you must also define an explicit type, as it can
no longer be inferred.



## Dot-separated accessors

Property mapping is particularly useful when using **dot syntax** to access fields.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        MyProject\Pages\Blog:
          fields:
            title: true
            authorName:
              type: String
              property: 'Author.FirstName'
```

Fields on plural relationships will automatically convert to a `column` array:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        MyProject\Pages\Blog:
          fields:
            title: true
            categoryTitles:
              type: '[String]'
              property: 'Categories.Title'
            authorsFavourites:
              type: '[String]'
              property: 'Author.FavouritePosts.Title'
```

We can even use a small subset of **aggregates**, including `Count()`, `Max()`, `Min()` and `Avg()`.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        MyProject\Models\ProductCategory:
          fields:
            title: true
            productCount:
              type: Int
              property: 'Products.Count()'
            averageProductPrice:
              type: Float
              property: 'Products.Avg(Price)'
```
