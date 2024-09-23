<?php

namespace SilverStripe\Control\Tests\RSS;

use SilverStripe\Control\Director;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\ArrayList;
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

        $this->assertStringContainsString('<link>http://www.example.org/item-a</link>', $content);
        $this->assertStringContainsString('<link>http://www.example.com/item-b.html</link>', $content);
        $this->assertStringContainsString('<link>http://www.example.com/item-c.html</link>', $content);

        $this->assertStringContainsString('<title>ItemA</title>', $content);
        $this->assertStringContainsString('<title>ItemB</title>', $content);
        $this->assertStringContainsString('<title>ItemC</title>', $content);

        $this->assertStringContainsString('<description>ItemA Content</description>', $content);
        $this->assertStringContainsString('<description>ItemB Content</description>', $content);
        $this->assertStringContainsString('<description>ItemC Content</description>', $content);


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

        $this->assertStringContainsString('<title>ItemA Content</title>', $content);
        $this->assertStringContainsString('<title>ItemB Content</title>', $content);
        $this->assertStringContainsString('<title>ItemC Content</title>', $content);

        $this->assertStringContainsString('<description>ItemA AltContent</description>', $content);
        $this->assertStringContainsString('<description>ItemB AltContent</description>', $content);
        $this->assertStringContainsString('<description>ItemC AltContent</description>', $content);
    }

    public function testLinkEncoding()
    {
        $list = new ArrayList();
        $rssFeed = new RSSFeed($list, "http://www.example.com?param1=true&param2=true", "Test RSS Feed");
        $content = $rssFeed->outputToBrowser();
        $this->assertStringContainsString('<link>http://www.example.com?param1=true&amp;param2=true', $content);
    }

    public function testRSSFeedWithShortcode()
    {
        $list = new ArrayList();
        $list->push(new RSSFeedTest\ItemD());

        $rssFeed = new RSSFeed($list, "http://www.example.com", "Test RSS Feed", "Test RSS Feed Description");
        $content = $rssFeed->outputToBrowser();

        $this->assertStringContainsString('<link>http://www.example.org/item-d.html</link>', $content);

        $this->assertStringContainsString('<title>ItemD</title>', $content);

        $this->assertStringContainsString(
            '<description><![CDATA[<p>ItemD Content test shortcode output</p>]]></description>',
            $content
        );
    }

    public function testRenderWithTemplate()
    {
        $rssFeed = new RSSFeed(new ArrayList(), "", "", "");
        $rssFeed->setTemplate('RSSFeedTest');

        $content = $rssFeed->outputToBrowser();
        $this->assertStringContainsString('<title>Test Custom Template</title>', $content);

        $rssFeed->setTemplate(null);
        $content = $rssFeed->outputToBrowser();
        $this->assertStringNotContainsString('<title>Test Custom Template</title>', $content);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Config::modify()->set(Director::class, 'alternate_base_url', '/');
        if (!RSSFeedTest::$original_host) {
            RSSFeedTest::$original_host = $_SERVER['HTTP_HOST'];
        }
        $_SERVER['HTTP_HOST'] = 'www.example.org';

        ShortcodeParser::get('default')->register(
            'test_shortcode',
            function () {
                return 'test shortcode output';
            }
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER['HTTP_HOST'] = RSSFeedTest::$original_host;
    }
}
