<?php

namespace SilverStripe\View\Tests\Parsers;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\HTMLValue;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\Core\Convert;

class HTMLValueTest extends SapphireTest
{
    public function testInvalidHTMLParsing()
    {
        $value = new HTMLValue();

        $invalid = [
            '<p>Enclosed Value</p><p>a' => '<p>Enclosed Value</p><p>a</p>',
            '<meta content="text/html"></meta>' => '<meta content="text/html">',
            '<p><div class="example"></div><p>' => '<p></p><div class="example"></div><p></p>',
            '<html><html><body><falsetag "attribute=""attribute""">' => '<falsetag></falsetag>',
            '<body<body<body>/bodu>/body>' => '/bodu&gt;/body&gt;'
        ];

        foreach ($invalid as $input => $expected) {
            $value->setContent($input);
            $this->assertEquals($expected, $value->getContent(), 'Invalid HTML can be parsed');
        }
    }

    public function testUtf8Saving()
    {
        $value = new HTMLValue();

        $value->setContent('<p>ö ß ā い 家</p>');
        $this->assertEquals('<p>ö ß ā い 家</p>', $value->getContent());
    }

    public function testWhitespaceHandling()
    {
        $value = new HTMLValue();

        $value->setContent('<p></p> <p></p>');
        $this->assertEquals('<p></p> <p></p>', $value->getContent());
    }

    public function testInvalidHTMLTagNames()
    {
        $value = new HTMLValue();

        $invalid = [
            '<p><div><a href="test-link"></p></div>',
            '<html><div><a href="test-link"></a></a></html_>'
        ];

        foreach ($invalid as $input) {
            $value->setContent($input);

            $this->assertEquals(
                'test-link',
                $value->getElementsByTagName('a')->item(0)->getAttribute('href'),
                'Link data can be extraced from malformed HTML'
            );
        }
    }

    public function testMixedNewlines()
    {
        $value = new HTMLValue();

        $value->setContent("<p>paragraph</p>\n<ul><li>1</li>\r\n</ul>");
        $this->assertEquals(
            "<p>paragraph</p>\n<ul><li>1</li>\n</ul>",
            $value->getContent(),
            'Newlines get converted'
        );
    }

    public function testAttributeEscaping()
    {
        $value = new HTMLValue();

        $value->setContent('<a href="[]"></a>');
        $this->assertEquals('<a href="[]"></a>', $value->getContent(), "'[' character isn't escaped");

        $value->setContent('<a href="&quot;"></a>');
        $this->assertEquals('<a href="&quot;"></a>', $value->getContent(), "'\"' character is escaped");
    }

    public function testShortcodeValue()
    {
        ShortcodeParser::get('default')->register(
            'test_shortcode',
            function () {
                return 'bit of test shortcode output';
            }
        );
        $content = DBHTMLText::create('Test', ['shortcodes' => true])
            ->setValue('<p>Some content with a [test_shortcode] and a <br /> followed by an <hr> in it.</p>')
            ->forTemplate();
        $this->assertStringContainsString(
            // hr is flow content, not phrasing content, so must be corrected to be outside the p tag.
            '<p>Some content with a bit of test shortcode output and a <br> followed by an </p><hr> in it.',
            $content
        );
    }

    public function testEntities()
    {
        $content = '<a href="http://domain.test/path?two&vars">ampersand &amp; test & link</a>';
        $output = new HTMLValue($content);
        $output = $output->getContent();
        $this->assertEquals(
            '<a href="http://domain.test/path?two&amp;vars">ampersand &amp; test &amp; link</a>',
            $output
        );
    }

    public function testShortcodeEntities()
    {
        ShortcodeParser::get('default')->register(
            'sitetree_link_test',
            // A mildly stubbed copy from SilverStripe\CMS\Model\SiteTree::link_shortcode_handler
            function ($arguments, $content = null, $parser = null) {
                $link = Convert::raw2att('https://google.com/search?q=unit&test');
                if ($content) {
                    $link = sprintf('<a href="%s">%s</a>', $link, $parser->parse($content));
                }
                return $link;
            }
        );
        $content = [
            '[sitetree_link_test,id=2]' => 'https://google.com/search?q=unit&amp;test',
            // the random [ triggers the shortcode parser, which seems to be where problems arise.
            '<a href="https://google.com/search?q=unit&test"> [ non shortcode link</a>' =>
                '<a href="https://google.com/search?q=unit&amp;test"> [ non shortcode link</a>',
            '[sitetree_link_test,id=1]test link[/sitetree_link_test]' =>
                '<a href="https://google.com/search?q=unit&amp;test">test link</a>'
        ];
        foreach ($content as $input => $expected) {
            $output = DBHTMLText::create('Test', ['shortcodes' => true])
                ->setValue($input)
                ->forTemplate();
            $this->assertEquals($expected, $output);
        }
    }

    public function testValidHTMLInNoscriptTags()
    {
        $value = new HTMLValue();

        $noscripts = [
            '<noscript><p>Enclosed Value</p></noscript>',
            '<noscript><span class="test">Enclosed Value</span></noscript>',
            '<noscript><img src="/test.jpg" alt="test"></noscript>',
        ];

        foreach ($noscripts as $noscript) {
            $value->setContent($noscript);
            $this->assertEquals($noscript, $value->getContent(), 'Child tags are left untouched in noscript tags.');
        }
    }
}
