<?php
/**
 * ModelAsController will hand over all control to the appopriate model object
 * It uses URLSegment to determine the right object.  Also, if (ModelClass)_Controller exists,
 * that controller will be used instead.  It should be a subclass of ContentController.
 *
 * @package sapphire
 * @subpackage control
 */
class ModelAsController extends Controller implements NestedController {
	
	public function handleRequest($request) {
		$this->pushCurrent();
		$this->urlParams = $request->allParams();
		
		$this->init();

		// If the basic database hasn't been created, then build it.
		if(!DB::isActive() || !ClassInfo::hasTable('SiteTree')) {
			$this->response = new HTTPResponse();
			$this->redirect("dev/build?returnURL=" . (isset($_GET['url']) ? urlencode($_GET['url']) : ''));
			$this->popCurrent();
			return $this->response;
		}

		$result = $this->getNestedController();
		
		if(is_object($result) && $result instanceOf RequestHandler) {
			$result = $result->handleRequest($request);
		}
		
		$this->popCurrent();
		return $result;
	}
	
	public function init() {
		singleton('SiteTree')->extend('modelascontrollerInit', $this);
		
		Director::set_site_mode('site');
	}

	public function getNestedController() {
		if($this->urlParams['URLSegment']) {
			$SQL_URLSegment = Convert::raw2sql($this->urlParams['URLSegment']);
			$child = SiteTree::get_by_url($SQL_URLSegment);
			
			if(!$child) {
				if($child = $this->findOldPage($SQL_URLSegment)) {
					$url = Controller::join_links(
						Director::baseURL(),
						$child->URLSegment,
						(isset($this->urlParams['Action'])) ? $this->urlParams['Action'] : null,
						(isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : null,
						(isset($this->urlParams['OtherID'])) ? $this->urlParams['OtherID'] : null
					);

					$response = new HTTPResponse();
					$response->redirect($url, 301);
					return $response;
				}
				
				$child = $this->get404Page();
			}
		
			if($child) {
				if(isset($_REQUEST['debug'])) Debug::message("Using record #$child->ID of type $child->class with URL {$this->urlParams['URLSegment']}");
				
				// set language
				if($child->Locale) Translatable::set_current_locale($child->Locale);
				
				$controllerClass = "{$child->class}_Controller";
	
				if($this->urlParams['Action'] && ClassInfo::exists($controllerClass.'_'.$this->urlParams['Action'])) {
					$controllerClass = $controllerClass.'_'.$this->urlParams['Action'];	
				}
	
				if(ClassInfo::exists($controllerClass)) {
					$controller = new $controllerClass($child);
				} else {
					$controller = $child;
				}
			
				return $controller;
			} else {
				return new HTTPResponse("The requested page couldn't be found.",404);
			}
			
		} else {
			user_error("ModelAsController not geting a URLSegment.  It looks like the site isn't redirecting to home", E_USER_ERROR);
		}
	}
	
	protected function findOldPage($urlSegment) {
		// Build the query by  replacing `SiteTree` with `SiteTree_versions` in a regular query.
		// Note that this should *really* be handled by a more full-featured data mapper; as it stands
		// this is a bit of a hack.
		$origStage = Versioned::current_stage();
		Versioned::reading_stage('Stage');
		$versionedQuery = singleton('SiteTree')->extendedSQL('');
		Versioned::reading_stage($origStage);
		
		foreach($versionedQuery->from as $k => $v) {
			$versionedQuery->renameTable($k, $k . '_versions');
		}
		$versionedQuery->select = array("`SiteTree_versions`.RecordID");
		$versionedQuery->where[] = "`SiteTree_versions`.`WasPublished` = 1 AND `URLSegment` = '$urlSegment'";
		$versionedQuery->orderby = '`LastEdited` DESC, `SiteTree_versions`.`WasPublished`';
		$versionedQuery->limit = 1;

		$result = $versionedQuery->execute();
		
		if($result->numRecords() == 1 && $redirectPage = $result->nextRecord()) {
			$redirectObj = DataObject::get_by_id('SiteTree', $redirectPage['RecordID']);
			if($redirectObj) {
				// Double-check by querying this page in the same way that getNestedController() does.  This
				// will prevent query muck-ups from modules such as subsites
				$doubleCheck = SiteTree::get_by_url($redirectObj->URLSegment);
				if($doubleCheck) return $redirectObj;
			}
		}
		
		return false;
	}
	
	protected function get404Page() {
		$page = DataObject::get_one("ErrorPage", "`ErrorCode` = '404'");
		if($page) {
			return $page;
		} else {
			// @deprecated 2.5 Use ErrorPage class
			return DataObject::get_one("SiteTree", "`URLSegment` = '404'");
		}
	}
}

?>