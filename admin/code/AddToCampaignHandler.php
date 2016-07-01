<?php

use SilverStripe\Framework\Core\Injectable;

use SilverStripe\ORM\Versioning\ChangeSet;
use SilverStripe\ORM\DataObject;
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
class AddToCampaignHandler {
	use Injectable;

	/**
	 * The EditForm that contains the action we're being delegated to from
	 *
	 * @var Form
	 */
	protected $editForm;

	/**
	 * The submitted form data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * AddToCampaignHandler constructor.
	 *
	 * @param Form $editForm The parent form that triggered this action
	 * @param array $data The data submitted as part of that form
	 */
	public function __construct($editForm, $data) {
		$this->editForm = $editForm;
		$this->data = $data;
	}

	/**
	 * Perform the action. Either returns a Form or performs the action, as per the class doc
	 *
	 * @return DBHTMLText|SS_HTTPResponse
	 */
	public function handle() {
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
	protected function getAvailableChangeSets() {
		return ChangeSet::get()
			->filter('State', ChangeSet::STATE_OPEN)
			->filterByCallback(function($item) {
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
	 * @throws SS_HTTPResponse_Exception
	 */
	protected function getObject($id, $class) {
		$id = (int)$id;
		$class = ClassInfo::class_name($class);

		if (!$class || !is_subclass_of($class, 'SilverStripe\\ORM\\DataObject') || !Object::has_extension($class, 'SilverStripe\\ORM\\Versioning\\Versioned')) {
			$this->editForm->httpError(400, _t(
				'AddToCampaign.ErrorGeneral',
				'We apologise, but there was an error'
			));
			return null;
		}

		$object = DataObject::get($class)->byID($id);

		if (!$object) {
			$this->editForm->httpError(404, _t(
				'AddToCampaign.ErrorNotFound',
				'That {Type} couldn\'t be found',
				'',
				['Type' => $class]
			));
			return null;
		}

		if (!$object->canView()) {
			$this->editForm->httpError(403, _t(
					'AddToCampaign.ErrorItemPermissionDenied',
					'It seems you don\'t have the necessary permissions to add {ObjectTitle} to a campaign',
					'',
					['ObjectTitle' => $object->Title]
				)
			);
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
	public function Form($object) {
		$inChangeSets = array_unique(ChangeSetItem::get_for_object($object)->column('ChangeSetID'));
		$changeSets = $this->getAvailableChangeSets()->map();

		$campaignDropdown = DropdownField::create('Campaign', '', $changeSets);
		$campaignDropdown->setEmptyString(_t('Campaigns.AddToCampaign', 'Select a Campaign'));
		$campaignDropdown->addExtraClass('noborder');
		$campaignDropdown->setDisabledItems($inChangeSets);

		$fields = new FieldList([
			$campaignDropdown,
			HiddenField::create('ID', null, $this->data['ID']),
			HiddenField::create('ClassName', null, $this->data['ClassName'])
		]);

		$form = new Form(
			$this->editForm->getController(),
			$this->editForm->getName(),
			new FieldList(
				$header = new CompositeField(
					new LiteralField(
						'Heading',
						sprintf('<h3>%s</h3>', _t('Campaigns.AddToCampaign', 'Add To Campaign'))
					)
				),

				$content = new CompositeField($fields)
			),
			new FieldList(
				$action = AddToCampaignHandler_FormAction::create()
			)
		);

		$header->addExtraClass('add-to-campaign__header');
		$content->addExtraClass('add-to-campaign__content');
		$action->addExtraClass('add-to-campaign__action');

		$form->setHTMLID('Form_EditForm_AddToCampaign');

		$form->unsetValidator();
		$form->loadDataFrom($this->data);
		$form->addExtraClass('add-to-campaign__form');

		return $form;
	}

	/**
	 * Performs the actual action of adding the object to the ChangeSet, once the ChangeSet ID is known
	 *
	 * @param DataObject $object The object to add to the ChangeSet
	 * @param int $campaignID The ID of the ChangeSet to add $object to
	 * @return SS_HTTPResponse
	 * @throws SS_HTTPResponse_Exception
	 */
	public function addToCampaign($object, $campaignID) {
		/** @var ChangeSet $changeSet */
		$changeSet = ChangeSet::get()->byID($campaignID);

		if (!$changeSet) {
			$this->editForm->httpError(404, _t(
				'AddToCampaign.ErrorNotFound',
				'That {Type} couldn\'t be found',
				'',
				['Type' => 'Campaign']
			));
			return null;
		}

		if (!$changeSet->canEdit()) {
			$this->editForm->httpError(403, _t(
				'AddToCampaign.ErrorCampaignPermissionDenied',
				'It seems you don\'t have the necessary permissions to add {ObjectTitle} to {CampaignTitle}',
				'',
				['ObjectTitle' => $object->Title, 'CampaignTitle' => $changeSet->Title]
			));
			return null;
		}

		$changeSet->addObject($object);

		if (Director::is_ajax()) {
			$response = new SS_HTTPResponse(_t(
				'AddToCampaign.Success',
				'Successfully added {ObjectTitle} to {CampaignTitle}',
				'',
				['ObjectTitle' => $object->Title, 'CampaignTitle' => $changeSet->Title]
			), 200);

			$response->addHeader('Content-Type', 'text/plain; charset=utf-8');
			return $response;
		} else {
			return $this->editForm->getController()->redirectBack();
		}
	}
}

/**
 * A form action to return from geCMSActions or otherwise include in a CMS Edit Form that
 * has the right action name and CSS classes to trigger the AddToCampaignHandler.
 *
 * See SiteTree.php and CMSMain.php for an example of it's use
 */
class AddToCampaignHandler_FormAction extends FormAction {

	function __construct() {
		parent::__construct('addtocampaign', _t('CAMPAIGNS.ADDTOCAMPAIGN', 'Add to campaign'));
		$this->addExtraClass('add-to-campaign-action');
		$this->setValidationExempt(true);
	}
}
