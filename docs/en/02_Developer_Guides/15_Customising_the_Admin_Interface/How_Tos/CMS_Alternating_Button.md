# How to implement an alternating button #

## Introduction ##

*Save* and *Save & publish* buttons alternate their appearance to reflect the state of the underlying `SiteTree` object.
This is based on a `ssui.button` extension available in `ssui.core.js`.

The button can be configured via the data attributes in the backend, or through jQuery UI initialisation options. The
state can be toggled from the backend (again through data attributes), and can also be easily toggled or set on the
frontend.

This how-to will walk you through creation of a "Clean-up" button with two appearances:

* active: "Clean-up now" green constructive button if the actions can be performed
* netural: "Cleaned" default button if the action does not need to be done

The controller code that goes with this example is listed in [Extend CMS Interface](../reference/extend-cms-interface).

## Backend support ##

First create and configure the action button with alternate state on a page type. The button comes with the default
state already, so you just need to add the alternate state using two data additional attributes:

* `data-icon-alternate`: icon to be shown when the button is in the alternate state
* `data-text-alternate`: likewise for text.

Here is the configuration code for the button:

	:::php
	public function getCMSActions() {
		$fields = parent::getCMSActions();

		$fields->fieldByName('MajorActions')->push(
			$cleanupAction = FormAction::create('cleanup', 'Cleaned')
				// Set up an icon for the neutral state that will use the default text.
				->setAttribute('data-icon', 'accept')
				// Initialise the alternate constructive state.
				->setAttribute('data-icon-alternate', 'addpage')
				->setAttribute('data-text-alternate', 'Clean-up now')
		);

		return $fields;
	}

You can control the state of the button from the backend by applying `ss-ui-alternate` class to the `FormAction`. To
simplify our example, let's assume the button state is controlled on the backend only, but you'd usually be better off
adjusting the state in the frontend to give the user the benefit of immediate feedback. This technique might still be
used for initialisation though.

Here we initialise the button based on the backend check, and assume that the button will only update after page reload
(or on CMS action).

	:::php
	public function getCMSActions() {
		// ...
		if ($this->needsCleaning()) {
			// Will initialise the button into alternate state.
			$cleanupAction->addExtraClass('ss-ui-alternate');
		}
		// ...
	}

## Frontend support ##

As with the *Save* and *Save & publish* buttons, you might want to add some scripted reactions to user actions on the
frontend. You can affect the state of the button through the jQuery UI calls.

First of all, you can toggle the state of the button - execute this code in the browser's console to see how it works.

	:::js
	jQuery('.cms-edit-form .Actions #Form_EditForm_action_cleanup').button('toggleAlternate');

Another, more useful, scenario is to check the current state.

	:::js
	jQuery('.cms-edit-form .Actions #Form_EditForm_action_cleanup').button('option', 'showingAlternate');

You can also force the button into a specific state by using UI options.

	:::js
	jQuery('.cms-edit-form .Actions #Form_EditForm_action_cleanup').button({showingAlternate: true});

This will allow you to react to user actions in the CMS and give immediate feedback. Here is an example taken from the
CMS core that tracks the changes to the input fields and reacts by enabling the *Save* and *Save & publish* buttons
(changetracker will automatically add `changed` class to the form if a modification is detected).

	:::js
	/**
	 * Enable save buttons upon detecting changes to content.
	 * "changed" class is added by jQuery.changetracker.
	 */
	$('.cms-edit-form .changed').entwine({
		// This will execute when the class is added to the element.
		onmatch: function(e) {
			var form = this.closest('.cms-edit-form');
			form.find('#Form_EditForm_action_save').button({showingAlternate: true});
			form.find('#Form_EditForm_action_publish').button({showingAlternate: true});
			this._super(e);
		},
		// Entwine requires us to define this, even if we don't use it.
		onunmatch: function(e) {
			this._super(e);
		}
	});

## Frontend hooks ##

`ssui.button` defines several additional events so that you can extend the code with your own behaviours. For example
this is used in the CMS to style the buttons. Three events are available:

* `ontogglealternate`: invoked when the `toggleAlternate` is called. Return `false` to prevent the toggling.
* `beforerefreshalternate`: invoked before the alternate-specific rendering takes place, including the button
initialisation.
* `afterrefreshalternate`: invoked after the rendering has been done, including on init. Good place to add styling
extras.

Continuing our example let's add a "constructive" style to our *Clean-up* button. First you need to be able to add
custom JS code into the CMS. You can do this by adding a new source file, here
`mysite/javascript/CMSMain.CustomActionsExtension.js`, and requiring it
through a YAML configuration value: `LeftAndMain.extra_requirements_javascript`.
Set it to 'mysite/javascript/CMSMain.CustomActionsExtension.js'.

You can now add the styling in response to `afterrefreshalternate` event. Let's use entwine to avoid accidental memory
leaks. The only complex part here is how the entwine handle is constructed. `onbuttonafterrefreshalternate` can be
disassembled into:

* `on` signifies the entiwne event handler
* `button` is jQuery UI widget name
* `afterrefreshalternate`: the event from ssui.button to react to.

Here is the entire handler put together. You don't need to add any separate initialisation code, this will handle all
cases.

	:::js
	(function($) {

		$.entwine('mysite', function($){
			$('.cms-edit-form .Actions #Form_EditForm_action_cleanup').entwine({
				/**
				 * onafterrefreshalternate is SS-specific jQuery UI hook that is executed
				 * every time the button is rendered (including on initialisation).
				 */
				onbuttonafterrefreshalternate: function() {
					if (this.button('option', 'showingAlternate')) {
						this.addClass('ss-ui-action-constructive');
					}
					else {
						this.removeClass('ss-ui-action-constructive');
					}
				}
			});
		});

	}(jQuery));

## Summary ##

The code presented gives you a fully functioning alternating button, similar to the defaults that come with the the CMS.
These alternating buttons can be used to give user the advantage of visual feedback upon his actions.
