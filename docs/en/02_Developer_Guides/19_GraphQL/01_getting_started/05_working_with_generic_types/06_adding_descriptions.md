---
title: Adding descriptions
summary: Add descriptions to just about anything in your schema to improve your developer experience
---

# Adding descriptions

One of the great features of a schema-backed API is that it is self-documenting. Many
API developers choose to maximise the benefit of this by adding descriptions to some or
all of the components of their schema.

The trade-off for using descriptions is that the YAML configuration becomes a bit more verbose.

Let's add some descriptions to our types and fields.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          description: A record that describes one of the world's sovereign nations
          fields:
            code:
              type: String!
              description: The unique two-letter country code
            name:
              type: String!
              description: The canonical name of the country, in English
```

We can also add descriptions to our query arguments. We'll have to remove the inline argument
definition to do that.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      queries:
        readCountries:
          type: '[Country]'
          description: Get all the countries in the world
          args:
            limit:
              type: Int = 20
              description: The limit that is applied to the result set
```
