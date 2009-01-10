<?php
/**
 * ModelAsController will hand over all control to the appopriate model object
 * It uses URLSegment to determine the right object.  Also, if (ModelClass)_Controller exists,
 * that controller will be used instead.  It should be a subclass of ContentController.
 *
 * @package sapphire
 */
class ModelAsController extends Controller implements NestedController {
	
	public function handleRequest($request) {
		$this->pushCurrent();
		$this->urlParams = $request->allParams();
		
		$this->init();
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
						$this->urlParams['Action'],
						$this->urlParams['ID'],
						$this->urlParams['OtherID']
					);
					
					$response = new HTTPResponse();
					$response->redirect($url, 301);
					return $response;
				}
				
				$child = $this->get404Page();
			}
		
			if($child) {
				if(isset($_REQUEST['debug'])) Debug::message("Using record #$child->ID of type $child->class with URL {$this->urlParams['URLSegment']}");
				
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
		$versionedQuery = new SQLQuery (
			'RecordID', 'SiteTree_versions',
			"`WasPublished` = 1 AND `URLSegment` = '$urlSegment'",
			'`LastEdited` DESC, `WasPublished`',
			null, null, 1
		);
		
		$result = $versionedQuery->execute();
		
		if($result->numRecords() == 1 && $redirectPage = $result->nextRecord()) {
			if($redirectObj = DataObject::get_by_id('SiteTree', $redirectPage['RecordID'])) return $redirectObj;
		}
		
		return false;
	}
	
	protected function get404Page() {
		if($page = DataObject::get_one("ErrorPage", "ErrorCode = '404'")) return $page;
		else return DataObject::get_one("SiteTree", "URLSegment = '404'");
	}
}

?>