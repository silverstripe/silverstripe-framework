<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\Versioning\ChangeSet;
use SilverStripe\ORM\Versioning\ChangeSetItem;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\UnexpectedDataException;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\PermissionProvider;
use LogicException;

/**
 * Campaign section of the CMS
 */
class CampaignAdmin extends LeftAndMain implements PermissionProvider
{

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

    private static $menu_icon_class = 'font-icon-page-multiple';

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

    private static $required_permission_codes = 'CMS_ACCESS_CampaignAdmin';

    public function getClientConfig()
    {
        return array_merge(parent::getClientConfig(), [
            'reactRouter' => true,
            'form' => [
                // TODO Use schemaUrl instead
                'EditForm' => [
                    'schemaUrl' => $this->Link('schema/EditForm')
                ],
                'DetailEditForm' => [
                    'schemaUrl' => $this->Link('schema/DetailEditForm')
                ],
            ],
            'itemListViewEndpoint' => [
                'url' => $this->Link() . 'set/:id/show',
                'method' => 'get'
            ],
            'publishEndpoint' => [
                'url' => $this->Link() . 'set/:id/publish',
                'method' => 'post'
            ],
            'treeClass' => $this->config()->tree_class
        ]);
    }

    public function getEditForm($id = null, $fields = null)
    {
        $fields = new FieldList(
            CampaignAdminList::create('ChangeSets')
        );
        $actions = new FieldList();
        $form = Form::create($this, 'EditForm', $fields, $actions);

        // Set callback response
        $form->setValidationResponseCallback(function () use ($form) {
            $schemaId = $this->Link('schema/EditForm');
            return $this->getSchemaResponse($form, $schemaId);
        });

        return $form;
    }

    public function EditForm($request = null)
    {
        // Get ID either from posted back value, or url parameter
        $request = $request ?: $this->getRequest();
        $id = $request->param('ID') ?: $request->postVar('ID');
        return $this->getEditForm($id);
    }

