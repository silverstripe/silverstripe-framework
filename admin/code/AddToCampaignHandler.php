<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Object;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\Versioning\ChangeSet;
use SilverStripe\ORM\Versioning\ChangeSetItem;

/**
 * Class AddToCampaignHandler - handle the AddToCampaign action.
 *
 * This is a class designed to be delegated to by a Form action handler method in the EditForm of a LeftAndMain
 * child class.
 *
 * Add To Campaign can be seen as an item action like "publish" or "rollback", but unlike those actions
 * it needs one additional piece of information to execute, the ChangeSet ID.
 *
 * So this handler does one of two things to respond to the action request, depending on whether the ChangeSet ID
 * was included in the submitted data
 * - If it was, perform the Add To Campaign action (as per any other action)
 * - If it wasn't, return a form to get the ChangeSet ID and then repeat this action submission
 *
 * To use, you'd add an action to your LeftAndMain subclass, like this:
 *
 *     function addtocampaign($data, $form) {
 *         $handler = AddToCampaignHandler::create($form, $data);
 *         return $handler->handle();
 *     }
 *
 *  and add an AddToCampaignHandler_FormAction to the EditForm, possibly through getCMSActions
 */
class AddToCampaignHandler
{
    use Injectable;

    /**
     * Parent controller for this form
     *
     * @var Controller
     */
    protected $controller;

    /**
     * The submitted form data
     *
     * @var array
     */
    protected $data;

    /**
     * Form name to use
     *
     * @var string
     */
    protected $name;

    /**
     * AddToCampaignHandler constructor.
     *
     * @param Controller $controller Controller for this form
     * @param array|DataObject $data The data submitted as part of that form
     * @param string $name Form name
     */
    public function __construct($controller = null, $data = [], $name = 'AddToCampaignForm')
    {
        $this->controller = $controller;
        if ($data instanceof DataObject) {
            $data = $data->toMap();
        }
        $this->data = $data;
        $this->name = $name;
    }

    /**
     * Perform the action. Either returns a Form or performs the action, as per the class doc
     *
     * @return DBHTMLText|HTTPResponse
     */
    public function handle()
    {
        $object = $this->getObject($this->data['ID'], $this->data['ClassName']);

        if (empty($this->data['Campaign'])) {
            return $this->Form($object)->forTemplate();
        } else {
            return $this->addToCampaign($object, $this->data['Campaign']);
        }
    }

    /**
     * Get what ChangeSets are available for an item to be added to by this user
     *
     * @return ArrayList[ChangeSet]
     */
    protected function getAvailableChangeSets()
    {
        return ChangeSet::get()
            ->filter('State', ChangeSet::STATE_OPEN)
            ->filterByCallback(function ($item) {
                /** @var ChangeSet $item */
                return $item->canView();
            });
    }

    /**
     * Safely get a DataObject from a client-supplied ID and ClassName, checking: argument
     * validity; existence; and canView permissions.
     *
     * @param int $id The ID of the DataObject
     * @param string $class The Class of the DataObject
     * @return DataObject The referenced DataObject
     * @throws HTTPResponse_Exception
     */
    protected function getObject($id, $class)
    {
        $id = (int)$id;
        $class = ClassInfo::class_name($class);

        if (!$class || !is_subclass_of($class, 'SilverStripe\\ORM\\DataObject') || !Object::has_extension($class, 'SilverStripe\\ORM\\Versioning\\Versioned')) {
            $this->controller->httpError(400, _t(
                'AddToCampaign.ErrorGeneral',
                'We apologise, but there was an error'
            ));
            return null;
        }

        $object = DataObject::get($class)->byID($id);

        if (!$object) {
            $this->controller->httpError(404, _t(
                'AddToCampaign.ErrorNotFound',
                'That {Type} couldn\'t be found',
                '',
                ['Type' => $class]
            ));
            return null;
        }

        if (!$object->canView()) {
            $this->controller->httpError(403, _t(
                'AddToCampaign.ErrorItemPermissionDenied',
                'It seems you don\'t have the necessary permissions to add {ObjectTitle} to a campaign',
                '',
                ['ObjectTitle' => $object->Title]
            ));
            return null;
        }

        return $object;
    }

    /**
     * Builds a Form that mirrors the parent editForm, but with an extra field to collect the ChangeSet ID
     *
     * @param DataObject $object The object we're going to be adding to whichever ChangeSet is chosen
     * @return Form
     */
    public function Form($object)
    {
        $inChangeSets = array_unique(ChangeSetItem::get_for_object($object)->column('ChangeSetID'));
        $changeSets = $this->getAvailableChangeSets()->map();

        $campaignDropdown = DropdownField::create('Campaign', '', $changeSets);
        $campaignDropdown->setEmptyString(_t('Campaigns.AddToCampaignFormFieldLabel', 'Select a Campaign'));
        $campaignDropdown->addExtraClass('noborder');
        $campaignDropdown->addExtraClass('no-chosen');
        $campaignDropdown->setDisabledItems($inChangeSets);

        $fields = new FieldList([
            $campaignDropdown,
            HiddenField::create('ID', null, $this->data['ID']),
            HiddenField::create('ClassName', null, $this->data['ClassName'])
        ]);


        $form = new Form(
            $this->controller,
            $this->name,
            $fields,
            new FieldList(
                $action = AddToCampaignHandler_FormAction::create()
            )
        );

        $action->addExtraClass('add-to-campaign__action');

        $form->setHTMLID('Form_EditForm_AddToCampaign');

        $form->loadDataFrom($this->data);
        $form->getValidator()->addRequiredField('Campaign');
        $form->addExtraClass('form--no-dividers add-to-campaign__form');

        return $form;
    }

    /**
     * Performs the actual action of adding the object to the ChangeSet, once the ChangeSet ID is known
     *
     * @param DataObject $object The object to add to the ChangeSet
     * @param int $campaignID The ID of the ChangeSet to add $object to
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function addToCampaign($object, $campaignID)
    {
        /** @var ChangeSet $changeSet */
        $changeSet = ChangeSet::get()->byID($campaignID);

        if (!$changeSet) {
            $this->controller->httpError(404, _t(
                'AddToCampaign.ErrorNotFound',
                'That {Type} couldn\'t be found',
                '',
                ['Type' => 'Campaign']
            ));
            return null;
        }

        if (!$changeSet->canEdit()) {
            $this->controller->httpError(403, _t(
                'AddToCampaign.ErrorCampaignPermissionDenied',
                'It seems you don\'t have the necessary permissions to add {ObjectTitle} to {CampaignTitle}',
                '',
                ['ObjectTitle' => $object->Title, 'CampaignTitle' => $changeSet->Title]
            ));
            return null;
        }

        $changeSet->addObject($object);

        $request = $this->controller->getRequest();
        $message = _t(
            'AddToCampaign.Success',
            'Successfully added {ObjectTitle} to {CampaignTitle}',
            '',
            ['ObjectTitle' => $object->Title, 'CampaignTitle' => $changeSet->Title]
        );
        if ($request->getHeader('X-Formschema-Request')) {
            return $message;
        } elseif (Director::is_ajax()) {
            $response = new HTTPResponse($message, 200);

            $response->addHeader('Content-Type', 'text/plain; charset=utf-8');
            return $response;
        } else {
            return $this->controller->getController()->redirectBack();
        }
    }
}
