<?php

/**
 * Tests {@see InlineFormAction}
 */
class InlineFormActionTest extends SapphireTest {

	public function testField() {
		$action = new InlineFormAction('dothing', 'My Title', 'ss-action');
        $html = (string)$action->Field();
        $this->assertContains('<input', $html);
        $this->assertContains('type="submit"', $html);
        $this->assertContains('name="action_dothing"', $html);
        $this->assertContains('value="My Title"', $html);
        $this->assertContains('id="dothing"', $html);
        $this->assertContains('class="action ss-action"', $html);
	}
}
