<?php
/**
 * Interface to provide enough information about a record to make it previewable
 * through the CMS. It uses the record database ID, its "frontend" and "backend"
 * links to link up the edit form with its preview.
 * 
 * Also used by {@link SilverStripeNavigator} to generate links -  both within
 * the CMS preview, and as a frontend utility for logged-in CMS authors in
 * custom themes (with the $SilverStripeNavigator template marker).
 *
 * @package framework
 * @subpackage admin
 */
interface CMSPreviewable {
	
	/**
	 * @return String Absolute URL to the end-user view for this record.
	 * Example: http://mysite.com/my-record
	 */
	public function Link();
	
	/**
	 * @return String Absolute URL to the CMS-author view. Should point to a
	 * controller subclassing {@link LeftAndMain}. Example:
	 * http://mysite.com/admin/edit/6
	 */
	public function CMSEditLink();

}
