# CMS Architecture

## Introduction

A lot can be achieved in SilverStripe by adding properties and form fields
to your own page types (via [SiteTree::getCMSFields()](api:SilverStripe\CMS\Model\SiteTree::getCMSFields())), as well as creating
your own data management interfaces through [ModelAdmin](api:SilverStripe\Admin\ModelAdmin). But sometimes
you'll want to go deeper and tailor the underlying interface to your needs as well.
For example, to build a personalized CMS dashboard, or content "slots" where authors
can drag their content into. At its core, SilverStripe is a web application
built on open standards and common libraries, so lots of the techniques should
feel familiar to you. This is just a quick run down to get you started
with some special conventions.

For a more practical-oriented approach to CMS customizations, refer to the
[Howto: Extend the CMS Interface](/developer_guides/customising_the_admin_interface/how_tos/extend_cms_interface).

## Installation

In order to contribute to the core frontend code, you need [NodeJS 4.x](https://nodejs.org/).
This will install the package manager necessary to download frontend requirements.
Once NodeJS is ready to go, you can download those requirements:

```
(cd framework && npm install)
```

Note: For each core module (e.g. `framework` and `cms`), a separate `npm install` needs to be run.

## Building

All "source" files for the frontend logic are located in `vendor/silverstripe/framework/client/src`.
The base CMS interface has its own folder with `vendor/silverstripe/framework/admin/client/src`.
If you have the `cms`  module installed, there's additional files in `vendor/silverstripe/cms/client/src`.

All build commands are run through `npm`. You can check the module's
`package.json` for available commands.
Under the hood, files are built through [Webpack](https://webpack.github.io/).
You'll need to run at least the `build` and `css` commands.
Check our [build tooling](/contributing/build_tooling) docs for more details. 


```
cd vendor/silverstripe/admin && yarn build
```

## Coding Conventions

Please follow our [CSS](/contributing/css_coding_conventions)
and [JavaScript](/contributing/javascript_coding_conventions)
coding conventions.


## Pattern library

A pattern library is a collection of user interface design elements, this helps developers and designers collaborate and to provide a quick preview of elements as they were intended without the need to build an entire interface to see it.
Components built in React and used by the CMS are actively being added to the pattern library.

To access the pattern library, starting from your project root:

```
cd vendor/silverstripe/admin && yarn pattern-lib
```

Then browse to `http://localhost:6006/`


## The Admin URL

The CMS interface can be accessed by default through the `admin/` URL. You can change this by setting your own [Director routing rule](director#routing-rules) to the `[AdminRootController](api:SilverStripe\Admin\AdminRootController)` and clear the old rule like in the example below.


```yml

---
Name: myadmin
After:
  - '#adminroutes'
---
SilverStripe\Control\Director:
  rules:
    'admin': ''
    'newAdmin': 'AdminRootController'
---
```

When extending the CMS or creating modules, you can take advantage of various functions that will return the configured admin URL (by default 'admin/' is returned):

In PHP you should use:


```php
SilverStripe\Admin\AdminRootController::admin_url()
```

When writing templates use:


```ss
$AdminURL
```

And in JavaScript, this is avaible through the `ss` namespace


```js
ss.config.adminUrl
```

### Multiple Admin URL and overrides

You can also create your own classes that extend the `[AdminRootController](api:SilverStripe\Admin\AdminRootController)` to create multiple or custom admin areas, with a `Director.rules` for each one.

## Templates and Controllers

The CMS backend is handled through the [LeftAndMain](api:SilverStripe\Admin\LeftAndMain) controller class,
which contains base functionality like displaying and saving a record.
This is extended through various subclasses, e.g. to add a group hierarchy ([SecurityAdmin](api:SilverStripe\Admin\SecurityAdmin)),
a search interface ([ModelAdmin](api:SilverStripe\Admin\ModelAdmin)) or an "Add Page" form ([CMSPageAddController](api:SilverStripe\CMS\Controllers\CMSPageAddController)).

The controller structure is too complex to document here, a good starting point
for following the execution path in code are [LeftAndMain::getRecord()](api:SilverStripe\Admin\LeftAndMain::getRecord()) and [LeftAndMain::getEditForm()](api:SilverStripe\Admin\LeftAndMain::getEditForm()).
If you have the `cms` module installed, have a look at [CMSMain::getEditForm()](api:SilverStripe\CMS\Controllers\CMSMain::getEditForm()) for a good
example on how to extend the base functionality (e.g. by adding page versioning hints to the form).

CMS templates are inherited based on their controllers, similar to subclasses of
the common `Page` object (a new PHP class `MyPage` will look for a `MyPage.ss` template).
We can use this to create a different base template with `LeftAndMain.ss`
(which corresponds to the `LeftAndMain` PHP controller class).
In case you want to retain the main CMS structure (which is recommended),
just create your own "Content" template (e.g. `MyCMSController_Content.ss`),
which is in charge of rendering the main content area apart from the CMS menu.

Depending on the complexity of your layout, you'll also need to overload the
"EditForm" template (e.g. `MyCMSController_EditForm.ss`), e.g. to implement
a tabbed form which only scrolls the main tab areas, while keeping the buttons at the bottom of the frame.
This requires manual assignment of the template to your form instance, see [CMSMain::getEditForm()](api:SilverStripe\CMS\Controllers\CMSMain::getEditForm()) for details.

Often its useful to have a "tools" panel in between the menu and your content,
usually occupied by a search form or navigational helper.
In this case, you can either overload the full base template as described above.
To avoid duplicating all this template code, you can also use the special [LeftAndMain::Tools()](api:SilverStripe\Admin\LeftAndMain::Tools()) and
[LeftAndMain::EditFormTools()](api:SilverStripe\Admin\LeftAndMain::EditFormTools()) methods available in `LeftAndMain`.
These placeholders are populated by auto-detected templates,
with the naming convention of "<controller classname>_Tools.ss" and "<controller classname>_EditFormTools.ss".
So to add or "subclass" a tools panel, simply create this file and it's automatically picked up.

## Layout and Panels

The various panels and UI components within them are loosely coupled to the layout engine through the `data-layout-type`
attribute. The layout is triggered on the top element and cascades into children, with a `redraw` method defined on
each panel and UI component that needs to update itself as a result of layouting.

Refer to [Layout reference](/developer_guides/customising_the_admin_interface/cms_layout) for further information.

## Forms

SilverStripe constructs forms and its fields within PHP,
mainly through the [getCMSFields()](api:SilverStripe\ORM\DataObject::getCMSFields()) method.
This in turn means that the CMS loads these forms as HTML via Ajax calls,
e.g. after saving a record (which requires a form refresh), or switching the section in the CMS.

Depending on where in the DOM hierarchy you want to use a form,
custom templates and additional CSS classes might be required for correct operation.
For example, the "EditForm" has specific view and logic JavaScript behaviour
which can be enabled via adding the "cms-edit-form" class.
In order to set the correct layout classes, we also need a custom template.
To obey the inheritance chain, we use `$this->getTemplatesWithSuffix('_EditForm')` for
selecting the most specific template (so `MyAdmin_EditForm.ss`, if it exists).

The form should use a `LeftAndMainFormRequestHandler`, since it allows the use
of a `PjaxResponseNegotiator` to handle its display.

Basic example form in a CMS controller subclass:


```php
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\LeftAndMainFormRequestHandler;

class MyAdmin extends LeftAndMain 
{
    public function getEditForm() {
        return Form::create(
            $this,
            'EditForm',
            new FieldList(
                TabSet::create(
                    'Root',
                    Tab::create('Main',
                        TextField::create('MyText')
                    )
                )->setTemplate('CMSTabset')
            ),
            new FieldList(
                FormAction::create('doSubmit')
            )
        )
            // Use a custom request handler
            ->setRequestHandler(
                LeftAndMainFormRequestHandler::create($form)
            )
            // JS and CSS use this identifier
            ->setHTMLID('Form_EditForm')
            // Render correct responses on validation errors
            ->setResponseNegotiator($this->getResponseNegotiator());
            // Required for correct CMS layout
            ->addExtraClass('cms-edit-form')
            ->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
    }
}
```

Note: Usually you don't need to worry about these settings,
and will simply call `parent::getEditForm()` to modify an existing,
correctly configured form.

## JavaScript through jQuery.entwine

__Deprecated:__
The following documentation regarding Entwine applies to legacy code only.
If you're developing new functionality in React powered sections please refer to
[ReactJS in SilverStripe](./How_Tos/Extend_CMS_Interface.md#reactjs-in-silverstripe).

[jQuery.entwine](https://github.com/hafriedlander/jquery.entwine) is a thirdparty library
which allows us to attach behaviour to DOM elements in a flexible and structured mannger.
It replaces the `behaviour.js` library used in previous versions of the CMS interface.
See [JavaScript Development](/developer_guides/customising_the_admin_interface/javascript_development) for more information on how to use it.
In the CMS interface, all entwine rules should be placed in the "ss" entwine namespace.
If you want to call methods defined within these rules outside of entwine logic,
you have to use this namespace, e.g. `$('.cms-menu').entwine('ss').collapse()`.

Note that only functionality that is custom to the CMS application needs to be built
in jQuery.entwine, we're trying to reuse library code wherever possible.
The most prominent example of this is the usage of [jQuery UI](http://jqueryui.com) for
dialogs and buttons.

The CMS includes the jQuery.entwine inspector. Press Ctrl+` ("backtick") to bring down the inspector.
You can then click on any element in the CMS to see which entwine methods are bound to
any particular element.

## JavaScript and CSS dependencies via Requirements and Ajax

The JavaScript logic powering the CMS is divided into many files,
which typically are included via the [Requirements](api:SilverStripe\View\Requirements) class, by adding
them to [LeftAndMain::init()](api:SilverStripe\Admin\LeftAndMain::init()) and its subclassed methods.
This class also takes care of minification and combination of the files,
which is crucial for the CMS performance (see [Requirements::combine_files()](api:SilverStripe\View\Requirements::combine_files())).

Due to the procedural and selector-driven style of UI programming in jQuery.entwine,
it can be difficult to find the piece of code responsible for a certain behaviour.
Therefore it is important to adhere to file naming conventions.
E.g. a feature only applicable to `ModelAdmin` should be placed in
`vendor/silverstripe/framework/admin/javascript/src/ModelAdmin.js`, while something modifying all forms (including ModelAdmin forms)
would be better suited in `vendor/silverstripe/framework/admin/javascript/src/LeftAndMain.EditForm.js`.
Selectors used in these files should mirrow the "scope" set by its filename,
so don't place a rule applying to all form buttons inside `ModelAdmin.js`.

The CMS relies heavily on Ajax-loading of interfaces, so each interface and the JavaScript
driving it have to assume its underlying DOM structure is appended via an Ajax callback
rather than being available when the browser window first loads.
jQuery.entwine is effectively an advanced version of [jQuery.live](http://api.jquery.com/live/)
and [jQuery.delegate](http://api.jquery.com/delegate/), so takes care of dynamic event binding.

Most interfaces will require their own JavaScript and CSS files, so the Ajax loading has
to ensure they're loaded unless already present. A custom-built library called
`jQuery.ondemand` (located in `vendor/silverstripe/framework/thirdparty`) takes care of this transparently -
so as a developer just declare your dependencies through the [Requirements](api:SilverStripe\View\Requirements) API.

## Client-side routing

SilverStripe uses the HTML5 browser history to modify the URL without a complete
window refresh. We us the below systems in combination to achieve this:
  * [Page.js](https://github.com/visionmedia/page.js) routing library is used for most
    cms sections, which provides additional SilverStripe specific functionality via the
    `vendor/silverstripe/admin/client/src/lib/Router.js` wrapper.
	The router is available on `window.ss.router` and provides the same API as
	described in the
	[Page.js docs](https://github.com/visionmedia/page.js/blob/master/Readme.md#api).
  * [React router](https://github.com/reactjs/react-router) is used for react-powered
    CMS sections. This provides a native react-controlled bootstrapping and route handling
    system that works most effectively with react components. Unlike page.js routes, these
    may be lazy-loaded or registered during the lifetime of the application on the
    `window.ss.routeRegister` wrapper. The `history` object is passed to react components.

### Registering routes

### page.js (non-react) CMS sections

CMS sections that rely on entwine, page.js, and normal ajax powered content loading mechanisms
(such as modeladmin) will typically have a single wildcard route that initiates the pajax loading
mechanism.

The main place that routes are registered are via the `LeftAndMain::getClientConfig()` overridden method,
which by default registers a single 'url' route. This will generate a wildcard route handler for each CMS
section in the form `/admin/<section>(/*)?`, which will capture any requests for this section.

Additional routes can be registered like so `window.ss.router('admin/pages', callback)`, however
these must be registered prior to `window.onload`, as they would otherwise be added with lower priority
than the wildcard routes, as page.js prioritises routes in order of registration, not by specificity.
Once registered, routes can we called with `windw.ss.router.show('admin/pages')`.

Route callbacks are invoked with two arguments, `context` and `next`. The [context object](https://github.com/visionmedia/page.js/blob/master/Readme.md#context)
can be used to pass state between route handlers and inspect the current
history state. The `next` function invokes the next matching route. If `next`
is called when there is no 'next' route, a page refresh will occur.

### React router CMS sections

Similarly to page.js powered routing, the main point of registration for react routing
sections is the `LeftAndMain::getClientConfig()` overridden method, which controls the main
routing mechanism for this section. However, there are two major differences:

Firstly, `reactRouter` must be passed as a boolean flag to indicate that this section is
controlled by the react section, and thus should suppress registration of a page.js route
for this section.

```php
public function getClientConfig() 
{
    return array_merge(parent::getClientConfig(), [
        'reactRouter' => true
    ]);
}
```

Secondly, you should ensure that your react CMS section triggers route registration on the client side
with the reactRouteRegister component. This will need to be done on the `DOMContentLoaded` event
to ensure routes are registered before window.load is invoked. 

```js
import { withRouter } from 'react-router';
import ConfigHelpers from 'lib/Config';
import reactRouteRegister from 'lib/ReactRouteRegister';
import MyAdmin from './MyAdmin';

document.addEventListener('DOMContentLoaded', () => {
    const sectionConfig = ConfigHelpers.getSection('MyAdmin');

    reactRouteRegister.add({
        path: sectionConfig.url,
        component: withRouter(MyAdminComponent),
        childRoutes: [
            { path: 'form/:id/:view', component: MyAdminComponent },
        ],
    });
});
```

Child routes can be registered post-boot by using `ReactRouteRegister` in the same way.

```js
// Register a nested url under `sectionConfig.url`
const sectionConfig = ConfigHelpers.getSection('MyAdmin');
reactRouteRegister.add({
    path: 'nested',
    component: NestedComponent,
}, [ sectionConfig.url ]);
```

## PJAX: Partial template replacement through Ajax

Many user interactions can change more than one area in the CMS.
For example, editing a page title in the CMS form changes it in the page tree
as well as the breadcrumbs. In order to avoid unnecessary processing,
we often want to update these sections independently from their neighbouring content.

In order for this to work, the CMS templates declare certain sections as "PJAX fragments"
through a `data-pjax-fragment` attribute. These names correlate to specific
rendering logic in the PHP controllers, through the [PjaxResponseNegotiator](api:SilverStripe\Control\PjaxResponseNegotiator) class.

Through a custom `X-Pjax` HTTP header, the client can declare which view they're expecting,
through identifiers like `CurrentForm` or `Content` (see [LeftAndMain::getResponseNegotiator()](api:SilverStripe\Admin\LeftAndMain::getResponseNegotiator())).
These identifiers are passed to `loadPanel()` via the `pjax` data option.
The HTTP response is a JSON object literal, with template replacements keyed by their Pjax fragment.
Through PHP callbacks, we ensure that only the required template parts are actually executed and rendered.
When the same URL is loaded without Ajax (and hence without `X-Pjax` headers),
it should behave like a normal full page template, but using the same controller logic.

Example: Create a bare-bones CMS subclass which shows breadcrumbs (a built-in method),
as well as info on the current record. A single link updates both sections independently
in a single Ajax request.


```php
use SilverStripe\Admin\LeftAndMain;

// app/code/MyAdmin.php
class MyAdmin extends LeftAndMain 
{
    private static $url_segment = 'myadmin';
    public function getResponseNegotiator() 
    {
        $negotiator = parent::getResponseNegotiator();
        $controller = $this;
        // Register a new callback
        $negotiator->setCallback('MyRecordInfo', function() use(&$controller) {
            return $controller->MyRecordInfo();
        });
        return $negotiator;
    }
    public function MyRecordInfo() 
    {
        return $this->renderWith('MyRecordInfo');
    }
}
```

```js
// MyAdmin.ss
<% include SilverStripe\\Admin\\CMSBreadcrumbs %>
<div>Static content (not affected by update)</div>
<% include MyRecordInfo %>
<a href="{$AdminURL}myadmin" class="cms-panel-link" data-pjax-target="MyRecordInfo,Breadcrumbs">
    Update record info
</a>
```    

```ss
// MyRecordInfo.ss
<div data-pjax-fragment="MyRecordInfo">
    Current Record: $currentPage.Title
</div>
```

A click on the link will cause the following (abbreviated) ajax HTTP request:

```
GET /admin/myadmin HTTP/1.1
X-Pjax:MyRecordInfo,Breadcrumbs
X-Requested-With:XMLHttpRequest
```
... and result in the following response:

```
{"MyRecordInfo": "<div...", "CMSBreadcrumbs": "<div..."}
```
Keep in mind that the returned view isn't always decided upon when the Ajax request
is fired, so the server might decide to change it based on its own logic,
sending back different `X-Pjax` headers and content.

On the client, you can set your preference through the `data-pjax-target` attributes
on links or through the `X-Pjax` header. For firing off an Ajax request that is
tracked in the browser history, use the `pjax` attribute on the state data.

```js
$('.cms-container').loadPanel(ss.config.adminUrl+'pages', null, {pjax: 'Content'});
```

## Loading lightweight PJAX fragments

Normal navigation between URLs in the admin section of the Framework occurs through `loadPanel` and `submitForm`.
These calls make sure the HTML5 history is updated correctly and back and forward buttons work. They also take
care of some automation, for example restoring currently selected tabs.

However there are situations when you would like to only update a small area in the CMS, and when this operation should
not trigger a browser's history pushState. A good example here is reloading a dropdown that relies on backend session
information that could have been updated as part of action elsewhere, updates to sidebar status, or other areas
unrelated to the main flow.

In this case you can use the `loadFragment` call supplied by `LeftAndMain.js`. You can trigger as many of these in
parallel as you want. This will not disturb the main navigation.

```js
$('.cms-container').loadFragment(ss.config.adminUrl+'foobar/', 'Fragment1');
$('.cms-container').loadFragment(ss.config.adminUrl+'foobar/', 'Fragment2');
$('.cms-container').loadFragment(ss.config.adminUrl+'foobar/', 'Fragment3');
```

The ongoing requests are tracked by the PJAX fragment name (Fragment1, 2, and 3 above) - resubmission will
result in the prior request for this fragment to be aborted. Other parallel requests will continue undisturbed.

You can also load multiple fragments in one request, as long as they are to the same controller (i.e. URL):

```js
$('.cms-container').loadFragment(ss.config.adminUrl+'foobar/', 'Fragment2,Fragment3');
```

This counts as a separate request type from the perspective of the request tracking, so will not abort the singular
`Fragment2` nor `Fragment3`.

Upon the receipt of the response, the fragment will be injected into DOM where a matching `data-pjax-fragment` attribute
has been found on an element (this element will get completely replaced). Afterwards a `afterloadfragment` event
will be triggered. In case of a request error a `loadfragmenterror` will be raised and DOM will not be touched.

You can hook up a response handler that obtains all the details of the XHR request via Entwine handler:

```js
'from .cms-container': {
    onafterloadfragment: function(e, data) {
        // Say 'success'!
        alert(data.status);
    }
}
```

Alternatively you can use the jQuery deferred API:

```js
$('.cms-container')
    .loadFragment(ss.config.adminUrl+'foobar/', 'Fragment1')
    .success(function(data, status, xhr) {
        // Say 'success'!
        alert(status);
    });
```

## Ajax Redirects

Sometimes, a server response represents a new URL state, e.g. when submitting an "add record" form,
the resulting view will be the edit form of the new record. On non-ajax submissions, that's easily
handled through a HTTP redirection. On ajax submissions, browsers handle these redirects
transparently, so the CMS JavaScript doesn't know about them (or the new URL).
To work around this, we're using a custom `X-ControllerURL` HTTP response header
which can declare a new URL. If this header is set, the CMS JavaScript will
push the URL to its history stack, causing the logic to fetch it in a subsequent ajax request.
Note: To avoid double processing, the first response body is usually empty.

## State through HTTP response metadata

By loading mostly HTML responses, we don't have an easy way to communicate
information which can't be directly contained in the produced HTML.
For example, the currently used controller class might've changed due to a "redirect",
which affects the currently active menu entry. We're using HTTP response headers to contain this data
without affecting the response body.


```php
use SilverStripe\Admin\LeftAndMain;

class MyController extends LeftAndMain 
{
    class myaction() 
    {
        // ...
        $this->getResponse()->addHeader('X-Controller', 'MyOtherController');
        return $html;
    }
}
```

Built-in headers are:

 * `X-Title`: Set window title (requires URL encoding)
 * `X-Controller`: PHP class name matching a menu entry, which is marked active
 * `X-ControllerURL`: Alternative URL to record in the HTML5 browser history
 * `X-Status`: Extended status information, used for an information popover.
 * `X-Reload`: Force a full page reload based on `X-ControllerURL`

## Special Links

Some links should do more than load a new page in the browser window.
To avoid repetition, we've written some helpers for various use cases:

 * Load into a PJAX panel: `<a href="..." class="cms-panel-link" data-pjax-target="Content">`
 * Load URL as an iframe into a popup/dialog: `<a href="..." class="ss-ui-dialog-link">`
 * GridField click to redirect to external link: `<a href="..." class="cms-panel-link action external-link">`

## Buttons

SilverStripe automatically applies a [jQuery UI button style](http://jqueryui.com/demos/button/)
to all elements with the class `.ss-ui-button`. We've extended the jQuery UI widget a bit
to support defining icons via HTML5 data attributes (see `ssui.core.js`).
These icon identifiers relate to icon files in `vendor/silverstripe/framework/admin/images/sprites/src/btn-icons`,
and are sprited into a single file through SCSS and [sprity](https://www.npmjs.com/package/sprity)
(sprites are compiled with `yarn run build`). There are classes set up to show the correct sprite via
background images (see `vendor/silverstripe/framework/admin/scss/_sprites.scss`).

Input: `<a href="..." class="ss-ui-button" data-icon="add" />Button text</a>`

Output: `<a href="..." data-icon="add" class="ss-ui-button ss-ui-action-constructive ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button"><span class="ui-button-icon-primary ui-icon btn-icon-add"></span><span class="ui-button-text">Button text</span></a>`

Note that you can create buttons from pretty much any element, although
when using an input of type button, submit or reset, support is limited to plain text labels with no icons.

## Menu

The navigation menu in the CMS is created through the [CMSMenu](api:SilverStripe\Admin\CMSMenu) API,
which auto-detects all subclasses of `LeftAndMain`. This means that your custom
`ModelAdmin` subclasses will already appear in there without any explicit definition.
To modify existing menu entries or create new ones, see [CMSMenu::add_menu_item()](api:SilverStripe\Admin\CMSMenu::add_menu_item())
and [CMSMenu::remove_menu_item()](api:SilverStripe\Admin\CMSMenu::remove_menu_item()).

New content panels are typically loaded via Ajax, which might change
the current menu context. For example, a link to edit a file might be clicked
within a page edit form, which should change the currently active menu entry
from "Page" to "Files & Images". To communicate this state change, a controller
response has the option to pass along a special HTTP response header,
which is picked up by the menu:


```php
public function mycontrollermethod() 
{
    // .. logic here
    $this->getResponse()->addHeader('X-Controller', 'AssetAdmin');
    return 'my response';
}
```

This is usually handled by the existing [LeftAndMain](api:SilverStripe\Admin\LeftAndMain) logic,
so you don't need to worry about it. The same concept applies for
'X-Title' (change the window title) and 'X-ControllerURL' (change the URL recorded in browser history).
Note: You can see any additional HTTP headers through the web developer tools in your browser of choice.

## Tree

The CMS tree for viewing hierarchical structures (mostly pages) is powered
by the [jstree](http://jstree.com) library. It is configured through
`client/src/legacy/LeftAndMain.Tree.js` in the `silverstripe/admin` module, as well as some
HTML5 metadata generated on its container (see the `data-hints` attribute).
For more information, see the [Howto: Customise the CMS tree](/developer_guides/customising_the_admin_interface/how_tos/customise_cms_tree).

Note that a similar tree logic is also used for the
form fields to select one or more entries from those hierarchies
([TreeDropdownField](api:SilverStripe\Forms\TreeDropdownField) and [TreeMultiselectField](api:SilverStripe\Forms\TreeMultiselectField)).

## Tabs

We're using [jQuery UI tabs](http://jqueryui.com/), but in a customised fashion.
HTML with tabs can be created either directly through HTML templates in the CMS,
or indirectly through a [TabSet](api:SilverStripe\Forms\TabSet) form field. Since tabsets are useable
outside of the CMS as well, the baseline application of tabs happens via
a small wrapper around `jQuery.tabs()` stored in `TabSet.js`.

In the CMS however, tabs need to do more: They memorize their active tab
in the user's browser, and lazy load content via ajax once they're activated.

They also need to work across different "layout containers" (see above),
meaning a tab navigation might be in a layout header, while the tab
content is occupied by the main content area. jQuery assumes a common
parent in the DOM for both the tab navigation and its target DOM elements.
In order to achieve this level of flexibility, most tabsets in the CMS
use a custom template which leaves rendering the tab navigation to
a separate template: `CMSMain.ss`. See the "Forms" section above
for an example form.

Here's how you would apply this template to your own tabsets used in the CMS.
Note that you usually only need to apply it to the outermost tabset,
since all others should render with their tab navigation inline.

Form template with custom tab navigation (trimmed down):


```ss

<form $FormAttributes data-layout-type="border">

    <div class="cms-content-header north">
        <% if Fields.hasTabset %>
            <% with Fields.fieldByName('Root') %>
            <div class="cms-content-header-tabs">
                <ul>
                <% loop Tabs %>
                    <li><a href="#$id">$Title</a></li>
                <% end_loop %>
                </ul>
            </div>
            <% end_with %>
        <% end_if %>
    </div>

    <div class="cms-content-fields center">
        <fieldset>
            <% loop Fields %>$FieldHolder<% end_loop %>
        </fieldset>
    </div>

</form>
```

Tabset template without tab navigation (e.g. `CMSTabset.ss`)


```ss

<div $AttributesHTML>
    <% loop Tabs %>
        <% if Tabs %>
            $FieldHolder
        <% else %>
            <div $AttributesHTML>
                <% loop Fields %>
                    $FieldHolder
                <% end_loop %>
            </div>
        <% end_if %>
    <% end_loop %>
</div>
```

Lazy loading works based on the `href` attribute of the tab navigation.
The base behaviour is applied through adding a class `.cms-tabset` to a container.
Assuming that each tab has its own URL which is tracked in the HTML5 history,
the current tab display also has to work when loaded directly without Ajax.
This is achieved by template conditionals (see "MyActiveCondition").
The `.cms-panel-link` class will automatically trigger the ajax loading,
and load the HTML content into the main view. Example:


```ss

<div id="my-tab-id" class="cms-tabset" data-ignore-tab-state="true">
    <ul>
        <li class="<% if MyActiveCondition %> ui-tabs-active<% end_if %>">
            <a href="{$AdminURL}mytabs/tab1" class="cms-panel-link">
                Tab1
            </a>
        </li>
        <li class="<% if MyActiveCondition %> ui-tabs-active<% end_if %>">
            <a href="{$AdminURL}mytabs/tab2" class="cms-panel-link">
                Tab2
            </a>
        </li>
    </ul>
</div>
```

The URL endpoints `{$AdminURL}mytabs/tab1` and `{$AdminURL}mytabs/tab2`
should return HTML fragments suitable for inserting into the content area,
through the `PjaxResponseNegotiator` class (see above).


## Related

 * [Howto: Extend the CMS Interface](/developer_guides/customising_the_admin_interface/how_tos/extend_cms_interface)
 * [Howto: Customise the CMS tree](/developer_guides/customising_the_admin_interface/how_tos/customise_cms_tree)
 * [ModelAdmin API](api:SilverStripe\Admin\ModelAdmin)
 * [Reference: Layout](/developer_guides/customising_the_admin_interface/cms_layout)
 * [Rich Text Editing](/developer_guides/forms/field_types/htmleditorfield)
