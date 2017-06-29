<?php

namespace SilverStripe\View\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\HTML;

class HTMLTest extends SapphireTest
{
    public function testCreateVoidTag()
    {
        $tag = HTML::createTag('meta', [
            'name' => 'description',
            'content' => 'test tag',
        ]);
        $this->assertEquals('<meta name="description" content="test tag" />', $tag);
    }

    public function testEmptyAttributes()
    {
        $tag = HTML::createTag('meta', [
            'value' => 0,
            'content' => '',
            'max' => 3,
            'details' => null,
            'disabled' => false,
            'readonly' => true,
        ]);
        $this->assertEquals('<meta value="0" max="3" readonly="1" />', $tag);
    }

    public function testNormalTag()
    {
        $tag = HTML::createTag('a', [
            'title' => 'Some link',
            'nullattr' => null,
        ]);
        $this->assertEquals('<a title="Some link"></a>', $tag);

        $tag = HTML::createTag('a', [
            'title' => 'HTML & Text',
            'nullattr' => null,
        ], 'Some <strong>content!</strong>');
        $this->assertEquals('<a title="HTML &amp; Text">Some <strong>content!</strong></a>', $tag);
    }

    public function testVoidContentError()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Void element \"link\" cannot have content");

        HTML::createTag('link', [
            'title' => 'HTML & Text',
            'nullattr' => null,
        ], 'Some <strong>content!</strong>');
    }
}
