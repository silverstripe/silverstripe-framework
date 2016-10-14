<?php

namespace SilverStripe\Forms\Tests;

use Embed\Adapters\Webpage;
use Embed\Embed;
use SilverStripe\Forms\HtmlEditor\EmbedShortcodeProvider;
use SilverStripe\Dev\SapphireTest;

/**
 * Class EmbedShortcodeProviderTest
 *
 * Because Embed/Embed does not have a mockup, the tests have to run against a live environment.
 * I've tried to fix it by serializing the data to a file, but to no avail.
 * Any improvements on not having to call external resources are welcome.
 */
class EmbedShortcodeProviderTest extends SapphireTest
{

	/**
	 * @var string test youtube. The SilverStripe Platform promotion by UncleCheese
	 */
	protected static $test_youtube = 'https://www.youtube.com/watch?v=dM15HfUYwF0';

	/**
	 * @var string test Soundcloud. One of my favorite bands, Delain, Suckerpunch.
	 */
	protected static $test_soundcloud = 'http://soundcloud.com/napalmrecords/delain-suckerpunch';

	public function testYoutube()
	{
		/** @var Webpage $result */
		$result = Embed::create(self::$test_youtube, array());
		self::assertEquals($result->providerName, 'YouTube');
		$embedded = EmbedShortcodeProvider::embedForTemplate($result);
		self::assertContains("<div class='media'", $embedded);
		self::assertContains('iframe', $embedded);
		self::assertContains('youtube.com', $embedded);
		self::assertContains('embed', $embedded);
		self::assertContains('dM15HfUYwF0', $embedded);
	}

	public function testSoundcloud()
	{
		/** @var Webpage $result */
		$result = Embed::create(self::$test_soundcloud, array());
		self::assertEquals($result->providerName, 'SoundCloud');
		$embedded = EmbedShortcodeProvider::embedForTemplate($result);
		self::assertContains("<div class='media'", $embedded);
		self::assertContains('iframe', $embedded);
		self::assertContains('soundcloud.com', $embedded);
		self::assertContains('player', $embedded);
		self::assertContains('tracks%2F242518079', $embedded);
	}
}

