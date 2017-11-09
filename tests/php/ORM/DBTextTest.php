<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBText;

/**
 * Tests parsing and summary methods on DBText
 */
class DBTextTest extends SapphireTest
{

    private $previousLocaleSetting = null;

    public function setUp()
    {
        parent::setUp();
        // clear the previous locale setting
        $this->previousLocaleSetting = null;
    }

    public function tearDown()
    {
        parent::tearDown();
        // If a test sets the locale, reset it on teardown
        if ($this->previousLocaleSetting) {
            setlocale(LC_CTYPE, $this->previousLocaleSetting);
        }
    }

    /**
     * Test {@link Text->LimitCharacters()}
     */
    public function providerLimitCharacters()
    {
        // Plain text values always encoded safely
        // HTML stored in non-html fields is treated literally.
        return [
            ['The little brown fox jumped over the lazy cow.', 'The little brown fox...'],
            ['<p>Short & Sweet</p>', '&lt;p&gt;Short &amp; Sweet&lt;/p&gt;'],
            ['This text contains &amp; in it', 'This text contains &amp;...'],
            ['Is an umault in schön?', 'Is an umault in schö...'],
        ];
    }

    /**
     * Test {@link Text->LimitCharacters()}
     *
     * @dataProvider providerLimitCharacters
     * @param        string $originalValue
     * @param        string $expectedValue
     */
    public function testLimitCharacters($originalValue, $expectedValue)
    {
        $textObj = DBField::create_field('Text', $originalValue);
        $result = $textObj->obj('LimitCharacters')->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @return array
     */
    public function providerLimitCharactersToClosestWord()
    {
        return [
            // Standard words limited, ellipsis added if truncated
            ['Lorem ipsum dolor sit amet', 24, 'Lorem ipsum dolor sit...'],

            // Complete words less than the character limit don't get truncated, ellipsis not added
            ['Lorem ipsum', 24, 'Lorem ipsum'],
            ['Lorem', 24, 'Lorem'],
            ['', 24, ''],    // No words produces nothing!

            // Special characters are encoded safely
            ['Nice & Easy', 24, 'Nice &amp; Easy'],

            // HTML stored in non-html fields is treated literally.
            // If storing HTML you should use DBHTMLText instead
            ['<p>Lorem ipsum dolor sit amet</p>', 24, '&lt;p&gt;Lorem ipsum dolor...'],
            ['<p><span>Lorem ipsum dolor sit amet</span></p>', 24, '&lt;p&gt;&lt;span&gt;Lorem ipsum...'],
            ['<p>Lorem ipsum</p>', 24, '&lt;p&gt;Lorem ipsum&lt;/p&gt;'],
            ['Lorem &amp; ipsum dolor sit amet', 24, 'Lorem &amp;amp; ipsum dolor...'],

            ['Is an umault in schön or not?', 22, 'Is an umault in schön...'],

        ];
    }

    /**
     * Test {@link Text->LimitCharactersToClosestWord()}
     *
     * @dataProvider providerLimitCharactersToClosestWord
     *
     * @param string $originalValue Raw string input
     * @param int    $limit
     * @param string $expectedValue Expected template value
     */
    public function testLimitCharactersToClosestWord($originalValue, $limit, $expectedValue)
    {
        $textObj = DBField::create_field('Text', $originalValue);
        $result = $textObj->obj('LimitCharactersToClosestWord', [$limit])->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    /**
     * Test {@link Text->LimitWordCount()}
     */
    public function providerLimitWordCount()
    {
        return [
            // Standard words limited, ellipsis added if truncated
            ['The little brown fox jumped over the lazy cow.', 3, 'The little brown...'],
            [' This text has white space around the ends ', 3, 'This text has...'],

            // Words less than the limt word count don't get truncated, ellipsis not added
            ['Two words', 3, 'Two words'],  // Two words shouldn't have an ellipsis
            ['These three words', 3, 'These three words'], // Three words shouldn't have an ellipsis
            ['One', 3, 'One'],  // Neither should one word
            ['', 3, ''],    // No words produces nothing!

            // Text with special characters
            ['Nice & Easy', 3, 'Nice &amp; Easy'],
            ['One & Two & Three', 3, 'One &amp; Two...'],

            // HTML stored in non-html fields is treated literally.
            // If storing HTML you should use DBHTMLText instead
            ['<p>Text inside a paragraph tag should also work</p>', 3, '&lt;p&gt;Text inside a...'],
            ['<p>Two words</p>', 3, '&lt;p&gt;Two words&lt;/p&gt;'],

            // Check UTF8
            ['Is an umault in schön or not?', 5, 'Is an umault in schön...'],
        ];
    }

    /**
     * Test {@link DBText->LimitWordCount()}
     *
     * @dataProvider providerLimitWordCount
     *
     * @param string $originalValue Raw string input
     * @param int    $limit         Number of words
     * @param string $expectedValue Expected template value
     */
    public function testLimitWordCount($originalValue, $limit, $expectedValue)
    {
        $textObj = DBField::create_field('Text', $originalValue);
        $result = $textObj->obj('LimitWordCount', [$limit])->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    /**
     */
    public function providerLimitSentences()
    {
        return [
            ['', 2, ''],
            ['First sentence.', 2, 'First sentence.'],
            ['First sentence. Second sentence.', 2, 'First sentence. Second sentence.'],

            // HTML stored in non-html fields is treated literally.
            // If storing HTML you should use DBHTMLText instead
            ['<p>First sentence.</p>', 2, '&lt;p&gt;First sentence.&lt;/p&gt;'],
            ['<p>First sentence. Second sentence. Third sentence</p>', 2, '&lt;p&gt;First sentence. Second sentence.'],

            // Check UTF8
            ['Is schön. Isn\'t schön.', 1, 'Is schön.'],
        ];
    }

    /**
     * Test {@link DBText->LimitSentences()}
     *
     * @dataProvider providerLimitSentences
     * @param        string $originalValue
     * @param        int    $limit         Number of sentences
     * @param        string $expectedValue Expected template value
     */
    public function testLimitSentences($originalValue, $limit, $expectedValue)
    {
        $textObj = DBField::create_field('Text', $originalValue);
        $result = $textObj->obj('LimitSentences', [$limit])->forTemplate();
        $this->assertEquals($expectedValue, $result);
    }

    public function providerFirstSentence()
    {
        return [
            ['', ''],
            ['First sentence.', 'First sentence.'],
            ['First sentence. Second sentence', 'First sentence.'],
            ['First sentence? Second sentence', 'First sentence?'],
            ['First sentence! Second sentence', 'First sentence!'],

            // HTML stored in non-html fields is treated literally.
            // If storing HTML you should use DBHTMLText instead
            ['<br />First sentence.', '&lt;br /&gt;First sentence.'],
            ['<p>First sentence. Second sentence. Third sentence</p>', '&lt;p&gt;First sentence.'],

            // Check UTF8
            ['Is schön. Isn\'t schön.', 'Is schön.'],
        ];
    }

    /**
     * @dataProvider providerFirstSentence
     * @param string $originalValue
     * @param string $expectedValue
     */
    public function testFirstSentence($originalValue, $expectedValue)
    {
        $textObj = DBField::create_field('Text', $originalValue);
        $result = $textObj->obj('FirstSentence')->forTemplate();
        $this->assertEquals($expectedValue, $result);
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
                'Here is some text & HTML included',
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
            [
                'both schön and können have umlauts',
                21,
                'schön',
                // check UTF8 support
                'both <mark>schön</mark> and können...',
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
        $text = DBField::create_field('Text', $originalValue);
        $result = $text->obj('ContextSummary', [$limit, $keywords])->forTemplate();
        // it should highlight 3 letters or more.
        $this->assertEquals($expectedValue, $result);
    }

    public function testRAW()
    {
        $data = DBField::create_field('Text', 'This &amp; This');
        $this->assertEquals($data->RAW(), 'This &amp; This');
    }

    public function testXML()
    {
        $data = DBField::create_field('Text', 'This & This');
        $this->assertEquals($data->XML(), 'This &amp; This');
    }

    public function testHTML()
    {
        $data = DBField::create_field('Text', 'This & This');
        $this->assertEquals($data->HTML(), 'This &amp; This');
    }

    public function testJS()
    {
        $data = DBField::create_field('Text', '"this is a test"');
        $this->assertEquals($data->JS(), '\"this is a test\"');
    }

    public function testATT()
    {
        $data = DBField::create_field('Text', '"this is a test"');
        $this->assertEquals($data->ATT(), '&quot;this is a test&quot;');
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

        $problematicText = html_entity_decode('This is a&nbsp;Test with non-breaking&nbsp;space!', ENT_COMPAT, 'UTF-8');

        $textObj = new DBText('Test');
        $textObj->setValue($problematicText);

        $this->assertTrue(mb_check_encoding($textObj->FirstSentence(), 'UTF-8'));
    }
}
