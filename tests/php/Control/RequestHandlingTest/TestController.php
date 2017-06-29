<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\View\SSViewer;

/**
 * Controller for the test
 */
class TestController extends Controller implements TestOnly
{
    private static $url_segment = 'TestController';

    private static $allowed_actions = array(
        'method',
        'legacymethod',
        'virtualfile',
        'TestForm',
        'throwexception',
        'throwresponseexception',
        'throwhttperror',
    );

    private static $url_handlers = array(
        // The double-slash is need here to ensure that
        '$Action//$ID/$OtherID' => "handleAction",
    );

    private static $extensions = array(
        ControllerExtension::class,
        AllowedControllerExtension::class,
    );

    public function __construct()
    {
        $this->failover = new ControllerFailover();
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    public function index(HTTPRequest $request)
    {
        return "This is the controller";
    }

    public function method(HTTPRequest $request)
    {
        return "This is a method on the controller: " . $request->param('ID') . ', ' . $request->param('OtherID');
    }

    public function legacymethod(HTTPRequest $request)
    {
        return "\$this->urlParams can be used, for backward compatibility: " . $this->urlParams['ID'] . ', '
        . $this->urlParams['OtherID'];
    }

    public function virtualfile(HTTPRequest $request)
    {
        return "This is the virtualfile method";
    }

    public function TestForm()
    {
        return new TestForm(
            $this,
            "TestForm",
            new FieldList(
                new TestFormField("MyField"),
                new SubclassedFormField("SubclassedField")
            ),
            new FieldList(
                new FormAction("myAction")
            )
        );
    }

    public function throwexception()
    {
        throw new HTTPResponse_Exception('This request was invalid.', 400);
    }

    public function throwresponseexception()
    {
        throw new HTTPResponse_Exception(new HTTPResponse('There was an internal server error.', 500));
    }

    public function throwhttperror()
    {
        $this->httpError(404, 'This page does not exist.');
    }

    public function getViewer($action)
    {
        return new SSViewer('BlankPage');
    }
}
