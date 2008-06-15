<?php
/**
 * Interface that is implemented by controllers that are designed to hand control over to another controller.  
 * ModelAsController, which selects up a SiteTree object and passes control over to a suitable subclass of ContentController, is a good
 * example of this.
 * @package sapphire
 * @subpackage control
 */
interface NestedController {
	public function getNestedController();

}

?>