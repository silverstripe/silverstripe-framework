<?php

/**
 * Campaign section of the CMS
 *
 * @package framework
 * @subpackage admin
 */
class CampaignAdmin extends LeftAndMain implements PermissionProvider {

	private static $allowed_actions = [
		'createCampaign',
		'readCampaign',
		'updateCampaign',
		'deleteCampaign',
	];

	private static $menu_priority = 11;

	private static $menu_title = 'Campaigns';

	private static $url_handlers = [
		'POST item/$ID' => 'createCampaign',
		'GET item/$ID' => 'readCampaign',
		'PUT item/$ID' => 'updateCampaign',
		'DELETE item/$ID' => 'deleteCampaign',
	];

	private static $url_segment = 'campaigns';

	public function init() {
		parent::init();

		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/dist/bundle-react.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/dist/campaign-admin.js');
	}

	public function getEditForm($id = null, $fields = null) {
		return '';
	}

	/**
	 * REST endpoint to create a campaign.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function createCampaign(SS_HTTPRequest $request) {
		$response = new SS_HTTPResponse();
		$response->addHeader('Content-Type', 'application/json');
		$response->setBody(Convert::raw2json(['campaign' => 'create']));

		return $response;
	}

	/**
	 * REST endpoint to get a campaign.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function readCampaign(SS_HTTPRequest $request) {
		$response = new SS_HTTPResponse();
		$response->addHeader('Content-Type', 'application/json');
		$response->setBody(Convert::raw2json(['campaign' => 'read']));

		return $response;
	}

	/**
	 * REST endpoint to update a campaign.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function updateCampaign(SS_HTTPRequest $request) {
		$response = new SS_HTTPResponse();
		$response->addHeader('Content-Type', 'application/json');
		$response->setBody(Convert::raw2json(['campaign' => 'update']));

		return $response;
	}

	/**
	 * REST endpoint to delete a campaign.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function deleteCampaign(SS_HTTPRequest $request) {
		$response = new SS_HTTPResponse();
		$response->addHeader('Content-Type', 'application/json');
		$response->setBody(Convert::raw2json(['campaign' => 'delete']));

		return $response;
	}

}
