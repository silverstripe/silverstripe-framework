<?php

return function(array $action) {
	$action = trim(print_r($action, true));

	return "

There seems to be an error with the format of one of the batch actions:

{$action}

This usually happens because the action has been incorrectly formatted, in YAML config. The format should resemble:

my_batch_action:
  class: BatchActionClass
  recordClass: ClassToApplyBatchActionTo

Alternatively, you can register the action, in mysite/_config.php, with:

CMSBatchActionHandler::register('my_batch_action', 'BatchActionClass', 'ClassToApplyBatchActionTo');

";
};
