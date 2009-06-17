<?php

class TextareaFieldTest extends SapphireTest {

	/**
	 * Quick smoke test to ensure that text is being encoded properly.
	 */
	function testTextEncoding() {
		$inputText = "This is my <text>
What's on a new-line?
These are some unicodes: äöü&<>";

		$field = new TextareaField("Test", "Test", 5, 20);
		$field->setValue($inputText);
		
		$this->assertEquals(<<<HTML
<textarea id="Test" name="Test" rows="5" cols="20">This is my &lt;text&gt;
What's on a new-line?
These are some unicodes: &auml;&ouml;&uuml;&amp;&lt;&gt;</textarea>
HTML
			, $field->Field());
	}

	/**
	 * Quick smoke test to ensure that text is being encoded properly in readonly fields.
	 */
	function testReadonlyTextEncoding() {
		$inputText = "This is my <text>
What's on a new-line?
These are some unicodes: äöü&<>";

		$field = new TextareaField("Test", "Test", 5, 20);
		$field = $field->performReadonlyTransformation();
		
		// Make sure that the field is smart enough to have its value set after being made readonly
		$field->setValue($inputText);

		$this->assertEquals(<<<HTML
<span id="Test" class="readonly" name="Test" readonly="readonly">This is my &lt;text&gt;<br />
What's on a new-line?<br />
These are some unicodes: &auml;&ouml;&uuml;&amp;&lt;&gt;</span>
HTML
			, $field->Field());
	}
	
}