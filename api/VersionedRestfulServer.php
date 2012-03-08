<?php
/**
 * Simple wrapper to allow access to the live site via REST
 * 
 * @package sapphire
 * @subpackage integration
 */ 
class VersionedRestfulServer extends Controller {
	
	static $allowed_actions = array( 
		'index'
	);
	
	function handleRequest($request, $model) {
		$this->setModel($model);
		Versioned::reading_stage('Live');
		$restfulserver = new RestfulServer();
		$response = $restfulserver->handleRequest($request, $model);
		return $response;
	}
}


