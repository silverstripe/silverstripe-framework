---
title: Strict HTTP method checking
summary: Ensure requests are GET or POST
---

# Security & best practices

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## Strict HTTP Method Checking

According to GraphQL best practices, mutations should be done over `POST`, while queries have the option
to use either `GET` or `POST`. By default, this module enforces the `POST` request method for all mutations.

To disable that requirement, you can remove the [`HTTPMethodMiddleware`](api:SilverStripe\GraphQL\Middleware\HTTPMethodMiddleware)
from the [`QueryHandler`](api:SilverStripe\GraphQL\QueryHandler\QueryHandler).

```yaml
SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface.default:
  class: SilverStripe\GraphQL\QueryHandler\QueryHandler
  properties:
    Middlewares:
      httpMethod: false
```

### Further reading

[CHILDREN]
