---
title: Configuring your schema
summary: Add a basic type to the schema configuration
---

# Getting started

[CHILDREN asList]

## Configuring your schema

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

### Initial setup

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

### Avoid config flushes

Because the schema YAML is only consumed at build time and never used at runtime, it doesn't
make much sense to store it in the configuration layer, because it just means you'll
have to `flush=1` every time you make a schema update, which will slow down your builds.

It is recommended that you store your schema YAML **outside of the _config directory** to
increase performance and remove the need for flushing.

We can do this by adding a `src` key to our schema definition that maps to a directory
relative to the project root.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      src: app/_graphql
```

It can also be an array of directories.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      src:
        myDir: app/_graphql
        myOtherDir: module/_graphql
```

[info]
Your directory can also be a module reference, e.g. `somevendor/somemodule: _graphql`
[/info]

Now, in our `app/_graphql` file, we can create YAML file definitions.

[notice]
This doesn't mean there is never a need to flush your schema config. If you were to add a new
 one, or make a change to the value of this `src` attribute, those are still a standard config changes.
[/notice]



**app/_graphql/schema.yml**
```yaml
# no schema key needed. it's implied!
types:
  # your generic types here
models:
  # your dataobjects here
queries:
  # your queries here
mutations:
  # your mutations here
```

#### Namespacing your schema files

Your schema YAML file will get quite bloated if it's just used as a monolithic source of truth
like this. We can tidy this up quite a bit by simply placing the files in directories that map
to the keys they populate -- e.g. `types/`, `models/`, `queries/`, `mutations/`, etc.

There are two approaches to namespacing:
* By filename
* By directory name

##### Namespacing by directory name

If you use a parent directory name (at any depth) of one of the four keywords above, it will
be implicitly placed in the corresponding section of the schema.

**app/_graphql/types/types.yml**
```yaml
# my type definitions here
```

**app/_graphql/models/models.yml**
```yaml
# my type definitions here
```

##### Namespacing by filename

If the filename is named one of the four keywords above, it will be implicitly placed 
in the corresponding section of the schema. **This only works in the root source directory**.

**app/_graphql/types.yml**
```yaml
# my types here
```

**app/_graphql/models.yml**
```yaml
# my models here
```
#### Going even more granular

These special directories can contain multiple files that will all merge together, so you can even
create one file per type, or some other convention. All that matters is that the parent directory name
matches one of the schema keys.

The following are perfectly valid:

* `app/_graphql/types/mySingleType.yml`
* `app/_graphql/models/allElementalBlocks.yml`
* `app/_graphql/news-and-blog/models/blog.yml`
* `app/_graphql/mySchema.yml`

### Changing schema defaults

In addition to all the keys mentioned above, each schema can declare a couple of generic
 configuration files, `defaults` and `modelConfig`. These are
 mostly used for assigning or removing  default plugins to models and operations.

[info]
As of now, the only one of these being used 
 is `modelConfig`, but `defaults` could some day apply non-model configuration to the schema.
[/info]

Like the other sections, it can have its own `modelConfig.yml`, or just be added as a `modelConfig:` 
mapping to a generic schema yaml document.

**app/_graphql/modelConfig.yml**
```yaml
DataObject:
  plugins:
    inheritance: true
  operations:
    read:
      plugins:
        readVersion: false
        paginateList: false
```


### Defining a basic type

Let's define a generic type for our GraphQL schema.

**app/_graphql/types.yml***
```yaml
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


### Further reading

[CHILDREN]
