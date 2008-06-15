<?php
/**
 * This interface lets us set up objects that will tell us what the current page is.
 * @package sapphire
 * @subpackage model
 */
interface CurrentPageIdentifier {
	/**
	 * Get the current page ID.
	 * @return int
	 */
	function currentPageID();
	
	/**
	 * Check if the given DataObject is the current page.
	 * @param DataObject $page The page to check.
	 * @return boolean
	 */
	function isCurrentPage(DataObject $page);
}

?>