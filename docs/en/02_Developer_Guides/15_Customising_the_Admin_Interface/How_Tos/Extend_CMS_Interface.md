# How to extend the CMS interface

## Introduction

The CMS interface works just like any other part of your website: It consists of
PHP controllers, templates, CSS stylesheets and JavaScript. Because it uses the
same base elements, it is relatively easy to extend.

As an example, we're going to add a permanent "bookmarks" link list to popular pages
into the main CMS menu. A page can be bookmarked by a CMS author through a
simple checkbox.

For a deeper introduction to the inner workings of the CMS, please refer to our
guide on [CMS Architecture](/developer_guides/customising_the_admin_interface/cms_architecture).

## Redux Devtools

It's important to be able to view the state of the React application when you're debugging and
building the interface.

To be able to view the state, you'll need to be in a dev environment 
and have the [Redux Devtools](https://github.com/zalmoxisus/redux-devtools-extension)
installed on Google Chrome or Firefox, which can be found by searching with your favourite search
engine.

## Overload a CMS template

If you place a template with an identical name into your application template
directory (usually `mysite/templates/`), it'll take priority over the built-in
one.

CMS templates are inherited based on their controllers, similar to subclasses of
the common `Page` object (a new PHP class `MyPage` will look for a `MyPage.ss` template).
We can use this to create a different base template with `LeftAndMain.ss`
(which corresponds to the `LeftAndMain` PHP controller class).

Copy the template markup of the base implementation at `framework/admin/templates/Includes/LeftAndMain_Menu.ss`
into `mysite/templates/Includes/LeftAndMain_Menu.ss`. It will automatically be picked up by
the CMS logic. Add a new section into the `<ul class="cms-menu-list">`

	:::ss
	...
	<ul class="cms-menu-list">
		<!-- ... -->
		<li class="bookmarked-link first">
			<a href="{$AdminURL}pages/edit/show/1">Edit "My popular page"</a>
		</li>
		<li class="bookmarked-link last">
			<a href="{$AdminURL}pages/edit/show/99">Edit "My other page"</a>
		</li>
	</ul>
	...

Refresh the CMS interface with `admin/?flush=all`, and you should see those
hardcoded links underneath the left-hand menu. We'll make these dynamic further down.

## Include custom CSS in the CMS

In order to show the links a bit separated from the other menu entries,
we'll add some CSS, and get it to load
with the CMS interface. Paste the following content into a new file called
`mysite/css/BookmarkedPages.css`:

	:::css
	.bookmarked-link.first {margin-top: 1em;}

Load the new CSS file into the CMS, by setting the `LeftAndMain.extra_requirements_css`
[configuration value](../../configuration).

	:::yml
	LeftAndMain:
	  extra_requirements_css:
	    - mysite/css/BookmarkedPages.css

## Create a "bookmark" flag on pages

Now we'll define which pages are actually bookmarked, a flag that is stored in
the database. For this we need to decorate the page record with a
`DataExtension`. Create a new file called `mysite/code/BookmarkedPageExtension.php`
and insert the following code.

	:::php
	<?php

	class BookmarkedPageExtension extends DataExtension {

		private static $db = array(
			'IsBookmarked' => 'Boolean'
		);

		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldToTab('Root.Main',
				new CheckboxField('IsBookmarked', "Show in CMS bookmarks?")
			);
		}
	}

Enable the extension in your [configuration file](../../configuration)

	:::yml
	SiteTree:
	  extensions:
	    - BookmarkedPageExtension

In order to add the field to the database, run a `dev/build/?flush=all`.
Refresh the CMS, open a page for editing and you should see the new checkbox.

## Retrieve the list of bookmarks from the database

One piece in the puzzle is still missing: How do we get the list of bookmarked
pages from the database into the template we've already created (with hardcoded
links)? Again, we extend a core class: The main CMS controller called
`LeftAndMain`.

Add the following code to a new file `mysite/code/BookmarkedLeftAndMainExtension.php`;

	:::php
	<?php

	class BookmarkedPagesLeftAndMainExtension extends LeftAndMainExtension {

		public function BookmarkedPages() {
			return Page::get()->filter("IsBookmarked", 1);
		}
	}

Enable the extension in your [configuration file](../../configuration)

	:::yml
	LeftAndMain:
	  extensions:
	    - BookmarkedPagesLeftAndMainExtension

