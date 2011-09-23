# Built-in Page Controls

 
Ever wonder when you use `$Title` and `<% Control Children %>` what else you can call in the templates?. This page is
here to help with a guide on what template controls you can call.

**Note for advanced users:** These built-in page controls are defined in the `[api:SiteTree]` classes, which are the
'root' data-object and controller classes for all the sites.  So if you're dealing with something that isn't a sub-class
of one of these, our handy reference to 'built-in page controls' won't be so relevant.


## Page controls that can't be nested

These page controls are defined on the **controller** which means they can only be used at a top level, not nested
within another page control.

### Controlling Menus Datafeeds

#### &lt;% control Menu(1) %&gt;, &lt;% control Menu(2) %&gt;, ...

Returns a fixed level menu.  Because this only works in the top level, you can't use it for nested menus.  Used <%
control Children %> instead. You can nest `<% control Children %>`.

#### &lt;% control ChildrenOf(page-url) %&gt;

This will create a datafeed of the children of the given page. Handy if you want a list of the subpages under staff (eg
the staff) on the homepage etc

### Controlling Certain Pages

#### &lt;% control Level(1) %&gt;, &lt;% control Level(2) %&gt;, $Level(1).Title, $Level(2).Content, etc
Returns the current section of the site that we're in, at the level specified by the numbers.  For example, imagine
you're on the page __about us > staff > bob marley__:

*  `<% control Level(1) %>` would return the about us page
*  `<% control Level(2) %>` would return the staff page
*  `<% control Level(3) %>` would return the bob marley page

#### &lt;% control Page(my-page) %&gt;$Title&lt;% end_control %&gt;

"Page" will return a single page from the site tree, looking it up by URL.  You can use it in the `<% control %>` format.
Can't be called using $Page(my-page).Title.

## Page controls that can be used anywhere

These are defined in the data-object and so can be used as nested page controls.  Lucky us! we can control Children of
Children of Children for example.

### Conditional Logic

SilverStripe supports a simple set of conditional logic

	:::ss
	<% if Foo %>
	// if Foo is true or an object do this
	<% else_if Bar %>
	// if Bar is true or an object do this
	<% else %>
	// then do this by default
	<% end_if %>


See more information on conditional logic on [templates](/topics/templates).

### Site wide settings

Since 2.4.0, SilverStripe provides a generic interface for accessing global properties such as *Site name* or *Site tag
line*. This interface is implemented by the [api:SiteConfig] class.

### Controlling Parents and Children

#### &lt;% control Children %&gt;

This will return the children of the current page as a nested datafeed.  Useful for nested navigations such as pop-out
menus.

#### &lt;% control AllChildren %&gt;

This will show all children of a page even if the option 'show in menus?' is unchecked in the tab panel behaviour.

#### &lt;% control Parent %&gt; or $Parent.Title, $Parent.Content, etc

This will return the parent page.  The $ variable format lets us reference an attribute of the parent page directly.

### Site Navigation - Breadcrumbs

#### &lt;% control Breadcrumbs %&gt;

This will return a breadcrumbs widgets for the given page.  You can call this on any data-object, so, for example, you
could display the breadcrumbs of every search result if you wanted.  It has a few options.

####  &lt;% control Breadcrumbs(3) %&gt;

Will return a maximum of 3 pages in the breadcrumb list, this can be handy if you're wanting to put breadcrumbs in a
place without spilling

####  &lt;% control Breadcrumbs(3, true) %&gt;

Will return the same, but without any links. This is handy if you're wanting to put the breadcrumb list into another
link tag.


### Links and Classes

#### $LinkingMode, $LinkOrCurrent and $LinkOrSection

These return different linking modes.  $LinkingMode provides the greatest control, outputting 3 different strings:

*  link: Neither this page nor any of its children are current open.
*  section: A child of this page is currently open, which means that we're currently in this section of the site.
*  current: This page is currently open.

A useful way of using this is in your menus. You can use the following code below to generate an class="current" or
class="section" on your links. Take the following code

	:::ss
	<li><a href="$Link" class="$LinkingMode">$Title</a></li>


When viewed on the Home page it will render like this

	:::ss
	<li><a href="home/" class="current">Home</a></li>


$LinkOrCurrent ignores the section status, returning link instead.  $LinkOrSection ingores the current status, returning
section instead.  Both of these options can simplify your CSS when you only have 2 different cases to consider.

#### &lt;% if LinkOrCurrent = current %&gt;

