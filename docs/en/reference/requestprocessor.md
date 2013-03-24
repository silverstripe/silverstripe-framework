Request Processor
================

The `[api:RequestProcessor]` class is used by the `[api:Director]` class to execute pre and
post-request filters before or after a request is processed.

You can use request filters to hook into the request process in order to execute an action before
a request is handled, or after a response has been created and before it is served to the user.
These can be used to hook into the request process to augment the response, or to interrupt the
request processing.

To execute a piece of code before the request processing begins, you should create a class which
implements the `[api:PreRequestFilter]` interface, and then register your class with the
`[api:RequestProcessor]` class using the config system:

```yaml
Injector:
  RequestProcessor:
    properties:
      filters:
        - '%$MyCustomRequestProcessor'
```

You can implement a post-request filter, you should create a class which implements the
`[api:PostRequestFilter]` interface, and register it the same way. See these interfaces for more
information about the methods you should implement.

If your pre-request or post-request filter return `false`, then the request processing will be
halted and an exception will be thrown. This should only be done in exception circumstances.
