---
title: Nested type definitions
summary: Define dependent types inline with a parent type
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

## Nested type definitions

For readability and ergonomics, you can take advantage of nested type definitions. Let's imagine
we have a `Blog` and we want to expose `Author` and `Categories`, but while we're at it, we want
to specify what fields they should have.

**app/_graphql/models.yml**
```yaml
MyProject\Pages\Blog:
  fields:
    title: true
    author:
      fields:
        firstName: true
        surname: true
        email: true
    categories:
      fields: '*'
```

Alternatively, we could flatten that out:

**app/_graphql/models.yml**
```yaml
MyProject\Pages\Blog:
  fields:
    title: true
    author: true
    categories: true
SilverStripe\Security\Member:
  fields:
    firstName: true
    surname: true
    email: true
MyProject\Models\BlogCategory:
  fields: '*'
```

[info]
You cannot define operations on nested types. They only accept fields.
[/info]

### Further reading

[CHILDREN]
