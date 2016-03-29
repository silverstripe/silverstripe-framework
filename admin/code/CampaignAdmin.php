<?php

/**
 * Campaign section of the CMS
 *
 * @package framework
 * @subpackage admin
 */
class CampaignAdmin extends LeftAndMain implements PermissionProvider {

	private static $allowed_actions = [
		'item',
		'items',
		'schema',
		'DetailEditForm',
		'readCampaigns',
		'createCampaign',
		'readCampaign',
		'updateCampaign',
		'deleteCampaign',
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
				"recordType": "ChangeSet",
				"collectionReadEndpoint": {
					"url": "admin\/campaigns\/items",
					"method": "GET"
				},
				"itemReadEndpoint": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "GET"
				},
				"itemUpdateEndpoint": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "PUT"
				},
				"itemCreateEndpoint": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "POST"
				},
				"itemDeleteEndpoint": {
					"url": "admin\/campaigns\/item\/:id",
					"method": "DELETE"
				},
				"editFormSchemaEndpoint": "admin\/campaigns\/schema\/DetailEditForm",
				"columns": [
					{"name": "Title", "field": "Name"},
					{"name": "Changes", "field": "_embedded.ChangeSetItems.length"},
					{"name": "Description", "field": "Description"}
				]
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
		$json = <<<JSON
{
	"_links": {
		"self": {
			"href": "/api/ChangeSet/"
		}
	},
	"count": 3,
	"total": 3,
	"_embedded": {
		"ChangeSets": [
			{
				"_links": {
					"self": {
						"href": "/api/ChangeSet/show/1"
					}
				},
				"ID": 1,
				"Created": "2016-01-01 00:00:00",
				"LastEdited": "2016-01-01 00:00:00",
				"Name": "March 2016 release",
				"Description": "All the stuff related to the 4.0 announcement",
				"State": "open",
				"_embedded": {
					"ChangeSetItems": [
						{
							"_links": {
								"self": {
									"href": "/api/ChangeSetItem/show/1"
								},
								"owns": [
									{"href": "/api/ChangeSetItem/show/3"},
									{"href": "/api/ChangeSetItem/show/4"}
								]
							},
							"ID": 1,
							"Created": "2016-01-01 00:00:00",
							"LastEdited": "2016-01-01 00:00:00",
							"VersionBefore": 1,
							"VersionAfter": 2,
							"State": "open",
							"_embedded": {
								"Object": [
									{
										"_links": {
											"self": {
												"href": "/api/SiteTree/show/1"
											}
										},
										"ID": 1,
										"ChangeSetCategory": "Page",
										"Title": "Home",
										"StatusFlags": ["addedtodraft"]
									}
								]
							}
						},
						{
							"_links": {
								"self": {
									"href": "/api/ChangeSetItem/show/2"
								},
								"owns": [
									{"href": "/api/ChangeSetItem/show/4"}
								]
							},
							"ID": 2,
							"Created": "2016-01-01 00:00:00",
							"LastEdited": "2016-01-01 00:00:00",
							"VersionBefore": 1,
							"VersionAfter": 2,
							"State": "open",
							"_embedded": {
								"Object": [
									{
										"_links": {
											"self": {
												"href": "/api/SiteTree/show/2"
											}
										},
										"ID": 2,
										"ChangeSetCategory": "Page",
										"Title": "Features",
										"StatusFlags": ["modified"]
									}
								]
							}
						},
						{
							"_links": {
								"self": {
									"href": "/api/ChangeSetItem/show/3"
								},
								"ownedby": [
									{"href": "/api/ChangeSetItem/show/1"}
								]
							},
							"ID": 3,
							"Created": "2016-01-01 00:00:00",
							"LastEdited": "2016-01-01 00:00:00",
							"VersionBefore": 1,
							"VersionAfter": 2,
							"State": "open",
							"_embedded": {
								"Object": [
									{
										"_links": {
											"self": {
												"href": "/api/File/show/1"
											}
										},
										"ID": 1,
										"ChangeSetCategory": "File",
										"Title": "A picture of George",
										"PreviewThumbnailURL": "/george.jpg",
										"StatusFlags": ["modified"]
									}
								]
							}
						},
						{
							"_links": {
								"self": {
									"href": "/api/ChangeSetItem/show/4"
								},
								"ownedby": [
									{"href": "/api/ChangeSetItem/show/1"},
									{"href": "/api/ChangeSetItem/show/2"}
								]
							},
							"ID": 4,
							"Created": "2016-01-01 00:00:00",
							"LastEdited": "2016-01-01 00:00:00",
							"VersionBefore": 1,
							"VersionAfter": 2,
							"State": "open",
							"_embedded": {
								"Object": [
									{
										"_links": {
											"self": {
												"href": "/api/File/show/2"
											}
										},
										"ID": 2,
										"ChangeSetCategory": "File",
										"Title": "Out team",
										"PreviewThumbnailURL": "/team.jpg",
										"StatusFlags": ["modified"]
									}
								]
							}
						}
					]
				}
			},
			{
				"_links": {
					"self": {
						"href": "/api/ChangeSet/show/2"
					}
				},
				"ID": 2,
				"Created": "2016-02-01 00:00:00",
				"LastEdited": "2016-02-01 00:00:00",
				"Name": "Shop products",
				"State": "open",
				"_embedded": {
					"ChangeSetItems": []
				}
			}
		]
	}
}
JSON;
		$response->setBody($json);

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
		$response->setBody('');

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
