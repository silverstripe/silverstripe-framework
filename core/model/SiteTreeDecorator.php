<?php
/**
 * Plug-ins for additional functionality in your SiteTree classes.
 * 
 * @package sapphire
 * @subpackage model
 */
abstract class SiteTreeDecorator extends DataObjectDecorator {

	function onBeforePublish(&$original) {
	}

	function onAfterPublish(&$original) {
	}
	
	function onBeforeUnpublish() {
	}
	
	function onAfterUnpublish() {
	}
	
	function canAddChildren($member) {
	}
	
	function canPublish($member) {
		
	}

}

?>