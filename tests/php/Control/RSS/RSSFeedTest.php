<?php

namespace SilverStripe\Control\Tests\RSS;

use SilverStripe\Control\Director;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Parsers\ShortcodeParser;

class RSSFeedTest extends SapphireTest
{

    protected static $original_host;

    public function testRSSFeed()
    {
        $list = new ArrayList();
        $list->push(new RSSFeedTest\ItemA());
        $list->push(new RSSFeedTest\ItemB());
        $list->push(new RSSFeedTest\ItemC());

        $rssFeed = new RSSFeed($list, "http://www.example.com", "Test RSS Feed", "Test RSS Feed Description");
        $content = $rssFeed->outputToBrowser();

        $this->assertContains('<link>http://www.example.org/item-a/</link>', $content);
        $this->assertContains('<link>http://www.example.com/item-b.html</link>', $content);
        $this->assertContains('<link>http://www.example.com/item-c.html</link>', $content);

        $this->assertContains('<title>ItemA</title>', $content);
        $this->assertContains('<title>ItemB</title>', $content);
        $this->assertContains('<title>ItemC</title>', $content);

        $this->assertContains('<description>ItemA Content</description>', $content);
        $this->assertContains('<description>ItemB Content</description>', $content);
        $this->assertContains('<description>ItemC Content</description>', $content);


        // Feed #2 - put Content() into <title> and AltContent() into <description>
        $rssFeed = new RSSFeed(
            $list,
            "http://www.example.com",
            "Test RSS Feed",
            "Test RSS Feed Description",
            "Content",
            "AltContent"
        );
        $content = $rssFeed->outputToBrowser();

        $this->assertContains('<title>ItemA Content</title>', $content);
        $this->assertContains('<title>ItemB Content</title>', $content);
        $this->assertContains('<title>ItemC Content</title>', $content);

        $this->assertContains('<description>ItemA AltContent</description>', $content);
        $this->assertContains('<description>ItemB AltContent</description>', $content);
        $this->assertContains('<description>ItemC AltContent</description>', $content);
    }

    public function testLinkEncoding()
    {
        $list = new ArrayList();
        $rssFeed = new RSSFeed($list, "http://www.example.com/?param1=true&param2=true", "Test RSS Feed");
        $content = $rssFeed->outputToBrowser();
        $this->assertContains('<link>http://www.example.com/?param1=true&amp;param2=true', $content);
    }

    public function testRSSFeedWithShortcode()
    {
        $list = new ArrayList();
        $list->push(new RSSFeedTest\ItemD());

        $rssFeed = new RSSFeed($list, "http://www.example.com", "Test RSS Feed", "Test RSS Feed Description");
        $content = $rssFeed->outputToBrowser();

        $this->assertContains('<link>http://www.example.org/item-d.html</link>', $content);

        $this->assertContains('<title>ItemD</title>', $content);

        $this->assertContains(
            '<description><![CDATA[<p>ItemD Content test shortcode output</p>]]></description>',
            $content
        );
    }

    /**
     * @skipUpgrade
     */
    public function testRenderWithTemplate()
    {
        $rssFeed = new RSSFeed(new ArrayList(), "", "", "");
        $rssFeed->setTemplate('RSSFeedTest');

        $content = $rssFeed->outputToBrowser();
        $this->assertContains('<title>Test Custom Template</title>', $content);

        $rssFeed->setTemplate(null);
        $content = $rssFeed->outputToBrowser();
        $this->assertNotContains('<title>Test Custom Template</title>', $content);
    }

    protected function setUp()
    {
        parent::setUp();
        Config::modify()->set(Director::class, 'alternate_base_url', '/');
        if (!self::$original_host) {
            self::$original_host = $_SERVER['HTTP_HOST'];
        }
        $_SERVER['HTTP_HOST'] = 'www.example.org';

        ShortcodeParser::get('default')->register(
            'test_shortcode',
            function () {
                return 'test shortcode output';
            }
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        $_SERVER['HTTP_HOST'] = self::$original_host;
    }
}
