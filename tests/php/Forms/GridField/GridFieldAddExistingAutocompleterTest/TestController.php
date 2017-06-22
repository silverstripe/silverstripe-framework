<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldAddExistingAutocompleterTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;

/**
 * @skipUpgrade
 */
class TestController extends Controller implements TestOnly
{
    public function __construct()
    {
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    private static $allowed_actions = array('Form');

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links('GridFieldAddExistingAutocompleterTest_Controller', $action, '/');
    }

    public function Form()
    {
        /** @var Player $player */
        $player = Player::get()->find('Email', 'player1@test.com');
        $config = GridFieldConfig::create()->addComponents(
            $relationComponent = new GridFieldAddExistingAutocompleter('before'),
            new GridFieldDataColumns()
        );
        $field = new GridField('testfield', 'testfield', $player->Teams(), $config);
        return new Form($this, 'Form', new FieldList($field), new FieldList());
    }
}
