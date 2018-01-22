<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DBHTMLTextTest\TestShortcode;
use SilverStripe\View\Parsers\ShortcodeParser;

class DBHTMLTextTest extends SapphireTest
{

    private $previousLocaleSetting = null;

    protected function setUp()
    {
        parent::setUp();

        // clear the previous locale setting
        $this->previousLocaleSetting = null;

        // Set test handler
        ShortcodeParser::get('htmltest')
            ->register('test_shortcode', array(TestShortcode::class, 'handle_shortcode'));
        ShortcodeParser::set_active('htmltest');
    }

    protected function tearDown()
    {

        // If a test sets the locale, reset it on teardown
        if ($this->previousLocaleSetting) {
            setlocale(LC_CTYPE, $this->previousLocaleSetting);
        }

        ShortcodeParser::set_active('default');
        parent::tearDown();
    }

    /**
     * Test {@link Text->LimitCharacters()}
     */
    public function providerLimitCharacters()
    {
        // HTML characters are stripped safely
        return [
            ['The little brown fox jumped over the lazy cow.', 'The little brown fox...'],
            ['<p>Short &amp; Sweet</p>', 'Short &amp; Sweet'],
            ['This text contains &amp; in it', 'This text contains &amp;...'],
        ];
    }

    /**
     * Test {@link DBHTMLText->LimitCharacters()}
     *
     * @dataProvider providerLimitCharacters
     * @param        string $originalValue
     * @param        string $expectedValue
     */
    public function testLimitCharacters($originalValue, $expectedValue)
    {
        $textObj = DBField::create_field('HTMLFragment', $originalValue);
        $result = $textObj->obj('LimitCharacters')->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @return array
     */
    public function providerLimitCharactersToClosestWord()
    {
        // HTML is converted safely to plain text
        return [
            // Standard words limited, ellipsis added if truncated
            ['<p>Lorem ipsum dolor sit amet</p>', 24, 'Lorem ipsum dolor sit...'],

            // Complete words less than the character limit don't get truncated, ellipsis not added
            ['<p>Lorem ipsum</p>', 24, 'Lorem ipsum'],
            ['<p>Lorem</p>', 24, 'Lorem'],
            ['', 24, ''],    // No words produces nothing!

            // Special characters are encoded safely
            ['Nice &amp; Easy', 24, 'Nice &amp; Easy'],

            // HTML is safely converted to plain text
            ['<p>Lorem ipsum dolor sit amet</p>', 24, 'Lorem ipsum dolor sit...'],
            ['<p><span>Lorem ipsum dolor sit amet</span></p>', 24, 'Lorem ipsum dolor sit...'],
            ['<p>Lorem ipsum</p>', 24, 'Lorem ipsum'],
            ['Lorem &amp; ipsum dolor sit amet', 24, 'Lorem &amp; ipsum dolor sit...']
        ];
    }

    /**
     * Test {@link DBHTMLText->LimitCharactersToClosestWord()}
     *
     * @dataProvider providerLimitCharactersToClosestWord
     *
     * @param string $originalValue Raw string input
     * @param int    $limit
     * @param string $expectedValue Expected template value
     */
    public function testLimitCharactersToClosestWord($originalValue, $limit, $expectedValue)
    {
        $textObj = DBField::create_field('HTMLFragment', $originalValue);
        $result = $textObj->obj('LimitCharactersToClosestWord', [$limit])->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    public function providerSummary()
    {
        return [
            [
                '<p>Should strip <b>tags, but leave</b> text</p>',
                50,
                'Should strip tags, but leave text',
            ],
            [
                // Line breaks are preserved
                '<p>Unclosed tags <br>should not phase it</p>',
                50,
                "Unclosed tags <br />\nshould not phase it",
            ],
            [
                // Paragraphs converted to linebreak
                '<p>Second paragraph</p><p>should not cause errors or appear in output</p>',
                50,
                "Second paragraph<br />\n<br />\nshould not cause errors or appear in output",
            ],
            [
                '<img src="hello" /><p>Second paragraph</p><p>should not cause errors or appear in output</p>',
                50,
                "Second paragraph<br />\n<br />\nshould not cause errors or appear in output",
            ],
            [
                '  <img src="hello" /><p>Second paragraph</p><p>should not cause errors or appear in output</p>',
                50,
                "Second paragraph<br />\n<br />\nshould not cause errors or appear in output",
            ],
            [
                '<p><img src="remove me">example <img src="include me">text words hello<img src="hello"></p>',
                50,
                'example text words hello',
            ],

            // Shorter limits
            [
                '<p>A long paragraph should be cut off if limit is set</p>',
                5,
                'A long paragraph should be...',
            ],
            [
                '<p>No matter <i>how many <b>tags</b></i> are in it</p>',
                5,
                'No matter how many tags...',
            ],
            [
                '<p>A sentence is. nicer than hard limits</p>',
                5,
                'A sentence is.',
            ],
        ];
    }

    /**
     * @dataProvider providerSummary
     * @param string $originalValue
     * @param int    $limit
     * @param string $expectedValue
     */
    public function testSummary($originalValue, $limit, $expectedValue)
    {
        $textObj = DBField::create_field('HTMLFragment', $originalValue);
        $result = $textObj->obj('Summary', [$limit])->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    public function testSummaryEndings()
    {
        $cases = array(
            '...',
            ' -> more',
            ''
        );

        $orig = '<p>Cut it off, cut it off</p>';
        $match = 'Cut it off, cut';

        foreach ($cases as $add) {
            $textObj = DBField::create_field('HTMLFragment', $orig);
            $result = $textObj->obj('Summary', [4, $add])->forTemplate();
            $this->assertEquals($match . Convert::raw2xml($add), $result);
        }
    }



    public function providerFirstSentence()
    {
        return [
            // Same behaviour as DBTextTest
            ['', ''],
            ['First sentence.', 'First sentence.'],
            ['First sentence. Second sentence', 'First sentence.'],
            ['First sentence? Second sentence', 'First sentence?'],
            ['First sentence! Second sentence', 'First sentence!'],

            // DBHTHLText strips HTML first
            ['<br />First sentence.', 'First sentence.'],
            ['<p>First sentence. Second sentence. Third sentence</p>', 'First sentence.'],
        ];
    }

    /**
     * @dataProvider providerFirstSentence
     * @param string $originalValue
     * @param string $expectedValue
     */
    public function testFirstSentence($originalValue, $expectedValue)
    {
        $textObj = DBField::create_field('HTMLFragment', $originalValue);
        $result = $textObj->obj('FirstSentence')->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    public function testCreate()
    {
        /** @var DBHTMLText $field */
        $field = Injector::inst()->create("HTMLFragment(['whitelist' => 'link'])", 'MyField');
        $this->assertEquals(['link'], $field->getWhitelist());
        $field = Injector::inst()->create("HTMLFragment(['whitelist' => 'link,a'])", 'MyField');
        $this->assertEquals(['link', 'a'], $field->getWhitelist());
        $field = Injector::inst()->create("HTMLFragment(['whitelist' => ['link', 'a']])", 'MyField');
        $this->assertEquals(['link', 'a'], $field->getWhitelist());
        $field = Injector::inst()->create("HTMLFragment", 'MyField');
        $this->assertEmpty($field->getWhitelist());

        // Test shortcodes
        $field = Injector::inst()->create("HTMLFragment(['shortcodes' => true])", 'MyField');
        $this->assertEquals(true, $field->getProcessShortcodes());
        $field = Injector::inst()->create("HTMLFragment(['shortcodes' => false])", 'MyField');
        $this->assertEquals(false, $field->getProcessShortcodes());

        // Mix options
        $field = Injector::inst()->create("HTMLFragment(['shortcodes' => true, 'whitelist' => ['a'])", 'MyField');
        $this->assertEquals(true, $field->getProcessShortcodes());
        $this->assertEquals(['a'], $field->getWhitelist());
    }

    public function providerToPlain()
    {
        return [
            [
                '<p><img />Lots of <strong>HTML <i>nested</i></strong> tags',
                'Lots of HTML nested tags',
            ],
            [
                '<p>Multi</p><p>Paragraph<br>Also has multilines.</p>',
                "Multi\n\nParagraph\nAlso has multilines.",
            ],
            [
                '<p>Collapses</p><p></p><p>Excessive<br/><br /><br>Newlines</p>',
                "Collapses\n\nExcessive\n\nNewlines",
            ]
        ];
    }

    /**
     * @dataProvider providerToPlain
     * @param string $html
     * @param string $plain
     */
    public function testToPlain($html, $plain)
    {
        /**
 * @var DBHTMLText $textObj
*/
        $textObj = DBField::create_field('HTMLFragment', $html);
        $this->assertEquals($plain, $textObj->Plain());
    }

    /**
     * each test is in the format input, charactere limit, highlight, expected output
     *
     * @return array
     */
    public function providerContextSummary()
    {
        return [
            [
                'This is some text. It is a test',
                20,
                'test',
                '... text. It is a <mark>test</mark>'
            ],
            [
                // Retains case of original string
                'This is some test text. Test test what if you have multiple keywords.',
                50,
                'some test',
                'This is <mark>some</mark> <mark>test</mark> text.'
                . ' <mark>Test</mark> <mark>test</mark> what if you have...'
            ],
            [
                'Here is some text &amp; HTML included',
                20,
                'html',
                '... text &amp; <mark>HTML</mark> inc...'
            ],
            [
                'A dog ate a cat while looking at a Foobar',
                100,
                'a',
                // test that it does not highlight too much (eg every a)
                'A dog ate a cat while looking at a Foobar',
            ],
            [
                'A dog ate a cat while looking at a Foobar',
                100,
                'ate',
                // it should highlight 3 letters or more.
                'A dog <mark>ate</mark> a cat while looking at a Foobar',
            ],

            // HTML Content is plain-textified, and incorrect tags removed
            [
                '<p>A dog ate a cat while <mark>looking</mark> at a Foobar</p>',
                100,
                'ate',
                // it should highlight 3 letters or more.
                'A dog <mark>ate</mark> a cat while looking at a Foobar',
            ]
        ];
    }

    /**
     * @dataProvider providerContextSummary
     * @param string $originalValue Input
     * @param int    $limit         Numer of characters
     * @param string $keywords      Keywords to highlight
     * @param string $expectedValue Expected output (XML encoded safely)
     */
    public function testContextSummary($originalValue, $limit, $keywords, $expectedValue)
    {
        $text = DBField::create_field('HTMLFragment', $originalValue);
        $result = $text->obj('ContextSummary', [$limit, $keywords])->forTemplate();
        // it should highlight 3 letters or more.
        $this->assertEquals($expectedValue, $result);
    }

    public function testRAW()
    {
        $data = DBField::create_field('HTMLFragment', 'This &amp; This');
        $this->assertEquals('This &amp; This', $data->RAW());

        $data = DBField::create_field('HTMLFragment', 'This & This');
        $this->assertEquals('This & This', $data->RAW());
    }

    public function testXML()
    {
        $data = DBField::create_field('HTMLFragment', 'This & This');
        $this->assertEquals('This &amp; This', $data->XML());
        $data = DBField::create_field('HTMLFragment', 'This &amp; This');
        $this->assertEquals('This &amp;amp; This', $data->XML());
    }

    public function testHTML()
    {
        $data = DBField::create_field('HTMLFragment', 'This & This');
        $this->assertEquals('This &amp; This', $data->HTML());
        $data = DBField::create_field('HTMLFragment', 'This &amp; This');
        $this->assertEquals('This &amp;amp; This', $data->HTML());
    }

    public function testJS()
    {
        $data = DBField::create_field('HTMLText', '"this is &amp; test"');
        $this->assertEquals('\"this is \x26amp; test\"', $data->JS());
    }

    public function testATT()
    {
        // HTML Fragment
        $data = DBField::create_field('HTMLFragment', '"this is a test"');
        $this->assertEquals('&quot;this is a test&quot;', $data->ATT());

        // HTML Text (passes shortcodes + tidy)
        $data = DBField::create_field('HTMLText', '"');
        $this->assertEquals('&quot;', $data->ATT());
    }

    public function testShortcodesProcessed()
    {
        /**
 * @var DBHTMLText $obj
*/
        $obj = DBField::create_field(
            'HTMLText',
            '<p>Some content <strong>[test_shortcode]</strong> with shortcode</p>'
        );
        // Basic DBField methods process shortcodes
        $this->assertEquals(
            'Some content shortcode content with shortcode',
            $obj->Plain()
        );
        $this->assertEquals(
            '<p>Some content <strong>shortcode content</strong> with shortcode</p>',
            $obj->RAW()
        );
        $this->assertEquals(
            '&lt;p&gt;Some content &lt;strong&gt;shortcode content&lt;/strong&gt; with shortcode&lt;/p&gt;',
            $obj->XML()
        );
        $this->assertEquals(
            '&lt;p&gt;Some content &lt;strong&gt;shortcode content&lt;/strong&gt; with shortcode&lt;/p&gt;',
            $obj->HTML()
        );
        // Test summary methods
        $this->assertEquals(
            'Some content shortcode...',
            $obj->Summary(3)
        );
        $this->assertEquals(
            'Some content shortcode content with shortcode',
            $obj->LimitSentences(1)
        );
        $this->assertEquals(
            'Some content shortco...',
            $obj->LimitCharacters(20)
        );
    }

    function testExists()
    {
        $h = new DBHTMLText();
        $h->setValue("");
        $this->assertFalse($h->exists());
        $h->setValue("<p>content</p>");
        $this->assertTrue($h->exists());
    }

    function testWhitelist()
    {
        $textObj = new DBHTMLText('Test', ['whitelist'=> 'meta,link']);
        $this->assertEquals(
            '<meta content="Keep"><link href="Also Keep">',
            $textObj->whitelistContent('<meta content="Keep"><p>Remove</p><link href="Also Keep" />Remove Text'),
            'Removes any elements not in whitelist excluding text elements'
        );

        $textObj = new DBHTMLText('Test', ['whitelist'=> 'meta,link,text()']);
        $this->assertEquals(
            '<meta content="Keep"><link href="Also Keep">Keep Text',
            $textObj->whitelistContent('<meta content="Keep"><p>Remove</p><link href="Also Keep" />Keep Text'),
            'Removes any elements not in whitelist including text elements'
        );
    }

    public function testShortCodeParsedInRAW()
    {
        $parser = ShortcodeParser::get('HTMLTextTest');
        $parser->register(
            'shortcode',
            function ($arguments, $content, $parser, $tagName, $extra) {
                return 'replaced';
            }
        );
        ShortcodeParser::set_active('HTMLTextTest');
        /**
 * @var DBHTMLText $field
*/
        $field = DBField::create_field('HTMLText', '<p>[shortcode]</p>');
        $this->assertEquals('<p>replaced</p>', $field->RAW());
        $this->assertEquals('<p>replaced</p>', (string)$field);

        $field->setOptions(
            array(
            'shortcodes' => false,
            )
        );

        $this->assertEquals('<p>[shortcode]</p>', $field->RAW());
        $this->assertEquals('<p>[shortcode]</p>', (string)$field);


        ShortcodeParser::set_active('default');
    }

    public function testShortCodeParsedInTemplateHelpers()
    {
        $parser = ShortcodeParser::get('HTMLTextTest');
        $parser->register(
            'shortcode',
            function ($arguments, $content, $parser, $tagName, $extra) {
                return 'Replaced short code with this. <a href="home">home</a>';
            }
        );
        ShortcodeParser::set_active('HTMLTextTest');
        /**
 * @var DBHTMLText $field
*/
        $field = DBField::create_field('HTMLText', '<p>[shortcode]</p>');

        $this->assertEquals(
            '&lt;p&gt;Replaced short code with this. &lt;a href=&quot;home&quot;&gt;home&lt;/a&gt;&lt;/p&gt;',
            $field->HTMLATT()
        );
        $this->assertEquals(
            '%3Cp%3EReplaced+short+code+with+this.+%3Ca+href%3D%22home%22%3Ehome%3C%2Fa%3E%3C%2Fp%3E',
            $field->URLATT()
        );
        $this->assertEquals(
            '%3Cp%3EReplaced%20short%20code%20with%20this.%20%3Ca%20href%3D%22home%22%3Ehome%3C%2Fa%3E%3C%2Fp%3E',
            $field->RAWURLATT()
        );
        $this->assertEquals(
            '&lt;p&gt;Replaced short code with this. &lt;a href=&quot;home&quot;&gt;home&lt;/a&gt;&lt;/p&gt;',
            $field->ATT()
        );
        $this->assertEquals(
            '<p>Replaced short code with this. <a href="home">home</a></p>',
            $field->RAW()
        );
        $this->assertEquals(
            '\x3cp\x3eReplaced short code with this. \x3ca href=\"home\"\x3ehome\x3c/a\x3e\x3c/p\x3e',
            $field->JS()
        );
        $this->assertEquals(
            '&lt;p&gt;Replaced short code with this. &lt;a href=&quot;home&quot;&gt;home&lt;/a&gt;&lt;/p&gt;',
            $field->HTML()
        );
        $this->assertEquals(
            '&lt;p&gt;Replaced short code with this. &lt;a href=&quot;home&quot;&gt;home&lt;/a&gt;&lt;/p&gt;',
            $field->XML()
        );
        $this->assertEquals(
            'Repl...',
            $field->LimitCharacters(4, '...')
        );
        $this->assertEquals(
            'Replaced...',
            $field->LimitCharactersToClosestWord(10, '...')
        );
        $this->assertEquals(
            'Replaced...',
            $field->LimitWordCount(1, '...')
        );
        $this->assertEquals(
            '<p>replaced short code with this. <a href="home">home</a></p>',
            $field->LowerCase()
        );
        $this->assertEquals(
            '<P>REPLACED SHORT CODE WITH THIS. <A HREF="HOME">HOME</A></P>',
            $field->UpperCase()
        );
        $this->assertEquals(
            'Replaced short code with this. home',
            $field->Plain()
        );
        Config::nest();
        Director::config()->set('alternate_base_url', 'http://example.com/');
        $this->assertEquals(
            '<p>Replaced short code with this. <a href="http://example.com/home">home</a></p>',
            $field->AbsoluteLinks()
        );
        Config::unnest();
        $this->assertEquals(
            'Replaced short code with this.',
            $field->LimitSentences(1)
        );
        $this->assertEquals(
            'Replaced short code with this.',
            $field->FirstSentence()
        );
        $this->assertEquals(
            'Replaced short...',
            $field->Summary(2)
        );
        $this->assertEquals(
            'Replaced short code with this. home',
            $field->FirstParagraph()
        );
        $this->assertEquals(
            'Replaced <mark>short</mark> <mark>code</mark> with this. home',
            $field->ContextSummary(500, 'short code')
        );

        ShortcodeParser::set_active('default');
    }

    public function testValidUtf8()
    {
        // Install a UTF-8 locale
        $this->previousLocaleSetting = setlocale(LC_CTYPE, 0);
        $locales = array('en_US.UTF-8', 'en_NZ.UTF-8', 'de_DE.UTF-8');
        $localeInstalled = false;
        foreach ($locales as $locale) {
            if ($localeInstalled = setlocale(LC_CTYPE, $locale)) {
                break;
            }
        }

        // If the system doesn't have any of the UTF-8 locales, exit early
        if ($localeInstalled === false) {
            $this->markTestIncomplete('Unable to run this test because of missing locale!');
            return;
        }

        $problematicText = html_entity_decode('<p>This is a&nbsp;Test with non-breaking&nbsp;space!</p>', ENT_COMPAT, 'UTF-8');

        $textObj = new DBHTMLText('Test');
        $textObj->setValue($problematicText);

        $this->assertTrue(mb_check_encoding($textObj->FirstSentence(), 'UTF-8'));
        $this->assertTrue(mb_check_encoding($textObj->Summary(), 'UTF-8'));
    }
}
