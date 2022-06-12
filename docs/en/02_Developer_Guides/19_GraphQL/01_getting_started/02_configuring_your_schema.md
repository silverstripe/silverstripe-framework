---
title: Configuring your schema
summary: Add a basic type to the schema configuration
icon: code
---

# Getting started

[CHILDREN asList]

[info]
You are viewing docs for silverstripe/graphql 4.x.
If you are using 3.x, documentation can be found
[in the github repository](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/info]

## Configuring your schema

GraphQL is a strongly-typed API layer, so having a schema behind it is essential. Simply put:

* A schema consists of **[types](https://graphql.org/learn/schema/#type-system)**
* **Types** consist of **[fields](https://graphql.org/learn/queries/#fields)**
* **Fields** can have **[arguments](https://graphql.org/learn/queries/#arguments)**.
* **Fields** need to be **[resolved](https://graphql.org/learn/execution/#root-fields-resolvers)**

**Queries** are just **fields** on a type called "query". They can take arguments, and they
must be resolved.

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

Let's populate a schema that is pre-configured for us out of the box called "default".

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      config:
        # general schema config here
      types:
        # your generic types here
      models:
        # your DataObjects here
      queries:
        # your queries here
      mutations:
        # your mutations here
```

### Avoid config flushes

Because the schema definition is only consumed at build time and never used at runtime, it doesn't
make much sense to store it in the configuration layer, because it just means you'll
have to `flush=1` every time you make a schema update, which will slow down your builds.

It is recommended that you store your schema YAML **outside of the _config directory** to
increase performance and remove the need for flushing when you [build your schema](building_the_schema).

[notice]
This doesn't mean there is never a need to `flush=1` when building your schema. If you were to add a new
schema, make a change to the value of this `src` attribute, or create new PHP classes, those are still
standard config changes which won't take effect without a flush.
[/notice]

We can do this by adding a `src` key to our `app/_config/graphql.yml` schema definition
that maps to a directory relative to the project root.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      src: 
        - app/_graphql
```

Your `src` must be an array. This allows further source files to be merged into your schema.
This feature can be use to extend the schema of third party modules.

[info]
Your directory can also be relative to a module reference, e.g. `somevendor/somemodule: _graphql`:

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      src:
        - app/_graphql
        - module/_graphql
        # The next line would map to `vendor/somevendor/somemodule/_graphql`
        - 'somevendor/somemodule: _graphql'
```
[/info]

Now, in the new `app/_graphql` folder, we can create YAML file definitions.

**app/_graphql/schema.yml**
```yaml
# no schema key needed. it's implied!
config:
  # your schema config here
types:
  # your generic types here
models:
  # your DataObjects here
bulkLoad:
  # your bulk loader directives here
queries:
  # your queries here
mutations:
  # your mutations here
```

#### Namespacing your schema files

Your schema YAML file will get quite bloated if it's just used as a monolithic source of truth
like this. We can tidy this up quite a bit by simply placing the files in directories that map
to the keys they populate -- e.g. `config/`, `types/`, `models/`, `queries/`, `mutations/`, etc.

There are two approaches to namespacing:

* By filename
* By directory name

##### Namespacing by filename

If the filename is named one of the four keywords above, it will be implicitly placed
in the corresponding section of the schema - e.g. any configuration
added to `app/_graphql/config.yml` will be implicitly added to
`SilverStripe\GraphQL\Schema\Schema.schemas.default.config`.

**This only works in the root source directory** (i.e. `app/_graphql/some-directory/config.yml`
will not work).

**app/_graphql/config.yml**
```yaml
# my config here
```

**app/_graphql/types.yml**
```yaml
# my types here
```

**app/_graphql/models.yml**
```yaml
# my models here
```

**app/_graphql/bulkLoad.yml**
```yaml
# my bulk loader directives here
```

##### Namespacing by directory name

If you use a parent directory name (at any depth) of one of the four keywords above, it will
be implicitly placed in the corresponding section of the schema - e.g. any configuration
added to a `.yml` file in `app/_graphql/config/` will be implicitly added to
`SilverStripe\GraphQL\Schema\Schema.schemas.default.config`.

[hint]
The names of the actual files here do not matter. You could for example have a separate file
for each of your types, e.g. `app/_graphql/types/my-first-type.yml`.
[/hint]

**app/_graphql/config/config.yml**
```yaml
# my config here
```

**app/_graphql/types/types.yml**
```yaml
# my types here
```

**app/_graphql/models/models.yml**
```yaml
# my models here
```

**app/_graphql/bulkLoad/bulkLoad.yml**
```yaml
# my bulk loader directives here
```

##### Going even more granular

These special directories can contain multiple files that will all merge together, so you can even
create one file per type, or some other convention. All that matters is that the parent directory name
_or_ the filename matches one of the schema keys.

The following are perfectly valid:

* `app/_graphql/config/config.yml` maps to `SilverStripe\GraphQL\Schema\Schema.schemas.default.config`
* `app/_graphql/types/allElementalBlocks.yml` maps to `SilverStripe\GraphQL\Schema\Schema.schemas.default.types`
* `app/_graphql/news-and-blog/models/blog.yml` maps to `SilverStripe\GraphQL\Schema\Schema.schemas.default.models`
* `app/_graphql/mySchema.yml` maps to `SilverStripe\GraphQL\Schema\Schema.schemas.default`

### Schema config

Each schema can declare a generic configuration section, `config`. This is mostly used for assigning
or removing plugins and resolvers.

An important subsection of `config` is `modelConfig`, where you can configure settings for specific
models, e.g. `DataObject`.

Like the other sections, it can have its own `config.yml`, or just be added as a `config:`
mapping to a generic schema yaml document.

**app/_graphql/config.yml**
```yaml
modelConfig:
  DataObject:
    plugins:
      inheritance: true
    operations:
      read:
        plugins:
          readVersion: false
          paginateList: false
```

You can learn more about plugins and resolvers in the [query plugins](../working_with_dataobjects/query_plugins),
[plugins](../plugins), [building a custom query](../working_with_generic_types/building_a_custom_query#building-a-custom-query),
and [resolver discovery](../working_with_generic_types/resolver_discovery) sections.

### Defining a basic type

Let's define a generic type for our GraphQL schema.

[info]
Generic types don't map to `DataObject` classes - they're useful for querying more 'generic' data (hence the name).
You'll learn more about adding DataObjects in [working with DataObjects](../working_with_DataObjects).
[/info]

**app/_graphql/types.yml***
```yaml
Country:
  fields:
    name: String
    code: String
    population: Int
    languages: '[String]'
```

If you're familiar with [GraphQL type language](https://graphql.org/learn/schema/#type-language),
this should look pretty familiar.

There are only a handful of [scalar types](https://graphql.org/learn/schema/#scalar-types)
available in GraphQL by default. They are:

* String
* Int
* Float
* Boolean

To define a type as a list, you wrap it in brackets: `[String]`, `[Int]`

To define a type as required (non-null), you add an exclamation mark: `String!`

Often times, you may want to do both: `[String!]!`

[notice]
Look out for the footgun, here. Make sure your bracketed type is in quotes
(i.e. `'[String]'`, not `[String]`), otherwise it's valid YAML that will get parsed as an array!
[/notice]

That's all there is to it! To learn how we can take this further, check out the
[working with generic types](../working_with_generic_types) documentation. Otherwise,
let's get started on [**adding some DataObjects**](../working_with_DataObjects).

### Further reading

[CHILDREN]
