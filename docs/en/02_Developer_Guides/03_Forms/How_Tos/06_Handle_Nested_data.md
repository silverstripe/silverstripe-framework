---
title: How to handle nested data in forms
summary: Forms can save into arrays, including has_one relations
iconBrand: wpforms
---

# How to: Save nested data

## Overview

Forms often save into fields `DataObject` records, through [Form::saveInto()](api:Form::saveInto()).
There are a number of ways to save nested data into those records, including their relationships.

Let's take the following data structure, and walk through different approaches.

```php
<?php
use SilverStripe\ORM\DataObject;

class Player extends DataObject
{
    private static $db = [
        'Name' => 'Varchar',
    ];

    private static $has_one = [
        'HometownTeam' => Team::class,
    ];

    private static $many_many = [
        'Teams' => Team::class,
    ];
}
```

```
<?php
use SilverStripe\ORM\DataObject;

class Team extends DataObject
{
    private static $db = [
        'Name' => 'Varchar',
    ];

    private static $belongs_many_many = [
        'Players' => Player::class,
    ];
}
```

## Form fields

Some form fields like [MultiSelectField](api:MultiSelectField) and [CheckboxSetField](api:CheckboxSetField)
support saving lists of identifiers into a relation. Naming the field by the relation name will
trigger the form field to write into the relationship.

Example: Select teams for an existing player

```php
<?php

use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;

class MyController extends Controller
{
    private static $allowed_actions = ['Form'];

    private static $url_segment = 'MyController';

    public function Form()
    {
        $player = Player::get()->byID(1);
        return Form::create(
            $this,
            'Form',
            FieldList::create([
                TextField::create('Name'),
                CheckboxSetField::create('Teams')
                    ->setSource(Team::get()->map()),
                HiddenField::create('ID'),
            ]),
            FieldList::create([
                FormAction::create('doSubmitForm', 'Submit')
            ]),
            RequiredFields::create([
                'Name',
                'Teams',
                'ID',
            ])
        )->loadDataFrom($player);
    }

    public function doSubmitForm($data, $form)
    {
        $player = Player::get()->byID($data['ID']);

        // Only works for updating existing records
        if (!$player) {
            return false;
        }

        // Check permissions for the current user.
        if (!$player->canEdit()) {
            return false;
        }

        // Automatically writes Teams() relationship
        $form->saveInto($player);

        $form->sessionMessage('Saved!', 'good');

        return $this->redirectBack();
    }
}
```


## Dot notation

For single record relationships (e.g. `has_one`),
forms can automatically traverse into this relationship by using dot notation
in the form field name. This also works with custom getters returning 
`DataObject` instances. 

Example: Update team name (via a `has_one` relationship) on an existing player.

```php
<?php

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;

class MyController extends Controller
{
    private static $allowed_actions = ['Form'];

    private static $url_segment = 'MyController';

    public function Form()
    {
        return Form::create(
            $this,
            'Form',
            FieldList::create([
                TextField::create('Name'),
                TextField::create('HometownTeam.Name'),
                HiddenField::create('ID'),
            ]),
            FieldList::create([
                FormAction::create('doSubmitForm', 'Submit')
            ]),
            RequiredFields::create([
                'Name',
                'HometownTeam.Name',
                'ID',
            ])
        );
    }

    public function doSubmitForm($data, $form)
    {
        $player = Player::get()->byID($data['ID']);

        // Only works for updating existing records
        if (!$player) {
            return false;
        }

        // Check permissions for the current user.
        if (!$player->canEdit() || !$player->HometownTeam()->canEdit()) {
            return false;
        }

        $form->saveInto($player);

        // Write relationships *before* the original object
        // to avoid changes being lost when flush() is called after write().
        // CAUTION: This will create a new record if none is set on the relationship.
        // This might or might not be desired behaviour.
        $player->HometownTeam()->write();
        $player->write();

        $form->sessionMessage('Saved!', 'good');

        return $this->redirectBack();
    }
}
```

## Array notation

This is the most advanced technique, since it works with the form submission directly,
rather than relying on form field logic. 

Example: Create one or more new teams for existing player

```
<?php

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;

class MyController extends Controller
{
    private static $allowed_actions = ['Form'];

    private static $url_segment = 'MyController';

    public function Form()
    {
        $player = Player::get()->byID(1);
        return Form::create(
            $this,
            'Form',
            FieldList::create([
                TextField::create('Name'),
                // The UI could duplicate this field to allow creating multiple fields
                TextField::create('NewTeams[]', 'New Teams'),
                HiddenField::create('ID'),
            ]),
            FieldList::create([
                FormAction::create('doSubmitForm', 'Submit')
            ]),
            RequiredFields::create([
                'Name',
                'MyTeams[]',
                'ID',
            ])
        )->loadDataFrom($player);
    }

    public function doSubmitForm($data, $form)
    {
        $player = Player::get()->byID($data['ID']);

        // Only works for updating existing records
        if (!$player) {
            return false;
        }

        // Check permissions for the current user.
        // if (!$player->canEdit()) {
        //     return false;
        // }

        $form->saveInto($player);

        // Manually create teams based on provided data
        foreach ($data['NewTeams'] as $teamName) {
            // Caution: Requires data validation on model
            $team = Team::create()->update(['Name' => $teamName]);
            $team->write();
            $player->Teams()->add($team);
        }

        $form->sessionMessage('Saved!', 'good');

        return $this->redirectBack();
    }
}
```
