<?php

/**
 * @package sapphire
 * @subpackage control
 */

/**
 * ModelAsController will hand over all control to the appopriate model object
 * It uses URLSegment to determine the right object.  Also, if (ModelClass)_Controller exists,
 * that controller will be used instead.  It should be a subclass of ContentController.
 *
 * @package sapphire
 */
class ModelAsController extends Controller implements NestedController {
	
	public function run($requestParams) {
		$this->pushCurrent();
		
		$this->init();
		$nested = $this->getNestedController();
		$result = $nested->run($requestParams);
		
		$this->popCurrent();
		return $result;
	}
	
	public function init() {
		singleton('SiteTree')->extend('modelascontrollerInit', $this);
	}

	public function getNestedController() {
		if($this->urlParams['URLSegment']) {
			$SQL_URLSegment = Convert::raw2sql($this->urlParams['URLSegment']);
			if (Translatable::is_enabled()) {
				$child = Translatable::get_one("SiteTree", "URLSegment = '$SQL_URLSegment'");
			} else {
				$child = DataObject::get_one("SiteTree", "URLSegment = '$SQL_URLSegment'");
			}
			if(!$child) {
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
				$controller->setURLParams($this->urlParams);
			
				return $controller;
			} else {
				die("The requested page couldn't be found.");
			}
			
		} else {
			user_error("ModelAsController not geting a URLSegment.  It looks like the site isn't redirecting to home", E_USER_ERROR);
		}
	}
	
	protected function get404Page() {
		if($page = DataObject::get_one("ErrorPage", "ErrorCode = '404'")) return $page;
		else return DataObject::get_one("SiteTree", "URLSegment = '404'");
	}
}

?>
