<?php
/**
 * @package framework
 * @subpackage tests
 */
class CSSContentParserTest extends SapphireTest {
	public function testSelector2xpath() {
		$parser = new CSSContentParser("<html><head><title>test</title></head><body><p>test</p></body></html>");

		$this->assertEquals("//div[@id='UserProfile']//label", $parser->selector2xpath("div#UserProfile label"));
		$this->assertEquals("//div", $parser->selector2xpath("div"));
		$this->assertEquals("//div[contains(@class,'test')]", $parser->selector2xpath("div.test"));
		$this->assertEquals(
			"//*[@id='UserProfile']//div[contains(@class,'test')]//*[contains(@class,'other')]//div[@id='Item']",
			$parser->selector2xpath("#UserProfile div.test .other div#Item"));
	}

	public function testGetBySelector() {
		$parser = new CSSContentParser(<<<HTML
<html>
	<head>
		<title>test</title>
	</head>
	<body>
		<div id="A" class="one two three">
			<p class="other">result</p>
		</div>
		<p>test</p>
	</body>
</html>
HTML
);

		$result = $parser->getBySelector('div.one');
		$this->assertEquals("A", $result[0]['id'].'');
		$result = $parser->getBySelector('div.two');
		$this->assertEquals("A", $result[0]['id'].'');
		$result = $parser->getBySelector('div.three');
		$this->assertEquals("A", $result[0]['id'].'');

		$result = $parser->getBySelector('div#A p.other');
		$this->assertEquals("result", $result[0] . '');
		$result = $parser->getBySelector('#A .other');
		$this->assertEquals("result", $result[0] . '');
	}
}
