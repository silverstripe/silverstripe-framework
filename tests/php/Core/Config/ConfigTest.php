<?php

namespace SilverStripe\Core\Tests\Config;

use SilverStripe\Core\Object;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;

class ConfigTest extends SapphireTest
{

    protected $depSettings = null;

    public function setUp()
    {
        parent::setUp();
        $this->depSettings = Deprecation::dump_settings();
        Deprecation::set_enabled(false);
    }

    public function tearDown()
    {
        Deprecation::restore_settings($this->depSettings);
        parent::tearDown();
    }

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
        Config::inst()->update(ConfigTest\TestNest::class, 'foo', 4);
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
            Config::inst()->get(ConfigTest\First::class, 'first', Config::FIRST_SET),
            array('test_1')
        );
        $this->assertEquals(
            Config::inst()->get(ConfigTest\Second::class, 'first', Config::FIRST_SET),
            array('test_2')
        );
        $this->assertEquals(
            Config::inst()->get(ConfigTest\Third::class, 'first', Config::FIRST_SET),
            array('test_3')
        );

        Config::inst()->update(ConfigTest\First::class, 'first', array('test_1_2'));
        Config::inst()->update(ConfigTest\Third::class, 'first', array('test_3_2'));
        Config::inst()->update(ConfigTest\Fourth::class, 'first', array('test_4'));

        $this->assertEquals(
            Config::inst()->get(ConfigTest\First::class, 'first', Config::FIRST_SET),
            array('test_1_2', 'test_1')
        );

        Config::inst()->update(ConfigTest\Fourth::class, 'second', array('test_4'));
        Config::inst()->update(ConfigTest\Third::class, 'second', array('test_3_2'));

        $this->assertEquals(
            Config::inst()->get(ConfigTest\Fourth::class, 'second', Config::FIRST_SET),
            array('test_4')
        );
        $this->assertEquals(
            Config::inst()->get(ConfigTest\Third::class, 'second', Config::FIRST_SET),
            array('test_3_2', 'test_3')
        );

        Config::inst()->remove(ConfigTest\Third::class, 'second');
        $this->assertEquals(array(), Config::inst()->get(ConfigTest\Third::class, 'second'));
        Config::inst()->update(ConfigTest\Third::class, 'second', array('test_3_2'));
        $this->assertEquals(
            Config::inst()->get(ConfigTest\Third::class, 'second', Config::FIRST_SET),
            array('test_3_2')
        );
    }

    public function testUpdateWithFalsyValues()
    {
        // Booleans
        $this->assertTrue(Config::inst()->get(ConfigTest\First::class, 'bool'));
        Config::inst()->update(ConfigTest\First::class, 'bool', false);
        $this->assertFalse(Config::inst()->get(ConfigTest\First::class, 'bool'));
        Config::inst()->update(ConfigTest\First::class, 'bool', true);
        $this->assertTrue(Config::inst()->get(ConfigTest\First::class, 'bool'));

        // Integers
        $this->assertEquals(42, Config::inst()->get(ConfigTest\First::class, 'int'));
        Config::inst()->update(ConfigTest\First::class, 'int', 0);
        $this->assertEquals(0, Config::inst()->get(ConfigTest\First::class, 'int'));
        Config::inst()->update(ConfigTest\First::class, 'int', 42);
        $this->assertEquals(42, Config::inst()->get(ConfigTest\First::class, 'int'));

        // Strings
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'string'));
        Config::inst()->update(ConfigTest\First::class, 'string', '');
        $this->assertEquals('', Config::inst()->get(ConfigTest\First::class, 'string'));
        Config::inst()->update(ConfigTest\First::class, 'string', 'value');
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'string'));

        // Nulls
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'nullable'));
        Config::inst()->update(ConfigTest\First::class, 'nullable', null);
        $this->assertNull(Config::inst()->get(ConfigTest\First::class, 'nullable'));
        Config::inst()->update(ConfigTest\First::class, 'nullable', 'value');
        $this->assertEquals('value', Config::inst()->get(ConfigTest\First::class, 'nullable'));
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

        Config::inst()->update(ConfigTest\First::class, 'first', array('test_1b'));
        Config::inst()->update(ConfigTest\Second::class, 'first', array('test_2b'));

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
        Config::inst()->update(ConfigTest\Fourth::class, 'first', array('test_4b'));
        $this->assertContains('test_4b', Config::inst()->get(ConfigTest\Fourth::class, 'first', Config::UNINHERITED));
    }

    public function testCombinedStatic()
    {
        $this->assertEquals(
            Config::inst()->get(ConfigTest\Combined3::class, 'first'),
            array('test_3', 'test_2', 'test_1')
        );

        // test that null values are ignored, but values on either side are still merged
        $this->assertEquals(
            Config::inst()->get(ConfigTest\Combined3::class, 'second'),
            array('test_3', 'test_1')
        );
    }

    public function testMerges()
    {
        $result = array('A' => 1, 'B' => 2, 'C' => 3);
        Config::merge_array_low_into_high($result, array('C' => 4, 'D' => 5));
        $this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => 3, 'D' => 5));

        $result = array('A' => 1, 'B' => 2, 'C' => 3);
        Config::merge_array_high_into_low($result, array('C' => 4, 'D' => 5));
        $this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => 4, 'D' => 5));

        $result = array('A' => 1, 'B' => 2, 'C' => array(1, 2, 3));
        Config::merge_array_low_into_high($result, array('C' => array(4, 5, 6), 'D' => 5));
        $this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => array(1, 2, 3, 4, 5, 6), 'D' => 5));

        $result = array('A' => 1, 'B' => 2, 'C' => array(1, 2, 3));
        Config::merge_array_high_into_low($result, array('C' => array(4, 5, 6), 'D' => 5));
        $this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => array(4, 5, 6, 1, 2, 3), 'D' => 5));

        $result = array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2), 'D' => 3);
        Config::merge_array_low_into_high($result, array('C' => array('Bar' => 3, 'Baz' => 4)));
        $this->assertEquals(
            $result,
            array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2, 'Baz' => 4), 'D' => 3)
        );

        $result = array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2), 'D' => 3);
        Config::merge_array_high_into_low($result, array('C' => array('Bar' => 3, 'Baz' => 4)));
        $this->assertEquals(
            $result,
            array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 3, 'Baz' => 4), 'D' => 3)
        );
    }

    public function testStaticLookup()
    {
        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesFoo::class, 'foo'), 1);
        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesFoo::class, 'bar'), null);

        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesBar::class, 'foo'), null);
        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesBar::class, 'bar'), 2);

        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesFooAndBar::class, 'foo'), 3);
        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesFooAndBar::class, 'bar'), 3);

        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesFooDoesntExtendObject::class, 'foo'), 4);
        $this->assertEquals(Object::static_lookup(ConfigTest\DefinesFooDoesntExtendObject::class, 'bar'), null);
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

    public function testFragmentOrder()
    {
        $this->markTestIncomplete();
    }

    public function testCacheCleaning()
    {
        $cache = new ConfigTest\ConfigTestMemCache();

        for ($i = 0; $i < 1000;
        $i++) {
            $cache->set($i, $i);
        }
        $this->assertEquals(1000, count($cache->cache));

        $cache->clean();
        $this->assertEquals(0, count($cache->cache), 'Clean clears all items');
        $this->assertFalse($cache->get(1), 'Clean clears all items');

        $cache->set(1, 1, array('Foo'));
        $this->assertEquals(1, count($cache->cache));
        $this->assertEquals(1, count($cache->tags));

        $cache->clean('Foo');
        $this->assertEquals(0, count($cache->tags), 'Clean items with matching tag');
        $this->assertFalse($cache->get(1), 'Clean items with matching tag');

        $cache->set(1, 1, array('Foo', 'Bar'));
        $this->assertEquals(2, count($cache->tags));
        $this->assertEquals(1, count($cache->cache));

        $cache->clean('Bar');
        $this->assertEquals(1, count($cache->tags));
        $this->assertEquals(0, count($cache->cache), 'Clean items with any single matching tag');
        $this->assertFalse($cache->get(1), 'Clean items with any single matching tag');
    }
}
