<?php
/**
 * @package framework
 * @subpackage tests
 */

class LabelFieldTest extends SapphireTest {

	public function testFieldHasNoNameAttribute() {
		$field = new LabelField('MyName', 'MyTitle');
		$this->assertEquals($field->Field(), '<label id="MyName" class="readonly">MyTitle</label>');
	}
}
