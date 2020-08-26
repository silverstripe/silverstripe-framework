---
title: CSRF protection
summary: Protect destructive actions from cross-site request forgery
---

# CSRF tokens (required for mutations)

Even if your graphql endpoints are behind authentication, it is still possible for unauthorised
users to access that endpoint through a [CSRF exploitation](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)). This involves
forcing an already authenticated user to access an HTTP resource unknowingly (e.g. through a fake image), thereby hijacking the user's
session.

In the absence of a token-based authentication system, like OAuth, the best countermeasure to this
is the use of a CSRF token for any requests that destroy or mutate data.

By default, this module comes with a `CSRFMiddleware` implementation that forces all mutations to check
for the presence of a CSRF token in the request. That token must be applied to a header named` X-CSRF-TOKEN`.

In SilverStripe, CSRF tokens are most commonly stored in the session as `SecurityID`, or accessed through
the `SecurityToken` API, using `SecurityToken::inst()->getValue()`.

Queries do not require CSRF tokens.

## Disabling CSRF protection (for token-based authentication only)

If you are using HTTP basic authentication or a token-based system like OAuth or [JWT](https://github.com/Firesphere/silverstripe-graphql-jwt),
you will want to remove the CSRF protection, as it just adds unnecessary overhead. You can do this by setting
the middleware to `false`.


```yaml
  SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface.default:
    class: SilverStripe\GraphQL\QueryHandler\QueryHandler
    properties:
      Middlewares:
        csrf: false
```
