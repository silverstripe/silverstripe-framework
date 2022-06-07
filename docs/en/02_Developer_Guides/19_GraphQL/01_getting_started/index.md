---
title: Getting started
summary: Open up your first GraphQL server and build your schema
icon: rocket
---

# Getting started

This section of the documentation will give you an overview of how to get a simple GraphQL API
up and running with some dataobject content.

## Quick start: Stand up an API in 60 seconds.

The following steps will create a public facing API on the `/graphql` endpoint
with your app's dataobjects exposed.

[info]
**Before you begin**: It is strongly recommended that you install the GraphQL devtools
module to help with testing your API.

`$ composer require silverstripe/graphql-devtools`
[/info]

### Step 1: Run the initialise task

`$ vendor/bin/sake dev/graphql/init namespace="MyAgency\MyProject"`

**namespace** is a required argument. It should be the namespace used by your
app.

For a full list of options, run:

`$ vendor/bin/sake dev/graphql/init help=1`

The task will create a `_graphql/` folder in your project, a new `Resolvers`
class in your code directory, and a new `graphql.yml` file in your `_config/` directory.

### Step 2: Bulk load all of your models

The [bulk loader](working_with_dataobjects/adding_dataobjects_to_the_schema/#bulk-loading-models) API can be used to add groups of dataobjects to the schema. Let's start with all of our models.

Edit the `_graphql/bulkLoad.yml` file:

```
app:
  # Load everything in our app that has the Versioned extension
  load:
    namespaceLoader:
      include:
        - MyApp\Models\*
  apply:
    fields:
      '*': true
    operations:
      '*': true
```

We've added all the fields and all operations (read, readOne, update,
create, delete) to all of the models in the namespace provided.

### Step 3: Build

The schema relies on generated code, so any time we update our schema, we'll need to run this task.

`$ vendor/bin/sake dev/graphql/build flush=1`

**Note**: Most of the time `flush=1` is not required for schema build changes, since they are outside the `_config/` directory, but in this case, 
because we've added a `_config/graphql.yml` file, we need to flush.

### Step 4: Profit!

If you have the [GraphQL devtools](https://github.com/silverstripe/graphql-devtools) library
installed, you should be able to browse your schema using the in-browser
IDE at `/dev/graphql/ide`.

Try out this query to get started:

```graphql
query readPages {
    nodes {
        id
        title
    }
}
```

## Installing on silverstripe/recipe-cms < 4.11

The 4.8 - 4.10 releases of `recipe-cms` support both versions `3` and `4.0.0-alpha` versions of this module. Using the alpha (or beta) releases requires inlining the recipe and updating the `silverstripe/graphql` version.

You can inline silverstripe/recipe-cms by running this command:

```
composer update-recipe silverstripe/recipe-cms
```

Alternatively, you can remove `silverstripe/recipe-cms` from your root `composer.json` and replace it with the contents of the `composer.json` in `silverstripe/recipe-cms`.

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

[CHILDREN]
