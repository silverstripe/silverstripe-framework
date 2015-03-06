title: Routing
summary: A more in depth look at how to map requests to particular controllers and actions.

# Routing

Routing is the process of mapping URL's to [api:Controllers] and actions. In the introduction we defined a new custom route
for our `TeamsController` mapping any `teams` URL to our `TeamsController`

<div class="info" markdown="1">
If you're using the `cms` module with and dealing with `Page` objects then for your custom `Page Type` controllers you 
would extend `ContentController` or `Page_Controller`. You don't need to define the routes value as the `cms` handles 
routing.
</div>

These routes by standard, go into a `routes.yml` file in your applications `_config` folder alongside your other 
[Configuration](../configuration) information.

**mysite/_config/routes.yml**

	:::yml
	---
	Name: mysiteroutes
	After: framework/routes#coreroutes
	---
	Director:
	  rules:
	    'teams//$Action/$ID/$Name': 'TeamController'
	    'player/': 'PlayerController'
	    '': 'HomeController'

<div class="notice" markdown="1">
To understand the syntax for the `routes.yml` file better, read the [Configuration](../configuration) documentation.
</div>

## Parameters

	:::yml
	'teams//$Action/$ID/$Name': 'TeamController'

This route has defined that any URL beginning with `team` should create, and be handled by a `TeamController` instance.

It also contains 3 `parameters` or `params` for short. `$Action`, `$ID` and `$Name`. These variables are placeholders 
which will be filled when the user makes their request. Request parameters are available on the `SS_HTTPRequest` object 
and able to be pulled out from a controller using `$this->request->param($name)`.

<div class="info" markdown="1">
All Controllers have access to `$this->request` for the request object and `$this->response` for the response. 
</div>

Here is what those parameters would look like for certain requests

	:::php
	// GET /teams/

	print_r($this->request->params());

	// Array
	// (
	//   [Action] => null
	//   [ID] => null
	//   [Name] => null
	// )

	// GET /teams/players/

	print_r($this->request->params());

	// Array
	// (
	//   [Action] => 'players'
	//   [ID] => null
	//   [Name] => null
	// )

	// GET /teams/players/1

	print_r($this->request->params());

	// Array
	// (
	//   [Action] => 'players'
	//   [ID] => 1
	//   [Name] => null
	// )

You can also fetch one parameter at a time.

	:::php

	// GET /teams/players/1/

	echo $this->request->param('ID');
	// returns '1'


## URL Patterns

The `[api:RequestHandler]` class will parse all rules you specify against the following patterns. The most specific rule
will be the one followed for the response.

<div class="alert">
A rule must always start with alphabetical ([A-Za-z]) characters or a $Variable declaration
</div>

 | Pattern     | Description | 
 | ----------- | --------------- | 
 | `$`         | **Param Variable** - Starts the name of a paramater variable, it is optional to match this unless ! is used | 
 | `!`         | **Require Variable** - Placing this after a parameter variable requires data to be present for the rule to match | 
 | `//`        | **Shift Point** - Declares that only variables denoted with a $ are parsed into the $params AFTER this point in the regex | 

	:::yml
	'teams/$Action/$ID/$OtherID': 'TeamController' 

	# /teams/
	# /teams/players/
	# /teams/

Standard URL handler syntax. For any URL that contains 'team' this rule will match and hand over execution to the 
matching controller. The `TeamsController` is passed an optional action, id and other id parameters to do any more
decision making.

	:::yml
	'teams/$Action!/$ID!/': 'TeamController'

This does the same matching as the previous example, any URL starting with `teams` will look at this rule **but** both
`$Action` and `$ID` are required. Any requests to `team/` will result in a `404` error rather than being handed off to
the `TeamController`.

	:::yml
	`admin/help//$Action/$ID`: 'AdminHelp'

Match an url starting with `/admin/help/`, but don't include `/help/` as part of the action (the shift point is set to 
start parsing variables and the appropriate controller action AFTER the `//`).


## URL Handlers

In the above example the URLs were configured using the [api:Director] rules in the **routes.yml** file. Alternatively 
you can specify these in your Controller class via the **$url_handlers** static array. This array is processed by the 
[api:RequestHandler] at runtime once the `Controller` has been matched.

This is useful when you want to provide custom actions for the mapping of `teams/*`. Say for instance we want to respond
`coaches`, and `staff` to the one controller action `payroll`.

**mysite/code/controllers/TeamController.php**

	:::php
	<?php

	class TeamController extends Controller {

		private static $allowed_actions = array(
			'payroll'
		);

	    private static $url_handlers = array(
			'staff/$ID/$Name' => 'payroll'
			'coach/$ID/$Name' => 'payroll'
	    );

The syntax for the `$url_handlers` array users the same pattern matches as the `YAML` configuration rules.

## Links

* [api:Controller] API documentation
* [api:Director] API documentation
* [Example routes: framework](https://github.com/silverstripe/silverstripe-framework/blob/master/_config/routes.yml)
* [Example routes: cms](https://github.com/silverstripe/silverstripe-cms/blob/master/_config/routes.yml)
