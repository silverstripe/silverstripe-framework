<?php

namespace SilverStripe\View\Tests\Shortcodes;

use SilverStripe\View\Shortcodes\EmbedShortcodeProvider;
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
        /** @var string $result */
        $result = $this->mockRequest(
            [
                'url' => static::$test_youtube,
                'caption' => 'A nice video',
                'width' => 480,
                'height' => 360,
            ],
            [
                'version' => '1.0',
                'provider_url' => 'https://www.youtube.com/',
                'title' => 'SilverStripe Platform 2 min introduction',
                'html' => '<iframe width="480" height="270" src="https://www.youtube.com/embed/dM15HfUYwF0?feature=oembed" frameborder="0" allowfullscreen></iframe>',
                'provider_name' => 'YouTube',
                'thumbnail_width' => 480,
                'type' => 'video',
                'thumbnail_url' => 'https://i.ytimg.com/vi/dM15HfUYwF0/hqdefault.jpg',
                'thumbnail_height' => 360,
                'width' => 480,
                'author_url' => 'https://www.youtube.com/user/SilverStripe',
                'author_name' => 'SilverStripe',
                'height' => 270,
            ]
        );
        $this->assertEquals(
            <<<EOS
<div style="width: 480px;"><iframe width="480" height="270" src="https://www.youtube.com/embed/dM15HfUYwF0?feature=oembed" frameborder="0" allowfullscreen></iframe>
<p class="caption">A nice video</p></div>
EOS
            ,
            $result
        );
    }

    public function testSoundcloud()
    {
        /** @var string $result */
        $result = $this->mockRequest(
            ['url' => static::$test_soundcloud],
            [
                'version' => 1,
                'type' => 'rich',
                'provider_name' => 'SoundCloud',
                'provider_url' => 'http://soundcloud.com',
                'height' => 400,
                'width' => '100%',
                'title' => 'DELAIN - Suckerpunch by Napalm Records',
                'description' => 'Taken from the EP "Lunar Prelude": http://shop.napalmrecords.com/delain',
                'thumbnail_url' => 'http://i1.sndcdn.com/artworks-000143578557-af0v6l-t500x500.jpg',
                'html' => '<iframe width="100%" height="400" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?visual=true&url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F242518079&show_artwork=true"></iframe>',
                'author_name' => 'Napalm Records',
                'author_url' => 'http://soundcloud.com/napalmrecords',
            ]
        );
        $this->assertEquals(
            <<<EOS
<div style="width: 100px;"><iframe width="100%" height="400" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?visual=true&url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F242518079&show_artwork=true"></iframe></div>
EOS
            ,
            $result
        );
    }

    /**
     * Mock an oembed request
     *
     * @param array $arguments Input arguments
     * @param array $response JSON response body
     * @return string
     */
    protected function mockRequest($arguments, $response)
    {
        return EmbedShortcodeProvider::handle_shortcode(
            $arguments,
            '',
            null,
            'embed',
            [
                'resolver' => [
                    'class' => MockResolver::class,
                    'config' => [
                        'expectedContent' => json_encode($response),
                    ],
                ],
            ]
        );
    }
}
