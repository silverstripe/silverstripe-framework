---
title: Building the schema
summary: Turn your schema configuration into executable code
---

# Getting started

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## Building the schema

The primary API surface of the `silverstripe/graphql` module is the yaml configuration, along
with some [procedural configuration](using_procedual_code). It is important to understand
that **none of this configuration gets interpreted at runtime**. Loading the schema configuration
(which we refer to as the "schema definition") at runtime and converting it to executable code
has dire effects on performance, making API requests slower and slower as the schema grows larger.

To mitigate this problem, the schema that gets executed at runtime is **generated PHP code**.
This code generation happens during a build step, and it is critical to run this build step
whenever the schema changes.

### Running the build

The task that generates the schema code is `dev/graphql/build`.

`vendor/bin/sake dev/graphql/build`

This task takes an optional `schema` parameter. If you only want to generate a specific schema
(e.g. generate your custom schema, but not the CMS schema), you should pass in the name of the
schema you want to build.

`vendor/bin/sake dev/graphql/build schema=default`

[info]
Most of the time, the name of your custom schema is `default`. If you're editing DataObjects
that are accessed with GraphQL in the CMS, you may have to build the `admin` schema as well.
[/info]

Keep in mind that many of your changes will be in YAML, which also requires a flush.

`vendor/bin/sake dev/graphql/build schema=default flush=1`

[info]
If you do not provide a `schema` parameter, the task will build all schemas.
[/info]

### Building on dev/build

By default, all schemas will be built as a side-effect of `dev/build`. To disable this, change
the config:

```yaml
SilverStripe\GraphQL\Extensions\DevBuildExtension:
  enabled: false
```

### Caching

Generating code is a pretty expensive process. A large schema with 50 dataobject classes exposing
all their operations can take up to **20 seconds** to generate. This may be acceptable
for initial builds and deployments, but during incremental development this can really
slow things down.

To mitigate this, the generated code for each type is cached against a signature.
If the type hasn't changed, it doesn't re-render. This reduces build times to **under one second** for incremental changes. 

#### Clearing the cache

Normally, we'd use `flush=1` to clear the cache, but since you almost always need to run `flush=1` with the build task, it isn't a good fit. Instead, use `clear=1`.

`vendor/bin/sake dev/graphql/build schema=default clear=1`

If your schema is producing unexpected results, try using `clear=1` to eliminate the possibility
of a caching issue. If the issue is resolved, record exactly what you changed and [create an issue](https://github.com/silverstripe/silverstripe-graphql/issues/new).

### Build gotchas

Keep in mind that it's not always explicit schema configuration changes that require a build.
Anything influencing the output of the schema will require a build. This could include
tangential changes such as:

* Updating the `$db` array (or relationships) of a DataObject that has `fields: '*'`.
* Adding a new resolver for a type that uses [resolver discovery](../working_with_generic_types/resolver_discovery)
* Adding an extension to a DataObject
* Adding a new subclass to a DataObject that is already exposed
* If you are using Silverstripe CMS **without the [silverstripe/assets](https://github.com/silverstripe/silverstripe-assets) module installed, the build task will leave a `.graphql` file artefact in your public directory for CMS reference.
Though it doesn't contain any highly sensitive data, we recommend you block this file from being viewed by outside
  traffic.
  


### Viewing the generated code

By default, the generated code is placed in the `.graphql-generated/` directory in the root of your project.
It is not meant to be accessible through your webserver (which is ensured by dot-prefixing)
and keeping it outside of the `public/` webroot. 

Additional files are generated for CMS operation in `public/_graphql/`, and
those are meant to be accessible through your webserver.
See [Tips and Tricks: Schema Introspection](tips_and_tricks#schema-introspection)
to find out how to generate these files for your own schema.


### Further reading

[CHILDREN]
