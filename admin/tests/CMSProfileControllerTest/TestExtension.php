<?php

namespace SilverStripe\Admin\Tests\CMSProfileControllerTest;

use SilverStripe\ORM\DataExtension;

class TestExtension extends DataExtension
{
    public function canEdit($member = null)
    {
        return false;
    }
}
