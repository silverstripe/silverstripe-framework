<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Confirmation;

/**
 * A simple controller using DebugView to wrap up the confirmation form
 * with a template similar to other DevelopmentAdmin endpoints and UIs
 *
 * This is done particularly for the confirmation of URL special parameters
 * and /dev/build, so that people opening the confirmation form wouldn't
 * mix it up with some non-dev functionality
 */
class DevConfirmationController extends Confirmation\Handler
{
    public function index()
    {
        $response = parent::index();

        $renderer = DebugView::create();
        echo $renderer->renderHeader();
        echo $renderer->renderInfo(
            _t(__CLASS__ . ".INFO_TITLE", "Security Confirmation"),
            Director::absoluteBaseURL(),
            _t(__CLASS__ . ".INFO_DESCRIPTION", "Confirm potentially dangerous operation")
        );

        return $response;
    }
}
