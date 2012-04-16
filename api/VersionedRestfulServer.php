<?php
/**
 * Simple wrapper to allow access to the live site via REST
 * 
 * @package framework
 * @subpackage integration
 */ 
class VersionedRestfulServer extends Controller {
	
	static $allowed_actions = array( 
		'index'
	);
	
	function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		$this->setModel($model);
		Versioned::reading_stage('Live');
		$restfulserver = new RestfulServer();
		$response = $restfulserver->handleRequest($request, $model);
		return $response;
	}
}


