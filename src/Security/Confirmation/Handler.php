<?php

namespace SilverStripe\Security\Confirmation;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form as BaseForm;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;

/**
 * Confirmation form handler implementation
 *
 * Handles StorageID identifier in the URL
 */
class Handler extends RequestHandler
{
    private static $url_handlers = [
        '$StorageID!/$Action//$ID/$OtherID' => '$Action',
    ];

    private static $allowed_actions = [
        'index',
        'Form'
    ];

    public function Link($action = null)
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $link = Controller::join_links(Director::baseURL(), $request->getUrl(), $action);

        $this->extend('updateLink', $link, $action);

        return $link;
    }

    /**
     * URL handler for the log-in screen
     *
     * @return array
     */
    public function index()
    {
        return [
            'Title' => _t(__CLASS__ . '.FORM_TITLE', 'Confirm potentially dangerous action'),
            'Form' => $this->Form()
        ];
    }

    /**
     * This method is being used by Form to check whether it needs to use SecurityToken
     *
     * We always return false here as the confirmation form should decide this on its own
     * depending on the Storage data. If we had the original request to
     * be POST with its own SecurityID, we don't want to interfre with it. If it's been
     * GET request, then it will generate a new SecurityToken
     *
     * @return bool
     */
    public function securityTokenEnabled()
    {
        return false;
    }

    /**
     * Returns an instance of Confirmation\Form initialized
     * with the proper storage id taken from URL
     *
     * @return Form
     */
    public function Form()
    {
        $storageId = $this->request->param('StorageID');

        if (!strlen(trim($storageId ?? ''))) {
            $this->httpError(404, "Undefined StorageID");
        }

        return Form::create($storageId, $this, __FUNCTION__);
    }
}
