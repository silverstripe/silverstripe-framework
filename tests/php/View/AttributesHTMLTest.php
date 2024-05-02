<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Tests\AttributesHTMLTest\DummyAttributesHTML;

class AttributesHTMLTest extends SapphireTest
{

    public function provideGetAttribute(): array
    {
        return [
            'empty string' => ['test', '', 'Empty string is not converted to a different falsy value'],
            'Zero' => ['test', 0, 'Zero is not converted to a different falsy value'],
            'Null' => ['test', 0, 'Null is not converted to a different falsy value'],
            'False' => ['test', false, 'False is not converted to a different falsy value'],
            'Empty array' => ['test', [], 'Empty array is not converted to a different falsy value'],
            'True' => ['test', true, 'True is stored properly as an attribute'],
            'String' => ['test', 'test', 'String is stored properly as an attribute'],
            'Int' => ['test', -1, 'Int is stored properly as an attribute'],
            'Array' => ['test', ['foo' => 'bar'], 'Array is stored properly as an attribute'],
        ];
    }

    /** @dataProvider provideGetAttribute */
    public function testGetAttribute($name, $value, $message): void
    {
        $dummy = new DummyAttributesHTML();
        $this->assertNull(
            $dummy->getAttribute('non-existent attribute'),
            'Trying to access a non-existent attribute should return null'
        );

        $dummy->setAttribute($name, $value);
        $this->assertSame(
            $value,
            $dummy->getAttribute($name),
            $message
        );
    }

    public function testGetAttributes(): void
    {
        $dummy = new DummyAttributesHTML();
        $dummy->setDefaultAttributes([]);
        $this->assertSame(
            [],
            $dummy->getAttributes(),
            'When no attributes are set and the default attributes are empty, an empty array should be returned'
        );

        $dummy->setAttribute('empty', '');
        $dummy->setAttribute('foo', 'bar');
        $dummy->setAttribute('Number', 123);
        $dummy->setAttribute('Array', ['foo' => 'bar']);

        $this->assertSame(
            [
                'empty' => '',
                'foo' => 'bar',
                'Number' => 123,
                'Array' => ['foo' => 'bar'],
            ],
            $dummy->getAttributes(),
            'All explicitly defined attributes should be returned'
        );

        $dummy = new DummyAttributesHTML();
        $dummy->setDefaultAttributes([
            'foo' => 'Will be overridden',
            'bar' => 'Not overridden',
        ]);
        $this->assertSame(
            [
                'foo' => 'Will be overridden',
                'bar' => 'Not overridden',
            ],
            $dummy->getAttributes(),
            'When no attributes are set and the default attributes are used'
        );

        $dummy->setAttribute('empty', '');
        $dummy->setAttribute('foo', 'bar');
        $dummy->setAttribute('Number', 123);
        $dummy->setAttribute('Array', ['foo' => 'bar']);

        $this->assertSame(
            [
                'foo' => 'bar',
                'bar' => 'Not overridden',
                'empty' => '',
                'Number' => 123,
                'Array' => ['foo' => 'bar'],
            ],
            $dummy->getAttributes(),
            'Explicitly defined attributes overrides default ones'
        );
    }

    public function testAttributesHTML(): void
    {
        $dummy = new DummyAttributesHTML();

        $dummy->setAttribute('emptystring', '');
        $dummy->setAttribute('nullvalue', null);
        $dummy->setAttribute('false', false);
        $dummy->setAttribute('emptyarray', []);
        $dummy->setAttribute('zeroint', 0);
        $dummy->setAttribute('zerostring', '0');
        $dummy->setAttribute('alt', '');
        $dummy->setAttribute('array', ['foo' => 'bar']);
        $dummy->setAttribute('width', 123);
        $dummy->setAttribute('hack>', '">hack');

        $html = $dummy->getAttributesHTML();

        $this->assertStringNotContainsString(
            'emptystring',
            $html,
            'Attribute with empty string are not rendered'
        );

        $this->assertStringNotContainsString(
            'nullvalue',
            $html,
            'Attribute with null are not rendered'
        );

        $this->assertStringNotContainsString(
            'false',
            $html,
            'Attribute with false are not rendered'
        );

        $this->assertStringNotContainsString(
            'emptyarray',
            $html,
            'Attribute with empty array are not rendered'
        );

        $this->assertStringContainsString(
            'zeroint="0"',
            $html,
            'Attribute with a zero int value are rendered'
        );

        $this->assertStringContainsString(
            'zerostring="0"',
            $html,
            'Attribute with a zerostring value are rendered'
        );

        $this->assertStringContainsString(
            'alt=""',
            $html,
            'alt attribute is rendered even when empty set to an empty string'
        );

        $this->assertStringContainsString(
            'array="{&quot;foo&quot;:&quot;bar&quot;}"',
            $html,
            'Array attribute is converted to JSON'
        );

        $this->assertStringContainsString(
            'width="123"',
            $html,
            'Numeric values are rendered with quotes'
        );

        $this->assertStringNotContainsString(
            'hack&quot;&gt;="&quot;&gt;hack"',
            $html,
            'Attribute names and value are escaped'
        );

        $html = $dummy->getAttributesHTML('zeroint', 'array');

        $this->assertStringNotContainsString(
            'zeroint="0"',
            $html,
            'Excluded attributes are not rendered'
        );

        $this->assertStringContainsString(
            'zerostring="0"',
            $html,
            'Attribute not excluded still render'
        );

        $this->assertStringContainsString(
            'alt=""',
            $html,
            'Attribute not excluded still render'
        );

        $this->assertStringNotContainsString(
            'array',
            $html,
            'Excluded attributes are not rendered'
        );
    }

    public function testAttributesHTMLwithExplicitAttr(): void
    {
        $dummy = new DummyAttributesHTML();

        $this->assertEmpty(
            '',
            $dummy->getAttributesHTML(),
            'If no attributes are provided, an empty string should be returned'
        );

        $attributes = [
            'emptystring' => '',
            'nullvalue' => null,
            'false' => false,
            'emptyarray' => [],
            'zeroint' => 0,
            'zerostring' => '0',
            'alt' => '',
            'array' => ['foo' => 'bar'],
            'width' => 123,
            'hack>' => '">hack',
        ];

        $html = $dummy->getAttributesHTML($attributes);

        $this->assertStringNotContainsString(
            'emptystring',
            $html,
            'Attribute with empty string are not rendered'
        );

        $this->assertStringNotContainsString(
            'nullvalue',
            $html,
            'Attribute with null are not rendered'
        );

        $this->assertStringNotContainsString(
            'false',
            $html,
            'Attribute with false are not rendered'
        );

        $this->assertStringNotContainsString(
            'emptyarray',
            $html,
            'Attribute with empty array are not rendered'
        );

        $this->assertStringContainsString(
            'zeroint="0"',
            $html,
            'Attribute with a zero int value are rendered'
        );

        $this->assertStringContainsString(
            'zerostring="0"',
            $html,
            'Attribute with a zerostring value are rendered'
        );

        $this->assertStringContainsString(
            'alt=""',
            $html,
            'alt attribute is rendered even when empty set to an empty string'
        );

        $this->assertStringContainsString(
            'array="{&quot;foo&quot;:&quot;bar&quot;}"',
            $html,
            'Array attribute is converted to JSON'
        );

        $this->assertStringContainsString(
            'width="123"',
            $html,
            'Numeric values are rendered with quotes'
        );

        $this->assertStringNotContainsString(
            'hack&quot;&gt;="&quot;&gt;hack"',
            $html,
            'Attribute names and value are escaped'
        );
    }
}
