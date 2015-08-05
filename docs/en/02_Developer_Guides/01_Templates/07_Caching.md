title: Caching
summary: Reduce rendering time with cached templates and understand the limitations of the ViewableData object caching.

# Caching 

## Object caching

All functions that provide data to templates must have no side effects, as the value is cached after first access. For 
example, this controller method will not behave as you might imagine.

	:::php
	private $counter = 0;

	public function Counter() {
	    $this->counter += 1;

	    return $this->counter;
	}


	:::ss
	$Counter, $Counter, $Counter

	// returns 1, 1, 1

When we render `$Counter` to the template we would expect the value to increase and output `1, 2, 3`. However, as 
`$Counter` is cached at the first access, the value of `1` is saved.


## Partial caching

Partial caching is a feature that allows the caching of just a portion of a page. Instead of fetching the required data
from the database to display, the contents of the area are fetched from the `TEMP_FOLDER` file-system pre-rendered and
ready to go. More information about Partial caching is in the [Performance](../performance) guide.

	:::ss
	<% cached 'MyCachedContent', LastEdited %>
		$Title
	<% end_cached %>
