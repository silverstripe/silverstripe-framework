<?php
/**
 * @package framework
 * @subpackage tests
 */
class CMSMenuItemTest extends SapphireTest {

	public function testAttributes() {
		$menuItem = new CMSMenuItem('Foo', 'foo');
		$exampleAttributes = array('title' => 'foo bar', 'disabled' => true, 'data-foo' => '<something>');

		$this->assertEquals(
			'title="foo bar" disabled="disabled" data-foo="&lt;something&gt;"',
			$menuItem->getAttributesHTML($exampleAttributes),
			'Attributes appear correctly when passed as an argument'
		);

		$emptyAttributes = array('empty' => '');
		$this->assertEquals(
			'',
			$menuItem->getAttributesHTML($emptyAttributes),
			'No attributes are output when argument values are empty'
		);
		$this->assertEquals(
			'',
			$menuItem->getAttributesHTML('some string'),
			'getAttributesHTML() ignores a string argument'
		);

		// Set attributes as class property
		$menuItem->setAttributes($exampleAttributes);
		$this->assertEquals(
			'title="foo bar" disabled="disabled" data-foo="&lt;something&gt;"',
			$menuItem->getAttributesHTML(),
			'Attributes appear correctly when using setAttributes()'
		);
		$this->assertEquals(
			'title="foo bar" disabled="disabled" data-foo="&lt;something&gt;"',
			$menuItem->getAttributesHTML('foo bar'),
			'getAttributesHTML() ignores a string argument and falls back to class property'
		);
	}

}
