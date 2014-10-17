title: Request Filters
summary: Create objects for modifying request and response objects across controllers.

# Request Filters

[api:RequestFilter] is an interface that provides two key methods. `preRequest` and `postRequest`. These methods are 
executed before and after a request occurs to give developers a hook to modify any global state, add request tracking or
perform operations wrapped around responses and request objects. A `RequestFilter` is defined as:

**mysite/code/CustomRequestFilter.php**

	:::php
	<?php

	class CustomRequestFilter implements RequestFilter {

		public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
			
			// if(!something) {
			// 	By returning 'false' from the preRequest method, request execution will be stopped from continuing.
			//	return false;
			// }

			// we can also set any properties onto the request that we need or add any tracking
			// Foo::bar();

			// return true to continue processing.
			return true;
		}

		public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {
			// response is about to be sent.
			// any modifications or tracking to be done?
			// Foo::unbar();

			// return true to send the response.
			return true;
		}
	}

After defining the `RequestFilter`, add it as an allowed `filter` through the [Configuration API](../configuration)

**mysite/_config/app.yml**

	:::yml
	Injector:
	  RequestProcessor:
	    properties:
	      filters:
	        - '%$CustomRequestFilter'

## API Documentation

* [api:RequestFilter]
* [api:RequestProcessor]


