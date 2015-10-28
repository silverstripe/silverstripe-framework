<?php

/**
 * Tests {@see InlineFormAction}
 */
class InlineFormActionTest extends SapphireTest {

	public function testField() {
		$action = new InlineFormAction('dothing', 'My Title', 'ss-action');
		$this->assertEquals(
			'<input type="submit" name="action_dothing" value="My Title" id="dothing" class="action ss-action">',
			(string)$action->Field()
		);
	}
}
