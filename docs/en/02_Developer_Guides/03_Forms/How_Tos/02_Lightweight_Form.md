title: How to Create Lightweight Form

# How to Create Lightweight Form

Out of the box, SilverStripe provides a robust and reusable set of HTML markup for [api:FormFields], however this can 
sometimes produce markup which is unnecessarily bloated.

For example, a basic search form. We want to use the [api:Form] API to handle the form but we may want to provide a 
totally custom template to meet our needs. To do this, we'll provide the class with a unique template through 
`setTemplate`.

**mysite/code/Page.php**

	:::php
	<?php

	public function SearchForm() {
		$fields = new FieldList(
			TextField::create('q')
		);

		$actions = new FieldList(
			FormAction::create('doSearch', 'Search')
		);

		$form = new Form($this, 'SearchForm', $fields, $actions);
		$form->setTemplate('SearchForm');

		return $form;
	}

**mysite/templates/Includes/SearchForm.ss**

	:::ss
	<form $FormAttributes>
		<fieldset>
			$Fields.dataFieldByName(q)
		</fieldset>
		
		<div class="Actions">
			<% loop $Actions %>$Field<% end_loop %>
		</div>
	</form>

`SearchForm.ss` will be executed within the scope of the `Form` object so has access to any of the methods and 
properties on [api:Form] such as `$Fields` and `$Actions`. 

<div class="notice">
To understand more about Scope or the syntax for custom templates, read the [Templates](../../templates) guide.
</div>


