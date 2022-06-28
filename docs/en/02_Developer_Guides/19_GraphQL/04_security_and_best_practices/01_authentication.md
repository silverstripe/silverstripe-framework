---
title: Authentication
summary: Ensure your GraphQL api is only accessible to provisioned users
icon: user-lock
---

# Security & Best Practices

[CHILDREN asList]

[info]
You are viewing docs for silverstripe/graphql 4.x.
If you are using 3.x, documentation can be found
[in the github repository](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/info]

## Authentication

Some Silverstripe CMS resources have permission requirements to perform CRUD operations
on, for example the `Member` object in the previous examples.

If you are logged into the CMS and performing a request from the same session then
the same `Member` session is used to authenticate GraphQL requests, however if you
are performing requests from an anonymous/external application you may need to
authenticate before you can complete a request.

[notice]
Please note that when implementing GraphQL resources it is the developer's
responsibility to ensure that permission checks are implemented wherever
resources are accessed.
[/notice]

### Default authentication

The [`MemberAuthenticator`](api:SilverStripe\GraphQL\Auth\MemberAuthenticator) class is
configured as the default option for authentication,
and will attempt to use the current CMS `Member` session for authentication context.

**If you are using the default session-based authentication, please be sure that you have
not disabled the [CSRF Middleware](csrf_protection). (It is enabled by default).**

### HTTP basic authentication

Silverstripe CMS has built-in support for [HTTP basic authentication](https://en.wikipedia.org/wiki/Basic_access_authentication).

There is a [`BasicAuthAuthenticator`](api:SilverStripe\GraphQL\Auth\BasicAuthAuthenticator)
which can be configured for GraphQL that
will only activate when required. It is kept separate from the Silverstripe CMS
authenticator because GraphQL needs to use the successfully authenticated member
for CMS permission filtering, whereas the global [`BasicAuth`](api:SilverStripe\Security\BasicAuth) does not log the
member in or use it for model security. Note that basic auth will bypass MFA authentication
so if MFA is enabled it is not recommended that you also use basic auth for GraphQL.

When using HTTP basic authentication, you can feel free to remove the [CSRF Middleware](csrf_protection),
as it just adds unnecessary overhead to the request.

#### In GraphiQL

If you want to add basic authentication support to your GraphQL requests you can
do so by adding a custom `Authorization` HTTP header to your GraphiQL requests.

If you are using the [GraphiQL macOS app](https://github.com/skevy/graphiql-app)
this can be done from "Edit HTTP Headers".

The `/dev/graphql/ide` endpoint in [silverstripe/graphql-devtools](https://github.com/silverstripe/silverstripe-graphql-devtools)
does not support custom HTTP headers at this point.

Your custom header should follow the following format:

```
# Key: Value
Authorization: Basic aGVsbG86d29ybGQ=
```

`Basic` is followed by a [base64 encoded](https://en.wikipedia.org/wiki/Base64)
combination of your username, colon and password. The above example is `hello:world`.

**Note:** Authentication credentials are transferred in plain text when using HTTP
basic authentication. We strongly recommend using TLS for non-development use.

Example:

```shell
php -r 'echo base64_encode("hello:world");'
# aGVsbG86d29ybGQ=
```

### Defining your own authenticators

You will need to define the class under `SilverStripe\GraphQL\Auth\Handlers.authenticators`.
You can optionally provide a `priority` number if you want to control which
authenticator is used when multiple are defined (higher priority returns first).

Authenticator classes need to implement the [`AuthenticatorInterface`](api:SilverStripe\GraphQL\Auth\AuthenticatorInterface)
interface, which requires you to define an `authenticate()` method to return a `Member` or `false`, and
and an `isApplicable()` method which tells the [`Handler`](api:SilverStripe\GraphQL\Auth\Handler) whether
or not this authentication method
is applicable in the current request context (provided as an argument).

Here's an example for implementing HTTP basic authentication:

[notice]
Note that basic authentication for GraphQL will bypass Multi-Factor Authentication (MFA) if that's enabled. Using basic authentication for GraphQL is considered insecure if you are using MFA.
[/notice]

```yaml
SilverStripe\GraphQL\Auth\Handler:
  authenticators:
    - class: SilverStripe\GraphQL\Auth\BasicAuthAuthenticator
      priority: 10
```

### Further reading

[CHILDREN]
