# jquery.changetracker - Change tracking for forms #

## Setup ##

	jQuery('<my-form>).changetracker();
	
## Usage ##

Finding out if the form has changed:
	jQuery('<my-form>).is('.changed');
	
## Options ##

* fieldSelector: jQuery selector string for tracked fields 
  (Default: ':input:not(:submit),:select:not(:submit)')
* ignoreFieldSelector: jQuery selector string for specifically excluded fields
* changedCssClass: CSS class attribute which is appended to all changed fields and the form itself