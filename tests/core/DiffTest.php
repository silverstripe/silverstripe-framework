<?php
/**
 * @package cms
 * @subpackage tests
 */

class DiffTest extends SapphireTest {

	/**
	 * @see https://groups.google.com/forum/#!topic/silverstripe-dev/yHcluCvuszo
	 */
	public function testTableDiff() {
		if(!class_exists('DOMDocument')) {
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

		$this->assertEquals($compare, $expected);
	}
}
