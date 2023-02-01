<?php

namespace SilverStripe\View\Tests\Parsers;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\HtmlDiff;

class HtmlDiffTest extends SapphireTest
{

    /**
     * Most if not all other tests strip out the whitespace from comparisons to avoid complexities with checking
     * if the actual HTML content is correct, since whitespace in HTML isn't all that vital and the algorithm
     * can add some extra spaces where they're not stricly necessary but don't affect anything.
     *
     * This test is here to ensure that spaces _are_ kept where they're actually needed (i.e. between text)
     */
    public function testKeepsSpacesBetweenText()
    {
        $from = '<span>Some text</span> <span>more text</span>';
        $to = '<span>Other text</span> <span>more text</span>';
        $diff = HtmlDiff::compareHtml($from, $to);
        $this->assertEquals('<span><del>Some</del> <ins>Other</ins> text</span> <span>more text</span>', $diff, false);

        // Note that the end result here isn't perfect (there are new spaces where there weren't before)...
        // If we make improvements later on that keep only the original spaces, that would be preferred.
        // This test is more here to protect against any unexpected changes to the spacing, so that we can make an intentional
        // decision as to whether those changes are desirable.
        $diff = HtmlDiff::compareHtml($from, $to, true);
        $this->assertEquals('&lt;span&gt; <del>Some</del> <ins>Other</ins> text &lt;/span&gt; &lt;span&gt; more text &lt;/span&gt;', $diff, true);
    }

    public function provideCompareHtml(): array
    {
        return [
            [
                'from' => '<p><span>Some text</span></p>',
                'to' => '<p><span>Other text</span></p>',
                'escape' => false,
                'expected' => '<p><span><del>Some</del><ins>Other</ins> text</span></p>',
            ],
            [
                'from' => '<p><span>Some text</span></p>',
                'to' => '<span>Other text</span>',
                'escape' => false,
                'expected' => '<del><p><span>Some text</span></p></del><ins><span>Other text</span></ins>',
            ],
            [
                'from' => '<p><span>Some text</span></p>',
                'to' => '<p>Other text</p>',
                'escape' => false,
                'expected' => '<p><del><span>Some text</span></del><ins>Other text</ins></p>',
            ],
            [
                'from' => '<h2 class="mb-3 h4">About</h2>',
                'to' => '<h2 class="mb-3 h2">About</h2>',
                'escape' => false,
                'expected' => '<del><h2 class="mb-3 h4">About</h2></del><ins><h2 class="mb-3 h2">About</h2></ins>',
            ],
            [
                'from' => '<div class="BorderGrid-cell"><h2 class="mb-3 h4">About</h2><p class="f4 my-3">A comprehensive Library</p></div>',
                'to' => '<div class="BorderGrid-cell"><h2 class="mb-3 h4">About</h2><span class="etc"><p class="f4 my-3">A comprehensive</p></span></div>',
                'escape' => false,
                'expected' => '<div class="BorderGrid-cell"><h2 class="mb-3 h4">About</h2><del><p class="f4 my-3">A comprehensive Library</p></del><ins><p class="f4 my-3"><span class="etc">A comprehensive</span></p></ins></div>',
            ],
            [
                'from' => '<p><span>Some text</span><span>more stuff</span></p>',
                'to' => '<p><span>Some text</span></p><p>more stuff</p>',
                'escape' => false,
                'expected' => '<p><span>Sometext</span><del><span>morestuff</span></del></p><ins><p>morestuff</p></ins>',
            ],
            // Same examples as above, but with escaped HTML
            [
                'from' => '<p><span>Some text</span></p>',
                'to' => '<p><span>Other text</span></p>',
                'escape' => true,
                'expected' => '&lt;p&gt;&lt;span&gt;<del>Some</del><ins>Other</ins> text&lt;/span&gt;&lt;/p&gt;',
            ],
            [
                'from' => '<p><span>Some text</span></p>',
                'to' => '<span>Other text</span>',
                'escape' => true,
                'expected' => '<del>&lt;p&gt;</del>&lt;span&gt;<del>Some</del><ins>Other</ins> text&lt;/span&gt;<del>&lt;/p&gt;</del>',
            ],
            [
                'from' => '<p><span>Some text</span></p>',
                'to' => '<p>Other text</p>',
                'escape' => true,
                'expected' => '&lt;p&gt;<del>&lt;span&gt;Some</del><ins>Other</ins> text<del>&lt;/span&gt;</del>&lt;/p&gt;',
            ],
            [
                'from' => '<h2 class="mb-3 h4">About</h2>',
                'to' => '<h2 class="mb-3 h2">About</h2>',
                'escape' => true,
                // Note: This sees the whole h2 tag as being changed because of the initial call to explodeToHtmlChunks.
                // There is room to improve this in the future, but care would have to be taken not to aversely affect other scenarios.
                'expected' => '<del>&lt;h2 class="mb-3 h4"&gt;</del><ins>&lt;h2 class="mb-3 h2"&gt;</ins>About&lt;/h2&gt;',
            ],
            [
                'from' => '<div class="BorderGrid-cell"><h2 class="mb-3 h4">About</h2><p class="f4 my-3">A comprehensive Library</p></div>',
                'to' => '<div class="BorderGrid-cell"><h2 class="mb-3 h4">About</h2><span class="etc"><p class="f4 my-3">A comprehensive</p></span></div>',
                'escape' => true,
                'expected' => '&lt;div class="BorderGrid-cell"&gt;&lt;h2 class="mb-3 h4"&gt;About&lt;/h2&gt;<ins>&lt;span class="etc"&gt;</ins>&lt;p class="f4 my-3"&gt;A comprehensive<del> Library</del>&lt;/p&gt;<ins>&lt;/span&gt;</ins>&lt;/div&gt;',
            ],
            [
                'from' => '<p><span>Some text</span><span>more stuff</span></p>',
                'to' => '<p><span>Some text</span></p><p>more stuff</p>',
                'escape' => true,
                'expected' => '&lt;p&gt;&lt;span&gt;Some text&lt;/span&gt;<del>&lt;span&gt;</del><ins>&lt;/p&gt;&lt;p&gt;</ins>more stuff<del>&lt;/span&gt;</del>&lt;/p&gt;',
            ],
        ];
    }

