<?php

namespace SilverStripe\Core\Tests\Config;

use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class ConfigTest extends SapphireTest
{

    public function testNest()
    {
        // Check basic config
        $this->assertEquals(3, Config::inst()->get(ConfigTest\TestNest::class, 'foo'));
        $this->assertEquals(5, Config::inst()->get(ConfigTest\TestNest::class, 'bar'));

        // Test nest copies data
        Config::nest();
        $this->assertEquals(3, Config::inst()->get(ConfigTest\TestNest::class, 'foo'));
        $this->assertEquals(5, Config::inst()->get(ConfigTest\TestNest::class, 'bar'));

        // Test nested data can be updated
        Config::modify()->merge(ConfigTest\TestNest::class, 'foo', 4);
        $this->assertEquals(4, Config::inst()->get(ConfigTest\TestNest::class, 'foo'));
        $this->assertEquals(5, Config::inst()->get(ConfigTest\TestNest::class, 'bar'));

        // Test unnest restores data
        Config::unnest();
        $this->assertEquals(3, Config::inst()->get(ConfigTest\TestNest::class, 'foo'));
        $this->assertEquals(5, Config::inst()->get(ConfigTest\TestNest::class, 'bar'));
    }

    public function testUpdateStatic()
    {
        $this->assertEquals(
            ['test_1'],
            Config::inst()->get(ConfigTest\First::class, 'first')
        );
        $this->assertEquals(
            [
                'test_1',
                'test_2'
            ],
            Config::inst()->get(ConfigTest\Second::class, 'first')
        );
        $this->assertEquals(
            [ 'test_2' ],
            Config::inst()->get(ConfigTest\Second::class, 'first', true)
        );
        $this->assertEquals(
            [
                'test_1',
                'test_2',
                'test_3'
            ],
            Config::inst()->get(ConfigTest\Third::class, 'first')
        );
        $this->assertEquals(
            [ 'test_3' ],
            Config::inst()->get(ConfigTest\Third::class, 'first', true)
        );

        Config::modify()->merge(ConfigTest\First::class, 'first', array('test_1_2'));
        Config::modify()->merge(ConfigTest\Third::class, 'first', array('test_3_2'));
        Config::modify()->merge(ConfigTest\Fourth::class, 'first', array('test_4'));

        $this->assertEquals(
            ['test_1', 'test_1_2'],
            Config::inst()->get(ConfigTest\First::class, 'first')
        );
        $this->assertEquals(
            ['test_1', 'test_1_2'],
            Config::inst()->get(ConfigTest\First::class, 'first', true)
        );

        Config::modify()->merge(ConfigTest\Fourth::class, 'second', array('test_4'));
        Config::modify()->merge(ConfigTest\Third::class, 'second', array('test_3_2'));

        $this->assertEquals(
            ['test_1', 'test_3', 'test_3_2', 'test_4'],
            Config::inst()->get(ConfigTest\Fourth::class, 'second')
        );
        $this->assertEquals(
            ['test_4'],
            Config::inst()->get(ConfigTest\Fourth::class, 'second', true)
        );
        $this->assertEquals(
            ['test_1', 'test_3', 'test_3_2'],
            Config::inst()->get(ConfigTest\Third::class, 'second')
        );
        $this->assertEquals(
            ['test_3', 'test_3_2'],
            Config::inst()->get(ConfigTest\Third::class, 'second', true)
        );

        Config::modify()->remove(ConfigTest\Third::class, 'second');
        $this->assertEquals(
            ['test_1'],
            Config::inst()->get(ConfigTest\Third::class, 'second')
        );
        $this->assertTrue(
            Config::inst()->exists(ConfigTest\Third::class, 'second')
        );
        $this->assertEquals(
            null,
            Config::inst()->get(ConfigTest\Third::class, 'second', true)
        );
        $this->assertFalse(
            Config::inst()->exists(ConfigTest\Third::class, 'second', true)
        );
        Config::modify()->merge(ConfigTest\Third::class, 'second', ['test_3_2']);
        $this->assertEquals(
            ['test_1', 'test_3_2'],
            Config::inst()->get(ConfigTest\Third::class, 'second')
        );
    }

    public function testUpdateWithFalsyValues()
    {
        // Booleans
        $this->assertTrue(Config::inst()->get(ConfigTest\First::class, 'bool'));
        Config::modify()->merge(ConfigTest\First::class, 'bool', false);
        $this->assertFalse(Config::inst()->get(ConfigTest\First::class, 'bool'));
        Config::modify()->merge(ConfigTest\First::class, 'bool', true);
        $this->assertTrue(Config::inst()->get(ConfigTest\First::class, 'bool'));

        // Integers
        $this->assertEquals(42, Config::inst()->get(ConfigTest\First::class, 'int'));
        Config::modify()->merge(ConfigTest\First::class, 'int', 0);
        $this->assertEquals(0, Config::inst()->get(ConfigTest\First::class, 'int'));
        Config::modify()->merge(ConfigTest\First::class, 'int', 42);
        $this->assertEquals(42, Config::inst()->get(ConfigTest\First::class, 'int'));

        // Strings
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'string'));
        Config::modify()->merge(ConfigTest\First::class, 'string', '');
        $this->assertEquals('', Config::inst()->get(ConfigTest\First::class, 'string'));
        Config::modify()->merge(ConfigTest\First::class, 'string', 'value');
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'string'));

        // Nulls
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'nullable'));
        Config::modify()->merge(ConfigTest\First::class, 'nullable', null);
        $this->assertNull(Config::inst()->get(ConfigTest\First::class, 'nullable'));
        Config::modify()->merge(ConfigTest\First::class, 'nullable', 'value');
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'nullable'));

        // arrays of false-y
        $array = Config::inst()->get(ConfigTest\First::class, 'default_array');
        $this->assertTrue($array['default_true']);
        $this->assertFalse($array['default_false']);

        Config::modify()->merge(ConfigTest\First::class, 'default_array', [
            'default_true' => false
        ]);

        $array = Config::inst()->get(ConfigTest\First::class, 'default_array');
        $this->assertFalse($array['default_true']);
        $this->assertFalse($array['default_false']);

        Config::modify()->merge(ConfigTest\First::class, 'default_array', [
            'default_false' => true
        ]);

        $array = Config::inst()->get(ConfigTest\First::class, 'default_array');
        $this->assertFalse($array['default_true']);
        $this->assertTrue($array['default_false']);

        // set the value we manually set to false, back to true
        Config::modify()->merge(ConfigTest\First::class, 'default_array', [
            'default_true' => true
        ]);

        $array = Config::inst()->get(ConfigTest\First::class, 'default_array');
        $this->assertTrue($array['default_true']);

        // missing statics
        Config::modify()->merge(ConfigTest\First::class, 'missing_static', true);
        $this->assertTrue(Config::inst()->get(ConfigTest\First::class, 'missing_static'));

        Config::modify()->merge(ConfigTest\First::class, 'missing_static', false);
        $this->assertFalse(Config::inst()->get(ConfigTest\First::class, 'missing_static'));
    }

    public function testSetsFalsyDefaults()
    {
        $this->assertFalse(Config::inst()->get(ConfigTest\First::class, 'default_false'));
        // Technically the same as an undefined config key
        $this->assertNull(Config::inst()->get(ConfigTest\First::class, 'default_null'));
        $this->assertEquals(0, Config::inst()->get(ConfigTest\First::class, 'default_zero'));
        $this->assertEquals('', Config::inst()->get(ConfigTest\First::class, 'default_empty_string'));
    }

    public function testUninheritedStatic()
    {
        $this->assertEquals(Config::inst()->get(ConfigTest\First::class, 'third', Config::UNINHERITED), 'test_1');
        $this->assertEquals(Config::inst()->get(ConfigTest\Fourth::class, 'third', Config::UNINHERITED), null);

        Config::modify()->merge(ConfigTest\First::class, 'first', array('test_1b'));
        Config::modify()->merge(ConfigTest\Second::class, 'first', array('test_2b'));

        // Check that it can be applied to parent and subclasses, and queried directly
        $this->assertContains(
            'test_1b',
            Config::inst()->get(ConfigTest\First::class, 'first', Config::UNINHERITED)
        );
        $this->assertContains(
            'test_2b',
            Config::inst()->get(ConfigTest\Second::class, 'first', Config::UNINHERITED)
        );

        // But it won't affect subclasses - this is *uninherited* static
        $this->assertNotContains(
            'test_2b',
            Config::inst()->get(ConfigTest\Third::class, 'first', Config::UNINHERITED)
        );
        $this->assertNull(Config::inst()->get(ConfigTest\Fourth::class, 'first', Config::UNINHERITED));

        // Subclasses that don't have the static explicitly defined should allow definition, also
        // This also checks that set can be called after the first uninherited get()
        // call (which can be buggy due to caching)
        Config::modify()->merge(ConfigTest\Fourth::class, 'first', array('test_4b'));
        $this->assertContains('test_4b', Config::inst()->get(ConfigTest\Fourth::class, 'first', Config::UNINHERITED));
    }

    public function testCombinedStatic()
    {
        $this->assertEquals(
            ['test_1', 'test_2', 'test_3'],
            ConfigTest\Combined3::config()->get('first')
        );

        // Test that unset values are ignored
        $this->assertEquals(
            ['test_1', 'test_3'],
            ConfigTest\Combined3::config()->get('second')
        );
    }

    public function testMerges()
    {
        $result = Priority::mergeArray(
            ['A' => 1, 'B' => 2, 'C' => 3],
            ['C' => 4, 'D' => 5]
        );
        $this->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            ['C' => 4, 'D' => 5],
            ['A' => 1, 'B' => 2, 'C' => 3]
        );
        $this->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => 4, 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            [ 'C' => [4, 5, 6], 'D' => 5 ],
            [ 'A' => 1, 'B' => 2, 'C' => [1, 2, 3] ]
        );
        $this->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => [1, 2, 3, 4, 5, 6], 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            ['A' => 1, 'B' => 2, 'C' => [1, 2, 3]],
            ['C' => [4, 5, 6], 'D' => 5]
        );
        $this->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => [4, 5, 6, 1, 2, 3], 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 2], 'D' => 3],
            ['C' => ['Bar' => 3, 'Baz' => 4]]
        );
        $this->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 2, 'Baz' => 4], 'D' => 3],
            $result
        );

        $result = Priority::mergeArray(
            ['C' => ['Bar' => 3, 'Baz' => 4]],
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 2], 'D' => 3]
        );
        $this->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 3, 'Baz' => 4], 'D' => 3],
            $result
        );
    }

    public function testForClass()
    {
        $config = ConfigTest\DefinesFoo::config();
        // Set values
        $this->assertTrue(isset($config->not_foo));
        $this->assertFalse(empty($config->not_foo));
        $this->assertEquals(1, $config->not_foo);

        // Unset values
        $this->assertFalse(isset($config->bar));
        $this->assertTrue(empty($config->bar));
        $this->assertNull($config->bar);
    }

    public function testUpdateOnExtension()
    {
        $base = new ConfigTest\BaseObject();
        $values = $base->config()->config_array;

        $this->assertArrayHasKey('foo', $values);
        $this->assertEquals('foo', $values['foo']);

        $this->assertTrue($base->config()->config_value);

        // try to update the default values on the extension
        Config::modify()->merge(ConfigTest\BaseObject::class, 'config_array', [
            'foo' => 'update foo value',
            'bar' => 'bar'
        ]);

        $base = new ConfigTest\BaseObject();
        $values = $base->config()->config_array;

        $this->assertArrayHasKey('foo', $values);
        $this->assertArrayHasKey('bar', $values);

        $this->assertEquals('update foo value', $values['foo']);

        Config::modify()->merge(ConfigTest\BaseObject::class, 'config_value', false);

        $base = new ConfigTest\BaseObject();
        $this->assertFalse($base->config()->config_value);
    }
}