As the last step, replace the hardcoded links with our list from the database.
Find the `<ul>` you created earlier in `mysite/admin/templates/LeftAndMain.ss`
and replace it with the following:

	:::ss
	<ul class="cms-menu-list">
		<!-- ... -->
		<% loop $BookmarkedPages %>
		<li class="bookmarked-link $FirstLast">
			<li><a href="{$AdminURL}pages/edit/show/$ID">Edit "$Title"</a></li>
		</li>
		<% end_loop %>
	</ul>

## Extending the CMS actions

CMS actions follow a principle similar to the CMS fields: they are built in the
backend with the help of `FormFields` and `FormActions`, and the frontend is
responsible for applying a consistent styling.

The following conventions apply:

* New actions can be added by redefining `getCMSActions`, or adding an extension
with `updateCMSActions`.
* It is required the actions are contained in a `FieldSet` (`getCMSActions`
returns this already).
* Standalone buttons are created by adding a top-level `FormAction` (no such
button is added by default).
* Button groups are created by adding a top-level `CompositeField` with
`FormActions` in it.
* A `MajorActions` button group is already provided as a default.
* Drop ups with additional actions that appear as links are created via a
`TabSet` and `Tabs` with `FormActions` inside.
* A `ActionMenus.MoreOptions` tab is already provided as a default and contains
some minor actions.
* You can override the actions completely by providing your own
`getAllCMSFields`.

Let's walk through a couple of examples of adding new CMS actions in `getCMSActions`.

First of all we can add a regular standalone button anywhere in the set. Here
we are inserting it in the front of all other actions. We could also add a
button group (`CompositeField`) in a similar fashion.

	:::php
	$fields->unshift(FormAction::create('normal', 'Normal button'));

We can affect the existing button group by manipulating the `CompositeField`
already present in the `FieldList`.

	:::php
	$fields->fieldByName('MajorActions')->push(FormAction::create('grouped', 'New group button'));

Another option is adding actions into the drop-up - best place for placing
infrequently used minor actions.

	:::php
	$fields->addFieldToTab('ActionMenus.MoreOptions', FormAction::create('minor', 'Minor action'));

We can also easily create new drop-up menus by defining new tabs within the
`TabSet`.

	:::php
	$fields->addFieldToTab('ActionMenus.MyDropUp', FormAction::create('minor', 'Minor action in a new drop-up'));

<div class="hint" markdown='1'>
Empty tabs will be automatically removed from the `FieldList` to prevent clutter.
</div>

To make the actions more user-friendly you can also use alternating buttons as
detailed in the [CMS Alternating Button](cms_alternating_button)
how-to.

## React components

Some admin modules render their UI with React, a popular Javascript library created by Facebook. 
For these sections, rendering happens via client side scripts that create and inject HTML 
declaratively using data structures. These UI elements are known as "components" and 
represent the fundamental building block of a React-rendered interface.

For example, a component expressed like this:
```js
<PhotoItem size={200} caption={'Angkor Wat'} onSelect={openLightbox}>
	<img src="path/to/image.jpg" />
</PhotoItem>
```

Might actually render HTML that looks like this:
```html
<div class="photo-item">
	<div class="photo" style="width:200px;height:200px;">
		<img src="path/to/image.jpg">
	</div>
	<div class="photo-caption">
		<h3><a>Angkor Wat/a></h3>
	</div> 
</div>
```

This syntax is known as JSX. It is transpiled at build time into native Javascript calls
to the React API. While optional, it is recommended to express components this way.

