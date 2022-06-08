---
title: Deploying the schema
summary: Deploy your GraphQL schema to a test or production environment
icon: rocket
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

## Deploying the schema

One way or another, you must get the `.graphql-generated/` and `public/_graphql/` folders into your test and production environments for Silverstripe CMS (and your own custom queries) to work as expected. There are many ways to do so. The options below are listed in order of complexity.

### Single-server hosting solutions with simple deployments {#simple-single-server}

If you host your site on a single server and you always run `dev/build` during the deployment, then assuming you have set up permissions to allow the webserver to write to the `.graphql-generated/` and `public/_graphql/` folders, your GraphQL schema will be built for you as a side-effect of running `dev/build`. You don't need to do anything further. Note that if your schema is exceptionally large you may still want to read through the rest of the options below.

### Options for any hosting solution

#### Commit the schema to version control {#commit-to-vcs}

A simplistic approach is to build the schema in your local development environment and add the `.graphql-generated/` and `public/_graphql/` folders to your version control system. With this approach you would most likely want to disable schema generation at `dev/build`.

This approach has the advantage of being very simple, but it will pollute your commits with massive diffs for the generated code.

[notice]
Make sure you set your site to `live` mode and remove any `DEBUG_SCHEMA=1` from your `.env` file if it is there before generating the schema to be committed.
[/notice]

#### Explicitly build the schema during each deployment {#build-during-deployment}

Many projects will automatically run a `dev/build` whenever they deploy a site to their production environment. If that’s your case, then you can just let this process run normally and generate the `.graphql-generated/` and `public/_graphql/` folders for you. This will allow you to add these folders to your `.gitignore` file and avoid tracking the folder in your version control system.

Be aware that for this approach to work, the process executing the `dev/build` must have write access to create the folders (or you must create those folders yourself, and give write access for those folders specifically), and for multi-server environments a `dev/build` or `dev/graphql/build` must be executed on each server hosting your site after each deployment.

#### Use a CI/CD pipeline to build your schema {#using-ci-cd}

Projects with more sophisticated requirements or bigger schemas exposing more than 100 `DataObject` classes may want to consider using a continuous-integration/continuous-deployment (CI/CD) pipeline to build their GraphQL schema.

In this kind of setup, you would need to update your deployment script to run the `dev/graphql/build` command which builds the `.graphql-generated/` and `public/_graphql/` folders. In multi-server environments this must be executed on each server hosting your site.

### Multi-server hosting solutions {#multi-server}

If your site is hosted in an environment with multiple servers or configured to auto-scale with demand, there are some additional considerations. For example if you only generate the schema on one single server (i.e. via `dev/build` or `dev/graphql/build`), then the other servers won’t have a `.graphql-generated/` or `public/_graphql/` folder (or those folders will be empty if you manually created them).

#### Rely on "on-demand" schema generation on the first GraphQL request {#on-demand}

When the first GraphQL schema request occurs, `silverstripe/graphql` will attempt to build the `.graphql-generated/` and `public/_graphql/` folders "on-demand" if they're not already present on the server. Similarly, if the folders are present but empty, it will build the schema "on-demand". This will impose a one-time performance hit on the first GraphQL request. If your project defines multiple schemas, only the schema that is being accessed will be generated.

For most common use cases, this process is relatively fast. For example, the GraphQL schema that is used to power the CMS can be built in about a quarter of a second.

While benchmarking schema generation performance, we measured that a schema exposing 180 DataObjects with 1600 relations could be built on-demand in less than 6 seconds on a small AWS instance.

Our expectation is that on-demand schema generation will be performant for most projects with small or medium schemas.

[warning]
Note that with this approach you will need to remove or empty the `.graphql-generated/` and `public/_graphql/` folders on each server for each deployment that includes a change to the schema definition, or you risk having an outdated GraphQL schema. The "on-demand" schema generation does not detect changes to the schema definition.
[/warning]

#### Build the schema during/before deployment and share it across your servers {#multi-server-shared-dirs}

If you have a particularly large schema, you may want to ensure it is always built before the first GraphQL request. It might make sense for you to sync your `.graphql-generated/` and `public/_graphql/` folders across all your servers using an EFS or similar mechanism. In that case you only need to run `dev/build` or `dev/graphql/build` on the server with the original folder - but bear in mind that this may have a performance impact.

### Performance considerations when building the GraphQL schema {#performance-considerations}

The main driver in the resources it takes to build a GraphQL schema is the number DataObjects and the number of exposed relations in that schema. In most cases, not all DataObjects in your database will be included in your schema - best practice is to only add classes to your schema definition if you will need to query them. DataObjects not included in your schema will not impact the time or memory needed to build it.

Silverstripe CMS defines an "admin" schema it uses for its own purpose. This schema is relatively small and has a negligible performance impact.

As an indication, benchmarks were run on a t3.micro AWS instance. The results are in the table below. These numbers may not be representative of the performance in your own environment. If you intend to build large GraphQL schemas, you should take the time to run your own benchmarks and adjust your deployment strategy accordingly.

DataObjects in schema | Build time (ms) | Memory use (MB)
-- | -- | --
5 | 290 | 26
10 | 310 | 26
40 | 1060 | 38
100 | 2160 | 58
250 | 5070 | 114
500 | 11,540 | 208

### Gotchas

#### Permissions of the `.graphql-generated/` and `public/_graphql/` folders {#gotchas-permissions}

The process that is generating these folders must have write permissions to create the folder and to update existing files. If different users are used to generate the folders, then you must make sure that each user retains write access on them.

For example, if you manually run a `dev/build` under a foobar user, the folders will be owned by foobar. If your web server is running under the www-data user and you try to call `dev/graphql/build` in your browser, you might get an error if www-data doesn’t have write access.

### Further reading

[CHILDREN]
