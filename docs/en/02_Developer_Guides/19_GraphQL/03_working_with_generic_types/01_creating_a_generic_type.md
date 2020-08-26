---
title: Creating a generic type
summary: Creating a type that doesn't map to a DataObject
---

# Creating a generic type

Let's create a simple type that will work with the inbuilt features of Silverstripe CMS.
We'll define some languages based on the `i18n` API.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            code: String!
            name: String!
```

We've defined a type called `Country` that has two fields: `code` and `name`. An example record
could be something like:

```
[
    'code' => 'bt',
    'name' => 'Bhutan'
]
```

That's all we have to do for now! Let's move on to [building a custom query](building_a_custom_query) to see how we
can use it.