This is an alternative way to set up your menus - if you want different HTML for the current menu item, you can do
something like this:

	:::ss
	<% if LinkOrCurrent = current %>
	<strong>$Title</strong>
	<% else %>
	<a href="$Link">$Title</a>
	<% end_if %>


#### &lt;% if LinkOrSection = section %&gt;

Will return true if you are on the current page OR a child page of the page. Useful for menus which you only want to
show a second level menu when you are on that page or a child of it

#### &lt;% if InSection(page-url) %&gt;

This if block will pass if we're currently on the page-url page or one of its children.

### Titles and CMS Defined Options

#### $MetaTags

This returns a segment of HTML appropriate for putting into the `<head>` tag.  It will set up title, keywords and
description meta-tags, based on the CMS content. If you don't want to include the title-tag (for custom templating), use
**$MetaTags(false)**.

#### $MenuTitle

This is the title that you should put into navigation menus.  CMS authors can choose to put a different menu title from
the main page title.

#### $Title

This is the title of the page which displays in the browser window and usually is the title of the page.

	:::ss
	<h1>$Title</h1>

#### $URLSegment

This returns the part of the URL of the page you're currently on. Could be handy to use as an id on your body-tag. (
when doing this, watch out that it doesn't create invalid id-attributes though.). This is useful for adding a class to
the body so you can target certain pages. Watch out for pages named clear or anything you might have used in your CSS
file

	:::ss
	<body class="$URLSegment">


####  $ClassName

Returns the ClassName of the PHP object. Eg if you have a custom HomePage page type with $ClassName in the template, it
will return "HomePage"

#### $BaseHref

Returns the base URL for the current site. This is used to populate the `<base>` tag by default, so if you want to
override `<% base_tag %>` with a specific piece of HTML, you can do something like `<base href="$BaseHref">``</base>`

### Controlling Members and Visitors Data

#### &lt;% control CurrentMember %&gt;, &lt;% if CurrentMember %&gt; or $CurrentMember.FirstName

CurrentMember returns the currently logged in member, if there is one.  All of their details or any special Member page
controls can be called on this.  Alternately, you can use `&lt;% if CurrentMember %>` to detect whether someone has logged
in. To Display a welcome message you can do

	:::ss
	<% if CurrentMember %>
	  Welcome Back, $CurrentMember.FirstName
	<% end_if %>


If the user is logged in this will print out

	:::ss
	Welcome Back, Admin

 
#### &lt;% if PastMember %&gt;, &lt;% if PastVisitor %&gt;

These controls detect the visitor's previous experience with the site:

*  $PastVisitor will return true if the visitor has been to the site before
*  $PastMember will return true if the visitor has signed up or logged in on the site before

### Date and Time

#### $Now.Nice, $Now.Year

$Now returns the current date.  You can call any of the methods from the `[api:Date]` class on
it. 

#### $Created.Nice, $Created.Ago

$Created returns the time the page was created, $Created.Ago returns how long ago the page was created. You can also
call any of methods of the `[api:Date]` class on it.

#### $LastEdited.Nice, $LastEdited.Ago

$LastEdited returns the time the page was modified, $LastEdited.Ago returns how long ago the page was modified.You can also
call any of methods of the `[api:Date]` class on it.

### DataObjectSet Options

If you are using a DataObjectSet you have a wide range of methods you can call on it from the templates

#### &lt;% if Even %&gt;, &lt;% if Odd %&gt;, $EvenOdd

These controls can be used to do zebra-striping.  $EvenOdd will return 'even' or 'odd' as appropriate.

#### &lt;% if First %&gt;, &lt;% if Last %&gt;, &lt;% if Middle %&gt;, $FirstLast

These controls can be used to set up special behaviour for the first and last records of a datafeed.  `<% if Middle %>` is
set when neither first not last are set.  $FirstLast will be 'first', 'last', or ''

#### $Pos, $TotalItems

$TotalItems will return the number of items on this page of the datafeed, and Pos will return a counter starting at 1.

#### $Top

When you're inside a control loop in your template, and want to reference methods on the current controller you're on,
breaking out of the loop to get it, you can use $Top to do so. For example:

	:::ss
	$URLSegment
	<% control News %>
	   $URLSegment <!-- may not return anything, as you're requesting URLSegment on the News objects -->
	   $Top.URLSegment <!-- returns the same as $URLSegment above -->
	<% end_control %>


##  Properties of a datafeed itself, rather than one of its items

If we have a control such as `<% control SearchResults %>`, there are some properties, such as $SearchResults.NextLink,
that aren't accessible within `<% control SearchResults %>`.  These can be used on any datafeed.

