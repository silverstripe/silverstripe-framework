# How to create a tooltip

In this how-to, we'll create a simple jQuery ui tooltip
you can use this to provide the user with extra information without cluttering up the view.

To add a tooltip set a title attribute with the text you want to appear in the tooltip and add the class ss-tooltip
and cms-help-toggle.
The tooltip will appear when the question mark icon is clicked and will be release when the click is released.

Below is an example of adding a tooltip to the custom date fields in MemberDatetimeOptionssetField.php
the tooltip contains data from the title attribute and the classes ss-tooltip and cms-help-toggle are added.


    :::ss
	$options .= sprintf(
		'<a class="ss-tooltip cms-help-toggle" title="' . $this->getFormattingHelpText() . '" href="#%s">%s</a>',
		$this->id() . '_Help',
		_t('MemberDatetimeOptionsetField.TOGGLEHELP', 'Toggle formatting help')
	);

