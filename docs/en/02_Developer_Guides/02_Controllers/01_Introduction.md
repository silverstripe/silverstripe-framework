title: Introduction to a Controller
summary: A brief look at the definition of a Controller, creating actions and how to respond to requests.

# Introduction to Controllers

The following example is for a simple [api:Controller] class. When building off the SilverStripe Framework you will
subclass the base `Controller` class.

**mysite/code/controllers/TeamController.php**

	:::php
	<?php
	
	class TeamController extends Controller {
			
		private static $allowed_actions = array(
			'players',
			'index'
		);
		
		public function index(SS_HTTPRequest $request) {
			// ..
		}

		public function players(SS_HTTPRequest $request) {
			print_r($request->allParams());
		}
	}

## Routing

We need to define the URL that this controller can be accessed on. In our case, the `TeamsController` should be visible 
at http://yoursite.com/teams/ and the `players` custom action is at http://yoursite.com/team/players/.

<div class="info" markdown="1">
If you're using the `cms` module with and dealing with `Page` objects then for your custom `Page Type` controllers you 
would extend `ContentController` or `Page_Controller`. You don't need to define the routes value as the `cms` handles 
routing.
</div>

<div class="alert" markdown="1">
Make sure that after you have modified the `routes.yml` file, that you clear your SilverStripe caches using `flush=1`.
</div>

**mysite/_config/routes.yml**

	:::yml
	---
	Name: mysiteroutes
	After: framework/routes#coreroutes
	---
	Director:
	  rules:
	    'teams//$Action/$ID/$Name': 'TeamController'


For more information about creating custom routes, see the [Routing](routing) documentation.

## Actions

Controllers respond by default to an `index` method. You don't need to define this method (as it's assumed) but you
can override the `index()` response to provide custom data back to the [Template and Views](../templates). 

<div class="notice" markdown="1">
It is standard in SilverStripe for your controller actions to be `lowercasewithnospaces`
</div>

Action methods can return one of four main things:

* an array. In this case the values in the array are available in the templates and the controller completes as usual by returning a [api:SS_HTTPResponse] with the body set to the current template.
* `HTML`. SilverStripe will wrap the `HTML` into a `SS_HTTPResponse` and set the status code to 200.
* an [api:SS_HTTPResponse] containing a manually defined `status code` and `body`.
* an [api:SS_HTTPResponse_Exception]. A special type of response which indicates a error. By returning the exception, the execution pipeline can adapt and display any error handlers.

**mysite/code/controllers/TeamController.php**

	:::php
	/**
	 * Return some additional data to the current response that is waiting to go out, this makes $Title set to 
	 * 'MyTeamName' and continues on with generating the response.
	 */
	public function index(SS_HTTPRequest $request) {
		return array(
			'Title' => 'My Team Name'
		);
	}

	/**
	 * We can manually create a response and return that to ignore any previous data.
	 */
	public function someaction(SS_HTTPRequest $request) {
		$this->response = new SS_HTTPResponse();
		$this->response->setStatusCode(400);
		$this->response->setBody('invalid');

		return $this->response;
	}

	/**
	 * Or, we can modify the response that is waiting to go out.
	 */
	public function anotheraction(SS_HTTPRequest $request) {
		$this->response->setStatusCode(400);

		return $this->response;
	}

	/**
	 * We can render HTML and leave SilverStripe to set the response code and body.
	 */
	public function htmlaction() {
		return $this->customize(new ArrayData(array(
			'Title' => 'HTML Action'
		)))->renderWith('MyCustomTemplate');
	}

	/**
	 * We can send stuff to the browser which isn't HTML
	 */
	public function ajaxaction() {
		$this->response->setBody(json_encode(array(
			'json' => true
		)));

		$this->response->addHeader("Content-type", "application/json");

		return $this->response.
	}

For more information on how a URL gets mapped to an action see the [Routing](routing) documentation.

## Security

See the [Access Controller](access_control) documentation.

## Templates

Controllers are automatically rendered with a template that makes their name. Our `TeamsController` would be rendered
with a `TeamsController.ss` template. Individual actions are rendered in `TeamsController_{actionname}.ss`. 

If a template of that name does not exist, then SilverStripe will fall back to the `TeamsController.ss` then to 
`Controller.ss`.

Controller actions can use `renderWith` to override this template selection process as in the previous example with 
`htmlaction`. `MyCustomTemplate.ss` would be used rather than `TeamsController`.

For more information about templates, inheritance and how to rendering into views, See the 
[Templates and Views](templates) documentation.

## Link

Each controller should define a `Link()` method. This should be used to avoid hard coding your routing in views etc.

**mysite/code/controllers/TeamController.php**

	:::php
    	public function Link($action = null) {
		return Controller::join_links('teams', $action);
	}

<div class="info" markdown="1">
The [api:Controller::join_links] is optional, but makes `Link()` more flexible by allowing an `$action` argument, and concatenates the path segments with slashes. The action should map to a method on your controller.
</div>

## Related Documentation

* [Execution Pipeline](../execution_pipeline)
* [Templates and Views](../templates)

## API Documentation

* [api:Controller]
* [api:Director]

