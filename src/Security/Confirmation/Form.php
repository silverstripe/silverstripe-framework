<?php

namespace SilverStripe\Security\Confirmation;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form as BaseForm;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LabelField;
use SilverStripe\ORM\ValidationException;

/**
 * Basic confirmation form implementation.
 *
 * Renders the list of confirmation items on the screen
 * and reconciles those with the confirmation storage.
 *
 * If the user confirms the action, marks the storage as confirmed
 * and redirects to the success url (kept in the storage).
 *
 * If the user declines the action, cleans up the storage and
 * redirects to the failure url (kept in the storage).
 */
class Form extends BaseForm
{
    /**
     * Confirmation storage instance
     *
     * @var Storage
     */
    private $storage;

    /**
     * @param string $storageId confirmation storage identifier
     * @param RequestHandler $controller active request handler
     * @param string $formConstructor form constructor name
     */
    public function __construct($storageId, RequestHandler $controller, $formConstructor)
    {
        $request = $controller->getRequest();
        $storage = Injector::inst()->createWithArgs(Storage::class, [$request->getSession(), $storageId, false]);

        if (count($storage->getItems() ?? [])) {
            $fieldList = $this->buildFieldList($storage);
            $actionList = $this->buildActionList($storage);
        } else {
            $fieldList = $this->buildEmptyFieldList();
            $actionList = null;
        }

        parent::__construct($controller, $formConstructor, $fieldList, $actionList);

        if ($storage->getHttpMethod() !== 'POST') {
            $this->enableSecurityToken();
        }

        $this->storage = $storage;
    }

    /**
     * The form refusal handler. Cleans up the confirmation storage
     * and returns the failure redirection (kept in the storage)
     *
     * @return HTTPResponse redirect
     */
    public function doRefuse()
    {
        $url = $this->storage->getFailureUrl();
        $this->storage->cleanup();
        return $this->controller->redirect($url);
    }

    /**
     * The form confirmation handler. Checks all the items in the storage
     * has been confirmed and marks them as such. Returns a redirect
     * when all the storage items has been verified and marked as confirmed.
     *
     * @return HTTPResponse success url
     *
     * @throws ValidationException when the confirmation storage has an item missing on the form
     */
    public function doConfirm()
    {
        $storage = $this->storage;
        $data = $this->getData();

        if (!$storage->confirm($data)) {
            throw new ValidationException('Sorry, we could not verify the parameters');
        }

        $url = $storage->getSuccessUrl();

        return $this->controller->redirect($url);
    }

    protected function buildActionList(Storage $storage)
    {
        $cancel = FormAction::create('doRefuse', _t(__CLASS__ . '.REFUSE', 'Cancel'));
        $confirm = FormAction::create('doConfirm', _t(__CLASS__ . '.CONFIRM', 'Run the action'))->setAutofocus(true);

        if ($storage->getHttpMethod() === 'POST') {
            $confirm->setAttribute('formaction', htmlspecialchars($storage->getSuccessUrl() ?? ''));
        }

        return FieldList::create($cancel, $confirm);
    }

    /**
     * Builds the form fields taking the confirmation items from the storage
     *
     * @param Storage $storage Confirmation storage instance
     *
     * @return FieldList
     */
    protected function buildFieldList(Storage $storage)
    {
        $fields = [];

        foreach ($storage->getItems() as $item) {
            $group = [];

            $group[] = HeaderField::create(null, $item->getName());

            if ($item->getDescription()) {
                $group[] = LabelField::create($item->getDescription());
            }

            $fields[] = FieldGroup::create(...$group);
        }

        foreach ($storage->getHashedItems() as $key => $val) {
            $fields[] = HiddenField::create($key, null, $val);
        }

        if ($storage->getHttpMethod() === 'POST') {
            // add the storage CSRF token
            $fields[] = HiddenField::create($storage->getCsrfToken(), null, '1');

            // replicate the original POST request parameters
            // so that the new confirmed POST request has those
            $data = $storage->getSuccessPostVars();

            if (is_null($data)) {
                throw new ValidationException('Sorry, your cookies seem to have expired. Try to repeat the initial action.');
            }

            foreach ($data as $key => $value) {
                $fields[] = HiddenField::create($key, null, $value);
            }
        }

        return FieldList::create(...$fields);
    }

    /**
     * Builds the fields showing the form is empty and there's nothing
     * to confirm
     *
     * @return FieldList
     */
    protected function buildEmptyFieldList()
    {
        return FieldList::create(
            HeaderField::create(null, _t(__CLASS__ . '.EMPTY_TITLE', 'Nothing to confirm'))
        );
    }
}
