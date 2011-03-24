<?php

class TextareaFieldTest extends SapphireTest {

	/**
	 * Quick smoke test to ensure that text is being encoded properly.
	 */
	function testTextEncoding() {
		$inputText = "This is my <text>These are some unicodes: äöü&<>";
		$field = new TextareaField("Test", "Test", 5, 20);
		$field->setValue($inputText);
		$this->assertContains('This is my &lt;text&gt;These are some unicodes: &auml;&ouml;&uuml;&amp;&lt;&gt;', $field->Field());
	}

	/**
	 * Quick smoke test to ensure that text is being encoded properly in readonly fields.
	 */
	function testReadonlyTextEncoding() {
		$inputText = "This is my <text>These are some unicodes: äöü&<>";
		$field = new TextareaField("Test", "Test", 5, 20);
		$field = $field->performReadonlyTransformation();
		$field->setValue($inputText);
		$this->assertContains('This is my &lt;text&gt;These are some unicodes: &auml;&ouml;&uuml;&amp;&lt;&gt;', $field->Field());
	}
	
}