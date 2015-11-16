<?php

class HtmlEditorFieldToolbarTest_Toolbar extends HtmlEditorField_Toolbar {
	public function viewfile_getLocalFileByID($id) {
		return parent::viewfile_getLocalFileByID($id);
	}

	public function viewfile_getLocalFileByURL($fileUrl) {
		return parent::viewfile_getLocalFileByURL($fileUrl);
	}

	public function viewfile_getRemoteFileByURL($fileUrl) {
		return parent::viewfile_getRemoteFileByURL($fileUrl);
	}
}

class HtmlEditorFieldToolbarTest extends SapphireTest {

	protected static $fixture_file = 'HtmlEditorFieldToolbarTest.yml';

	protected function getToolbar() {
		return new HtmlEditorFieldToolbarTest_Toolbar(null, '/');
	}

	public function setUp() {
		parent::setUp();

		Config::nest();
		Config::inst()->update('HtmlEditorField_Toolbar', 'fileurl_scheme_whitelist', array('http'));
		Config::inst()->update('HtmlEditorField_Toolbar', 'fileurl_domain_whitelist', array('example.com'));
	}

	public function tearDown() {
		Config::unnest();

		parent::tearDown();
	}

	public function testValidLocalReference() {
		list($file, $url) = $this->getToolbar()->viewfile_getLocalFileByURL('folder/subfolder/example.pdf');
		$this->assertEquals($this->objFromFixture('File', 'example_file'), $file);
	}

	public function testInvalidLocalReference() {
		list($file, $url) = $this->getToolbar()->viewfile_getLocalFileByURL('folder/subfolder/missing.pdf');
		$this->assertNull($file);
	}

	public function testValidScheme() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('http://example.com/test.pdf');
		$this->assertInstanceOf('File', $file);
		$this->assertEquals($file->Filename, 'http://example.com/test.pdf');
	}

	/** @expectedException SS_HTTPResponse_Exception */
	public function testInvalidScheme() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('nosuchscheme://example.com/test.pdf');
	}

	public function testValidDomain() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('http://example.com/test.pdf');
		$this->assertInstanceOf('File', $file);
		$this->assertEquals($file->Filename, 'http://example.com/test.pdf');
	}

	/** @expectedException SS_HTTPResponse_Exception */
	public function testInvalidDomain() {
		list($file, $url) = $this->getToolbar()->viewfile_getRemoteFileByURL('http://evil.com/test.pdf');
	}

}