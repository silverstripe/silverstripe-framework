<?php

class SSViewerTest extends SapphireTest {
	/**
	 * Test that a template without a <head> tag still renders.
	 */
	function testTemplateWithoutHeadRenders() {
		$data = new ArrayData(array(
			'Var' => 'var value'
		));
		
		$result = $data->renderWith("SSViewerTestPartialTemplate");
		$this->assertEquals('Test partial template: var value', trim(preg_replace("/<!--.*-->/U",'',$result)));
	}
	
	function testRequirements() {
		$requirements = $this->getMock("Requirements_Backend", array("javascript", "css"));
		$jsFile = 'sapphire/tests/forms/a.js';
		$cssFile = 'sapphire/tests/forms/a.js';
		
		$requirements->expects($this->once())->method('javascript')->with($jsFile);
		$requirements->expects($this->once())->method('css')->with($cssFile);
		
		Requirements::set_backend($requirements);
		
		$data = new ArrayData(array());
		
		$viewer = SSViewer::fromString(<<<SS
		<% require javascript($jsFile) %>
		<% require css($cssFile) %>
SS
);
		$template = $viewer->process($data);
		$this->assertFalse((bool)trim($template), "Should be no content in this return.");
	}
	
	function testComments() {
		$viewer = SSViewer::fromString(<<<SS
This is my template<%-- this is a comment --%>This is some content<%-- this is another comment --%>This is the final content
SS
);
		$output = $viewer->process(new ArrayData(array()));
		
		$this->assertEquals("This is my templateThis is some contentThis is the final content", preg_replace("/\n?<!--.*-->\n?/U",'',$output));
	}
	
	function testRewriteHashlinks() {
		$oldRewriteHashLinks = SSViewer::getOption('rewriteHashlinks');
		SSViewer::setOption('rewriteHashlinks', true);
		
		// Emulate SSViewer::process()
		$base = Convert::raw2att($_SERVER['REQUEST_URI']);
		$tmplFile = TEMP_FOLDER . '/SSViewerTest_testRewriteHashlinks_' . sha1(rand()) . '.ss';
		
		// Note: SSViewer_FromString doesn't rewrite hash links.
		file_put_contents($tmplFile, '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<body>
			</html>');
		$tmpl = new SSViewer($tmplFile);
		$obj = new ViewableData();
		$obj->InsertedLink = '<a class="inserted" href="#anchor">InsertedLink</a>';
		$result = $tmpl->process($obj);
		$this->assertContains(
			'<a class="inserted" href="' . $base . '#anchor">InsertedLink</a>',
			$result
		);
		$this->assertContains(
			'<a class="inline" href="' . $base . '#anchor">InlineLink</a>',
			$result
		);
		
		unlink($tmplFile);
		
		SSViewer::setOption('rewriteHashlinks', $oldRewriteHashLinks);
	}

	function testRewriteHashlinksInPhpMode() {
		$oldRewriteHashLinks = SSViewer::getOption('rewriteHashlinks');
		SSViewer::setOption('rewriteHashlinks', 'php');
		
		$tmplFile = TEMP_FOLDER . '/SSViewerTest_testRewriteHashlinksInPhpMode_' . sha1(rand()) . '.ss';
		
		// Note: SSViewer_FromString doesn't rewrite hash links.
		file_put_contents($tmplFile, '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<body>
			</html>');
		$tmpl = new SSViewer($tmplFile);
		$obj = new ViewableData();
		$obj->InsertedLink = '<a class="inserted" href="#anchor">InsertedLink</a>';
		$result = $tmpl->process($obj);
		$this->assertContains(
			'<a class="inserted" href="<?php echo str_replace(',
			$result
		);
		// TODO Fix inline links in PHP mode
		// $this->assertContains(
		// 	'<a class="inline" href="<?php echo str_replace(',
		// 	$result
		// );
		
		unlink($tmplFile);
		
		SSViewer::setOption('rewriteHashlinks', $oldRewriteHashLinks);
	}
}
