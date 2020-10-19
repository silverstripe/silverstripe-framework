---
title: DataObject inheritance
summary: Learn how inheritance is handled in DataObject types
---

# Working with DataObjects

[CHILDREN asList]

## DataObject inheritance

The inheritance pattern used in the ORM is a tricky thing to navigate in a GraphQL API, mostly owing
to the fact that there is no concept of inheritance in GraphQL types. The main tools we have at our
disposal are [interfaces](https://graphql.org/learn/schema/#interfaces) and [unions](https://graphql.org/learn/schema/#union-types) to deal with this type of architecture, but in practise, it quickly becomes cumbersome.
For instance, just adding a subclass to a DataObject can force the return type to change from a simple list
of types to a union of multiple types, and this would break frontend code.

While more conventional, unions and interfaces introduce more complexity, and given how much we rely
on inheritance in Silverstripe CMS, particularly with `SiteTree`, inheritance in GraphQL is handled in a less
conventional but more ergonomic way using a plugin called `inheritance`.

### Introducing pseudo-unions

Let's take a simple example. Imagine we have this design:

```
> SiteTree (fields: title, content)
  > Page (fields: pageField)
    > NewsPage (fields: newsPageField)
    > Contact Page (fields: contactPageField)
```

Now, let's expose `Page` to graphql:

*app/_graphql/models.yml*
```yaml
Page:
  fields:
    title: true
    content: true
    pageField: true
  operations: '*'
NewsPage:
  fields:
    newsPageField: true
```

Here's how we can query the inherited fields:

```graphql
query readPages {
  nodes {
    title
    content
    pageField
    _extend {
      NewsPage {
        newsPageField
      }
    }
  }
}
```

The `_extend` field is semantically aligned with is PHP counterpart -- it's an object whose fields are the
names of all the types that are descendants of the parent type. Each of those objects contains all the fields
on that type, both inherited and native.

[info]
The `_extend` field is only available on base classes, e.g. `Page` in the example above.
[/info]

### Implicit exposure

By exposing `Page`, we implicitly expose *all of its ancestors* and *all of its descendants*. Adding `Page`
to our schema implies that we also want its parent `SiteTree` in the schema (after all, that's where most of its fields
come from), but we also need to be mindful that queries for page will return descendants of `Page`, as well.

But these types are implicitly added to the schema, what are their fields?

The answer is *only the fields you've already opted into*. Parent classes will apply the fields exposed
by their descendants, and descendant classes will only expose their ancestors' exposed fields.
If you are opting into all fields on a model (`fields: "*"`), this only applies to the
model itself, not its subclasses.

In our case, we've exposed:

* `title` (on `SiteTree`)
* `content` (on `SiteTree`)
* `pageField` (on `Page`)
* `newsPageField` (on `NewsPage`)

The `Page` type will contain the following fields:

* `id` (required for all DataObject types)
* `title`
* `content`
* `pageField`

And the `NewsPage` type would contain the following fields:

* `newsPageField`

[info]
Operations are not implicitly exposed. If you add a `read` operation to `SiteTree`, you will not get one for
`NewsPage` and `ContactPage`, etc. You have to opt in to those.
[/info]

### Pseudo-unions fields are de-duped

To keep things tidy, the pseudo unions in the `_extend` field remove any fields that are already in 
the parent.

```graphql
query readPages {
  nodes {
    title
    content
    _extend {
      NewsPage {
         title <---- Doesn't exist
         newsPageField
      }
    }
  }
}
```


### Further reading

[CHILDREN]
