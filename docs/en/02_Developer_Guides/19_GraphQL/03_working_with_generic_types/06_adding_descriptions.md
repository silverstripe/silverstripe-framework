---
title: Adding descriptions
summary: Add descriptions to just about anything in your schema to improve your developer experience
---
# Working with generic types

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## Adding descriptions

One of the great features of a schema-backed API is that it is self-documenting. If you use
the [silverstripe/graphql-devtools](https://github.com/silverstripe/silverstripe-graphql-devtools)
module you can see the documentation by navigating to /dev/graphql/ide in your browser anc clicking
on "DOCS" on the right.

Many API developers choose to maximise the benefit of this by adding descriptions to some or
all of the components of their schema.

The trade-off for using descriptions is that the YAML configuration becomes a bit more verbose.

Let's add some descriptions to our types and fields.

**app/_graphql/schema.yml**
```yaml
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

**app/_graphql/schema.yml**
```yaml
queries:
  readCountries:
    type: '[Country]'
    description: Get all the countries in the world
    args:
      limit:
        type: Int = 20
        description: The limit that is applied to the result set
```

### Further reading

[CHILDREN]