### Search Results

#### &lt;% if SearchResults.MoreThanOnePage %&gt;

Returns true when we have a multi-page datafeed, restricted with a limit.

#### $SearchResults.NextLink, $SearchResults.PrevLink

This returns links to the next and previous page in a multi-page datafeed.  They will return blank if there's no
appropriate page to go to, so $PrevLink will return blank when you're on the first page.  You can therefore use &lt;% if
PrevLink %> to keep your template tidy.

#### $SearchResults.CurrentPage, $SearchResults.TotalPages

CurrentPage returns the number of the page you're currently on, and TotalPages returns the total number of pages.

#### $SearchResults.TotalItems

This returns the total number of items across all pages.

#### &lt;% control SearchResults.First %&gt;, &lt;% control SearchResults.Last %&gt;

These controls return the first and last item on the current page of the datafeed.

#### &lt;% control SearchResults.Pages %&gt;

This will return another datafeed, listing all of the pages in this datafeed.  It will have the following data
available:

*  **$PageNum:** page number, starting at 1
*  **$Link:** a link straight to that page
*  `<% if CurrentBool %>`:** returns true if you're currently on that page

`<% control SearchResults.Pages(30) %>` will show a maximum of 30 pages, useful in situations where you could get 100s of
pages returned.

#### $SearchResults.UL

This is a quick way of generating a `<ul>` containing an `<li>` and `<a>` for each item in the datafeed.  Usually too
restricted to use in a final application, but handy for debugging stuff.


## Quick Reference

Below is a list of fields and methods that are typically available for templates (grouped by their source) - use this as
a quick reference (not all of them are described above):
### All methods available in Page_Controller

$NexPageLink, $Link, $RelativeLink, $ChildrenOf, $Page, $Level, $Menu, $Section2, $LoginForm, $SilverStripeNavigator,
$PageComments, $Now, $LinkTo, $AbsoluteLink, $CurrentMember, $PastVisitor, $PastMember, $XML_val, $RAW_val, $SQL_val,
$JS_val, $ATT_val, $First, $Last, $FirstLast, $MiddleString, $Middle, $Even, $Odd, $EvenOdd, $Pos, $TotalItems,
$BaseHref, $Debug, $CurrentPage, $Top

### All fields available in Page_Controller

$ID, $ClassName, $Created, $LastEdited, $URLSegment, $Title, $MenuTitle, $Content, $MetaTitle, $MetaDescription,
$MetaKeywords, $ShowInMenus, $ShowInSearch, $HomepageForDomain, $ProvideComments, $Sort, $LegacyURL, $HasBrokenFile,
$HasBrokenLink, $Status, $ReportClass, $ParentID, $Version, $EmailTo, $EmailOnSubmit, $SubmitButtonText,
$OnCompleteMessage, $Subscribe, $AllNewsletters, $Subject, $ErrorCode, $LinkedPageID, $RedirectionType, $ExternalURL,
$LinkToID, $VersionID, $CopyContentFromID, $RecordClassName

### All methods available in Page

$Link, $LinkOrCurrent, $LinkOrSection, $LinkingMode, $ElementName, $InSection, $Comments, $Breadcrumbs, $NestedTitle,
$MetaTags, $ContentSource, $MultipleParents, $TreeTitle, $CMSTreeClasses, $Now, $LinkTo, $AbsoluteLink, $CurrentMember,
$PastVisitor, $PastMember, $XML_val, $RAW_val, $SQL_val, $JS_val, $ATT_val, $First, $Last, $FirstLast, $MiddleString,
$Middle, $Even, $Odd, $EvenOdd, $Pos, $TotalItems, $BaseHref, $CurrentPage, $Top

###  All fields available in Page

$ID, $ClassName, $Created, $LastEdited, $URLSegment, $Title, $MenuTitle, $Content, $MetaTitle, $MetaDescription,
$MetaKeywords, $ShowInMenus, $ShowInSearch, $HomepageForDomain, $ProvideComments, $Sort, $LegacyURL, $HasBrokenFile,
$HasBrokenLink, $Status, $ReportClass, $ParentID, $Version, $EmailTo, $EmailOnSubmit, $SubmitButtonText,
$OnCompleteMessage, $Subscribe, $AllNewsletters, $Subject, $ErrorCode, $LinkedPageID, $RedirectionType, $ExternalURL,
$LinkToID, $VersionID, $CopyContentFromID, $RecordClassName
