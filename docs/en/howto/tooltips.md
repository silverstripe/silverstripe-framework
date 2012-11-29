# How to create a CMS tooltip

In this how-to, we will create a simple jQuery ui tooltip
you can use this to provide the user with extra information without cluttering up the view.

To add a tooltip set a title attribute with the text you want to appear in the tooltip and add the class ss-tooltip
and cms-help-toggle.
The tooltip will appear when the question mark icon is clicked or touched and will remain as long as the cursor
remains or until the mouse is clicked or on a touch screen device when the user presses another section of the screen.

Below is an example of adding a tooltip to the custom date fields in MemberDatetimeOptionssetField.php
the tooltip contains data from the title attribute and the classes ss-tooltip and cms-help-toggle are added.
The javascript file Tooltip.js will also need to be included this has been written using Entwine and handles
initialising the tooltips and setting the tooltip parameters like fading, positioning and how to toggle the tooltips.


    :::ss

	Requirements::javascript(FRAMEWORK_DIR . "/javascript/Tooltip.js");

	$options .= sprintf(
		'<a class="ss-tooltip cms-help-toggle" title="' . $this->getFormattingHelpText() . '" href="#%s">%s</a>',
		$this->id() . '_Help',
		_t('MemberDatetimeOptionsetField.TOGGLEHELP', 'Toggle formatting help')
	);

