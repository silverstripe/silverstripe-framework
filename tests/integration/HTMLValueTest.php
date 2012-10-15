<?php
/**
 * @package framework
 * @subpackage tests
 */
class SS_HTMLValueTest extends SapphireTest {
	
	public function testInvalidHTMLSaving() {
		$value = new SS_HTMLValue();
		$invalid = array (
			'<p>Enclosed Value</p></p>'                              => '<p>Enclosed Value</p>',
			'<meta content="text/html"></meta>'                      => '<meta content="text/html">',
			'<p><div class="example"></div></p>'                     => '<p></p><div class="example"></div>',
			'<html><html><body><falsetag "attribute=""attribute""">' => '<falsetag></falsetag>',
			'<body<body<body>/bodu>/body>'                           => '/bodu&gt;/body&gt;'
		);
		
		foreach($invalid as $input => $expected) {
			$value->setContent($input);
			$this->assertEquals($expected, $value->getContent(), 'Invalid HTML can be saved');
		}
	}

	public function testUtf8Saving() {
		$value = new SS_HTMLValue();
		$value->setContent('<p>ö ß ā い 家</p>');
		$this->assertEquals('<p>ö ß ā い 家</p>', $value->getContent());
	}

	public function testOutputFormatting() {
		$value = new SS_HTMLValue();
		$value->setOutputFormatting(true);
		$value->setContent('<meta content="text/html">');
		$this->assertEquals('<meta content="text/html">', $value->getContent(), 'Formatted output works');
	}

	public function testInvalidHTMLTagNames() {
		$value = new SS_HTMLValue();
		$invalid = array(
			'<p><div><a href="test-link"></p></div>',
			'<html><div><a href="test-link"></a></a></html_>',
			'""\'\'\'"""\'""<<<>/</<htmlbody><a href="test-link"<<>'
		);
		
		foreach($invalid as $input) {
			$value->setContent($input);
			$this->assertEquals(
				'test-link',
				$value->getElementsByTagName('a')->item(0)->getAttribute('href'),
				'Link data can be extraced from malformed HTML'
			);
		}
	}
	
	public function testMixedNewlines() {
		$value = new SS_HTMLValue();
		$value->setContent("<p>paragraph</p>\n<ul><li>1</li>\r\n</ul>");
		$this->assertEquals(
			"<p>paragraph</p>\n<ul><li>1</li>\n</ul>",
			$value->getContent(),
			'Newlines get converted'
		);
	}

}
