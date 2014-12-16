title: Scaffolding with SearchContext
summary: Configure the search form within ModelAdmin using the SearchContext class.

# SearchContext

[api:SearchContext] manages searching of properties on one or more [api:DataObject] types, based on a given set of
input parameters. [api:SearchContext] is intentionally decoupled from any controller-logic, it just receives a set of
search parameters and an object class it acts on.

The default output of a [api:SearchContext] is either a [api:SQLQuery] object for further refinement, or a
[api:DataObject] instance.

<div class="notice" markdown="1">
[api:SearchContext] is mainly used by [ModelAdmin](../customising_the_cms/modeladmin).
</div>

## Usage

Defining search-able fields on your DataObject.

	:::php
	<?php

	class MyDataObject extends DataObject {

	   private static $searchable_fields = array(
	      'Name',
	      'ProductCode'
	   );
	}

## Customizing fields and filters

In this example we're defining three attributes on our MyDataObject subclass: `PublicProperty`, `HiddenProperty`
and `MyDate`. The attribute `HiddenProperty` should not be searchable, and `MyDate` should only search for dates
*after* the search entry (with a `GreaterThanFilter`).

	:::php
	<?php

	class MyDataObject extends DataObject {

		private static $db = array(
			'PublicProperty' => 'Text'
			'HiddenProperty' => 'Text',
			'MyDate' => 'Date'
		);
		
		public function getCustomSearchContext() {
			$fields = $this->scaffoldSearchFields(array(
				'restrictFields' => array('PublicProperty','MyDate')
			));

			$filters = array(
				'PublicProperty' => new PartialMatchFilter('PublicProperty'),
				'MyDate' => new GreaterThanFilter('MyDate')
			);

			return new SearchContext(
				$this->class, 
				$fields, 
				$filters
			);
		}
	}

<div class="notice" markdown="1">
See the [SearchFilter](../model/searchfilters) documentation for more information about filters to use such as the
`GreaterThanFilter`.
</div>

<div class="notice" markdown="1">
In case you need multiple contexts, consider name-spacing your request parameters by using `FieldList->namespace()` on
the `$fields` constructor parameter.
</div>

### Generating a search form from the context

	:::php
	<?php

	..

	class Page_Controller extends ContentController {

		public function SearchForm() {
			$context = singleton('MyDataObject')->getCustomSearchContext();
			$fields = $context->getSearchFields();

			$form = new Form($this, "SearchForm",
				$fields,
				new FieldList(
					new FormAction('doSearch')
				)
			);

			return $form;
		}

		public function doSearch($data, $form) {
			$context = singleton('MyDataObject')->getCustomSearchContext();
			$results = $context->getResults($data);

			return $this->customise(array(
				'Results' => $results
			))->renderWith('Page_results');
		}
	}


## Related Documentation

* [ModelAdmin](../customising_the_cms/modeladmin)
* [Tutorial: Site Search](/tutorials/site_search)

## API Documentation

* [api:SearchContext]
* [api:DataObject]

