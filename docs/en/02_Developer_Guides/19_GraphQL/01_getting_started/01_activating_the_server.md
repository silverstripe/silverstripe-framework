---
title: Activating the default server
summary: Open up a the default server that comes pre-configured with the module
icon: rocket
---

# Activating the default GraphQL server

GraphQL is used through a single route, typically `/graphql`. You need
to define *types* and *queries* to expose your data via this endpoint. While this recommended
route is left open for you to configure on your own, the modules contained in the [CMS recipe](https://github.com/silverstripe/recipe-cms),
 (e.g. `asset-admin`) run off a separate GraphQL server with its own endpoint
 (`admin/graphql`) with its own permissions and schema.

These separate endpoints have their own identifiers. `default` refers to the GraphQL server
in the user space (e.g. `/graphql`) while `admin` refers to the GraphQL server used by CMS modules
(`admin/graphql`). You can also [set up a new schema](#setting-up-a-new-graphql-schema) if you wish.

By default, this module does not route any GraphQL servers. To activate the default,
public-facing GraphQL server that ships with the module, just add a rule to `Director`.

```yaml
SilverStripe\Control\Director:
  rules:
    'graphql': '%$SilverStripe\GraphQL\Controller.default'
```

# Setting up a custom GraphQL server

In addition to the default `/graphql` endpoint provided by this module by default,
along with the `admin/graphql` endpoint provided by the CMS modules (if they're installed),
you may want to set up another GraphQL server running on the same installation of SilverStripe.

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

Now, you're ready to [configure your schema](configuring_your_schema.md).

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    myNewSchema:
      # ...
```
