---
title: Creating a generic type
summary: Creating a type that doesn't map to a DataObject
---

# Working with Generic Types

[CHILDREN asList]

[info]
You are viewing docs for silverstripe/graphql 4.x.
If you are using 3.x, documentation can be found
[in the github repository](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/info]

## Creating a generic type

Let's create a simple type that will work with the inbuilt features of Silverstripe CMS.
We'll define some languages based on the `i18n` API.

**app/_graphql/types.yml**
```yaml
Country:
  fields:
    code: String!
    name: String!
```

We've defined a type called `Country` that has two fields: `code` and `name`. An example record
could be something like:

```php
[
    'code' => 'bt',
    'name' => 'Bhutan'
]
```

That's all we have to do for now! We'll need to tell GraphQL how to get this data, but first
we need to [building a custom query](building_a_custom_query) to see how we can use it.

### Further reading

[CHILDREN]
