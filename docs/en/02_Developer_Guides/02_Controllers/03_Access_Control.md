title: Access Control
summary: Define allowed behavior and add permission based checks to your Controllers.

# Access Control

Within your controllers you should declare and restrict what people can see and do to ensure that users cannot run 
actions on the website they shouldn't be able to. 

## Allowed Actions

Any action you define on a controller must be defined in a `$allowed_actions` static array. This prevents users from
directly calling methods that they shouldn't.

	:::php
	<?php

	class MyController extends Controller {
		
		private static $allowed_actions = array(
			// someaction can be accessed by anyone, any time
			'someaction', 

			// So can otheraction
			'otheraction' => true, 
			
			// restrictedaction can only be people with ADMIN privilege
			'restrictedaction' => 'ADMIN', 

			// restricted to uses that have the 'CMS_ACCESS_CMSMain' access
			'cmsrestrictedaction' => 'CMS_ACCESS_CMSMain',
			
			// complexaction can only be accessed if $this->canComplexAction() returns true.
			'complexaction' '->canComplexAction' 

			// complexactioncheck can only be accessed if $this->canComplexAction("MyRestrictedAction", false, 42) is true.
			'complexactioncheck' => '->canComplexAction("MyRestrictedAction", false, 42)',
		);
	}

<div class="info">
If the permission check fails, SilverStripe will return a `403` Forbidden HTTP status.
</div>

An action named "index" is white listed by default, unless `allowed_actions` is defined as an empty array, or the action 
is specifically restricted.

	:::php
	<?php 

	class MyController extends Controller {

		public function index() {
			// allowed without an $allowed_action defined
		}
	}

`$allowed_actions` can be defined on `Extension` classes applying to the controller.

	:::php
	<?php

	class MyExtension extends Extension {

		private static $allowed_actions = array(
			'mycustomaction'
		);
	}

Only public methods can be made accessible.

	:::php
	<?php

	class MyController extends Controller {

		private static $allowed_actions = array(
			'secure',
			// secureaction won't work as it's private.
		);

		public function secure() {
			// ..
		}

		private function secureaction() {
			// ..
		}
	}

If a method on a parent class is overwritten, access control for it has to be redefined as well.
	
	:::php
	<?php

	class MyController extends Controller {

		private static $allowed_actions = array(
			'action',
		);

		public function action() {
			// ..
		}
	}

	class MyChildController extends MyController {

		private static $allowed_actions = array(
			'action', // required as we are redefining action
		);

		public function action() {

		}
	}

<div class="notice" markdown="1">
Access checks on parent classes need to be overwritten via the [Configuration API](../configuration).
</div>

## Forms

Form action methods should **not** be included in `$allowed_actions`. However, the form method **should** be included 
as an `allowed_action`.
	
	:::php
	<?php

	class MyController extends Controller {

		private static $allowed_actions = array(
			'ContactForm' // use the Form method, not the action
		);

		public function ContactForm() {
			return new Form(..);
		}

		public function doContactForm($data, $form) {
			// ..
		}
	}

## Action Level Checks

Each method responding to a URL can also implement custom permission checks, e.g. to handle responses conditionally on 
the passed request data.

	:::php
	<?php

	class MyController extends Controller {
		
		private static $allowed_actions = array(
			'myaction'
		);
		
		public function myaction($request) {
			if(!$request->getVar('apikey')) {
				return $this->httpError(403, 'No API key provided');
			} 
				
			return 'valid';
		}
	}

<div class="notice" markdown="1">
This is recommended as an addition for `$allowed_actions`, in order to handle more complex checks, rather than a 
replacement.
</div>

## Controller Level Checks

After checking for allowed_actions, each controller invokes its `init()` method, which is typically used to set up 
common state, If an `init()` method returns a `SS_HTTPResponse` with either a 3xx or 4xx HTTP status code, it'll abort 
execution. This behavior can be used to implement permission checks.

<div class="info" markdown="1">
`init` is called for any possible action on the controller and before any specific method such as `index`.
</div>
	:::php
	<?php

	class MyController extends Controller {
		
		private static $allowed_actions = array();
		
		public function init() {
			parent::init();

			if(!Permission::check('ADMIN')) {
				return $this->httpError(403);
			}
		}
	}

## Related Documentation

* [Security](../security)

## API Documentation

* [api:Controller]