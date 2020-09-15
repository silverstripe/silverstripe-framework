---
title: Nested type definitions
summary: Define dependent types inline with a parent type
---
# Working with DataObjects

[CHILDREN asList]

## Nested type definitions

For readability and ergonomics, you can take advantage of nested type definitions. Let's imagine
we have a `Blog` and we want to expose `Author` and `Categories`, but while we're at it, we want
to specify what fields they should have, and maybe even some operations of their own.

*app/_graphql/models.yml*
```yaml
MyProject\Pages\Blog:
  fields:
    title: true
    author:
      fields:
        firstName: true
        surname: true
        email: true
      operations: '*'
    categories:
      fields: '*'
```

Alternatively, we could flatten that out:

*app/_graphql/models.yml*
```yaml
MyProject\Pages\Blog:
  fields:
    title: true
    author: true
    categories: true
SilverStripe\Securty\Member:
  fields
    firstName: true
    surname: true
    email: true
  operations: '*'
MyProject\Models\BlogCategory:
  fields: '*'
```

### Further reading

[CHILDREN]
