title: Common Variables
summary: Some of the common variables and methods your templates can use, including Menu, SiteConfig, and more.

# Common Variables

The page below describes a few of common variables and methods you'll see in a SilverStripe template. This is not an 
exhaustive list. From your template you can call any method, database field, or relation on the object which is 
currently in scope as well as its' subclasses or extensions. 

Knowing what methods you can call can be tricky, but the first step is to understand the scope you're in. Scope is 
explained in more detail on the [syntax](syntax#scope) page. Many of the methods listed below can be called from any 
scope, and you can specify additional static methods to be available globally in templates by implementing the 
[TemplateGlobalProvider](api:SilverStripe\View\TemplateGlobalProvider) interface.

<div class="notice" markdown="1">
Want a quick way of knowing what scope you're in? Try putting `$ClassName` in your template. You should see a string 
such as `Page` of the object that's in scope. The methods you can call on that object then are any functions, database 
properties or relations on the `Page` class, `PageController` class as well as anything from their subclasses **or** 
extensions.
</div>

Outputting these variables is only the start, if you want to format or manipulate them before adding them to the template
have a read of the [Formating, Modifying and Casting Variables](casting) documentation.

<div class="alert" markdown="1">
Some of the following only apply when you have the `CMS` module installed. If you're using the `Framework` alone, this
functionality may not be included.
</div>


## Base Tag

```ss
<head>
    <% base_tag %>

    ..
</head>
```

The `<% base_tag %>` placeholder is replaced with the HTML base element. Relative links within a document (such as <img
src="someimage.jpg" />) will become relative to the URI specified in the base tag. This ensures the browser knows where
to locate your site’s images and css files.

It renders in the template as `<base href="http://www.yoursite.com" /><!--[if lte IE 6]></base><![endif]-->`

<div class="alert" markdown="1">
A `<% base_tag %>` is nearly always required or assumed by SilverStripe to exist.
</div>

## CurrentMember

Returns the currently logged in [Member](api:SilverStripe\Security\Member) instance, if there is one logged in.

```ss
<% if $CurrentMember %>
  Welcome Back, $CurrentMember.FirstName
<% end_if %>
```

## Title and Menu Title

```ss
$Title
$MenuTitle
```

Most objects within SilverStripe will respond to `$Title` (i.e they should have a `Title` database field or at least a
`getTitle()` method).

The CMS module in particular provides two fields to label a page: `Title` and `MenuTitle`. `Title` is the title 
displayed on the web page, while `MenuTitle` can be a shorter version suitable for size-constrained menus.

<div class="notice" markdown="1">
If `MenuTitle` is left blank by the CMS author, it'll just default to the value in `Title`.
</div>

## Page Content

```ss
$Content
```

It returns the database content of the `Content` property. With the CMS Module, this is the value of the WYSIWYG editor
but it is also the standard for any object that has a body of content to output.

<div class="info" markdown="1">
Please note that this database content can be `versioned`, meaning that draft content edited in the CMS can be different 
from published content shown to your website visitors. In templates, you don't need to worry about this distinction.

The `$Content` variable contains the published content by default,and only preview draft content if explicitly 
requested (e.g. by the "preview" feature in the CMS) (see the [versioning documentation](/../model/versioning) for 
more details).
</div>

### SiteConfig: Global settings

<div class="notice" markdown="1">
`SiteConfig` is a module that is bundled with the `CMS`. If you wish to include `SiteConfig` in your framework only 
web pages. You'll need to install it via `composer`.
</div>

```ss
$SiteConfig.Title
```

The [SiteConfig](../configuration/siteconfig) object allows content authors to modify global data in the CMS, rather 
than PHP code. By default, this includes a Website title and a Tagline.

`SiteConfig` can be extended to hold other data, for example a logo image which can be uploaded through the CMS or 
global content such as your footer content.


## Meta Tags

The `$MetaTags` placeholder in a template returns a segment of HTML appropriate for putting into the `<head>` tag. It
will set up title, keywords and description meta-tags, based on the CMS content and is editable in the 'Meta-data' tab
on a per-page basis. 

<div class="notice" markdown="1">
If you don’t want to include the title tag use `$MetaTags(false)`.
</div>

By default `$MetaTags` renders:

```ss
<title>Title of the Page</title>
<meta name="generator" http-equiv="generator" content="SilverStripe 3.0" />
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
```

`$MetaTags(false)` will render

```ss
<meta name="generator" http-equiv="generator" content="SilverStripe 3.0" />
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
```

If using `$MetaTags(false)` we can provide a more custom `title`.

```ss
$MetaTags(false)
<title>$Title - Bob's Fantasy Football</title>
```

### Modifying Meta Tags

You can override the `MetaComponents()` method on your `SiteTree` sub-classes or make use of the `MetaComponents` extension point to manipulate the underlying data that is rendered by `$MetaTags`. Example (for `Page` class):

```php
public function MetaComponents()
{
    $tags = parent::MetaComponents();
    // Override the content of the Title tag (needs to be html)
    if ($this->MetaTitle) {
        $tags['title']['content'] = $this->obj('MetaTitle')->forTemplate();
    }
    // Provide a default Meta Description
    if (!$tags['description']['attributes']['content']) {
        // provide raw text as attributes will be escaped later
        $tags['description']['attributes']['content'] = $this->dbObject('Content')->LimitCharactersToClosestWord(300);
    }
    return $tags;
}
```

## Links

```ss
<a href="$Link">..</a>
```

All objects that could be accessible in SilverStripe should define a `Link` method and an `AbsoluteLink` method. Link 
returns the relative URL for the object and `AbsoluteLink` outputs your full website address along with the relative 
link.

```ss
$Link
<!-- returns /about-us/offices/ -->

$AbsoluteLink
<!-- returns http://yoursite.com/about-us/offices/ -->
```

### Linking Modes

```ss
$isSection
$isCurrent
```

When looping over a list of `SiteTree` instances through a `<% loop $Menu %>` or `<% loop $Children %>`, `$isSection` and `$isCurrent`
will return true or false based on page being looped over relative to the currently viewed page. 

For instance, to only show the menu item linked if it's the current one:

```ss
<% if $isCurrent %>
    $Title
<% else %>
    <a href="$Link">$Title</a>
<% end_if %>
```

An example for checking for `current` or `section` is as follows:

```ss
<a class="<% if $isCurrent %>current<% else_if $isSection %>section<% end_if %>" href="$Link">$MenuTitle</a>
```

**Additional Utility Method**

 * `$InSection(page-url)`: This if block will pass if we're currently on the page-url page or one of its children.

```ss
<% if $InSection(about-us) %>
    <p>You are viewing the about us section</p>
<% end_if %>
```

### URLSegment

This returns the part of the URL of the page you're currently on. For example on the `/about-us/offices/` web page the 
`URLSegment` will be `offices`. `URLSegment` cannot be used to generate a link since it does not output the full path.
It can be used within templates to generate anchors or other CSS classes.

```ss
<div id="section-$URLSegment">

</div>

<!-- returns <div id="section-offices"> -->
```

##  ClassName

Returns the class of the current object in [scope](syntax#scope) such as `Page` or `HomePage`. The `$ClassName` can be 
handy for a number of uses. A common use case is to add to your `<body>` tag to influence CSS styles and JavaScript 
behavior based on the page type used:

```ss
<body class="$ClassName">

<!-- returns <body class="HomePage">, <body class="BlogPage"> -->
```

## Children Loops

```ss
<% loop $Children %>

<% end_loop %>
```

Will loop over all Children records of the current object context. Children are pages that sit under the current page in
the `CMS` or a custom list of data. This originates in the `Versioned` extension's `getChildren` method.

<div class="alert" markdown="1">
For doing your website navigation most likely you'll want to use `$Menu` since its independent of the page 
context.
</div>

### ChildrenOf

```ss
<% loop $ChildrenOf(<my-page-url>) %>

<% end_loop %>
```

Will create a list of the children of the given page, as identified by its `URLSegment` value. This can come in handy 
because it's not dependent on the context of the current page. For example, it would allow you to list all staff member 
pages underneath a "staff" holder on any page, regardless if its on the top level or elsewhere.


### AllChildren

Content authors have the ability to hide pages from menus by un-selecting the `ShowInMenus` checkbox within the CMS. 
This option will be honored by `<% loop $Children %>` and `<% loop $Menu %>` however if you want to ignore the user
preference, `AllChildren` does not filter by `ShowInMenus`.

```ss
<% loop $AllChildren %>
    ...
<% end_loop %>
```

### Menu Loops

```ss
<% loop $Menu(1) %>
    ...
<% end_loop %>
```

`$Menu(1)` returns the top-level menu of the website. You can also create a sub-menu using `$Menu(2)`, and so forth.

<div class="notice" markdown="1">
Pages with the `ShowInMenus` property set to `false` will be filtered out.
</div>

## Access to a specific Page

```ss
<% with $Page(my-page) %>
    $Title
<% end_with %>
```

Page will return a single page from site, looking it up by URL. 

## Access to Parent and Level Pages

### Level

```ss
<% with $Level(1) %>
    $Title
<% end_with %>
```

Will return a page in the current path, at the level specified by the numbers. It is based on the current page context, 
looking back through its parent pages. `Level(1)` being the top most level.

For example, imagine you're on the "bob marley" page, which is three levels in: "about us > staff > bob marley".

*  `$Level(1).Title` would return "about us"
*  `$Level(2).Title` would return "staff"
*  `$Level(3).Title` would return "bob marley"

### Parent

```ss
<!-- given we're on 'Bob Marley' in "about us > staff > bob marley" -->

$Parent.Title
<!-- returns 'staff' -->

$Parent.Parent.Title
<!-- returns 'about us' -->
```

## Navigating Scope

See [scope](syntax#scope).

## Breadcrumbs

Breadcrumbs are the path of pages which need to be taken to reach the current page, and can be a great navigation aid 
for website users.

While you can achieve breadcrumbs through the `$Level(<level>)` control manually, there's a nicer shortcut: The 
`$Breadcrumbs` variable.

```ss
$Breadcrumbs
```

By default, it uses the template defined in `templates/BreadcrumbsTemplate.ss`
of the `silverstripe/cms` module.

```ss
<% if $Pages %>
    <% loop $Pages %>
        <% if $Last %>$Title.XML<% else %><a href="$Link">$MenuTitle.XML</a> &raquo;<% end_if %>
    <% end_loop %>
<% end_if %>
```

<div class="info" markdown="1">
To customise the markup that the `$Breadcrumbs` generates, copy `templates/BreadcrumbsTemplate.ss`
 from the `silverstripe/cms` module to 
`app/templates/BreadcrumbsTemplate.ss`, modify the newly copied template and flush your SilverStripe cache.
</div>

## Forms

```ss
$Form
```

A page will normally contain some content and potentially a form of some kind. For example, the log-in page has a the
SilverStripe log-in form. If you are on such a page, the `$Form` variable will contain the HTML content of the form. 
Placing it just below `$Content` is a good default.


## Related Lessons
* [Adding dynamic content](https://www.silverstripe.org/learn/lessons/v4/adding-dynamic-content-1)

## Related Documentation

 * [Casting and Formating Variables](casting)
 * [Template Inheritance](template_inheritance)

## API Documentation

 * [ContentController](api:SilverStripe\CMS\Controllers\ContentController): The main controller responsible for handling pages.
 * [Controller](api:SilverStripe\Control\Controller): Generic controller (not specific to pages.)
 * [DataObject](api:SilverStripe\ORM\DataObject): Underlying model class for page objects.
 * [ViewableData](api:SilverStripe\View\ViewableData): Underlying object class for pretty much anything displayable.
