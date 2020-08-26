---
title: DataObject inheritance
summary: Learn how inheritance is handled in DataObject types
---

# DataObject inheritance

The inheritance pattern used in the ORM is a tricky thing to navigate in a GraphQL API, mostly owing
to the fact that there is no concept of inheritance in GraphQL types. The main tools we have at our
disposal are [interfaces](https://graphql.org/learn/schema/#interfaces) and [unions](https://graphql.org/learn/schema/#union-types) to deal with this type of architecture, but in practise, it quickly becomes cumbersome.
For instance, just adding a subclass to a DataObject can force the return type to change from a simple list
of types to a union of multiple types, and this would break frontend code.

While more conventional, unions and interfaces introduce more complexity, and given how much we rely
on inheritance in Silverstripe CMS, particularly with `SiteTree`, inheritance in GraphQL is handled in a less
conventional but more ergonomic way using a plugin called `inheritance`.

## Introducing pseudo-unions

Let's take a simple example. Imagine we have this design:

```
> SiteTree
  > Page
    > NewsPage
    > Contact Page
```

Now, let's expose `Page` to graphql:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        Page:
          fields:
            title: true
            content: true
            bannerImage: true
          operations: '*'
```

Here's how we can query the inherited fields:

```graphql
query readSiteTrees {
  nodes {
    title
    content
    __extends {
      Page {
         bannerImage {
           url
         }
      }
    }
  }
}
```

The `__extends` field is semantically aligned with is PHP counterpart -- it's an object whose fields are the
names of all the types that are descendants of the parent type. Each of those objects contains all the fields
on that type, both inherited and native.

But what if one of those pages is a `NewsPage`, and we want to access its `PublishDate` field
 if available? This raises an interesting point.

## Implicit exposure

By exposing `Page`, we implicitly expose *all of its ancestors* and *all of its descendants*. Adding `Page`
to our schema implies that we also want its parent SiteTree in the schema (after all, that's where most of its fields
come from), but we also need to be mindful that queries for page will return descendants of `Page`, as well.

But these types are implicitly added to the schema, what are their fields?

The answer is *only the fields you've already opted into*. Parent classes will apply the fields exposed
by their descendants, and descendant classes will only expose their ancestors' exposed fields.

In our case, we've exposed:

* title (`SiteTree` field)
* content (`SiteTree` field)
* bannerImage (`Page` field)

The `SiteTree` type will contain the following fields:

* id (required for all DataObject types)
* title
* content

And the `NewsPage` type would contain the following fields:

* id (required for all DataObject types)
* title
* content
* bannerImage

So if we want that `PublishDate`, we need to add it to the schema explicitly:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        Page:
          fields:
            title: true
            content: true
            bannerImage: true
          operations: '*'
        MyProject\Pages\NewsPage:
          fields:
            publishDate: true
```

Now we can query it:

```graphql
query readSiteTrees {
  nodes {
    title
    content
    __extends {
      Page {
         bannerImage {
           url
         }
      }
      NewsPage {
        publishDate
      }
    }
  }
}
```

[info]
Operations are not implicitly exposed. If you add a `read` operation to `SiteTree`, you will not get one for
`NewsPage` and `ContactPage`, etc. You have to opt into those.
[/info]

## A note about duplication

One drawback of this approach is that it results in a lot of duplication. Take for instance this query:

```graphql
query readSiteTrees {
  nodes {
    title
    content
    __extends {
      Page {
         title
         bannerImage {
           url
         }
      }
      NewsPage {
        content
        publishDate
      }
    }
  }
}
```

The `title` on `Page` and `content` on `NewsPage` are identical to the fields that are queried in the parent type.
This may be of some benefit when destructuring your frontend code, but for the most part, it's just important to
remember that there's nothing distinct about these fields.
