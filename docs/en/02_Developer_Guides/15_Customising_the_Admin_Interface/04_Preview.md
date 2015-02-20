# CMS preview

## Overview

With the addition of side-by-side editing, the preview has the ability to appear
within the CMS window when editing content in the _Pages_ section of the CMS.
The site is rendered into an iframe. It will update itself whenever the content
is saved, and relevant pages will be loaded for editing when the user navigates
around in the preview.

The root element for preview is `.cms-preview` which maintains the internal
states necessary for rendering within the entwine properties. It provides
function calls for transitioning between these states and has the ability to
update the appearance of the option selectors.

In terms of backend support, it relies on `SilverStripeNavigator` to be rendered
into the `.cms-edit-form`. _LeftAndMain_ will automatically take care of
generating it as long as the `*_SilverStripeNavigator` template is found -
first segment has to match current _LeftAndMain_-derived class (e.g.
`LeftAndMain_SilverStripeNavigator`).

We use `ss.preview` entwine namespace for all preview-related entwines.

<div class="notice" markdown='1'>
Caveat: `SilverStripeNavigator` and `CMSPreviewable` interface currently only
support SiteTree objects that are _Versioned_.  They are not general enough for
using on any other DataObject. That pretty much limits the extendability of the
feature.
</div>

## Configuration and Defaults

Like most of the CMS, the preview UI is powered by
[jQuery entwine](https://github.com/hafriedlander/jquery.entwine). This means
its defaults are configured through JavaScript, by setting entwine properties.
In order to achieve this, create a new file `mysite/javascript/MyLeftAndMain.Preview.js`.

In the following example we configure three aspects:

 * Set the default mode from "split view" to a full "edit view"
 * Make a wider mobile preview
 * Increase minimum space required by preview before auto-hiding

Note how the configuration happens in different entwine namespaces
("ss.preview" and "ss"), as well as applies to different selectors
(".cms-preview" and ".cms-container").

	:::js
	(function($) {
		$.entwine('ss.preview', function($){
			$('.cms-preview').entwine({
				DefaultMode: 'content',
				getSizes: function() {
					var sizes = this._super();
					sizes.mobile.width = '400px';
					return sizes;
				}
			});
		});
		$.entwine('ss', function($){
			$('.cms-container').entwine({
				getLayoutOptions: function() {
					var opts = this._super();
					opts.minPreviewWidth = 600;
					return opts;
				}
			});
		});
	}(jQuery));

Load the file in the CMS via setting adding 'mysite/javascript/MyLeftAndMain.Preview.js'
to the `LeftAndMain.extra_requirements_javascript` [configuration value](/topics/configuration)

	:::yml
	LeftAndMain:
	  extra_requirements_javascript:
	    - mysite/javascript/MyLeftAndMain.Preview.js

In order to find out which configuration values are available, the source code
is your best reference at the moment - have a look in `framework/admin/javascript/LeftAndMain.Preview.js`.
To understand how layouts are handled in the CMS UI, have a look at the
[CMS Architecture](/reference/cms-architecture) guide.

## Enabling preview

The frontend decides on the preview being enabled or disabled based on the
presence of the `.cms-previewable` class. If this class is not found the preview
will remain hidden, and the layout will stay in the _content_ mode.

If the class is found, frontend looks for the `SilverStripeNavigator` structure
and moves it to the `.cms-preview-control` panel at the bottom of the preview.
This structure supplies preview options such as state selector.

If the navigator is not found, the preview appears in the GUI, but is shown as
"blocked" - i.e. displaying the "preview unavailable" overlay.

The preview can be affected by calling `enablePreview` and `disablePreview`. You
can check if the preview is active by inspecting the `IsPreviewEnabled` entwine
property.

## Preview states

States are the site stages: _live_, _stage_ etc. Preview states are picked up
from the `SilverStripeNavigator`. You can invoke the state change by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').changeState('StageLink');
	```

Note the state names come from `SilverStripeNavigatorItems` class names - thus
the _Link_ in their names. This call will also redraw the state selector to fit
with the internal state. See `AllowedStates` in `.cms-preview` entwine for the
list of supported states.

You can get the current state by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').getCurrentStateName();
	```

## Preview sizes

This selector defines how the preview iframe is rendered, and try to emulate
different device sizes. The options are hardcoded. The option names map directly
to CSS classes applied to the `.cms-preview` and are as follows:

* _auto_: responsive layout
* _desktop_
* _tablet_
* _mobile_

You can switch between different types of display sizes programmatically, which
has the benefit of redrawing the related selector and maintaining a consistent
internal state:

	```js
	$('.cms-preview').entwine('.ss.preview').changeSize('auto');
	```

You can find out current size by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').getCurrentSizeName();
	```

## Preview modes

Preview modes map to the modes supported by the _threeColumnCompressor_ layout
algorithm, see [layout reference](../reference/layout) for more details. You
can change modes by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').changeMode('preview');
	```

Currently active mode is stored on the `.cms-container` along with related
internal states of the layout. You can reach it by calling:

	```js
	$('.cms-container').entwine('.ss').getLayoutOptions().mode;
	```

<div class="notice" markdown='1'>
Caveat: the `.preview-mode-selector` appears twice, once in the preview and
second time in the CMS actions area as `#preview-mode-dropdown-in-cms`. This is
done because the user should still have access to the mode selector even if
preview is not visible. Currently CMS Actions are a separate area to the preview
option selectors, even if they try to appear as one horizontal bar.
</div>

## Preview API

Namespace `ss.preview`, selector `.cms-preview`:

* **getCurrentStateName**: get the name of the current state (e.g. _LiveLink_ or _StageLink_).
* **getCurrentSizeName**: get the name of the current device size.
* **getIsPreviewEnabled**: check if the preview is enabled.
* **changeState**: one of the `AllowedStates`.
* **changeSize**: one of _auto_, _desktop_, _tablet_, _mobile_.
* **changeMode**: maps to _threeColumnLayout_ modes - _split_, _preview_, _content_.
* **enablePreview**: activate the preview and switch to the _split_ mode. Try to load the relevant URL from the content.
* **disablePreview**: deactivate the preview and switch to the _content_ mode. Preview will re-enable itself when new
previewable content is loaded.

## Related

 * [Reference: Layout](../reference/layout)
