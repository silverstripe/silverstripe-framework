title: Scaffolding with SearchContext
summary: Configure the search form within ModelAdmin using the SearchContext class.

# SearchContext

[api:SearchContext] manages searching of properties on one or more [api:DataObject] types, based on a given set of
input parameters. [api:SearchContext] is intentionally decoupled from any controller-logic, it just receives a set of
search parameters and an object class it acts on.

The default output of a [api:SearchContext] is either a [api:SQLQuery] object for further refinement, or a
[api:DataObject] instance.

<div class="notice" markdown="1">
[api:SearchContext] is mainly used by [ModelAdmin](../customising_the_admin_interface/modeladmin).
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

### Pagination

For pagination records on multiple pages, you need to wrap the results in a
`PaginatedList` object. This object is also passed the generated `SQLQuery`
in order to read page limit information. It is also passed the current
`SS_HTTPRequest` object so it can read the current page from a GET var.

	:::php
	public function getResults($searchCriteria = array()) {
		$start = ($this->getRequest()->getVar('start')) ? (int)$this->getRequest()->getVar('start') : 0;
		$limit = 10;
			
		$context = singleton('MyDataObject')->getCustomSearchContext();
		$query = $context->getQuery($searchCriteria, null, array('start'=>$start,'limit'=>$limit));
		$records = $context->getResults($searchCriteria, null, array('start'=>$start,'limit'=>$limit));
		
		if($records) {
			$records = new PaginatedList($records, $this->getRequest());
			$records->setPageStart($start);
			$records->setPageLength($limit);
			$records->setTotalItems($query->unlimitedRowCount());
		}
		
		return $records;
	}


notice that if you want to use this getResults function, you need to change the function doSearch for this one:

	:::php
	public function doSearch($data, $form) {
		$context = singleton('MyDataObject')->getCustomSearchContext();
		$results = $this->getResults($data);
		return $this->customise(array(
			'Results' => $results
		))->renderWith(array('Catalogo_results', 'Page'));
	}


The change is in **$results = $this->getResults($data);**, because you are using a custom getResults function.

Another thing you cant forget is to check the name of the singleton you are using in your project. the example uses
**MyDataObject**, you need to change it for the one you are using

For more information on how to paginate your results within the template, see [Tutorial: Site Search](/tutorials/4-site-search).


### The Pagination Template

to show the results of your custom search you need at least this content in your template, notice that
Results.PaginationSummary(4) defines how many pages the search will show in the search results. something like:

**Next   1 2  *3*  4  5 &hellip; 558**  


	:::ss
	<% if $Results %>
		<ul>
			<% loop $Results %>
				<li>$Title, $Autor</li>
			<% end_loop %>
		</ul>
	<% else %>
		<p>Sorry, your search query did not return any results.</p>
	<% end_if %>
	
	<% if $Results.MoreThanOnePage %>
		<div id="PageNumbers">
			<p>
				<% if $Results.NotFirstPage %>
					<a class="prev" href="$Results.PrevLink" title="View the previous page">Prev</a>
				<% end_if %>
			
				<span>
			    		<% loop $Results.PaginationSummary(4) %>
						<% if $CurrentBool %>
							$PageNum
						<% else %>
							<% if $Link %>
								<a href="$Link" title="View page number $PageNum">$PageNum</a>
							<% else %>
								&hellip;
							<% end_if %>
						<% end_if %>
					<% end_loop %>
				</span>
			
				<% if $Results.NotLastPage %>
					<a class="next" href="$Results.NextLink" title="View the next page">Next</a>
				<% end_if %>
			</p>
		</div>
	<% end_if %>


## Available SearchFilters

See `[api:SearchFilter]` API Documentation


## Related Documentation

* [ModelAdmin](../customising_the_cms/modeladmin)
* [Tutorial: Site Search](/tutorials/site_search)

## API Documentation

* [api:SearchContext]
* [api:DataObject]

