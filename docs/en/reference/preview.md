# CMS preview

## Overview

With the addition of side-by-side editing, the preview has the ability to appear within the CMS window when editing
content in the _Pages_ section of the CMS. The site is rendered into an iframe. It will update itself whenever the
content is saved, and relevant pages will be loaded for editing when the user navigates around in the preview.

The root element for preview is `.cms-preview` which maintains the internal states neccessary for rendering. It provides
function calls for transitioning between these states and has the ability to redraw the area.

In terms of backend support, it relies on `SilverStripeNavigator` to be rendered into the `.cms-edit-form`.
_LeftAndMain_ will automatically take care of generating it as long as the `*_SilverStripeNavigator` template is found -
first segment has to match current _LeftAndMain_-derived class (e.g. `LeftAndMain_SilverStripeNavigator`).

<div class="notice" markdown='1'>
Caveat: `SilverStripeNavigator` and `CMSPreviewable` interface currently only support SiteTree objects that are
_Versioned_.  They are not general enough for using on any other DataObject. That pretty much limits the extendability
of the feature.
</div>

If the `SilverStripeNavigator` structure is found, it is detached and installed in the `.cms-preview-control` panel at
the bottom of the preview, and the preview is enabled into _split_ mode.

We use `ss.preview` entwine namespace for all preview-related entwines.

## Preview states

States are the site stages: _live_, _stage_ etc. Preview states are picked up from the `SilverStripeNavigator`. 
You can invoke the state change by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').changeState('StageLink');
	```

Note the state names come from `SilverStripeNavigatorItems` class names - thus the _Link_ in their names. This call will
also redraw the state selector to fit with the internal state. See `AllowedStates` in `.cms-preview` entwine for the
list of supported states.

You can get the current state by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').getCurrentStateName();
	```

## Preview sizes

This selector defines how the preview iframe is rendered, and try to emulate different device sizes. The options are
hardcoded. The option names map directly to CSS classes applied to the `.cms-preview` and are as follows:

* _auto_: responsive layout
* _desktop_
* _tablet_
* _mobile_

You can switch between different types of display sizes programmatically, which has the benefit of redrawing the
related selector and maintaining a consistent internal state:

	```js
	$('.cms-preview').entwine('.ss.preview').changeSize('auto');
	```

You can find out current size by calling:

	```js
	$('.cms-preview').entwine('.ss.preview').getCurrentSizeName();
	```

## Preview modes

Preview modes map to the modes supported by the _threeColumnCompressor_ layout algorithm, see
[layout reference](../reference/layout) for more details. You can change modes by calling: 

	```js
	$('.cms-preview').entwine('.ss.preview').changeMode('preview');
	```

Currently active mode is stored on the `.cms-container` along with related internal states of the layout. You can reach
it by calling:

	```js
	$('.cms-container').entwine('.ss').getLayoutOptions().mode;
	```

<div class="notice" markdown='1'>
Caveat: the `.preview-mode-selector` appears twice, once in the preview and second time in the CMS actions area as
`#preview-mode-dropdown-in-cms`. This is done because the user should still have access to the mode selector even if
preview is not visible. Currently CMS Actions are a separate area to the preview option selectors, even if they try
to appear as one horizontal bar.
</div>

## Preview API

Namespace `ss.preview`, selector `.cms-preview`:

* **getCurrentStateName**: get the name of the current state (e.g. _LiveLink_ or _StageLink_).
* **getCurrentSizeName**: get the name of the current device size.
* **changeState**: one of the `AllowedStates`.
* **changeSize**: one of _auto_, _desktop_, _tablet_, _mobile_.
* **changeMode**: maps to _threeColumnLayout_ modes - _split_, _preview_, _content_.

## Related

 * [Reference: Layout](../reference/layout)
