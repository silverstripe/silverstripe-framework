---
title: Activating the default server
summary: Open up the default server that comes pre-configured with the module
icon: rocket
---

# Getting started

[CHILDREN asList]

[info]
You are viewing docs for silverstripe/graphql 4.x.
If you are using 3.x, documentation can be found
[in the github repository](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/info]

## Activating the default GraphQL server

GraphQL is used through a single route, typically `/graphql`. You need
to define *types* and *queries* to expose your data via this endpoint. While this recommended
route is left open for you to configure on your own, the modules contained in the [CMS recipe](https://github.com/silverstripe/recipe-cms),
(e.g. `silverstripe/asset-admin`) run off a separate GraphQL server with its own endpoint
(`admin/graphql`) with its own permissions and schema.

These separate endpoints have their own identifiers. `default` refers to the GraphQL server
in the user space (e.g. `/graphql`) - i.e. your custom schema, while `admin` refers to the
GraphQL server used by CMS modules (`admin/graphql`). You can also [set up a new schema server](#setting-up-a-custom-graphql-server)
if you wish.

[info]
The word "server" here refers to a route with its own isolated GraphQL schema. It does
not refer to a web server.
[/info]

By default, `silverstripe/graphql` does not route any GraphQL servers. To activate the default,
public-facing GraphQL server that ships with the module, just add a rule to [`Director`](api:SilverStripe\Control\Director).

```yaml
SilverStripe\Control\Director:
  rules:
    'graphql': '%$SilverStripe\GraphQL\Controller.default'
```

## Setting up a custom GraphQL server

In addition to the default `/graphql` endpoint provided by this module by default,
along with the `admin/graphql` endpoint provided by the CMS modules (if they're installed),
you may want to set up another GraphQL server running on the same installation of Silverstripe CMS.

Let's set up a new controller to handle the requests.

```yaml
SilverStripe\Core\Injector\Injector:
  # ...
  SilverStripe\GraphQL\Controller.myNewSchema:
    class: SilverStripe\GraphQL\Controller
    constructor:
      schemaKey: myNewSchema
```

We'll now need to route the controller.

```yaml
SilverStripe\Control\Director:
  rules:
    'my-graphql': '%$SilverStripe\GraphQL\Controller.myNewSchema'
```

Now, once you have [configured](configuring_your_schema) and [built](building_the_schema) your schema, you
can access it at `/my-graphql`.

### Further reading

[CHILDREN]
