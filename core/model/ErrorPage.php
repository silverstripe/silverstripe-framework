<?php
/**
 * ErrorPage holds the content for the page of an error response.
 * Renders the page on each publish action into a static HTML file
 * within the assets directory, after the naming convention
 * /assets/error-<statuscode>.html.
 * This enables us to show errors even if PHP experiences a recoverable error.
 * ErrorPages
 * 
 * @see Debug::friendlyError()
 * 
 * @package cms
 */
class ErrorPage extends Page {

	static $db = array(
		"ErrorCode" => "Int",
	);

	static $defaults = array(
		"ShowInMenus" => 0,
		"ShowInSearch" => 0
	);
	
	static $icon = array("sapphire/javascript/tree/images/page", "file");
	
	protected static $static_filepath = ASSETS_PATH;
	
	/**
	 * Get a {@link SS_HTTPResponse} to response to a HTTP error code if an {@link ErrorPage} for that code is present.
	 *
	 * @param int $statusCode
	 * @return SS_HTTPResponse
	 */
	public static function response_for($statusCode) {
		// first attempt to dynamically generate the error page
		if($errorPage = DataObject::get_one('ErrorPage', "\"ErrorCode\" = $statusCode")) {
			return ModelAsController::controller_for($errorPage)->handleRequest(new SS_HTTPRequest('GET', ''));
		}
		
		// then fall back on a cached version
		$cachedPath = self::get_filepath_for_errorcode($statusCode, Translatable::get_current_locale());
		
		if(file_exists($cachedPath)) {
			$response = new SS_HTTPResponse();	
			
			$response->setStatusCode($statusCode);
			$response->setBody(file_get_contents($cachedPath));
			
			return $response;
		}
	}
	
