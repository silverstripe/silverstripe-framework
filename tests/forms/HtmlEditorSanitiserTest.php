<?php
/**
 * @package framework
 * @subpackage tests
 */
class HtmlEditorSanitiserTest extends FunctionalTest {

	public function testSanitisation() {
		$tests = array(
			array(
				'p,strong',
				'<p>Leave Alone</p><div>Strip parent<strong>But keep children</strong> in order</div>',
				'<p>Leave Alone</p>Strip parent<strong>But keep children</strong> in order',
				'Non-whitelisted elements are stripped, but children are kept'
			),
			array(
				'p,strong',
				'<div>A <strong>B <div>Nested elements are still filtered</div> C</strong> D</div>',
				'A <strong>B Nested elements are still filtered C</strong> D',
				'Non-whitelisted elements are stripped even when children of non-whitelisted elements'
			),
			array(
				'p',
				'<p>Keep</p><script>Strip <strong>including children</strong></script>',
				'<p>Keep</p>',
				'Non-whitelisted script elements are totally stripped, including any children'
			),
			array(
				'p[id]',
				'<p id="keep" bad="strip">Test</p>',
				'<p id="keep">Test</p>',
				'Non-whitelisted attributes are stripped'
			),
			array(
				'p[default1=default1|default2=default2|force1:force1|force2:force2]',
				'<p default1="specific1" force1="specific1">Test</p>',
				'<p default1="specific1" force1="force1" default2="default2" force2="force2">Test</p>',
				'Default attributes are set when not present in input, forced attributes are always set'
			)
		);

		$config = HtmlEditorConfig::get('htmleditorsanitisertest');

		foreach($tests as $test) {
			list($validElements, $input, $output, $desc) = $test;

			$config->setOptions(array('valid_elements' => $validElements));
			$sanitiser = new HtmlEditorSanitiser($config);

			$htmlValue = Injector::inst()->create('HTMLValue', $input);
			$sanitiser->sanitise($htmlValue);

			$this->assertEquals($output, $htmlValue->getContent(), $desc);
		}
	}

}
