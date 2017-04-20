<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;

/**
 * @skipUpgrade
 */
class ValidatorForm extends Form implements TestOnly
{

    public function __construct()
    {
        parent::__construct(
            Controller::curr(),
            __CLASS__,
            new FieldList(
                new TextField('Email'),
                new TextField('Surname'),
                new TextField('ID'),
                new TextField('FirstName')
            ),
            new FieldList(
                new FormAction('someAction')
            )
        );
    }
}
