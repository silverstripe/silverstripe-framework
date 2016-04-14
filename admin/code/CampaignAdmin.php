<?php

/**
 * Campaign section of the CMS
 *
 * @package framework
 * @subpackage admin
 */
class CampaignAdmin extends LeftAndMain implements PermissionProvider {

	private static $allowed_actions = [
		'set',
		'sets',
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
		'GET sets' => 'readCampaigns',
		'POST set/$ID' => 'createCampaign',
		'GET set/$ID/$Name' => 'readCampaign',
		'PUT set/$ID' => 'updateCampaign',
		'DELETE set/$ID' => 'deleteCampaign',
	];

	private static $url_segment = 'campaigns';

	/**
	 * Size of thumbnail width
	 *
	 * @config
	 * @var int
	 */
	private static $thumbnail_width = 64;

	/**
	 * Size of thumbnail height
	 *
	 * @config
	 * @var int
	 */
	private static $thumbnail_height = 64;

	public function getClientConfig() {
		$urlSegment = Config::inst()->get($this->class, 'url_segment');

		return array_merge(parent::getClientConfig(), [
			'forms' => [
				// TODO Use schemaUrl instead
				'editForm' => [
					'schemaUrl' => $this->Link('schema/EditForm')
				]
			],
			'campaignViewRoute' => $urlSegment . '/:type?/:id?/:view?',
			'itemListViewEndpoint' => $this->Link('set/:id/show'),
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
					"url": "admin\/campaigns\/sets",
					"method": "GET"
				},
				"itemReadEndpoint": {
					"url": "admin\/campaigns\/set\/:id",
					"method": "GET"
				},
				"itemUpdateEndpoint": {
					"url": "admin\/campaigns\/set\/:id",
					"method": "PUT"
				},
				"itemCreateEndpoint": {
					"url": "admin\/campaigns\/set\/:id",
					"method": "POST"
				},
				"itemDeleteEndpoint": {
					"url": "admin\/campaigns\/set\/:id",
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

		// TODO Implement permission check and data creation

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
		$hal = $this->getListResource();
		$response->setBody(Convert::array2json($hal));
		return $response;
	}

	/**
	 * Get list contained as a hal wrapper
	 *
	 * @return array
	 */
	protected function getListResource() {
		$items = $this->getListItems();
		$count = $items->count();
		$hal = [
			'count' => $count,
			'total' => $count,
			'_links' => [
				'self' => [
					'href' => $this->Link('items')
				]
			],
			'_embedded' => ['ChangeSets' => []]
		];
		foreach($items as $item) {
			/** @var ChangeSet $item */
			$resource = $this->getChangeSetResource($item);
			$hal['_embedded']['ChangeSets'][] = $resource;
		}
		return $hal;
	}

	/**
	 * Build item resource from a changeset
	 *
	 * @param ChangeSet $changeSet
	 * @return array
	 */
	protected function getChangeSetResource(ChangeSet $changeSet) {
		$hal = [
			'_links' => [
				'self' => [
					'href' => $this->SetLink($changeSet->ID)
				]
			],
			'ID' => $changeSet->ID,
			'Name' => $changeSet->Name,
			'Description' => $changeSet->getDescription(),
			'Created' => $changeSet->Created,
			'LastEdited' => $changeSet->LastEdited,
			'State' => $changeSet->State,
			'_embedded' => ['ChangeSetItems' => []]
		];
		foreach($changeSet->Changes() as $changeSetItem) {
			if(!$changeSetItem) {
				continue;
			}

			/** @var ChangesetItem $changeSetItem */
			$resource = $this->getChangeSetItemResource($changeSetItem);
			$hal['_embedded']['ChangeSetItems'][] = $resource;
		}
		return $hal;
	}

	/**
	 * Build item resource from a changesetitem
	 *
	 * @param ChangeSetItem $changeSetItem
	 * @return array
	 */
	protected function getChangeSetItemResource(ChangeSetItem $changeSetItem) {
		$baseClass = ClassInfo::baseDataClass($changeSetItem->ObjectClass);
		$baseSingleton = DataObject::singleton($baseClass);
		$thumbnailWidth = (int)$this->config()->thumbnail_width;
		$thumbnailHeight = (int)$this->config()->thumbnail_height;
		$hal = [
			'_links' => [
				'self' => [
					'href' => $this->ItemLink($changeSetItem->ID)
				]
			],
			'ID' => $changeSetItem->ID,
			'Created' => $changeSetItem->Created,
			'LastEdited' => $changeSetItem->LastEdited,
			'Title' => $changeSetItem->getTitle(),
			'ChangeType' => $changeSetItem->getChangeType(),
			'Added' => $changeSetItem->Added,
			'ObjectClass' => $changeSetItem->ObjectClass,
			'ObjectID' => $changeSetItem->ObjectID,
			'BaseClass' => $baseClass,
			'Singular' => $baseSingleton->i18n_singular_name(),
			'Plural' => $baseSingleton->i18n_plural_name(),
			'Thumbnail' => $changeSetItem->ThumbnailURL($thumbnailWidth, $thumbnailHeight),
		];
		// Depending on whether the object was added implicitly or explicitly, set
		// other related objects.
		if($changeSetItem->Added === ChangeSetItem::IMPLICITLY) {
			$referencedItems = $changeSetItem->ReferencedBy();
			$referencedBy = [];
			foreach($referencedItems as $referencedItem) {
				$referencedBy[] = [
					'href' => $this->SetLink($referencedItem->ID)
				];
			}
			if($referencedBy) {
				$hal['_links']['referenced_by'] = $referencedBy;
			}
		}

		return $hal;
	}



	/**
	 * Gets viewable list of campaigns
	 *
	 * @return SS_List
	 */
	protected function getListItems() {
		return ChangeSet::get()
			->filter('State', ChangeSet::STATE_OPEN)
			->filterByCallback(function($item) {
				return ($item->canView());
			});
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

		if ($request->getHeader('Accept') == 'text/json') {
		$response->addHeader('Content-Type', 'application/json');
			$changeSet = ChangeSet::get()->byId($request->param('ID'));

			switch ($request->param('Name')) {
				case "edit":
					$response->setBody('{"message":"show the edit view"}');
					break;
				case "show":
					$response->setBody(Convert::raw2json($this->getChangeSetResource($changeSet)));
					break;
				default:
					$response->setBody('{"message":"404"}');
			}

		return $response;

		} else {
			return $this->index($request);
		}
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

		// TODO Implement data update and permission checks

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
		$id = $request->param('ID');
		if (!$id || !is_numeric($id)) {
            return (new SS_HTTPResponse(json_encode(['status' => 'error']), 400))
                ->addHeader('Content-Type', 'application/json');
        }

		$record = ChangeSet::get()->byID($id);
		if(!$record) {
			return (new SS_HTTPResponse(json_encode(['status' => 'error']), 404))
                ->addHeader('Content-Type', 'application/json');
		}

		if(!$record->canDelete()) {
			return (new SS_HTTPResponse(json_encode(['status' => 'error']), 401))
                ->addHeader('Content-Type', 'application/json');
		}

		$record->delete();

		return (new SS_HTTPResponse('', 204));
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
			ChangeSet::singleton()->getCMSFields(),
			FieldList::create(
				FormAction::create('save', 'Save')
			)
		);
	}

	/**
	 * Gets user-visible url to edit a specific {@see ChangeSet}
	 *
	 * @param $itemID
	 * @return string
	 */
	public function SetLink($itemID) {
		return Controller::join_links(
			$this->Link('set'),
			$itemID
		);
	}

	/**
	 * Gets user-visible url to edit a specific {@see ChangeSetItem}
	 *
	 * @param int $itemID
	 * @return string
	 */
	public function ItemLink($itemID) {
		return Controller::join_links(
			$this->Link('item'),
			$itemID
		);
	}

	/**
	 *
	 */
	public function FindReferencedChanges() {

	}

}
