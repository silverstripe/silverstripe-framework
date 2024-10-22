<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;

class TestBreadcrumbsController extends Controller implements TestOnly
{
    public function Breadcrumbs()
    {
        return new ArrayList([new ArrayData(['Title' => 'My Controller', 'Link' => 'my-link'])]);
    }
}
