<?php

class TextareaFieldTest extends SapphireTest {

	public function testCasting() {
		// Test special characters
		$inputText = "These are some unicodes: ä, ö, & ü";
		$field = new TextareaField("Test", "Test");
		$field->setValue($inputText);
		$this->assertContains('These are some unicodes: &auml;, &ouml;, &amp; &uuml;', $field->Field());

		// Test shortcodes
		$inputText = "Shortcode: [file_link id=4]";
		$field = new TextareaField("Test", "Test");
		$field->setValue($inputText);
		$this->assertContains('Shortcode: [file_link id=4]', $field->Field());
	}

	/**
	 * Quick smoke test to ensure that text with unicodes is being displayed properly in readonly fields.
	 */
	public function testReadonlyDisplayUnicodes() {
		$inputText = "These are some unicodes: äöü";
		$field = new TextareaField("Test", "Test");
		$field->setValue($inputText);
		$field = $field->performReadonlyTransformation();
		$this->assertContains('These are some unicodes: äöü', $field->Field());
	}

	/**
	 * Quick smoke test to ensure that text with special html chars is being displayed properly in readonly fields.
	 */
	public function testReadonlyDisplaySpecialHTML() {
		$inputText = "These are some special <html> chars including 'single' & \"double\" quotations";
		$field = new TextareaField("Test", "Test");
		$field = $field->performReadonlyTransformation();
		$field->setValue($inputText);
		$this->assertContains('These are some special &lt;html&gt; chars including &#039;single&#039; &amp;'
			. ' &quot;double&quot; quotations', $field->Field());
	}

	public function testValueEntities() {
		$inputText = "These <b>are</b> some unicodes: äöü";
		$field = new TextareaField("Test", "Test");
		$field->setValue($inputText);

		// Value should be safe-encoding only, but ValueEntities should be more aggressive
		$this->assertEquals(
			"These &lt;b&gt;are&lt;/b&gt; some unicodes: &auml;&ouml;&uuml;",
			$field->obj('Value')->forTemplate()
		);

		// Shortcodes are disabled
		$this->assertEquals(
			false,
			$field->obj('Value')->getProcessShortcodes()
		);
	}

}
