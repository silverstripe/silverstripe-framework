---
title: Routing
summary: A more in depth look at how to map requests to particular controllers and actions.
---

# Routing

Routing is the process of mapping URL's to [Controller](api:SilverStripe\Control\Controller) and actions. In the introduction we defined a new custom route
for our `TeamController` mapping any `teams` URL to our `TeamController`

[info]
If you're using the `cms` module with and dealing with `Page` objects then for your custom `Page Type` controllers you 
would extend `ContentController` or `PageController`. You don't need to define the routes value as the `cms` handles 
routing.
[/info]

These routes by standard, go into a `routes.yml` file in your applications `_config` folder alongside your other 
[Configuration](../configuration) information.

**app/_config/routes.yml**

```yml
---
Name: approutes
After:
  - '#rootroutes'
  - '#coreroutes'
---
SilverStripe\Control\Director:
  rules:
    'teams//$Action/$ID/$Name': 'TeamController'
    'player/': 'PlayerController'
    '': 'HomeController'
```

[notice]
To understand the syntax for the `routes.yml` file better, read the [Configuration](../configuration) documentation.
[/notice]

## Parameters

```yml
'teams//$Action/$ID/$Name': 'TeamController'
```

This route has defined that any URL beginning with `team` should create, and be handled by a `TeamController` instance.

It also contains 3 `parameters` or `params` for short. `$Action`, `$ID` and `$Name`. These variables are placeholders 
which will be filled when the user makes their request. Request parameters are available on the `HTTPRequest` object 
and able to be pulled out from a controller using `$this->getRequest()->param($name)`.

[info]
All Controllers have access to `$this->getRequest()` for the request object and `$this->getResponse()` for the response.
[/info]

Here is what those parameters would look like for certain requests

```php
// GET /teams/

print_r($this->getRequest()->params());

// Array
// (
//   [Action] => null
//   [ID] => null
//   [Name] => null
// )

// GET /teams/players/

print_r($this->getRequest()->params());

// Array
// (
//   [Action] => 'players'
//   [ID] => null
//   [Name] => null
// )

// GET /teams/players/1

print_r($this->getRequest()->params());

// Array
// (
//   [Action] => 'players'
//   [ID] => 1
//   [Name] => null
// )

```

You can also fetch one parameter at a time.

```php
// GET /teams/players/1/

echo $this->getRequest()->param('ID');
// returns '1'
```

## URL Patterns

The [RequestHandler](api:SilverStripe\Control\RequestHandler) class will parse all rules you specify against the following patterns. The most specific rule
will be the one followed for the response.

[alert]
A rule must always start with alphabetical ([A-Za-z]) characters or a $Variable declaration
[/alert]

 | Pattern     | Description | 
 | ----------- | --------------- | 
 | `$`         | **Param Variable** - Starts the name of a paramater variable, it is optional to match this unless ! is used | 
 | `!`         | **Require Variable** - Placing this after a parameter variable requires data to be present for the rule to match | 
 | `//`        | **Shift Point** - Declares that only variables denoted with a $ are parsed into the $params AFTER this point in the regex | 

```yml
'teams/$Action/$ID/$OtherID': 'TeamController' 

# /teams/
# /teams/players/
# /teams/
```

Standard URL handler syntax. For any URL that contains 'team' this rule will match and hand over execution to the 
matching controller. The `TeamsController` is passed an optional action, id and other id parameters to do any more
decision making.

```yml
'teams/$Action!/$ID!/': 'TeamController'
```

This does the same matching as the previous example, any URL starting with `teams` will look at this rule **but** both
`$Action` and `$ID` are required. Any requests to `team/` will result in a `404` error rather than being handed off to
the `TeamController`.

```yml
'admin/help//$Action/$ID: 'AdminHelp'
```

Match an url starting with `/admin/help/`, but don't include `/help/` as part of the action (the shift point is set to 
start parsing variables and the appropriate controller action AFTER the `//`).

### Wildcard URL Patterns

As of SilverStripe 4.6 there are two wildcard patterns that can be used. `$@` and `$*`. These parameters can only be used
at the end of a URL pattern, any further rules are ignored.

Inspired by bash variadic variable syntax there are two ways to capture all URL parameters without having to explicitly
specify them in the URL rule.

Using `$@` will split the URL into numbered parameters (`$1`, `$2`, ..., `$n`). For example:

