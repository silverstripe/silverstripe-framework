---
title: Nested type definitions
summary: Define dependent types inline with a parent type
---

# Nested type definitions

For readability and ergonomics, you can take advantage of nested type definitions. Let's imagine
we have a `Blog` and we want to expose `Author` and `Categories`, but while we're at it, we want
to specify what fields they should have, and maybe even some operations of their own.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
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

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
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

