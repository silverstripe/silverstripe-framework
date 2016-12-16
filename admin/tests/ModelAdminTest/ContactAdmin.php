<?php

namespace SilverStripe\Admin\Tests\ModelAdminTest;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class ContactAdmin extends ModelAdmin implements TestOnly
{
    private static $url_segment = 'contactadmin';

    private static $managed_models = array(
        Contact::class,
    );

    public function Link($action = null)
    {
        if (!$action) {
            $action = $this->sanitiseClassName($this->modelClass);
        }
        return Controller::join_links('ContactAdmin', $action, '/');
    }
}
