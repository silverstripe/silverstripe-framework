<?php

class TextareaFieldTest extends SapphireTest {

	/**
	 * Quick smoke test to ensure that text is being encoded properly.
	 */
	function testTextEncoding() {
		$inputText = "These are some unicodes: äöü";
		$field = new TextareaField("Test", "Test");
		$field->setValue($inputText);
		$this->assertContains('These are some unicodes: &auml;&ouml;&uuml;', $field->Field());
	}

	/**
	 * Quick smoke test to ensure that text is being encoded properly in readonly fields.
	 */
	function testReadonlyDisplaySepcialHTML() {
		$inputText = "These are some special <html> chars including 'single' & \"double\" quotations";
		$field = new TextareaField("Test", "Test");
		$field = $field->performReadonlyTransformation();
		$field->setValue($inputText);
		$this->assertContains('These are some special &lt;html&gt; chars including &#039;single&#039; &amp; &quot;double&quot; quotations', $field->Field());
	}
	
}
