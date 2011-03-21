# SearchContext

## Introduction

Manages searching of properties on one or more `[api:DataObject]` types, based on a given set of input parameters.
`[api:SearchContext]` is intentionally decoupled from any controller-logic,
it just receives a set of search parameters and an object class it acts on.

The default output of a `[api:SearchContext]` is either a `[api:SQLQuery]` object for further refinement, or a
`[api:DataObject]` instance.

In case you need multiple contexts, consider namespacing your request parameters by using `FieldSet->namespace()` on
the $fields constructor parameter.

`[api:SearchContext]` is mainly used by `[api:ModelAdmin]`, our generic data administration interface. Another
implementation can be found in generic frontend search forms through the [genericviews](http://silverstripe.org/generic-views-module) module.

## Requirements

*  *SilverStripe 2.3*

## Usage

Getting results

	:::php
	singleton('MyDataObject')->getDefaultSearchContext();


### Defining fields on your DataObject

See `[api:DataObject::$searchable_fields]`.

### Customizing fields and filters

In this example we're defining three attributes on our MyDataObject subclass: `PublicProperty`, `HiddenProperty`
and `MyDate`. The attribute `HiddenProperty` should not be searchable, and `MyDate` should only search for dates
*after* the search entry (with a `GreaterThanFilter`). Similiar to the built-in `DataObject->getDefaultSearchContext()`
method, we're building our own `getCustomSearchContext()` variant.

	:::php
	class MyDataObject extends DataObject {
		static $db = array(
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

### Generating a search form from the context

	:::php
	class Page_Controller extends ContentController {
		public function SearchForm() {
			$context = singleton('MyDataObject')->getCustomSearchContext();
			$fields = $context->getSearchFields();
			$form = new Form($this, "SearchForm",
				$fields,
				new FieldSet(
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

For paginating records on multiple pages, you need to get the generated `SQLQuery` before firing off the actual
search. This way we can set the "page limits" on the result through `setPageLimits()`, and only retrieve a fraction of
the whole result set.


	:::php
	function getResults($searchCriteria = array()) {
		$start = ($this->request->getVar('start')) ? (int)$this->request->getVar('start') : 0;
		$limit = 10;
			
		$context = singleton('MyDataObject')->getCustomSearchContext();
		$query = $context->getQuery($searchCriteria, null, array('start'=>$start,'limit'=>$limit));
		$records = $context->getResults($searchCriteria, null, array('start'=>$start,'limit'=>$limit));
		if($records) {
			$records->setPageLimits($start, $limit, $query->unlimitedRowCount());
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

**Next   1 2  *3*  4  5  … 558**  


	:::ss
	<% if Results %>
		<ul>
			<% control Results %>
				<li>$Titulo, $Autor</li>
			<% end_control %>
		</ul>
	<% else %>
		<p>Sorry, your search query did not return any results.</p>
	<% end_if %>
	
	<% if Results.MoreThanOnePage %>
		<div id="PageNumbers">
			<p>
				<% if Results.NotFirstPage %>
					<a class="prev" href="$Results.PrevLink" title="View the previous page">Prev</a>
				<% end_if %>
			
				<span>
			    		<% control Results.PaginationSummary(4) %>
						<% if CurrentBool %>
							$PageNum
						<% else %>
							<% if Link %>
								<a href="$Link" title="View page number $PageNum">$PageNum</a>
							<% else %>
								&hellip;
							<% end_if %>
						<% end_if %>
					<% end_control %>
				</span>
			
				<% if Results.NotLastPage %>
					<a class="next" href="$Results.NextLink" title="View the next page">Next</a>
				<% end_if %>
			</p>
		</div>
	<% end_if %>


## Available SearchFilters

See `[api:SearchFilter]` API Documentation

## API Documentation
`[api:SearchContext]`

## Related

*  `[api:ModelAdmin]`
*  `[api:RestfulServer]`
*  [Tutorial: Site Search](/tutorials/4-site-search)
