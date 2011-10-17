# Paginating A List

Adding pagination to a `[api:DataList]` or `[DataObjectSet]` is quite simple. All
you need to do is wrap the object in a `[api:PaginatedList]` decorator, which takes
care of fetching a sub-set of the total list and presenting it to the template.

In order to create a paginated list, you can create a method on your controller
that first creates a `DataList` that will return all pages, and then wraps it
in a `[api:PaginatedSet]` object. The `PaginatedList` object is also passed the
HTTP request object so it can read the current page information from the
"?start=" GET var.

The paginator will automatically set up query limits and read the request for
information.

	:::php
	/**
	 * Returns a paginated list of all pages in the site.
	 */
	public function PaginatedPages() {
		$pages = DataList::create('Page');
		return new PaginatedList($pages, $this->request);
	}

## Setting Up The Template

Now all that remains is to render this list into a template, along with pagination
controls. There are two ways to generate pagination controls:
`[api:PaginatedSet->Pages()]` and `[api:PaginatedSet->PaginationSummary()]`. In
this example we will use `PaginationSummary()`.

The first step is to simply list the objects in the template:

	:::ss
	<ul>
		<% control PaginatedPages %>
			<li><a href="$Link">$Title</a></li>
		<% end_control %>
	</ul>

By default this will display 10 pages at a time. The next step is to add pagination
controls below this so the user can switch between pages:

	:::ss
	<% if PaginatedPages.MoreThanOnePage %>
		<% if PaginatedPages.NotFirstPage %>
			<a class="prev" href="$PaginatedPages.PrevLink">Prev</a>
		<% end_if %>
		<% control PaginatedPages.Pages %>
			<% if CurrentBool %>
				$PageNum
			<% else %>
				<% if Link %>
					<a href="$Link">$PageNum</a>
				<% else %>
					...
				<% end_if %>
			<% end_if %>
			<% end_control %>
		<% if PaginatedPages.NotLastPage %>
			<a class="next" href="$PaginatedPages.NextLink">Next</a>
		<% end_if %>
	<% end_if %>

If there is more than one page, this block will render a set of pagination
controls in the form `[1] ... [3] [4] [[5]] [6] [7] ... [10]`.