This documentation will stop short of explaining React in-depth, as there is much better
documentation available all over the web. We recommend:
* [The Official React Tutorial](https://facebook.github.io/react/tutorial/tutorial.html)
* [Build With React](http://buildwithreact.com/tutorial)

#### A few words about ES6
The remainder of this tutorial is written in [ECMAScript 6](http://es6-features.org/#Constants), or _ES6_
for short. This is the new spec for Javascript (currently ES5) that is as of this writing
only partially implmented in modern browsers. Because it doesn't yet enjoy vast native support,
it has to be [transpiled](https://www.stevefenton.co.uk/2012/11/compiling-vs-transpiling/) in order to work
in a browser. This transpiling can be done using a variety of toolchains, but the basic
 principle is that a browser-ready, ES5 version of your code is generated in your dev
 environment as part of your workflow. Often called a "bundle," you should rarely have 
 to see this file. It is effectively an invisible layer that translates modern ES6
 code into something a browser can parse. As browsers evolve, this step will become less
 necessary. (Although, it is worth noting that because transpiling comes at such a low cost,
  and browsers are relatively slow to catch up, we'll probably be using it for the 
  foreseeable future in order to adopt new features beyond ES6).
   
   As stated above, there are many ways to solve the problem of transpiling. The toolchain
   we use in core SilverStripe modules includes:
   * [Babel](http://babeljs.io) (ES6 transpiler)
   * [Webpack](http://webpack.js.org) (Module bundler)
 
### Customising React components

React components can be customised in a similar way to PHP classes, using a dependency
injection API. The key difference is that components are not overriden the way backend
services are. Rather, new components are composed using [higher order components](https://facebook.github.io/react/docs/higher-order-components.html).
This has the inherent advantage of allowing all thidparty code to have an influence
over the behaviour, state, and UI of a component.

#### A simple higher order component

Using our example above, let's create a customised `PhotoItem` that allows a badge,
perhaps indicating that it is new to the gallery.

```js
const enhancePhoto = (PhotoItem) => (props) {
	const badge = props.isNew ? 
	  <div className="badge">New!</div> : 
	  null;

	return (
		<div>
			{badge}
			<PhotoItem {...props} />
		</div>
	);
}

const EnhancedPhotoItem = enhancedPhoto(PhotoItem);

<EnhancedPhotoItem isNew={true} size={300} />
```

Alternatively, this component could be expressed with an ES6 class, rather than a simple
function.

```js
const enhancePhoto = (PhotoItem) => {
	return class EnhancedPhotoItem extends React.Component {
		render() {
			const badge = this.props.isNew ? 
			  <div className="badge">New!</div> : 
			  null;

			return (
				<div>
					{badge}
					<PhotoItem {...this.props} />
				</div>
			);

		}
	}
}
```

When components are stateless, using a simple function in lieu of a class is recommended.

#### Using the injector to customise a core component

Let's make a more awesome text field. Because the `TextField` component is fetched 
through the injector, we can override it and augment it with our own functionality.

In this example, we'll add a simple character count below the text field.

First, let's create our higher order component.
__my-module/js/components/CharacterCounter.js__
```js
import React from 'react';

const CharacterCounter = (TextField) => (props) => {
    return (
        <div>
            <TextField {...props} />
            <small>Character count: {props.value.length}</small>
        </div>
    );
}

export default CharacterCounter;
```

Now let's add this higher order component to the injector. 

__my-module/js/main.js__
```js
import Injector from 'lib/Injector';
import CharacterCounter from './components/CharacterCounter';

Injector.update(
	{
		name: 'my-module',
	},
	wrap => {
		wrap('TextField', CharacterCounter);
	}
);
```

Much like the configuration layer, we need to specify a name for this mutation. This
will help other modules negotiate their priority over the injector in relation to yours.

The second parameter of the `update` argument is a callback which receives a `wrap()` function
that allows you to mutate the DI container with a wrapper for the component. Remember, this function does not _replace_
the component -- it enhances it with new functionality.

The last thing we'll have to do is make sure this script gets loaded into the admin
page.

__my-module/\_config/config.yml__

```yaml
    ---
    Name: my-module
    ---
    SilverStripe\Admin\LeftAndMain:
      extra_requirements_javascript:
        # The name of this file will depend on how you've configured your build process
        - 'my-module/js/dist/main.bundle.js'
```
Now that the customisation is applied, our text fields look like this:

![](../../../_images/react-di-1.png)

Let's add another customisation to TextField. If the text goes beyond a specified
length, let's throw a warning in the UI.

__my-module/js/components/TextLengthChecker.js__
```js
const TextLengthCheker = (TextField) => (props) => {  
  const {limit, value } = props;
  const invalid = limit !== undefined && value.length > limit;

  return (
    <div>
      <TextField {...props} />
      {invalid &&
        <span style={{color: 'red'}}>
          {`Text is too long! Must be ${limit} characters`}
        </span>
      }
    </div>
  );
}

export default TextLengthChecker;
```

We'll apply this one to the injector as well, but let's do it under a different name.
For the purposes of demonstration, let's imagine this customisation comes from another
module.

__my-module/js/main.js__
```js
import Injector from 'lib/Injector';
import TextLengthChecker from './components/TextLengthChecker';

Injector.update(
	{
		name: 'my-other-module',
	},
	wrap => {
		wrap('TextField', TextLengthChecker);
	}
);
```

Now, both components have applied themselves to the textfield.

![](../../../_images/react-di-2.png)


##### Getting multiple customisations to work together

Both these enhancements are nice, but what would be even better is if they could
work together collaboratively so that the character count only appeared when the user
input got within a certain range of the limit. In order to do that, we'll need to be
sure that the `TextLengthChecker` customisation is loaded ahead of the `CharacterCounter` 
customisation. 

First let's update the character counter to show characters _remaining_, which is
much more useful. We'll also update the API to allow a `warningBuffer` prop. This is
the amount of characters the input can be within the `limit` before the warning shows.

__my-module/js/components/CharacterCounter.js__
```js
import React from 'react';

const CharacterCounter = (TextField) => (props) => {
    const { warningBuffer, limit, value: { length } } = props;
    const remainingChars = limit - length;
    const showWarning = length + warningBuffer >= limit;
    return (
        <div>
            <TextField {...props} />
            {showWarning &&
            	<small>Characters remaining: {remainingChars}</small>
            }
        </div>
    );
}

export default CharacterCounter;
```

Now, when we apply this customisation, we need to be sure it loads _after_ the length
checker in the middleware chain, as it relies on the prop `limit`. We can do that by specifying priority using `before` and `after`
metadata to the customisation.

__my-module/js/main.js__
```js
import Injector from 'lib/Injector';
import CharacterCounter from './components/CharacterCounter';
import TextLengthChecker from './components/TextLengthChecker';
Injector.update(
	{
		name: 'my-module',
		after: 'my-other-module',
	},
	wrap => {
		wrap('TextField', CharacterCounter);
	}
);
Injector.update(
	{
		name: 'my-other-module',
	    before: 'my-module',
	},
	wrap => {
		wrap('TextField', TextLengthChecker);
	}
);
```

Now, both components play together nicely.

![](../../../_images/react-di-3.png)

### Registering new React components

If you've created a module using React, it's a good idea to afford other developers an 
API to enhance those components. To do that, simply register them with `Injector`.

__my-public-module/js/main.js__
```js
import Injector from 'lib/Injector';

Injector.register('MyComponent', MyComponent);
```

Now other developers can customise your components with `Injector.update()`.

Note: Overwriting components by calling `register()` multiple times for the same
service name is discouraged, and will throw an error. Should you really need to do this,
you can pass `{ force: true }` as the third argument to the `register()` function.

### Using the injector within your component

If your component has dependencies, you can add the injector via context using the `withInjector`
higher order component.

__my-module/js/components/Gallery.js__
```js
import React from 'react';
import { withInjector } from 'lib/Injector';

class Gallery extends React.Component {
  render() {
    const GalleryItem = this.context.injector.get('GalleryItem');
    return (
      <div>      
      {this.props.items.map(item => (
        <GalleryItem title={item.title} image={item.image} />
      ))}
      </div>
    );
  }
}

export default withInjector(Gallery);
```


### Interfacing with legacy CMS JavaScript

One of the great things about ReactJS is that it works great with DOM based libraries like jQuery and Entwine. To allow legacy-land scripts to notify your React component about changes, add the following.

__my-component.js__
```javascript
import SilverStripeComponent from 'silverstripe-component';

class MyComponent extends SilverStripeComponent {
	componentDidMount() {
		super.componentDidMount();
	}
	
	componentWillUnmount() {
		super.componentWillUnmount();
	}
}

export default MyComponent;
```

This is functionally no different from the first example. But it's a good idea to be explicit and add these `super` calls now. You will inevitably add `componentDidMount` and `componentWillUnmount` hooks to your component and it's easy to forget to call `super` then.

So what's going on when we call those? Glad you asked. If you've passed `cmsEvents` into your component's `props`, wonderful things will happen.

Let's take a look at some examples.

### Getting data into a component

Sometimes you'll want to call component methods when things change in legacy-land. For example when a CMS tab changes you might want to update some component state.

__main.js__
```javascript
import $ from 'jquery';
import React, { PropTypes, Component } from 'react';
import MyComponent from './my-component';

$.entwine('ss', function ($) {
	$('.my-component-wrapper').entwine({
		getProps: function (props) {
			var defaults = {
				cmsEvents: {
					'cms.tabchanged': function (event, title) {
						// Call a Redux action to update state.
					}
				}
			};
			
			return $.extend(true, defaults, props);
		},
		onadd: function () {
			var props = this.getProps();
			
			React.render(
				<MyComponent {...props} />,
				this[0]
			);
		}
	});
});
```

__legacy.js__
```javascript
(function ($) {
	$.entwine('ss', function ($) {
		$('.cms-tab').entwine({
			onclick: function () {
				$(document).trigger('cms.tabchanged', this.find('.title').text());
			}
		});
	});
}(jQuery));
```

Each key in `props.cmsEvents` gets turned into an event listener by `SilverStripeComponent.componentDidMount`. When a legacy-land script triggers that event on `document`, the associated component callback is invoked, with the component's context bound to it.

All `SilverStripeComponent.componentWillUnmount` does is clean up the event listeners when they're no longer required.

There are a couple of important things to note here:

1. Both files are using the same `ss` namespace.
2. Default properties are defined using the `getProps` method.

This gives us the flexibility to add and override event listeners from legacy-land. We're currently updating the current tab's title when `.cms-tab` is clicked. But say we also wanted to highlight the tab. We could do something like this.

__legacy.js__
```javascript
(function ($) {
	$.entwine('ss', function ($) {
		$('.main .my-component-wrapper').entwine({
			getProps: function (props) {
				return this._super({
					cmsEvents: {
						'cms.tabchanged': function (event, title) {
							// Call a Redux action to update state.
						}
					}
				});
			}
		});
		
		$('.cms-tab').entwine({
			onclick: function () {
				$(document).trigger('cms.tabchanged', this.find('.title').text());
			}
		});
	});
}(jQuery));
```

Here we're using Entwine to override the `getProps` method in `main.js`. Note we've made the selector more specific `.main .my-component-wrapper`. The most specific selector comes first in Entwine, so here our new `getProps` gets called, which passes the new callback to the `getProps` method defined in `main.js`.

### Getting data out of a component

There are times you'll want to update things in legacy-land when something changes in you component.

`SilverStripeComponent` has a handly method `emitCmsEvents` to help with this.

__my-component.js__
```javascript
import SilverStripeComponent from 'silverstripe-component';

class MyComponent extends SilverStripeComponent {
	componentDidMount() {
		super.componentDidMount();
	}
	
	componentWillUnmount() {
		super.componentWillUnmount();
	}
	
	componentDidUpdate() {
		this.emitCmsEvent('my-component.title-changed', this.state.title);
	}
}

export default MyComponent;
```

__legacy.js__
```javascript
(function ($) {
	$.entwine('ss', function ($) {
		$('.cms-tab').entwine({
			onmatch: function () {
				var self = this;

				$(document).on('my-component.title-changed', function (event, title) {
					self.find('.title').text(title);
				});
			},
			onunmatch: function () {
				$(document).off('my-component.title-changed');
			}
		});
	});
}(jQuery));
```

### Implementing handlers

Your newly created buttons need handlers to bind to before they will do anything.
To implement these handlers, you will need to create a `LeftAndMainExtension` and add
applicable controller actions to it:

	:::php
	class CustomActionsExtension extends LeftAndMainExtension {
		
		private static $allowed_actions = array(
        	'sampleAction'
    	);
    	
    	public function sampleAction()
    	{
    		// Create the web
    	}
    	
    }
    
The extension then needs to be registered:

	:::yaml
	LeftAndMain:
		extensions:
			- CustomActionsExtension
			
You can now use these handlers with your buttons:

	:::php
	$fields->push(FormAction::create('sampleAction', 'Perform Sample Action'));

## Summary

In a few lines of code, we've customised the look and feel of the CMS.

While this example is only scratching the surface, it includes most building
blocks and concepts for more complex extensions as well.

## Related

 * [Reference: CMS Architecture](../cms_architecture)
 * [Reference: Layout](../cms_layout)
 * [Rich Text Editing](/developer_guides/forms/field_types/htmleditorfield)
 * [CMS Alternating Button](cms_alternating_button)
