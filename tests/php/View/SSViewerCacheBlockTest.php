<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\TempFolder;
use SilverStripe\Versioned\Versioned;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Director;
use SilverStripe\View\SSViewer;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Cache\Simple\NullCache;

// Not actually a data object, we just want a ViewableData object that's just for us

class SSViewerCacheBlockTest extends SapphireTest
{
    protected static $extra_dataobjects = array(
        SSViewerCacheBlockTest\TestModel::class
    );

    public static function getExtraDataObjects()
    {
        $classes = parent::getExtraDataObjects();

        // Add extra classes if versioning is enabled
        if (class_exists(Versioned::class)) {
            $classes[] = SSViewerCacheBlockTest\VersionedModel::class;
        }
        return $classes;
    }

    /**
     * @var SSViewerCacheBlockTest\TestModel
     */
    protected $data = null;

    protected function _reset($cacheOn = true)
    {
        $this->data = new SSViewerCacheBlockTest\TestModel();

        $cache = null;
        if ($cacheOn) {
            $cache = new FilesystemCache('cacheblock', 0, TempFolder::getTempFolder(BASE_PATH)); // cache indefinitely
        } else {
            $cache = new NullCache();
        }

        Injector::inst()->registerService($cache, CacheInterface::class . '.cacheblock');
        Injector::inst()->get(CacheInterface::class . '.cacheblock')->clear();
    }

    protected function _runtemplate($template, $data = null)
    {
        if ($data === null) {
            $data = $this->data;
        }
        if (is_array($data)) {
            $data = $this->data->customise($data);
        }

        return SSViewer::execute_string($template, $data);
    }

