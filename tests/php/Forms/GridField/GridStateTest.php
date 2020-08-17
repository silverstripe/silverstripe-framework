<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridState;

class GridStateTest extends SapphireTest
{

    public function testValue()
    {
        $gridfield = new GridField('Test');

        $state = new GridState($gridfield);
        $this->assertEquals('{}', $state->Value(), 'GridState without any data has empty JSON object for Value');

        $data = $state->getData();
        $data->initDefaults(['Foo' => 'Bar']);

        $this->assertEquals('{}', $state->Value(), 'GridState without change has empty JSON object for Value');

        $data->Foo = 'Barrr';

        $this->assertEquals(
            '{"Foo":"Barrr"}',
            $state->Value(),
            'GridState with changes returns has a JSON object string for Value.'
        );
    }

}
