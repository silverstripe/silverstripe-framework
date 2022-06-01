---
title: Upgrade to GraphQL v4
summary: Upgrade your Silverstripe CMS project to use graphQL version 4
---

# Upgrading to GraphQL v4

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql).
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

Silverstripe CMS Recipe 4.11 defaults to installing silverstripe/graphql v4. Previous releases installed version 3. 

## What does silverstripe/graphql do and why are you changing this?

GraphQL is a query language for APIs. It was initially designed by Facebook but it is now used widely across the internet by all sorts of organisations including GitHub, AirBnB, Lyft, PayPal, Shopify and Silverstripe CMS … to name just a few.

`silverstripe/graphql` is an implementation of GraphQL specific to Silverstripe CMS. It is used to power some aspects of the CMS UI. It can also be used by developers to create APIs that other web services can use to read or update data in your CMS sites. This opens a lot of use cases like using Silverstripe CMS as “headless” CMS.

Until the 4.10 release, Silverstripe CMS would default to using silverstripe/graphql v3. While silverstripe/graphql v3 was sufficient to support the basic CMS use cases it was being used for, it was not performant enough to build more complex applications.

`silverstripe/graphql` v4 is a complete rewrite and provides substantial performance improvements.

`silverstripe/graphql` v4 provides developers a first class tool for building APIs and allowing third party services to integrate with their Silverstripe CMS websites.

## That sounds risky, do I absolutely have to use version 4?

Silverstripe CMS has been shipping with dual support for `silverstripe/graphql` v3 and v4 since the 4.8 release. Until now `silverstripe/graphql` v4 had been in alpha and you had to explicitly opt-in to get it. At Silverstripe, we are already using `silverstripe/graphql` v4 in production on several projects.

All the supported Silverstripe CMS modules that use `silverstripe/graphql` have dual-support for version 3 and version 4. If you wish to stay on `silverstripe/graphql` v3, you can do so and it will not block you from upgrading to Silverstripe CMS 4.11.

We will maintain support for `silverstripe/graphql` v3 in Silverstripe CMS 4 for the foreseeable future. Any change to this policy will be announced at least 6 months in advance. 

### Opting out of `silverstripe/graphql` v4 and sticking to version 3

If your project composer.json file already explicitly requires silverstripe/graphql, you don’t need to do anything.

If your project uses silverstripe/recipe-cms, composer will try to install `silverstripe/graphq` v4.0 when you upgrade to the 4.11 release. To stay on silverstripe/graphql:^3, you’ll need to explicitly require `silverstripe/graphql` v3.8.

```
composer require silverstripe/graphql:^3
```

To validate which version of `silverstripe/graphql` your project is using, run this composer command:
`composer show silverstripe/graphql`

To view which dependencies require `silverstripe/graphql`, run this composer command:
`composer why silverstripe/graphql`

## How do I get this thing working?

Part of the reason why `silverstripe/graphql` v4 is so much faster than v3 is that it has a “code generation” step. Silverstripe CMS will generate PHP classes for your GraphQL schemas and stores them in a `.graphql-generated` folder in the root of your project.

### What triggers a GraphQL code build? 

- This folder will automatically be generated when you run a `dev/build` on your project. 
- You can also run the `dev/graphql/build` command to explicitly build your GraphQL schemas. 
- Silverstripe CMS will attempt to generate your schema on the first graphql request if it wasn’t already generated.

### Deploying your `.graphql-generated` folder 

One way or another, you must get this `.graphql-generated` folder into your production environment for Silverstripe CMS to work as expected. There are many ways to do so.

#### Commit `.graphql-generated`

A simplistic approach is to build the `.graphql-generated` in your local development environment and add it to your source control system.

This approach has the advantage of being very simple, however it will pollute your commits with massive diff for the generated code.

#### Run a dev/build on each deployment
Many projects will automatically run a dev/build whenever they deploy a site to their production environment. If that’s your case, then you can just let this process run normally and generate the `.graphql-generated` folder for you. This will allow you to add `.graphql-generated` to your `.gitignore` file and avoid tracking the folder in your source control system.

Be aware that for this approach to work, the process executing the `dev/build` must have write access to create the `.graphql-generated` folder and a `dev/build` or `dev/graphql/build` must be executed on each server hosting your site after each deployment.

For example, if your site is hosted in an environment with multiple servers and you only run a dev/build on one single server, then the other servers won’t have a `.graphql-generated` folder. This might also impact you if your project is hosted in an environment configured to auto-scale with demand.

Alternatively, you could configure a process to sync your `.graphql-generated` folder across all your servers. In that case you only need to run `dev/build` or `dev/graphql/build` on the server with the original folder.

#### Rely on “on-demand” schema generation on the first GraphQL request
When the first GraphQL schema request occurs, Silverstripe CMS will attempt to build the `.graphql-generated` folder “on-demand” if it’s not already present on the server. This will impose a one-time hit on the first graphQL request. If your project defines multiple schemas, only the schema that is being accessed will be generated.

For most common use cases, this process is relatively fast. For example, the GraphQL schema that is used to power the CMS can be built in about a quarter of a second.

While benchmarking schema generation performance, we measured that a schema exposing 180 DataObjects with 1600 relations could be built on-demand in less than 6 seconds on a small AWS instance.

Our expectation is that on-demand schema generation will be appropriate for most projects with small or medium schemas.

#### Use a CI/CD pipeline to build your schema

Projects with more sophisticated requirements or bigger schemas exposing more than 100 `DataObject` classes may want to consider using a continuous-integration/continuous-deployment (CI/CD) pipeline to build their GraphQL schema.

In this kind of setup, you would need to update your deployment script to run the `dev/graphql/build` command to build the `.graphql-generated` folder.

## Performance considerations when building graphQL schema
The main driver in the resources it takes to build a GraphQL schema is the number DataObjects and the number of exposed relations in that schema. In most cases, not all DataObject in your database will be included in your schema. DataObjects not included in your schema will not impact the time or memory needed to build it.

Silverstripe CMS defines an “admin” schema it uses for its own purpose. This schema is relatively small and has a negligible performance impact.

As an indication, we ran some benchmarks on a t3.micro AWS instance. Those numbers may not be representative of the performance in your own environment. If you intend to build large graphQL schemas, you should take the time to run your own benchmarks and adjust your deployment strategy accordingly.

DataObjects in schema | Build time (ms) | Memory use (MB)
-- | -- | --
5 | 290 | 26
10 | 310 | 26
40 | 1060 | 38
100 | 2160 | 58
250 | 5070 | 114
500 | 11,540 | 208

## Gotchas

### Permissions of the `.graphql-generated` folder
The process that is generating the `.graphql-generated` folder must have write permissions to create the folder and to update existing files. If different users are used to generate the `.graphql-generated` folder, then you must make sure that each user retains write access on the folder.

For example, if you manually run a `dev/build` under a foobar user, `.graphql-generated` folder will be owned by foobar. If your web server is running under the www-data user and you try to call `dev/graphql/build` in your browser, you might get an error if www-data doesn’t have write access.

### Tracking or ignoring the `.graphql-generated` folder

Existing projects will not have an entry in their `.gitignore` file for `.graphql-generated`. If you do not want to track the `.graphql-generated` folder, you’ll have to manually add this entry to your `.gitignore`.

The `.gitignore` file in `silverstripe/installer` 4.11 has been updated to ignore the `.graphql-generated` folder. If you start a new project from `silverstripe/installer` 4.11.0 and want to track the `.graphql-generated` folder, you’ll have to update your `.gitignore` file.
