<?php
/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 * 
 * @package sapphire
 * @subpackage dev
 */
class DebugView {

	/**
	 * Render HTML header for development views
	 */
	public function writeHeader() {
		echo '<!DOCTYPE html><html><head><title>'. $_SERVER['REQUEST_METHOD'] . ' ' .$_SERVER['REQUEST_URI'] .'</title>';
		echo '<style type="text/css">';
		echo 'body { background-color:#eee; margin:0; padding:0; font-family:Helvetica,Arial,sans-serif; }';
		echo '.info { border-bottom:1px dotted #333; background-color:#ccdef3; margin:0; padding:6px 12px; }';
		echo '.info h1 { margin:0; padding:0; color:#333; letter-spacing:-2px; }';
		echo '.header { margin:0; border-bottom:6px solid #ccdef3; height:23px; background-color:#666673; padding:4px 0 2px 6px; background-image:url('.Director::absoluteBaseURL().'cms/images/mainmenu/top-bg.gif); }';
		echo '.trace { padding:6px 12px; }';
		echo '.trace li { font-size:14px; margin:6px 0; }';
		echo 'pre { margin-left:18px; }';
		echo 'pre span { color:#999;}';
		echo 'pre .error { color:#f00; }';
		echo '.pass { margin-top:18px; padding:2px 20px 2px 40px; color:#006600; background:#E2F9E3 url('.Director::absoluteBaseURL() .'cms/images/alert-good.gif) no-repeat scroll 7px 50%; border:1px solid #8DD38D; }';
		echo '.fail { margin-top:18px; padding:2px 20px 2px 40px; color:#C80700; background:#FFE9E9 url('.Director::absoluteBaseURL() .'cms/images/alert-bad.gif) no-repeat scroll 7px 50%; border:1px solid #C80700; }';	
		echo '.failure span { color:#C80700; font-weight:bold; }';
		echo '</style></head>';
		echo '<body>';
		echo '<div class="header"><img src="'. Director::absoluteBaseURL() .'cms/images/mainmenu/logo.gif" width="26" height="23"></div>';
	}
	
	/**
	 * Render HTML footer for development views
	 */
	public function writeFooter() {
		echo "</body></html>";		
	}	
	
}

?>