<?php
/**
 * ModelAsController deals with mapping the initial request to the first {@link SiteTree}/{@link ContentController}
 * pair, which are then used to handle the request.
 *
 * @package sapphire
 * @subpackage control
 */
class ModelAsController extends Controller implements NestedController {
	
	/**
	 * Get the appropriate {@link ContentController} for handling a {@link SiteTree} object, link it to the object and
	 * return it.
	 *
	 * @param SiteTree $sitetree
	 * @param string $action
	 * @return ContentController
	 */
	public static function controller_for(SiteTree $sitetree, $action = null) {
		if($sitetree->class == 'SiteTree') $controller = "ContentController";
		else $controller = "{$sitetree->class}_Controller";

		if($action && class_exists($controller . '_' . ucfirst($action))) {
			$controller = $controller . '_' . ucfirst($action);
		}
		
		return class_exists($controller) ? new $controller($sitetree) : $sitetree;
	}
	
	public function init() {
		singleton('SiteTree')->extend('modelascontrollerInit', $this);
		parent::init();
	}
	
	/**
	 * @uses ModelAsController::getNestedController()
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request) {
		$this->request = $request;
		
		$this->pushCurrent();

		// Create a response just in case init() decides to redirect
		$this->response = new SS_HTTPResponse();

		$this->init();
		
		// If the database has not yet been created, redirect to the build page.
		if(!DB::isActive() || !ClassInfo::hasTable('SiteTree')) {
			$this->response->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
			$this->popCurrent();
			
			return $this->response;
		}
		
		try {
			$result = $this->getNestedController();
		
			if($result instanceof RequestHandler) {
				$result = $result->handleRequest($this->request);
			} else if(!($result instanceof SS_HTTPResponse)) {
				user_error("ModelAsController::getNestedController() returned bad object type '" . 
					get_class($result)."'", E_USER_WARNING);
			}
		} catch(SS_HTTPResponse_Exception $responseException) {
			$result = $responseException->getResponse();
		}
		
		$this->popCurrent();
		return $result;
	}
	
	/**
	 * @return ContentController
	 */
	public function getNestedController() {
		$request = $this->request;
		
		if(!$URLSegment = $request->param('URLSegment')) {
			throw new Exception('ModelAsController->getNestedController(): was not passed a URLSegment value.');
		}
		
		Translatable::disable_locale_filter();
		
		$sitetree = DataObject::get_one('SiteTree', sprintf (
			'"URLSegment" = \'%s\' %s', Convert::raw2sql($URLSegment), (SiteTree::nested_urls() ? 'AND "ParentID" = 0' : null)
		));
		
		Translatable::enable_locale_filter();
		
		if(!$sitetree) {
			// If a root page has been renamed, redirect to the new location.
			if($redirect = $this->findOldPage($URLSegment)) {
				$this->response = new SS_HTTPResponse();
				$this->response->redirect($redirect->Link (
					Controller::join_links($request->param('Action'), $request->param('ID'), $request->param('OtherID'))
				));
				
				return $this->response;
			}
			
			if($response = ErrorPage::response_for(404)) {
				return $response;
			} else {
				$this->httpError(404, 'The requested page could not be found.');
			}
		}
		
		if($sitetree->Locale) Translatable::set_current_locale($sitetree->Locale);
		
		if(isset($_REQUEST['debug'])) {
			Debug::message("Using record #$sitetree->ID of type $sitetree->class with link {$sitetree->Link()}");
		}
		
		return self::controller_for($sitetree, $this->request->param('Action'));
	}
	
	/**
	 * @param string $URLSegment
	 * @return SiteTree
	 */
	protected function findOldPage($URLSegment) {
		$URLSegment = Convert::raw2sql($URLSegment);
		
		// First look for a non-nested page that has a unique URLSegment and can be redirected to.
		if(SiteTree::nested_urls() && $pages = DataObject::get('SiteTree', "\"URLSegment\" = '$URLSegment'")) {
			if($pages->Count() == 1) return $pages->First();
		}
		
		// Get an old version of a page that has been renamed.
		$query = new SQLQuery (
			'"RecordID"',
			'"SiteTree_versions"',
			"\"URLSegment\" = '$URLSegment' AND \"WasPublished\" = 1" . (SiteTree::nested_urls() ? ' AND "ParentID" = 0' : null),
			'"LastEdited" DESC',
			null,
			null,
			1
		);
		
		$record = $query->execute()->first();
		if(!$record) return false;
		
		if($oldPage = DataObject::get_by_id('SiteTree', $record['RecordID'])) {
			// Run the page through an extra filter to ensure that all decorators are applied.
			if(SiteTree::get_by_link($oldPage->RelativeLink())) return $oldPage;
		}
	}
	
}