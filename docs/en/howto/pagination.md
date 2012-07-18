# Paginating A List

Adding pagination to a `[api:SS_List]` is quite simple. All
you need to do is wrap the object in a `[api:PaginatedList]` decorator, which takes
care of fetching a sub-set of the total list and presenting it to the template.

In order to create a paginated list, you can create a method on your controller
that first creates a `SS_List` that will return all pages, and then wraps it
in a `[api:PaginatedList]` object. The `PaginatedList` object is also passed the
HTTP request object so it can read the current page information from the
"?start=" GET var.

The paginator will automatically set up query limits and read the request for
information.

	:::php
	/**
	 * Returns a paginated list of all pages in the site.
	 */
	public function PaginatedPages() {
		return new PaginatedList(Page::get(), $this->request);
	}

Note that the concept of "pages" used in pagination does not necessarily
mean that we're dealing with `Page` classes, its just a term to describe
a sub-collection of the list.

## Setting Up The Template

Now all that remains is to render this list into a template, along with pagination
controls. There are two ways to generate pagination controls:
`[api:PaginatedList->Pages()]` and `[api:PaginatedList->PaginationSummary()]`. In
this example we will use `PaginationSummary()`.

The first step is to simply list the objects in the template:

	:::ss
	<ul>
		<% loop PaginatedPages %>
			<li><a href="$Link">$Title</a></li>
		<% end_loop %>
	</ul>

By default this will display 10 pages at a time. The next step is to add pagination
controls below this so the user can switch between pages:

	:::ss
	<% if PaginatedPages.MoreThanOnePage %>
		<% if PaginatedPages.NotFirstPage %>
			<a class="prev" href="$PaginatedPages.PrevLink">Prev</a>
		<% end_if %>
		<% loop PaginatedPages.Pages %>
			<% if CurrentBool %>
				$PageNum
			<% else %>
				<% if Link %>
					<a href="$Link">$PageNum</a>
				<% else %>
					...
				<% end_if %>
			<% end_if %>
			<% end_loop %>
		<% if PaginatedPages.NotLastPage %>
			<a class="next" href="$PaginatedPages.NextLink">Next</a>
		<% end_if %>
	<% end_if %>

If there is more than one page, this block will render a set of pagination
controls in the form `[1] ... [3] [4] [[5]] [6] [7] ... [10]`.

## Paginating Custom Lists

In some situations where you are generating the list yourself, the underlying
list will already contain only the items that you wish to display on the current
page. In this situation the automatic limiting done by `[api:PaginatedList]`
will break the pagination. You can disable automatic limiting using the
`[api:PaginatedList->setLimitItems()]` method when using custom lists.

## Related

 * [Howto: "Grouping Lists"](/howto/grouping-dataobjectsets)