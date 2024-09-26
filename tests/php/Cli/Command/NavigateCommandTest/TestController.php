<?php

namespace SilverStripe\Cli\Tests\Command\NavigateCommandTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

class TestController extends Controller
{
    private static $allowed_actions = [
        'actionOne',
        'errorResponse',
    ];

    public function index(HTTPRequest $request): HTTPResponse
    {
        $var1 = $request->getVar('var1');
        $var2 = $request->getVar('var2');
        $var3 = $request->getVar('var3');
        $var4 = $request->getVar('var4');

        $output = 'This is the index for TestController.';

        if ($var1) {
            $output .= ' var1=' . $var1;
        }
        if ($var2) {
            $output .= ' var2=' . $var2;
        }
        if ($var3) {
            $output .= ' var3=' . $var3;
        }
        if ($var4) {
            $output .= ' var4=' . implode(',', $var4);
        }

        $this->response->setBody($output);
        return $this->response;
    }

    public function actionOne(HTTPRequest $request): HTTPResponse
    {
        $this->response->setBody('This is action one!');
        return $this->response;
    }

    public function errorResponse(HTTPRequest $request): HTTPResponse
    {
        $this->httpError(500);
    }
}