```php
<?php
class StaffController extends \SilverStripe\Control\Controller
{
    private static $url_handlers = [
        'staff/$@' => 'index',
    ];

    public function index($request)
    {
        // GET /staff/managers/bob
        $request->latestParam('$1'); // managers
        $request->latestParam('$2'); // bob
    }
}
```

Alternatively, if access to the parameters is not required in this way then it is possible to use `$*` to match all
URL parameters but not collect them in the same way:

```php
<?php
class StaffController extends \SilverStripe\Control\Controller
{
    private static $url_handlers = [
        'staff/$*' => 'index',
    ];

    public function index($request)
    {
        // GET /staff/managers/bob
        $request->remaining(); // managers/bob
    }
}
```

## URL Handlers

[alert]
You **must** use the **$url_handlers** static array described here if your URL
pattern does not use the Controller class's default pattern of
`$Action//$ID/$OtherID`. If you fail to do so, and your pattern has more than
2 parameters, your controller will throw the error "I can't handle sub-URLs of
a *class name* object" with HTTP status 404.
[/alert]

In the above example the URLs were configured using the [Director](api:SilverStripe\Control\Director) rules in the **routes.yml** file. Alternatively 
you can specify these in your Controller class via the **$url_handlers** static array. This array is processed by the 
[RequestHandler](api:SilverStripe\Control\RequestHandler) at runtime once the `Controller` has been matched.

This is useful when you want to provide custom actions for the mapping of `teams/*`. Say for instance we want to respond
`coaches`, and `staff` to the one controller action `payroll`.

**app/code/controllers/TeamController.php**

```php
use SilverStripe\Control\Controller;

class TeamController extends Controller
{
    private static $allowed_actions = [
        'payroll'
    ];

    private static $url_handlers = [
        'staff/$ID/$Name' => 'payroll',
        'coach/$ID/$Name' => 'payroll'
    ];

```

The syntax for the `$url_handlers` array users the same pattern matches as the `YAML` configuration rules.

Now let’s consider a more complex example from a real project, where using
**$url_handlers** is mandatory. In this example, the URLs are of the form
`http://example.org/feed/go/`, followed by 5 parameters. The PHP controller
class specifies the URL pattern in `$url_handlers`. Notice that it defines 5
parameters.


```php
use SilverStripe\CMS\Controllers\ContentController;

class FeedController extends ContentController
{
    private static $allowed_actions = ['go'];
    private static $url_handlers = [
        'go/$UserName/$AuthToken/$Timestamp/$OutputType/$DeleteMode' => 'go'
    ];

    public function go()
    {
        $this->validateUser(
            $this->getRequest()->param('UserName'),
            $this->getRequest()->param('AuthToken')
        );
        /* more processing goes here */
    }
}
```

The YAML rule, in contrast, is simple. It needs to provide only enough information for the framework to choose the desired controller.

```yml
Director:
  rules:
    'feed': 'FeedController'
```

## Root URL Handlers

In some cases, the Director rule covers the entire URL you intend to match, and you simply want the controller to respond to a 'root' request. This request will automatically direct to an `index()` method if it exists on the controller, but you can also set a custom method to use in `$url_handlers` with the `'/'` key:

```php
use SilverStripe\Control\Controller;

class BreadAPIController extends Controller
{
    private static $allowed_actions = [
        'getBreads',
        'createBread',
    ];

    private static $url_handlers = [
        'GET /' => 'getBreads',
        'POST /' => 'createBread',
    ];
```

<div class="alert" markdown="1">
In SilverStripe Framework versions prior to 4.6, an empty key (`''`) must be used in place of the `'/'` key. When specifying an HTTP method, the empty string must be separated from the method (e.g. `'GET '`). The empty key and slash key are also equivalent in Director rules.
</div>

## Related Lessons
* [Creating filtered views](https://www.silverstripe.org/learn/lessons/v4/creating-filtered-views-1)
* [Controller actions / DataObjects as pages](https://www.silverstripe.org/learn/lessons/v4/controller-actions-dataobjects-as-pages-1)

## Links

* [Controller](api:SilverStripe\Control\Controller) API documentation
* [Director](api:SilverStripe\Control\Director) API documentation
* [Example routes: framework](https://github.com/silverstripe/silverstripe-framework/blob/master/_config/routes.yml)
* [Example routes: cms](https://github.com/silverstripe/silverstripe-cms/blob/master/_config/routes.yml)
