---
title: Getting started
summary: Open up your first GraphQL server and build your schema
icon: rocket
---

# Getting started

This section of the documentation will give you an overview of how to get a simple GraphQL API
up and running with some dataobject content.

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
