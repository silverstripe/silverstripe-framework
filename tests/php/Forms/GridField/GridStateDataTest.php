<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\GridField\GridState_Data;
use SilverStripe\Forms\GridField\GridState;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Dev\SapphireTest;

class GridStateDataTest extends SapphireTest
{

    public function testGetData()
    {
        $state = new GridState_Data();

        $this->assertEquals('Bar', $state->getData('Foo', 'Bar'));
        $this->assertEquals('Bar', $state->Foo);
        $this->assertEquals('Bar', $state->getData('Foo', 'Hello World'));
    }

    public function testCall()
    {
        $state = new GridState_Data();

        $foo = $state->Foo();
        $this->assertInstanceOf(GridState_Data::class, $foo);

        $bar = $state->Bar(123456);
        $this->assertEquals(123456, $bar);

        $zone = $state->Zone(null);
        $this->assertEquals(null, $zone);
    }

    public function testInitDefaults()
    {
        $state = new GridState_Data();
        $state->initDefaults(['Foo' => 'Bar', 'Hello' => 'World']);

        $this->assertEquals('Bar', $state->Foo);
        $this->assertEquals('World', $state->Hello);
    }

    public function testToArray()
    {
        $state = new GridState_Data();

        $this->assertEquals([], $state->toArray());

        $state->Foo = 'Bar';
        $this->assertEquals(['Foo' => 'Bar'], $state->toArray());

        $state->initDefaults(['Foo' => 'Bar', 'Hello' => 'World']);

        $this->assertEquals(['Foo' => 'Bar', 'Hello' => 'World'], $state->toArray());
        $this->assertEquals([], $state->getChangesArray());

        $boom = $state->Boom();
        $boom->Pow = 'Kaboom';

        $state->Boom(null);

        $this->assertEquals(['Foo' => 'Bar', 'Hello' => 'World', 'Boom' => ['Pow' => 'Kaboom']], $state->toArray());
        $this->assertEquals(['Boom' => ['Pow' => 'Kaboom']], $state->getChangesArray());
    }

    public function testInitDefaultsAfterSetValue()
    {
        $state = new GridState(new GridField('x'));
        $state->setValue('{"Foo":{"Bar":"Baz","Wee":null}}');
        $data = $state->getData();

        $data->Foo->initDefaults([
            'Bar' => 'Bing',
            'Zoop' => 'Zog',
            'Wee' => 'Wing',
        ]);

        $this->assertEquals(['Bar' => 'Baz', 'Zoop' => 'Zog', 'Wee' => null], $data->Foo->toArray());
        $this->assertEquals(['Bar' => 'Baz', 'Wee' => null], $data->Foo->getChangesArray());
    }
}
