---
title: Cross-Origin Resource Sharing (CORS)
summary: Ensure that requests to your API come from a whitelist of origins
---

# Cross-Origin Resource Sharing (CORS)

By default [CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) is disabled in the GraphQL Server. This can be easily enabled via YAML:

```yaml
SilverStripe\GraphQL\Controller:
  cors:
    Enabled: true
```

Once you have enabled CORS you can then control four new headers in the HTTP Response.

1. **Access-Control-Allow-Origin.**

 This lets you define which domains are allowed to access your GraphQL API. There are
 4 options:

 * **Blank**:
 Deny all domains (except localhost)

 ```yaml
 Allow-Origin:
 ```

 * **'\*'**:
 Allow requests from all domains.

 ```yaml
 Allow-Origin: '*'
 ```

 * **Single Domain**:

 Allow requests from one specific external domain.

 ```yaml
 Allow-Origin: 'my.domain.com'
 ```

 * **Multiple Domains**:

 Allow requests from multiple specified external domains.

 ```yaml
 Allow-Origin:
   - 'my.domain.com'
   - 'your.domain.org'
 ```

2. **Access-Control-Allow-Headers.**

 Access-Control-Allow-Headers is part of a CORS 'pre-flight' request to identify
 what headers a CORS request may include.  By default, the GraphQL server enables the
 `Authorization` and `Content-Type` headers. You can add extra allowed headers that
 your GraphQL may need by adding them here. For example:

 ```yaml
 Allow-Headers: 'Authorization, Content-Type, Content-Language'
 ```

 **Note** If you add extra headers to your GraphQL server, you will need to write a
 custom resolver function to handle the response.

3. **Access-Control-Allow-Methods.**

 This defines the HTTP request methods that the GraphQL server will handle.  By
 default this is set to `GET, PUT, OPTIONS`. Again, if you need to support extra
 methods you will need to write a custom resolver to handle this. For example:

 ```yaml
 Allow-Methods: 'GET, PUT, DELETE, OPTIONS'
 ```

4. **Access-Control-Max-Age.**

 Sets the maximum cache age (in seconds) for the CORS pre-flight response. When
 the client makes a successful OPTIONS request, it will cache the response
 headers for this specified duration. If the time expires or the required
 headers are different for a new CORS request, the client will send a new OPTIONS
 pre-flight request to ensure it still has authorisation to make the request.
 This is set to 86400 seconds (24 hours) by default but can be changed in YAML as
 in this example:

 ```yaml
 Max-Age: 600
 ```

5. **Access-Control-Allow-Credentials.**

 When a request's credentials mode (Request.credentials) is "include", browsers
 will only expose the response to frontend JavaScript code if the
 Access-Control-Allow-Credentials value is true.

 The Access-Control-Allow-Credentials header works in conjunction with the
 XMLHttpRequest.withCredentials property or with the credentials option in the
 Request() constructor of the Fetch API. For a CORS request with credentials,
 in order for browsers to expose the response to frontend JavaScript code, both
 the server (using the Access-Control-Allow-Credentials header) and the client
 (by setting the credentials mode for the XHR, Fetch, or Ajax request) must
 indicate that they’re opting in to including credentials.

 This is set to empty by default but can be changed in YAML as in this example:

 ```yaml
 Allow-Credentials: 'true'
 ```

## Apply a CORS config to all GraphQL endpoints

```yaml
## CORS Config
SilverStripe\GraphQL\Controller:
  cors:
    Enabled: true
    Allow-Origin: 'silverstripe.org'
    Allow-Headers: 'Authorization, Content-Type'
    Allow-Methods:  'GET, POST, OPTIONS'
    Allow-Credentials: 'true'
    Max-Age:  600  # 600 seconds = 10 minutes.
```

## Apply a CORS config to a single GraphQL endpoint

```yaml
## CORS Config
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\Controller.default
    properties:
      corsConfig:
        Enabled: false
```

