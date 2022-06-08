---
title: Working with generic types
summary: Break away from the magic of `DataObject` models and build types and queries from scratch.
icon: clipboard
---

In this section of the documentation, we cover the fundamentals that are behind a lot of the magic that goes
into making `DataObject` types work. We'll create some types that are not based on DataObjects at all, and we'll
write some custom queries from the ground up.

This is useful for situations where your data doesn't come from a `DataObject`, or where you have very specific
requirements for your GraphQL API that don't easily map to the schema of your `DataObject` classes.

[info]
Just because we won't be using DataObjects in this example doesn't mean you can't do it - you can absolutely
declare `DataObject` classes as generic types. You would lose a lot of the benefits of the `DataObject` model
in doing so, but this lower level API may suit your needs for very specific use cases.
[/info]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

[CHILDREN]
