title: How to Create a Paginated List

# How to Create a Paginated List

In order to create a paginated list, create a method on your controller that first creates a `SS_List` that contains
all your record, then wraps it in a [api:PaginatedList] object. The `PaginatedList` object should also passed the 
[api:SS_HTTPRequest] object so it can read the current page information from the "?start=" GET var.

The `PaginatedList` will automatically set up query limits and read the request for information.

**mysite/code/Page.php**

	:::php
	/**
	 * Returns a paginated list of all pages in the site.
	 */
	public function PaginatedPages() {
		$list = Page::get();

		return new PaginatedList($list, $this->request);
	}

<div class="notice" markdown="1">
Note that the concept of "pages" used in pagination does not necessarily mean that we're dealing with `Page` classes, 
it's just a term to describe a sub-collection of the list.
</div>

There are two ways to generate pagination controls: [api:PaginatedList->Pages] and 
[api:PaginatedList->PaginationSummary]. In this example we will use `PaginationSummary()`.

The first step is to simply list the objects in the template:

**mysite/templates/Page.ss**

	:::ss
	<ul>
		<% loop $PaginatedPages %>
			<li><a href="$Link">$Title</a></li>
		<% end_loop %>
	</ul>

By default this will display 10 pages at a time. The next step is to add pagination controls below this so the user can 
switch between pages:

**mysite/templates/Page.ss**

	:::ss
	<% if $PaginatedPages.MoreThanOnePage %>
		<% if $PaginatedPages.NotFirstPage %>
			<a class="prev" href="$PaginatedPages.PrevLink">Prev</a>
		<% end_if %>
		<% loop $PaginatedPages.Pages %>
			<% if $CurrentBool %>
				$PageNum
			<% else %>
				<% if $Link %>
					<a href="$Link">$PageNum</a>
				<% else %>
					...
				<% end_if %>
			<% end_if %>
			<% end_loop %>
		<% if $PaginatedPages.NotLastPage %>
			<a class="next" href="$PaginatedPages.NextLink">Next</a>
		<% end_if %>
	<% end_if %>

If there is more than one page, this block will render a set of pagination controls in the form 
`[1] ... [3] [4] [5] [6] [7] ... [10]`.

## Paginating Custom Lists

In some situations where you are generating the list yourself, the underlying list will already contain only the items 
that you wish to display on the current page. In this situation the automatic limiting done by [api:PaginatedList]
will break the pagination. You can disable automatic limiting using the [api:PaginatedList->setLimitItems] method 
when using custom lists.

	:::php
	$myPreLimitedList = Page::get()->limit(10);

	$pages = new PaginatedList($myPreLimitedList, $this->request);
	$pages->setLimitItems(false);


## Setting the limit of items

	:::php
	$pages = new PaginatedList(Page::get(), $this->request);
	$pages->setPageLength(25);

## Template Variables

| Variable | Description |
| -------- | -------- |
| `$MoreThanOnePage` | Returns true when we have a multi-page list, restricted with a limit. |
| `$NextLink`, `$PrevLink` | They will return blank if there's no appropriate page to go to, so `$PrevLink` will return blank when you're on the first page. |
| `$CurrentPage` | Current page iterated on. |
| `$TotalPages` | The actual (limited) list of records, use in an inner loop |
| `$TotalItems` | This returns the total number of items across all pages. | 
| `$Pages` | Total number of pages. |
| `$PageNum` | Page number, starting at 1 (within `$Pages`) |
| `$Link` | Links to the current controller URL, setting this page as current via a GET parameter |
| `$CurrentBool` | Returns true if you're currently on that page |


## API Documentation

* [api:PaginatedList]


