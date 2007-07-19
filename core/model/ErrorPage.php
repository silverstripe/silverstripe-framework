<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * ErrorPage holds the content for the page of an error response.
 */
class ErrorPage extends Page {
	static $db = array(
		"ErrorCode" => "Int",
	);
	static $defaults = array(
		"ShowInMenus" => 0,
	);
	
	/**
	 * Ensures that there is always a 404 page.
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if(!DataObject::get_one("ErrorPage", "ErrorCode = '404'")) {
			$errorpage = new ErrorPage();
			$errorpage->ErrorCode = 404;
			$errorpage->Title = "Page not found";
			$errorpage->URLSegment = "page-not-found";
			$errorpage->ShowInMenus = false;
			$errorpage->Content = "<p>Sorry, it seems you were trying to access a page that doesn't exist.</p><p>Please check the spelling of the URL you were trying to access and try again.</p>";
			$errorpage->Status = "Published";
			$errorpage->write();
			$errorpage->publish("Stage", "Live");

			
			if(!Database::$supressOutput) {
				echo "<li style=\"color: orange\">404  page created</li>";
			}
		}
	}
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->addFieldToTab(
			"Root.Content.Main", 
			new DropdownField(
				"ErrorCode",
				"Error code",
				array(
					404 => "404 - Page not found",
					500 => "500 - Server error"
				)
			),
			"Content"
		);
		
		return $fields;
	}
	
	/**
	 * When an error page is published, create a static HTML page with its
	 * content, so the page can be shown even when SilverStripe is not
	 * functioning correctly before publishing this page normally.
	 * @param string|int $fromStage Place to copy from. Can be either a stage name or a version number.
	 * @param string $toStage Place to copy to. Must be a stage name.
	 * @param boolean $createNewVersion Set this to true to create a new version number.  By default, the existing version number will be copied over.
	 */
	function publish($fromStage, $toStage, $createNewVersion = false) {
		// Temporarily log out when producing this page
		$loggedInMember = Member::currentUser();
		Session::clear("loggedInAs");
		$alc_enc = isset($_COOKIE['alc_enc']) ? $_COOKIE['alc_enc'] : null;
		Cookie::set('alc_enc', null);

		// Run the page
		Requirements::clear();
		$controller = new ErrorPage_Controller($this);
		$errorContent = $controller->run( array() );

		if($fh = fopen("../assets/error-$this->ErrorCode.html", "w")) {
			fwrite($fh, $errorContent);
			fclose($fh);
		}

		// Log back in
		Session::set("loggedInAs", $loggedInMember->ID);
		if(isset($alc_enc)) {
			Cookie::set('alc_enc', $alc_enc);
		}
		
		return $this->extension_instances['Versioned']->publish($fromStage, $toStage, $createNewVersion);
	}
}

/**
 * Controller for ErrorPages.
 */
class ErrorPage_Controller extends Page_Controller {
	
}

?>