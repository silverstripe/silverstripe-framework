<?php
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Created by IntelliJ IDEA.
 * User: simon
 * Date: 1/10/16
 * Time: 14:38
 */
class ImageShortcodeProviderTest extends SapphireTest
{
	protected static $fixture_file = 'ImageTest.yml';



	public function testShortcodeHandlerFallsBackToFileProperties() {
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithTitle');
		$parser = new ShortcodeParser();
		$parser->register('image', array('SilverStripe\\Assets\\ViewSupport\\ImageShortcodeProvider', 'handle_shortcode'));

		$this->assertEquals(
			sprintf(
				'<img alt="%s">',
				$image->Title
			),
			$parser->parse(sprintf('[image id=%d]', $image->ID))
		);
	}

	public function testShortcodeHandlerUsesShortcodeProperties() {
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithTitle');
		$parser = new ShortcodeParser();
		$parser->register('image', array('SilverStripe\\Assets\\ViewSupport\\ImageShortcodeProvider', 'handle_shortcode'));

		$this->assertEquals(
				'<img alt="Alt content" title="Title content">',
			$parser->parse(sprintf(
				'[image id="%d" alt="Alt content" title="Title content"]',
				$image->ID
			))
		);
	}

	public function testShortcodeHandlerAddsDefaultAttributes() {
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');
		$parser = new ShortcodeParser();
		$parser->register('image', array('SilverStripe\\Assets\\ViewSupport\\ImageShortcodeProvider', 'handle_shortcode'));

		$this->assertEquals(
			sprintf(
				'<img alt="%s">',
				$image->Title
			),
			$parser->parse(sprintf(
				'[image id="%d"]',
				$image->ID
			))
		);
	}
}