    /**
     * REST endpoint to get a list of campaigns.
     *
     * @return HTTPResponse
     */
    public function readCampaigns()
    {
        $response = new HTTPResponse();
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
    protected function getListResource()
    {
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
        foreach ($items as $item) {
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
    protected function getChangeSetResource(ChangeSet $changeSet)
    {
        $hal = [
            '_links' => [
                'self' => [
                    'href' => $this->SetLink($changeSet->ID)
                ]
            ],
            'ID' => $changeSet->ID,
            'Name' => $changeSet->Name,
            'Created' => $changeSet->Created,
            'LastEdited' => $changeSet->LastEdited,
            'State' => $changeSet->State,
            'IsInferred' => $changeSet->IsInferred,
            'canEdit' => $changeSet->canEdit(),
            'canPublish' => false,
            '_embedded' => ['items' => []]
        ];

        // Before presenting the changeset to the client,
        // synchronise it with new changes.
        try {
            $changeSet->sync();
            $hal['Description'] = $changeSet->getDescription();
            $hal['canPublish'] = $changeSet->canPublish() && $changeSet->hasChanges();

            foreach ($changeSet->Changes() as $changeSetItem) {
                if (!$changeSetItem) {
                    continue;
                }

                /** @var ChangesetItem $changeSetItem */
                $resource = $this->getChangeSetItemResource($changeSetItem);
                $hal['_embedded']['items'][] = $resource;
            }
            $hal['ChangesCount'] = count($hal['_embedded']['items']);

        // An unexpected data exception means that the database is corrupt
        } catch (UnexpectedDataException $e) {
            $hal['Description'] = 'Corrupt database! ' . $e->getMessage();
            $hal['ChangesCount'] = '-';
        }
        return $hal;
    }

    /**
     * Build item resource from a changesetitem
     *
     * @param ChangeSetItem $changeSetItem
     * @return array
     */
    protected function getChangeSetItemResource(ChangeSetItem $changeSetItem)
    {
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
        if ($previews) {
            $hal['_links']['preview'] = $previews;
        }

        // Get edit link
        $editLink = $changeSetItem->CMSEditLink();
        if ($editLink) {
            $hal['_links']['edit'] = [
                'href' => $editLink,
            ];
        }

        // Depending on whether the object was added implicitly or explicitly, set
        // other related objects.
        if ($changeSetItem->Added === ChangeSetItem::IMPLICITLY) {
            $referencedItems = $changeSetItem->ReferencedBy();
            $referencedBy = [];
            foreach ($referencedItems as $referencedItem) {
                $referencedBy[] = [
                    'href' => $this->SetLink($referencedItem->ID)
                ];
            }
            if ($referencedBy) {
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
    protected function getListItems()
    {
        return ChangeSet::get()
            ->filter('State', ChangeSet::STATE_OPEN)
            ->filterByCallback(function ($item) {
                /** @var ChangeSet $item */
                return ($item->canView());
            });
    }


    /**
     * REST endpoint to get a campaign.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse
     */
    public function readCampaign(HTTPRequest $request)
    {
        $response = new HTTPResponse();

        if ($request->getHeader('Accept') == 'text/json') {
            $response->addHeader('Content-Type', 'application/json');
            if (!$request->param('Name')) {
                return (new HTTPResponse(null, 400));
            }

            /** @var ChangeSet $changeSet */
            $changeSet = ChangeSet::get()->byID($request->param('ID'));
            if (!$changeSet) {
                return (new HTTPResponse(null, 404));
            }

            if (!$changeSet->canView()) {
                return (new HTTPResponse(null, 403));
            }

            $body = Convert::raw2json($this->getChangeSetResource($changeSet));
            return (new HTTPResponse($body, 200))
                ->addHeader('Content-Type', 'application/json');
        } else {
            return $this->index($request);
        }
    }

    /**
     * REST endpoint to delete a campaign.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse
     */
    public function deleteCampaign(HTTPRequest $request)
    {
        // Check security ID
        if (!SecurityToken::inst()->checkRequest($request)) {
            return new HTTPResponse(null, 400);
        }

        $id = $request->param('ID');
        if (!$id || !is_numeric($id)) {
            return (new HTTPResponse(null, 400));
        }

        $record = ChangeSet::get()->byID($id);
        if (!$record) {
            return (new HTTPResponse(null, 404));
        }

        if (!$record->canDelete()) {
            return (new HTTPResponse(null, 403));
        }

        $record->delete();

        return (new HTTPResponse(null, 204));
    }

    /**
     * REST endpoint to publish a {@link ChangeSet} and all of its items.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse
     */
    public function publishCampaign(HTTPRequest $request)
    {
        // Protect against CSRF on destructive action
        if (!SecurityToken::inst()->checkRequest($request)) {
            return (new HTTPResponse(null, 400));
        }

        $id = $request->param('ID');
        if (!$id || !is_numeric($id)) {
            return (new HTTPResponse(null, 400));
        }

        /** @var ChangeSet $record */
        $record = ChangeSet::get()->byID($id);
        if (!$record) {
            return (new HTTPResponse(null, 404));
        }

        if (!$record->canPublish()) {
            return (new HTTPResponse(null, 403));
        }

        try {
            $record->publish();
        } catch (LogicException $e) {
            return (new HTTPResponse(json_encode(['status' => 'error', 'message' => $e->getMessage()]), 401))
                ->addHeader('Content-Type', 'application/json');
        }

        return (new HTTPResponse(
            Convert::raw2json($this->getChangeSetResource($record)),
            200
        ))->addHeader('Content-Type', 'application/json');
    }

    /**
     * Url handler for edit form
     *
     * @param HTTPRequest $request
     * @return Form
     */
    public function DetailEditForm($request)
    {
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
    public function getDetailEditForm($id = null)
    {
        // Get record-specific fields
        $record = null;
        if ($id) {
            $record = ChangeSet::get()->byID($id);
            if (!$record || !$record->canView()) {
                return null;
            }
        }

        if (!$record) {
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
                FormAction::create('save', _t('CMSMain.SAVE', 'Save'))
                    ->setIcon('save'),
                FormAction::create('cancel', _t('LeftAndMain.CANCEL', 'Cancel'))
                    ->setUseButtonTag(true)
            ),
            new RequiredFields('Name')
        );

        // Load into form
        if ($id && $record) {
            $form->loadDataFrom($record);
        }
        // Configure form to respond to validation errors with form schema
        // if requested via react.
        $form->setValidationResponseCallback(function (ValidationResult $errors) use ($form, $record) {
            $schemaId = Controller::join_links(
                $this->Link('schema/DetailEditForm'),
                $record->isInDB() ? $record->ID : ''
            );
            return $this->getSchemaResponse($schemaId, $form, $errors);
        });

        return $form;
    }

    /**
     * Gets user-visible url to edit a specific {@see ChangeSet}
     *
     * @param $itemID
     * @return string
     */
    public function SetLink($itemID)
    {
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
    public function ItemLink($itemID)
    {
        return Controller::join_links(
            $this->Link('item'),
            $itemID
        );
    }

    public function providePermissions()
    {
        return array(
            "CMS_ACCESS_CampaignAdmin" => array(
                'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => static::menu_title())),
                'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(
                    'CampaignAdmin.ACCESS_HELP',
                    'Allow viewing of the campaign publishing section.'
                )
            )
        );
    }
}
