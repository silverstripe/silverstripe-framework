<?php

return function($class) {
	return "

{$class} is not a subclass of CMSBatchAction. To add a new batch action, you'll need to subclass it, like this:

class {$class} extends CMSBatchAction {
	public function getActionTitle() {
		return 'Custom Batch Action';
	}

	public function run(SS_List \$objects) {
		// ...
	}
}

Once you've extended CMSBatchAction, you'll be able to add your new batch action to the CMS.

";
};