    public function testParsing()
    {

        // ** Trivial checks **

        // Make sure an empty cached block parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% cached %><% end_cached %>'), '');

        // Make sure an empty cacheblock block parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% cacheblock %><% end_cacheblock %>'), '');

        // Make sure an empty uncached block parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% uncached %><% end_uncached %>'), '');

        // ** Argument checks **

        // Make sure a simple cacheblock parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% cached %>Yay<% end_cached %>'), 'Yay');

        // Make sure a moderately complicated cacheblock parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% cached \'block\', Foo, "jumping" %>Yay<% end_cached %>'), 'Yay');

        // Make sure a complicated cacheblock parses
        $this->_reset();
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached \'block\', Foo, Test.Test(4).Test(jumping).Foo %>Yay<% end_cached %>'
            ),
            'Yay'
        );

        // ** Conditional Checks **

        // Make sure a cacheblock with a simple conditional parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% cached if true %>Yay<% end_cached %>'), 'Yay');

        // Make sure a cacheblock with a complex conditional parses
        $this->_reset();
        $this->assertEquals($this->_runtemplate('<% cached if Test.Test(yank).Foo %>Yay<% end_cached %>'), 'Yay');

        // Make sure a cacheblock with a complex conditional and arguments parses
        $this->_reset();
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached Foo, Test.Test(4).Test(jumping).Foo if Test.Test(yank).Foo %>Yay<% end_cached %>'
            ),
            'Yay'
        );
    }

    /**
     * Test that cacheblocks actually cache
     */
    public function testBlocksCache()
    {
        // First, run twice without caching, to prove we get two different values
        $this->_reset(false);

        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 1)), '1');
        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 2)), '2');

        // Then twice with caching, should get same result each time
        $this->_reset(true);

        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 1)), '1');
        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 2)), '1');
    }

    /**
     * Test that the cacheblocks invalidate when a flush occurs.
     */
    public function testBlocksInvalidateOnFlush()
    {
        Director::test('/?flush=1');
        $this->_reset(true);

        // Generate cached value for foo = 1
        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 1)), '1');

        // Test without flush
        Director::test('/');
        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 3)), '1');

        // Test with flush
        Director::test('/?flush=1');
        $this->assertEquals($this->_runtemplate('<% cached %>$Foo<% end_cached %>', array('Foo' => 2)), '2');
    }

    public function testVersionedCache()
    {
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('testVersionedCache requires Versioned extension');
        }
        $origReadingMode = Versioned::get_reading_mode();

        // Run without caching in stage to prove data is uncached
        $this->_reset(false);
        Versioned::set_stage(Versioned::DRAFT);
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('default');
        $this->assertEquals(
            'default Stage.Stage',
            SSViewer::execute_string('<% cached %>$Inspect<% end_cached %>', $data)
        );
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('first');
        $this->assertEquals(
            'first Stage.Stage',
            SSViewer::execute_string('<% cached %>$Inspect<% end_cached %>', $data)
        );

        // Run without caching in live to prove data is uncached
        $this->_reset(false);
        Versioned::set_stage(Versioned::LIVE);
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('default');
        $this->assertEquals(
            'default Stage.Live',
            $this->_runtemplate('<% cached %>$Inspect<% end_cached %>', $data)
        );
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('first');
        $this->assertEquals(
            'first Stage.Live',
            $this->_runtemplate('<% cached %>$Inspect<% end_cached %>', $data)
        );

        // Then with caching, initially in draft, and then in live, to prove that
        // changing the versioned reading mode doesn't cache between modes, but it does
        // within them
        $this->_reset(true);
        Versioned::set_stage(Versioned::DRAFT);
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('default');
        $this->assertEquals(
            'default Stage.Stage',
            $this->_runtemplate('<% cached %>$Inspect<% end_cached %>', $data)
        );
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('first');
        $this->assertEquals(
            'default Stage.Stage', // entropy should be ignored due to caching
            $this->_runtemplate('<% cached %>$Inspect<% end_cached %>', $data)
        );

        Versioned::set_stage(Versioned::LIVE);
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('first');
        $this->assertEquals(
            'first Stage.Live', // First hit in live, so display current entropy
            $this->_runtemplate('<% cached %>$Inspect<% end_cached %>', $data)
        );
        $data = new SSViewerCacheBlockTest\VersionedModel();
        $data->setEntropy('second');
        $this->assertEquals(
            'first Stage.Live', // entropy should be ignored due to caching
            $this->_runtemplate('<% cached %>$Inspect<% end_cached %>', $data)
        );

        Versioned::set_reading_mode($origReadingMode);
    }

    /**
     * Test that cacheblocks conditionally cache with if
     */
    public function testBlocksConditionallyCacheWithIf()
    {
        // First, run twice with caching
        $this->_reset(true);

        $this->assertEquals($this->_runtemplate('<% cached if True %>$Foo<% end_cached %>', array('Foo' => 1)), '1');
        $this->assertEquals($this->_runtemplate('<% cached if True %>$Foo<% end_cached %>', array('Foo' => 2)), '1');

        // Then twice without caching
        $this->_reset(true);

        $this->assertEquals($this->_runtemplate('<% cached if False %>$Foo<% end_cached %>', array('Foo' => 1)), '1');
        $this->assertEquals($this->_runtemplate('<% cached if False %>$Foo<% end_cached %>', array('Foo' => 2)), '2');

        // Then once cached, once not (and the opposite)
        $this->_reset(true);

        $this->assertEquals(
            $this->_runtemplate(
                '<% cached if Cache %>$Foo<% end_cached %>',
                array('Foo' => 1, 'Cache' => true )
            ),
            '1'
        );
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached if Cache %>$Foo<% end_cached %>',
                array('Foo' => 2, 'Cache' => false)
            ),
            '2'
        );

        $this->_reset(true);

        $this->assertEquals(
            $this->_runtemplate(
                '<% cached if Cache %>$Foo<% end_cached %>',
                array('Foo' => 1, 'Cache' => false)
            ),
            '1'
        );
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached if Cache %>$Foo<% end_cached %>',
                array('Foo' => 2, 'Cache' => true )
            ),
            '2'
        );
    }

    /**
     * Test that cacheblocks conditionally cache with unless
     */
    public function testBlocksConditionallyCacheWithUnless()
    {
        // First, run twice with caching
        $this->_reset(true);

        $this->assertEquals(
            $this->_runtemplate(
                '<% cached unless False %>$Foo<% end_cached %>',
                array('Foo' => 1)
            ),
            '1'
        );
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached unless False %>$Foo<% end_cached %>',
                array('Foo' => 2)
            ),
            '1'
        );

        // Then twice without caching
        $this->_reset(true);

        $this->assertEquals(
            $this->_runtemplate(
                '<% cached unless True %>$Foo<% end_cached %>',
                array('Foo' => 1)
            ),
            '1'
        );
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached unless True %>$Foo<% end_cached %>',
                array('Foo' => 2)
            ),
            '2'
        );
    }

    /**
     * Test that nested uncached blocks work
     */
    public function testNestedUncachedBlocks()
    {
        // First, run twice with caching, to prove we get the same result back normally
        $this->_reset(true);

        $this->assertEquals(
            $this->_runtemplate(
                '<% cached %> A $Foo B <% end_cached %>',
                array('Foo' => 1)
            ),
            ' A 1 B '
        );
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached %> A $Foo B <% end_cached %>',
                array('Foo' => 2)
            ),
            ' A 1 B '
        );

        // Then add uncached to the nested block
        $this->_reset(true);

        $this->assertEquals(
            $this->_runtemplate(
                '<% cached %> A <% uncached %>$Foo<% end_uncached %> B <% end_cached %>',
                array('Foo' => 1)
            ),
            ' A 1 B '
        );
        $this->assertEquals(
            $this->_runtemplate(
                '<% cached %> A <% uncached %>$Foo<% end_uncached %> B <% end_cached %>',
                array('Foo' => 2)
            ),
            ' A 2 B '
        );
    }

    /**
     * Test that nested blocks with different keys works
     */
    public function testNestedBlocks()
    {
        $this->_reset(true);

        $template = '<% cached Foo %> $Fooa <% cached Bar %>$Bara<% end_cached %> $Foob <% end_cached %>';

        // Do it the first time to load the cache
        $this->assertEquals(
            $this->_runtemplate(
                $template,
                array('Foo' => 1, 'Fooa' => 1, 'Foob' => 3, 'Bar' => 1, 'Bara' => 2)
            ),
            ' 1 2 3 '
        );

        // Do it again, the input values are ignored as the cache is hit for both elements
        $this->assertEquals(
            $this->_runtemplate(
                $template,
                array('Foo' => 1, 'Fooa' => 9, 'Foob' => 9, 'Bar' => 1, 'Bara' => 9)
            ),
            ' 1 2 3 '
        );

        // Do it again with a new key for Bar, Bara is picked up, Fooa and Foob are not
        $this->assertEquals(
            $this->_runtemplate(
                $template,
                array('Foo' => 1, 'Fooa' => 9, 'Foob' => 9, 'Bar' => 2, 'Bara' => 9)
            ),
            ' 1 9 3 '
        );

        // Do it again with a new key for Foo, Fooa and Foob are picked up, Bara are not
        $this->assertEquals(
            $this->_runtemplate(
                $template,
                array('Foo' => 2, 'Fooa' => 9, 'Foob' => 9, 'Bar' => 2, 'Bara' => 1)
            ),
            ' 9 9 9 '
        );
    }

    public function testNoErrorMessageForControlWithinCached()
    {
        $this->_reset(true);
        $this->assertNotNull($this->_runtemplate('<% cached %><% with Foo %>$Bar<% end_with %><% end_cached %>'));
    }

    /**
     * @expectedException \SilverStripe\View\SSTemplateParseException
     */
    public function testErrorMessageForCachedWithinControlWithinCached()
    {
        $this->_reset(true);
        $this->_runtemplate(
            '<% cached %><% with Foo %><% cached %>$Bar<% end_cached %><% end_with %><% end_cached %>'
        );
    }

    public function testNoErrorMessageForCachedWithinControlWithinUncached()
    {
        $this->_reset(true);
        $this->assertNotNull(
            $this->_runtemplate(
                '<% uncached %><% with Foo %><% cached %>$Bar<% end_cached %><% end_with %><% end_uncached %>'
            )
        );
    }

    /**
     * @expectedException \SilverStripe\View\SSTemplateParseException
     */
    public function testErrorMessageForCachedWithinIf()
    {
        $this->_reset(true);
        $this->_runtemplate('<% cached %><% if Foo %><% cached %>$Bar<% end_cached %><% end_if %><% end_cached %>');
    }

    /**
     * @expectedException \SilverStripe\View\SSTemplateParseException
     */
    public function testErrorMessageForInvalidConditional()
    {
        $this->_reset(true);
        $this->_runtemplate('<% cached Foo if %>$Bar<% end_cached %>');
    }
}
