<?php

namespace SilverStripe\View\Tests\Parsers;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\HTML4Value;

class HTML4ValueTest extends SapphireTest
{
    public function testInvalidHTMLSaving()
    {
        $value = new HTML4Value();

        $invalid = [
            '<p>Enclosed Value</p></p>'
                => '<p>Enclosed Value</p>',
            '<meta content="text/html"></meta>'
                => '<meta content="text/html">',
            '<p><div class="example"></div></p>'
                => '<p></p><div class="example"></div>',
            '<html><html><body><falsetag "attribute=""attribute""">'
                => '<falsetag></falsetag>',
            '<body<body<body>/bodu>/body>'
                => '/bodu&gt;/body&gt;'
        ];

        foreach ($invalid as $input => $expected) {
            $value->setContent($input);
            $this->assertEquals($expected, $value->getContent(), 'Invalid HTML can be saved');
        }
    }

    public function testUtf8Saving()
    {
        $value = new HTML4Value();

        $value->setContent('<p>ö ß ā い 家</p>');
        $this->assertEquals('<p>ö ß ā い 家</p>', $value->getContent());
    }

    public function testInvalidHTMLTagNames()
    {
        $value = new HTML4Value();

        $invalid = array(
            '<p><div><a href="test-link"></p></div>',
            '<html><div><a href="test-link"></a></a></html_>',
            '""\'\'\'"""\'""<<<>/</<htmlbody><a href="test-link"<<>'
        );

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
        $value = new HTML4Value();

        $value->setContent("<p>paragraph</p>\n<ul><li>1</li>\r\n</ul>");
        $this->assertEquals(
            "<p>paragraph</p>\n<ul><li>1</li>\n</ul>",
            $value->getContent(),
            'Newlines get converted'
        );
    }

    public function testAttributeEscaping()
    {
        $value = new HTML4Value();

        $value->setContent('<a href="[]"></a>');
        $this->assertEquals('<a href="[]"></a>', $value->getContent(), "'[' character isn't escaped");

        $value->setContent('<a href="&quot;"></a>');
        $this->assertEquals('<a href="&quot;"></a>', $value->getContent(), "'\"' character is escaped");
    }
}
