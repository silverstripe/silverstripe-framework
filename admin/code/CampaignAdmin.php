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
		'schema',
		'DetailEditForm'
	];

	private static $menu_priority = 11;

	private static $menu_title = 'Campaigns';

	private static $url_handlers = [
		'GET items' => 'readCampaigns',
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

	public function getClientConfig() {
		return array_merge(parent::getClientConfig(), [
			'forms' => [
				// TODO Use schemaUrl instead
				'editForm' => [
					'schemaUrl' => $this->Link('schema/EditForm')
				]
			]
		]);
	}

	public function schema($request) {
		// TODO Hardcoding schema until we can get GridField to generate a schema dynamically
		$json = <<<JSON
{
	"id": "EditForm",
	"schema": {
		"name": "EditForm",
		"id": "EditForm",
		"action": "schema",
		"method": "GET",
		"schema_url": "admin\/campaigns\/schema\/EditForm",
		"attributes": {
			"id": "Form_EditForm",
			"action": "admin\/campaigns\/EditForm",
			"method": "POST",
			"enctype": "multipart\/form-data",
			"target": null,
			"class": "cms-edit-form CampaignAdmin LeftAndMain"
		},
		"data": [],
		"fields": [{
			"name": "ID",
			"id": "Form_EditForm_ID",
			"type": "Hidden",
			"component": null,
			"holder_id": null,
			"title": false,
			"source": null,
			"extraClass": "hidden nolabel",
			"description": null,
			"rightTitle": null,
			"leftTitle": null,
			"readOnly": false,
			"disabled": false,
			"customValidationMessage": "",
			"attributes": [],
			"data": []
		}, {
			"name": "ChangeSets",
			"id": "Form_EditForm_ChangeSets",
			"type": "Custom",
			"component": "GridField",
			"holder_id": null,
			"title": "Campaigns",
			"source": null,
			"extraClass": null,
			"description": null,
			"rightTitle": null,
			"leftTitle": null,
			"readOnly": false,
			"disabled": false,
			"customValidationMessage": "",
			"attributes": [],
			"data": {
				"collectionReadUrl": {
					"url": "admin\/campaigns\/items",
					"method": "GET"
				},
				"itemReadUrl": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "GET"
				},
				"itemUpdateUrl": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "PUT"
				},
				"itemCreateUrl": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "POST"
				},
				"itemDeleteUrl": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "DELETE"
				},
				"editFormSchemaUrl": "admin\/campaigns\/schema\/DetailEditForm"
			}
		}, {
			"name": "SecurityID",
			"id": "Form_EditForm_SecurityID",
			"type": "Hidden",
			"component": null,
			"holder_id": null,
			"title": "Security ID",
			"source": null,
			"extraClass": "hidden",
			"description": null,
			"rightTitle": null,
			"leftTitle": null,
			"readOnly": false,
			"disabled": false,
			"customValidationMessage": "",
			"attributes": [],
			"data": []
		}],
		"actions": []
	}
}
JSON;

		$formName = $request->param('ID');
		if($formName == 'EditForm') {
			$response = $this->getResponse();
			$response->addHeader('Content-Type', 'application/json');
			$response->setBody($json);
			return $response;
		} else {
			return parent::schema($request);
		}
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
	 * REST endpoint to get a list of campaigns.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function readCampaigns(SS_HTTPRequest $request) {
		$response = new SS_HTTPResponse();
		$response->addHeader('Content-Type', 'application/json');
		$response->setBody(Convert::raw2json(['campaigns' => 'read']));

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

	/**
	 * @todo Use GridFieldDetailForm once it can handle structured data and form schemas
	 *
	 * @return Form
	 */
	public function getDetailEditForm() {
		return Form::create(
			$this,
			'DetailEditForm',
			FieldList::create(
				TextField::create('Name'),
				TextAreaField::create('Description')
			),
			FieldList::create(
				FormAction::create('save', 'Save')
			)
		);
	}

}
