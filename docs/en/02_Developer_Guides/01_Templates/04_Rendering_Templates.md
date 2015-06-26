title: Rendering data to a template
summary: Call and render SilverStripe templates manually.

# Rendering data to a template

Templates do nothing on their own. Rather, they are used to render a particular object.  All of the `<% if %>`, 
`<% loop %>` and other variables are methods or parameters that are called on the current object in 
[scope](syntax#scope).  All that is necessary is that the object is an instance of [api:ViewableData] (or one of its 
subclasses).

The following will render the given data into a template. Given the template:

**mysite/templates/Coach_Message.ss**
	
	:::ss
	<strong>$Name</strong> is the $Role on our team.

Our application code can render into that view using `renderWith`. This method is called on the [api:ViewableData] 
instance with a template name or an array of templates to render. 

**mysite/code/Page.php**

	:::php
	$arrayData = new ArrayData(array(
		'Name' => 'John',
		'Role' => 'Head Coach'
	));

	echo $arrayData->renderWith('Coach_Message');

	// returns "<strong>John</strong> is the Head Coach on our team."

<div class="info" markdown="1">
Most classes in SilverStripe you want in your template extend `ViewableData` and allow you to call `renderWith`. This 
includes [api:Controller], [api:FormField] and [api:DataObject] instances.
</div>

	:::php
	$controller->renderWith(array("MyController", "MyBaseController"));

	Member::currentUser()->renderWith('Member_Profile');

`renderWith` can be used to override the default template process. For instance, to provide an ajax version of a 
template.

	:::php
	<?php

	class Page_Controller extends ContentController {

		private static $allowed_actions = array('iwantmyajax');

		public function iwantmyajax() {
			if(Director::is_ajax()) {
				return $this->renderWith("AjaxTemplate");
			} else {
				return $this->httpError(404);
			}
		}
	}

Any data you want to render into the template that does not extend `ViewableData` should be wrapped in an object that
does, such as `ArrayData` or `ArrayList`.

	:::php
	<?php

	class Page_Controller extends ContentController {

		..

		public function iwantmyajax() {
			if(Director::is_ajax()) {
				$experience = new ArrayList();
				$experience->push(new ArrayData(array(
					'Title' => 'First Job'
				)));

				return $this->customise(new ArrayData(array(
					'Name' => 'John',
					'Role' => 'Head Coach',
					'Experience' => $experience
				)))->renderWith("AjaxTemplate");
			} else {
				return $this->httpError(404);
			}
		}
	}
	
