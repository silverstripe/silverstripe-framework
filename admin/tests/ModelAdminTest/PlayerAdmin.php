<?php

namespace SilverStripe\Admin\Tests\ModelAdminTest;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class PlayerAdmin extends ModelAdmin implements TestOnly
{
    private static $url_segment = 'playeradmin';

    private static $managed_models = array(
        Player::class
    );

    public function getExportFields()
    {
        return array(
            'Name' => 'Name',
            'Position' => 'Position'
        );
    }

    public function Link($action = null)
    {
        if (!$action) {
            $action = $this->sanitiseClassName($this->modelClass);
        }
        return Controller::join_links('PlayerAdmin', $action, '/');
    }
}