	/**
	 * Ensures that there is always a 404 page
	 * by checking if there's an instance of
	 * ErrorPage with a 404 and 500 error code. If there
	 * is not, one is created when the DB is built.
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		// Ensure that an assets path exists before we do any error page creation
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH);
		}

		$pageNotFoundErrorPage = DataObject::get_one('ErrorPage', "\"ErrorCode\" = '404'");
		$pageNotFoundErrorPageExists = ($pageNotFoundErrorPage && $pageNotFoundErrorPage->exists()) ? true : false;
		$pageNotFoundErrorPagePath = self::get_filepath_for_errorcode(404);
		if(!($pageNotFoundErrorPageExists && file_exists($pageNotFoundErrorPagePath))) {
			if(!$pageNotFoundErrorPageExists) {
				$pageNotFoundErrorPage = new ErrorPage();
				$pageNotFoundErrorPage->ErrorCode = 404;
				$pageNotFoundErrorPage->Title = _t('ErrorPage.DEFAULTERRORPAGETITLE', 'Page not found');
				$pageNotFoundErrorPage->Content = _t('ErrorPage.DEFAULTERRORPAGECONTENT', '<p>Sorry, it seems you were trying to access a page that doesn\'t exist.</p><p>Please check the spelling of the URL you were trying to access and try again.</p>');
				$pageNotFoundErrorPage->Status = 'New page';
				$pageNotFoundErrorPage->write();
				$pageNotFoundErrorPage->publish('Stage', 'Live');
			}

			// Ensure a static error page is created from latest error page content
			$response = Director::test(Director::makeRelative($pageNotFoundErrorPage->Link()));
			if($fh = fopen($pageNotFoundErrorPagePath, 'w')) {
				$written = fwrite($fh, $response->getBody());
				fclose($fh);
			}

			if($written) {
				DB::alteration_message('404 error page created', 'created');
			} else {
				DB::alteration_message(sprintf('404 error page could not be created at %s. Please check permissions', $pageNotFoundErrorPagePath), 'error');
			}
		}

		$serverErrorPage = DataObject::get_one('ErrorPage', "\"ErrorCode\" = '500'");
		$serverErrorPageExists = ($serverErrorPage && $serverErrorPage->exists()) ? true : false;
		$serverErrorPagePath = self::get_filepath_for_errorcode(500);
		if(!($serverErrorPageExists && file_exists($serverErrorPagePath))) {
			if(!$serverErrorPageExists) {
				$serverErrorPage = new ErrorPage();
				$serverErrorPage->ErrorCode = 500;
				$serverErrorPage->Title = _t('ErrorPage.DEFAULTSERVERERRORPAGETITLE', 'Server error');
				$serverErrorPage->Content = _t('ErrorPage.DEFAULTSERVERERRORPAGECONTENT', '<p>Sorry, there was a problem with handling your request.</p>');
				$serverErrorPage->Status = 'New page';
				$serverErrorPage->write();
				$serverErrorPage->publish('Stage', 'Live');
			}

			// Ensure a static error page is created from latest error page content
			$response = Director::test(Director::makeRelative($serverErrorPage->Link()));
			if($fh = fopen($serverErrorPagePath, 'w')) {
				$written = fwrite($fh, $response->getBody());
				fclose($fh);
			}

			if($written) {
				DB::alteration_message('500 error page created', 'created');
			} else {
				DB::alteration_message(sprintf('500 error page could not be created at %s. Please check permissions', $serverErrorPagePath), 'error');
			}
		}
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->addFieldToTab(
			"Root.Content.Main", 
			new DropdownField(
				"ErrorCode",
				$this->fieldLabel('ErrorCode'),
				array(
					400 => _t('ErrorPage.400', '400 - Bad Request'),
					401 => _t('ErrorPage.401', '401 - Unauthorized'),
					403 => _t('ErrorPage.403', '403 - Forbidden'),
					404 => _t('ErrorPage.404', '404 - Not Found'),
					405 => _t('ErrorPage.405', '405 - Method Not Allowed'),
					406 => _t('ErrorPage.406', '406 - Not Acceptable'),
					407 => _t('ErrorPage.407', '407 - Proxy Authentication Required'),
					408 => _t('ErrorPage.408', '408 - Request Timeout'),
					409 => _t('ErrorPage.409', '409 - Conflict'),
					410 => _t('ErrorPage.410', '410 - Gone'),
					411 => _t('ErrorPage.411', '411 - Length Required'),
					412 => _t('ErrorPage.412', '412 - Precondition Failed'),
					413 => _t('ErrorPage.413', '413 - Request Entity Too Large'),
					414 => _t('ErrorPage.414', '414 - Request-URI Too Long'),
					415 => _t('ErrorPage.415', '415 - Unsupported Media Type'),
					416 => _t('ErrorPage.416', '416 - Request Range Not Satisfiable'),
					417 => _t('ErrorPage.417', '417 - Expectation Failed'),
					500 => _t('ErrorPage.500', '500 - Internal Server Error'),
					501 => _t('ErrorPage.501', '501 - Not Implemented'),
					502 => _t('ErrorPage.502', '502 - Bad Gateway'),
					503 => _t('ErrorPage.503', '503 - Service Unavailable'),
					504 => _t('ErrorPage.504', '504 - Gateway Timeout'),
					505 => _t('ErrorPage.505', '505 - HTTP Version Not Supported'),
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
	function doPublish() {
		parent::doPublish();

		// Run the page (reset the theme, it might've been disabled by LeftAndMain::init())
		$oldTheme = SSViewer::current_theme();
		SSViewer::set_theme(SSViewer::current_custom_theme());
		$response = Director::test(Director::makeRelative($this->Link()));
		SSViewer::set_theme($oldTheme);

		$errorContent = $response->getBody();
		
		// Make the base tag dynamic.
		// $errorContent = preg_replace('/<base[^>]+href="' . str_replace('/','\\/', Director::absoluteBaseURL()) . '"[^>]*>/i', '<base href="$BaseURL" />', $errorContent);
		
		// Check we have an assets base directory, creating if it we don't
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH, 02775);
		}


		// if the page is published in a language other than default language,
		// write a specific language version of the HTML page
		$filePath = self::get_filepath_for_errorcode($this->ErrorCode, $this->Locale);
		if($fh = fopen($filePath, "w")) {
			fwrite($fh, $errorContent);
			fclose($fh);
		} else {
			$fileErrorText = sprintf(
				_t(
					"ErrorPage.ERRORFILEPROBLEM",
					"Error opening file \"%s\" for writing. Please check file permissions."
				),
				$errorFile
			);
			FormResponse::status_message($fileErrorText, 'bad');
			FormResponse::respond();
			return;
		}
	}
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * 
	 */
	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['ErrorCode'] = _t('ErrorPage.CODE', "Error code");
		
		return $labels;
	}
	
	/**
	 * Returns an absolute filesystem path to a static error file
	 * which is generated through {@link publish()}.
	 * 
	 * @param int $statusCode A HTTP Statuscode, mostly 404 or 500
	 * @param String $locale A locale, e.g. 'de_DE' (Optional)
	 * @return String
	 */
	static function get_filepath_for_errorcode($statusCode, $locale = null) {
		if (singleton('ErrorPage')->hasMethod('alternateFilepathForErrorcode')) {
			return singleton('ErrorPage')-> alternateFilepathForErrorcode($statusCode, $locale);
		}
		if(singleton('SiteTree')->hasExtension('Translatable') && $locale && $locale != Translatable::default_locale()) {
			return self::$static_filepath . "/error-{$statusCode}-{$locale}.html";
		} else {
			return self::$static_filepath . "/error-{$statusCode}.html";
		}
	}
	
	/**
	 * Set the path where static error files are saved through {@link publish()}.
	 * Defaults to /assets.
	 * 
	 * @param string $path
	 */
	static function set_static_filepath($path) {
		self::$static_filepath = $path;
	}
	
	/**
	 * @return string
	 */
	static function get_static_filepath() {
		return self::$static_filepath;
	}
}

/**
 * Controller for ErrorPages.
 * @package cms
 */
class ErrorPage_Controller extends Page_Controller {
	function init() {
		parent::init();

		$action = $this->request->param('Action');
		if(!$action || $action == 'index') {
			Director::set_status_code($this->failover->ErrorCode ? $this->failover->ErrorCode : 404); 
		}
		
	}
}


?>
