<?php

namespace SilverStripe\View\Tests\Parsers;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\Diff;

class DiffTest extends SapphireTest
{

    /**
     * @see https://groups.google.com/forum/#!topic/silverstripe-dev/yHcluCvuszo
     */
    public function testTableDiff()
    {
        if (!class_exists('DOMDocument')) {
            $this->markTestSkipped('"DOMDocument" required');
            return;
        }

        $from = "<table>
		<tbody>
			<tr class=\"blah\">
				<td colspan=\"2\">Row 1</td>
			</tr>
			<tr class=\"foo\">
				<td>Row 2</td>
				<td>Row 2</td>
			</tr>
			<tr>
				<td>Row 3</td>
				<td>Row 3</td>
			</tr>
			</tbody>
		</table>";

        $to = "<table class=\"new-class\">
		<tbody>
			<tr class=\"blah\">
				<td colspan=\"2\">Row 1</td>
			</tr>
			<tr class=\"foo\">
				<td>Row 2</td>
				<td>Row 2</td>
			</tr>
		</tbody>
		</table>";

        $expected = "<ins>" . $to . "</ins>" . "<del>" . $from . "</del>";
        $compare = Diff::compareHTML($from, $to);

        // Very hard to debug this way, wouldn't need to do this if PHP had an *actual* DOM parsing lib,
        // and not just the poor excuse that is DOMDocument
        $compare = preg_replace('/[\s\t\n\r]*/', '', $compare);
        $expected = preg_replace('/[\s\t\n\r]*/', '', $expected);

        $this->assertEquals($expected, $compare);
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

        $expected = "<del>$sentenceOne</del> $sentenceTwo <ins>$sentenceOne</ins>";
        $actual = Diff::compareHTML($from, $to);

        $this->assertEquals($expected, $actual);
    }
}