    /**
     * @dataProvider provideCompareHtml
     */
    public function testCompareHTML(string|array $from, string|array $to, bool $escape, string $expected)
    {
        $diff = HtmlDiff::compareHtml($from, $to, $escape);
        $this->assertEquals($this->removeWhiteSpace($expected), $this->removeWhiteSpace($diff));
    }

    /**
     * @see https://groups.google.com/forum/#!topic/silverstripe-dev/yHcluCvuszo
     */
    public function testTableDiff()
    {
        if (!class_exists('DOMDocument')) {
            $this->markTestSkipped('"DOMDocument" required');
            return;
        }

        $from = '<table>
		<tbody>
			<tr class="blah">
				<td colspan="2">Row 1</td>
			</tr>
			<tr class="foo">
				<td>Row 2</td>
				<td>Row 2</td>
			</tr>
			<tr>
				<td>Row 3</td>
				<td>Row 3</td>
			</tr>
			</tbody>
		</table>';

        $to = '<table class="new-class">
		<tbody>
			<tr class="blah">
				<td colspan="2">Row 1</td>
			</tr>
			<tr class="foo">
				<td>Row 2</td>
				<td>Row 2</td>
			</tr>
		</tbody>
		</table>';

        $expected = '<del>' . $from . '</del>' . '<ins>' . $to . '</ins>';
        $compare = HtmlDiff::compareHtml($from, $to);

        $this->assertEquals($this->removeWhiteSpace($expected), $this->removeWhiteSpace($compare));
    }

    /**
     * @see https://github.com/silverstripe/silverstripe-framework/issues/8053
     */
    public function testLegacyEachStatement()
    {
        $sentenceOne =
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        $sentenceTwo =
            'Nulla porttitor, ex quis commodo pharetra, diam dui efficitur justo, eu gravida elit eros vel libero.';

        $from = "$sentenceOne $sentenceTwo";
        $to = "$sentenceTwo $sentenceOne";

        // We're cheating our test a little bit here, because depending on what HTML cleaner you have, you'll get
        // spaces added or not added around the tags.
        $quotedOne = preg_quote($sentenceOne, '/');
        $quotedTwo = preg_quote($sentenceTwo, '/');
        $expected = '/^ *<del>' . $quotedOne . '<\/del> *' . $quotedTwo . ' *<ins>' . $quotedOne . '<\/ins> *$/';
        $actual = HtmlDiff::compareHtml($from, $to);

        $this->assertMatchesRegularExpression($expected, $actual);
    }

    public function testDiffArray()
    {
        $from = ['Lorem', ['array here please ignore'], 'ipsum dolor'];
        $to = 'Lorem,ipsum';
        $expected = '/^Lorem,ipsum *<del>dolor<\/del> *$/';
        $actual = HtmlDiff::compareHtml($from, $to);

        $this->assertMatchesRegularExpression($expected, $actual);
    }

    private function removeWhiteSpace(string $value): string
    {
        return preg_replace('/[\s]*/', '', $value);
    }
}
