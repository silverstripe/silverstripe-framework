<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\HTTPResponse;

/**
 * Allow overriding finished state for faux redirects.
 */
class LeftAndMain_HTTPResponse extends HTTPResponse
{

    protected $isFinished = false;

    public function isFinished()
    {
        return (parent::isFinished() || $this->isFinished);
    }

    public function setIsFinished($bool)
    {
        $this->isFinished = $bool;
    }
}
