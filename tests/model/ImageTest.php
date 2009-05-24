<?php
class ImageTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/ImageTest.yml';
	
	function testGetTagWithTitle() {
		$image = $this->fixture->objFromFixture('Image', 'imageWithTitle');
		$expected = '<img src="' . Director::baseUrl() . 'sapphire/tests/model/testimages/test_image.png" alt="This is a image Title" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetTagWithoutTitle() {
		$image = $this->fixture->objFromFixture('Image', 'imageWithoutTitle');
		$expected = '<img src="' . Director::baseUrl() . 'sapphire/tests/model/testimages/test_image.png" alt="test_image" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetTagWithoutTitleContainingDots() {
		$image = $this->fixture->objFromFixture('Image', 'imageWithoutTitleContainingDots');
		$expected = '<img src="' . Director::baseUrl() . 'sapphire/tests/model/testimages/test.image.with.dots.png" alt="test.image.with.dots" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
}
?>