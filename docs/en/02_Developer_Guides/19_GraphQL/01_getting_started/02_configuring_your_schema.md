---
title: Configuring your schema
summary: Add a basic type to the schema configuration
---

# Configuring your schema

GraphQL is a strongly-typed API layer, so having a schema behind it is essential. Simply put:

* A schema consists of **[types](https://graphql.org/learn/schema/#type-system)**
* **Types** consist of **[fields](https://graphql.org/learn/queries/#fields)**
* **Fields** can have **[arguments](https://graphql.org/learn/queries/#arguments)**.
* **Fields** need to **[resolve](https://graphql.org/learn/execution/#root-fields-resolvers)**

**Queries** are just **fields** on a type called "query". They can take arguments, and they
must resolve.

There's a bit more to it than that, and if you want to learn more about GraphQL, you can read
the [full documentation](https://graphql.org/learn/), but for now, these three concepts will
serve almost all of your needs to get started.

## Initial setup

To start your first schema, open a new configuration file. Let's call it `graphql.yml`.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    # your schemas here
```

Let's populate schema that is pre-configured for us out of the box, `default`.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        # your generic types here
      models:
        # your dataobjects here
      queries:
        # your queries here
      mutations:
        # your mutations here
```

## Defining a basic type

Let's define a generic type for our GraphQL schema.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            name: String
            code: String
            population: Int
            languages: '[String]'
```

If you're familiar with [GraphQL type language](https://graphql.org/learn/schema/#type-language), this should look pretty familiar. There are only a handful of scalar types available in
GraphQL by default. They are:

* String
* Int
* Float
* Boolean

To define a type as a list, you wrap it in brackets: `[String]`, `[Int]`

To define a type as required (non-null), you add an exclamation mark: `String!`

Often times, you may want to do both: `[String!]!`

[notice]
Look out for the footgun, here. Make sure your bracketed type is in quotes, otherwise it's valid YAML that will get parsed as an array!
[/notice]

That's all there is to it! To learn how we can take this further, check out the
[working with generic types](working_with_generic_types) documentation. Otherwise,
let's get started on [**adding some dataobjects**](working_with_dataobjects).


