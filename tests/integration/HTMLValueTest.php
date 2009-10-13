<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SS_HTMLValueTest extends SapphireTest {
	
	public function testInvalidHTMLSaving() {
		$value   = new SS_HTMLValue();
		$invalid = array (
			'<p>Enclosed Value</p></p>'                              => '<p>Enclosed Value</p>',
			'<p><div class="example"></div></p>'                     => '<p/><div class="example"/>',
			'<html><html><body><falsetag "attribute=""attribute""">' => '<falsetag/>',
			'<body<body<body>/bodu>/body>'                           => '/bodu&gt;/body&gt;'
		);
		
		foreach($invalid as $input => $expected) {
			$value->setContent($input);
			$this->assertEquals($expected, $value->getContent(), 'Invalid HTML can be saved');
		}
	}
	
	public function testInvalidHTMLTagNames() {
		$value   = new SS_HTMLValue();
		$invalid = array (
			'<p><div><a href="test-link"></p></div>',
			'<html><div><a href="test-link"></a></a></html_>',
			'""\'\'\'"""\'""<<<>/</<htmlbody><a href="test-link"<<>'
		);
		
		foreach($invalid as $input) {
			$value->setContent($input);
			$this->assertEquals (
				'test-link',
				$value->getElementsByTagName('a')->item(0)->getAttribute('href'),
				'Link data can be extraced from malformed HTML'
			);
		}
	}
	
}