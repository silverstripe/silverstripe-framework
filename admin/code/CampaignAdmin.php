<?php

use SilverStripe\ORM\Versioning\ChangeSet;
use SilverStripe\ORM\Versioning\ChangeSetItem;
use SilverStripe\ORM\DataObject;

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
		'readCampaign',
		'deleteCampaign',
		'publishCampaign',
	];

	private static $menu_priority = 3;

	private static $menu_title = 'Campaigns';

	private static $tree_class = 'SilverStripe\\ORM\\Versioning\\ChangeSet';

	private static $url_handlers = [
		'GET sets' => 'readCampaigns',
		'POST set/$ID/publish' => 'publishCampaign',
		'GET set/$ID/$Name' => 'readCampaign',
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
		return array_merge(parent::getClientConfig(), [
			'reactRoute' => true,
			'form' => [
				// TODO Use schemaUrl instead
				'EditForm' => [
					'schemaUrl' => $this->Link('schema/EditForm')
				],
				'DetailEditForm' => [
					'schemaUrl' => $this->Link('schema/DetailEditForm')
				],
			],
			'campaignViewRoute' => $this->Link() . ':type?/:id?/:view?',
			'itemListViewEndpoint' => $this->Link() . 'set/:id/show',
			'publishEndpoint' => [
				'url' => $this->Link() . 'set/:id/publish',
				'method' => 'post'
			],
			'treeClass' => $this->config()->tree_class
		]);
	}

	public function schema($request) {
		// TODO Hardcoding schema until we can get GridField to generate a schema dynamically
		$treeClassJS = Convert::raw2js($this->config()->tree_class);
		$json = <<<JSON
{
	"id": "Form_EditForm",
	"schema": {
		"name": "EditForm",
		"id": "Form_EditForm",
		"action": "schema",
		"method": "GET",
		"schema_url": "admin\/campaigns\/schema\/EditForm",
		"attributes": {
			"id": "Form_EditForm",
			"action": "admin\/campaigns\/EditForm",
			"method": "POST",
			"enctype": "multipart\/form-data",
			"target": null
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
				"recordType": "{$treeClassJS}",
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
					{"name": "Changes", "field": "ChangesCount"},
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
	 * REST endpoint to get a list of campaigns.
	 *
	 * @return SS_HTTPResponse
	 */
	public function readCampaigns() {
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
		/** @var string $treeClass */
		$treeClass = $this->config()->tree_class;
		$hal = [
			'count' => $count,
			'total' => $count,
			'_links' => [
				'self' => [
					'href' => $this->Link('items')
				]
			],
			'_embedded' => [$treeClass => []]
		];
		foreach($items as $item) {
			/** @var ChangeSet $item */
			$resource = $this->getChangeSetResource($item);
			$hal['_embedded'][$treeClass][] = $resource;
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
		// Before presenting the changeset to the client,
		// synchronise it with new changes.
		$changeSet->sync();
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
			'canEdit' => $changeSet->canEdit(),
			'canPublish' => $changeSet->canPublish(),
			'_embedded' => ['items' => []]
		];
		foreach($changeSet->Changes() as $changeSetItem) {
			if(!$changeSetItem) {
				continue;
			}

			/** @var ChangesetItem $changeSetItem */
			$resource = $this->getChangeSetItemResource($changeSetItem);
			$hal['_embedded']['items'][] = $resource;
		}
		$hal['ChangesCount'] = count($hal['_embedded']['items']);
		return $hal;
	}

	/**
	 * Build item resource from a changesetitem
	 *
	 * @param ChangeSetItem $changeSetItem
	 * @return array
	 */
	protected function getChangeSetItemResource(ChangeSetItem $changeSetItem) {
		$baseClass = DataObject::getSchema()->baseDataClass($changeSetItem->ObjectClass);
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
		// Get preview urls
		$previews = $changeSetItem->getPreviewLinks();
		if($previews) {
			$hal['_links']['preview'] = $previews;
		}

		// Get edit link
		$editLink = $changeSetItem->CMSEditLink();
		if($editLink) {
			$hal['_links']['edit'] = [
				'href' => $editLink,
			];
		}

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
				/** @var ChangeSet $item */
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
			if (!$request->param('Name')) {
				return (new SS_HTTPResponse(null, 400));
			}

			/** @var ChangeSet $changeSet */
			$changeSet = ChangeSet::get()->byID($request->param('ID'));
			if(!$changeSet) {
				return (new SS_HTTPResponse(null, 404));
			}

			if(!$changeSet->canView()) {
				return (new SS_HTTPResponse(null, 403));
			}

			$body = Convert::raw2json($this->getChangeSetResource($changeSet));
			return (new SS_HTTPResponse($body, 200))
				->addHeader('Content-Type', 'application/json');
		} else {
			return $this->index($request);
		}
	}

	/**
	 * REST endpoint to delete a campaign.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function deleteCampaign(SS_HTTPRequest $request) {
		// Check security ID
		if (!SecurityToken::inst()->checkRequest($request)) {
			return new SS_HTTPResponse(null, 400);
		}

		$id = $request->param('ID');
		if (!$id || !is_numeric($id)) {
			return (new SS_HTTPResponse(null, 400));
		}

		$record = ChangeSet::get()->byID($id);
		if(!$record) {
			return (new SS_HTTPResponse(null, 404));
		}

		if(!$record->canDelete()) {
			return (new SS_HTTPResponse(null, 403));
		}

		$record->delete();

		return (new SS_HTTPResponse(null, 204));
	}

	/**
	 * REST endpoint to publish a {@link ChangeSet} and all of its items.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function publishCampaign(SS_HTTPRequest $request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) {
			return (new SS_HTTPResponse(null, 400));
		}

		$id = $request->param('ID');
		if(!$id || !is_numeric($id)) {
			return (new SS_HTTPResponse(null, 400));
		}

		/** @var ChangeSet $record */
		$record = ChangeSet::get()->byID($id);
		if(!$record) {
			return (new SS_HTTPResponse(null, 404));
		}

		if(!$record->canPublish()) {
			return (new SS_HTTPResponse(null, 403));
		}

		try {
			$record->publish();
		} catch(LogicException $e) {
			return (new SS_HTTPResponse(json_encode(['status' => 'error', 'message' => $e->getMessage()]), 401))
				->addHeader('Content-Type', 'application/json');
		}

		return (new SS_HTTPResponse(
			Convert::raw2json($this->getChangeSetResource($record)),
			200
		))->addHeader('Content-Type', 'application/json');
	}

	/**
	 * Url handler for edit form
	 *
	 * @param SS_HTTPRequest $request
	 * @return Form
	 */
	public function DetailEditForm($request) {
		// Get ID either from posted back value, or url parameter
		$id = $request->param('ID') ?: $request->postVar('ID');
		return $this->getDetailEditForm($id);
	}

	/**
	 * @todo Use GridFieldDetailForm once it can handle structured data and form schemas
	 *
	 * @param int $id
	 * @return Form
	 */
	public function getDetailEditForm($id) {
		// Get record-specific fields
		$record = null;
		if($id) {
			$record = ChangeSet::get()->byID($id);
			if(!$record || !$record->canView()) {
				return null;
			}
		}

		if(!$record) {
			$record = ChangeSet::singleton();
		}

		$fields = $record->getCMSFields();

		// Add standard fields
		$fields->push(HiddenField::create('ID'));
		$form = Form::create(
			$this,
			'DetailEditForm',
			$fields,
			FieldList::create(
				FormAction::create('save', _t('CMSMain.SAVE', 'Save')),
				FormAction::create('cancel', _t('LeftAndMain.CANCEL', 'Cancel'))
			)
		);

		// Load into form
		if($id && $record) {
			$form->loadDataFrom($record);
		}

		// Configure form to respond to validation errors with form schema
		// if requested via react.
		$form->setValidationResponseCallback(function() use ($form) {
			return $this->getSchemaResponse($form);
		});

		return $form;
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

}
