<?php

namespace SilverStripe\View\Tests\Shortcodes;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\Shortcodes\EmbedShortcodeProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Tests\Embed\EmbedUnitTest;

class EmbedShortcodeProviderTest extends EmbedUnitTest
{
    public function assertEqualIgnoringWhitespace($a, $b, $message = '')
    {
        $this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b), $message);
    }

    private function getShortcodeHtml(
        string $urlA,
        string $urlB,
        string $firstResponse,
        string $secondResponse,
        array $arguments
    ): string {
        $firstResponse = str_replace("\n", '', $firstResponse);
        $secondResponse = str_replace("\n", '', $secondResponse);
        $embedContainer = $this->createEmbedContainer($urlA, $urlB, $firstResponse, $secondResponse);
        return EmbedShortcodeProvider::handle_shortcode($arguments, '', null, '', ['Embeddable' => $embedContainer]);
    }

    public function testYoutube()
    {
        $url = 'https://www.youtube.com/watch?v=dM15HfUYwF0';
        $html = $this->getShortcodeHtml(
            $url,
            $url,
            <<<EOT
            <link rel="alternate" type="application/json+oembed" href="https://www.youtube.com/oembed?format=json&amp;url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3Da2tDOYkFCYo" title="The flying car completes first ever inter-city flight (Official Video)">
            EOT,
            <<<EOT
            {"title":"The flying car completes first ever inter-city flight (Official Video)","author_name":"KleinVision","author_url":"https://www.youtube.com/channel/UCCHAHvcO7KSNmgXVRIJLNkw","type":"video","height":113,"width":200,"version":"1.0","provider_name":"YouTube","provider_url":"https://www.youtube.com/","thumbnail_height":360,"thumbnail_width":480,"thumbnail_url":"https://i.ytimg.com/vi/a2tDOYkFCYo/hqdefault.jpg","html":"\u003ciframe width=\u0022200\u0022 height=\u0022113\u0022 src=\u0022https://www.youtube.com/embed/a2tDOYkFCYo?feature=oembed\u0022 frameborder=\u00220\u0022 allow=\u0022accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\u0022 allowfullscreen\u003e\u003c/iframe\u003e"}
            EOT,
            [
                'url' => $url,
                'caption' => 'A nice video',
                'width' => 777,
                'height' => 437,
            ],
        );
        $this->assertEqualIgnoringWhitespace(
            <<<EOT
            <div style="width:777px;"><iframe width="777" height="437" src="https://www.youtube.com/embed/a2tDOYkFCYo?feature=oembed" frameborder="0" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe><p class="caption">A nice video</p></div>
            EOT,
            $html
        );
    }

    public function testSoundcloud()
    {
        $url = 'https://soundcloud.com/napalmrecords/delain-suckerpunch';
        $html = $this->getShortcodeHtml(
            $url,
            $url,
            <<<EOT
            <link rel="alternate" type="text/json+oembed" href="https://soundcloud.com/oembed?url=https%3A%2F%2Fsoundcloud.com%2Fnapalmrecords%2Fdelain-suckerpunch&amp;format=json">
            EOT,
            <<<EOT
            {"version":1.0,"type":"rich","provider_name":"SoundCloud","provider_url":"https://soundcloud.com","height":400,"width":"100%","title":"DELAIN - Suckerpunch by Napalm Records","description":"Taken from the EP \"Lunar Prelude\": https://shop.napalmrecords.com/delain","thumbnail_url":"https://i1.sndcdn.com/artworks-000143578557-af0v6l-t500x500.jpg","html":"<iframe width=\"100%\" height=\"400\" scrolling=\"no\" frameborder=\"no\" src=\"https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F242518079&show_artwork=true\"></iframe>","author_name":"Napalm Records","author_url":"https://soundcloud.com/napalmrecords"}
            EOT,
            [
                'url' => $url
            ],
        );
        $this->assertEqualIgnoringWhitespace(
            <<<EOT
            <div style="width:100px;"><iframe width="100%" height="400" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F242518079&show_artwork=true"></iframe></div>
            EOT,
            $html
        );
    }

    public function testVimeo()
    {
        $url = 'https://vimeo.com/680885625';
        $html = $this->getShortcodeHtml(
            $url,
            $url,
            <<<EOT
            <link rel="alternate" href="https://vimeo.com/api/oembed.json?url=https%3A%2F%2Fvimeo.com%2F680885625%3Fh%3D0cadf1a475" type="application/json+oembed" title="Mount Rainier National Park - 2021 - Episode 01">
            EOT,
            <<<EOT
            {"type":"video","version":"1.0","provider_name":"Vimeo","provider_url":"https:\/\/vimeo.com\/","title":"Mount Rainier National Park - 2021 - Episode 01","author_name":"Altered Stag Productions","author_url":"https:\/\/vimeo.com\/alteredstag","is_plus":"0","account_type":"pro","html":"<iframe src=\"https:\/\/player.vimeo.com\/video\/680885625?h=0cadf1a475&amp;app_id=122963\" width=\"640\" height=\"360\" frameborder=\"0\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen title=\"Mount Rainier National Park - 2021 - Episode 01\"><\/iframe>","width":640,"height":360,"duration":60,"description":"Mount Rainier was the first national park I ever visited so it was definitely exciting to be back with refined skills and better equipment. Here is a quick cap of the trip with more segments on the way.\n\nSong: And What Now of the Birds for Ben by David Jennings - March 3, 2021.","thumbnail_url":"https:\/\/i.vimeocdn.com\/video\/1380153025-d3b1840ae521cd936bdaaafaef280b9c0634e729c6b09bca7767792b553a5220-d_640","thumbnail_width":640,"thumbnail_height":360,"thumbnail_url_with_play_button":"https:\/\/i.vimeocdn.com\/filter\/overlay?src0=https%3A%2F%2Fi.vimeocdn.com%2Fvideo%2F1380153025-d3b1840ae521cd936bdaaafaef280b9c0634e729c6b09bca7767792b553a5220-d_640&src1=http%3A%2F%2Ff.vimeocdn.com%2Fp%2Fimages%2Fcrawler_play.png","upload_date":"2022-02-23 08:54:15","video_id":680885625,"uri":"\/videos\/680885625"}
            EOT,
            [
                'url' => $url
            ],
        );
        $this->assertEqualIgnoringWhitespace(
            <<<EOT
            <div style="width: 640px;"><iframe src="https://player.vimeo.com/video/680885625?h=0cadf1a475&amp;app_id=122963" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="Mount Rainier National Park - 2021 - Episode 01"></iframe></div>
            EOT,
            $html
        );
    }

    public function testFlickr()
    {
        $urlA = 'https://www.flickr.com/photos/philocycler/32119532132/in/photolist-QWhZSL-DFFK9V-JcDYRD-S5ksMB-KPznfz-dT81te-2aqUUb1-Gur1ok-cgfEL1-dUu2Cv-8iqmZ9-z5ktAq-z5mCCE-9FmXnE-UH4Y1d-VZsXJn-22zGNHz-e1mzTR-22uVLSo-VJJWsE-VJJJQG-8in8np-agL5ae-9KKkAe-29if7Rt';
        $urlB = 'https://live.staticflickr.com/759/32119532132_50c3f7933f_b.jpg';
        $html = $this->getShortcodeHtml(
            $urlA,
            $urlB,
            <<<EOT
            <link   rel="alternative" type="application/json+oembed"  href="https://www.flickr.com/services/oembed?url&#x3D;https://www.flickr.com/photos/philocycler/32119532132&amp;format&#x3D;json" data-dynamic-added-by="bb44774707b5780000000000000000000000000000000" data-dynamic="true" />
            EOT,
            <<<EOT
            {"type":"photo","flickr_type":"photo","title":"bird","author_name":"Philocycler","author_url":"https:\/\/www.flickr.com\/photos\/philocycler\/","width":1024,"height":742,"url":"https:\/\/live.staticflickr.com\/759\/32119532132_50c3f7933f_b.jpg","web_page":"https:\/\/www.flickr.com\/photos\/philocycler\/32119532132\/","thumbnail_url":"https:\/\/live.staticflickr.com\/759\/32119532132_50c3f7933f_q.jpg","thumbnail_width":150,"thumbnail_height":150,"web_page_short_url":"https:\/\/flic.kr\/p\/QWhZSL","license":"All Rights Reserved","license_id":0,"html":"<a data-flickr-embed=\"true\" href=\"https:\/\/www.flickr.com\/photos\/philocycler\/32119532132\/\" title=\"bird by Philocycler, on Flickr\"><img src=\"https:\/\/live.staticflickr.com\/759\/32119532132_50c3f7933f_b.jpg\" width=\"1024\" height=\"742\" alt=\"bird\"><\/a><script async src=\"https:\/\/embedr.flickr.com\/assets\/client-code.js\" charset=\"utf-8\"><\/script>","version":"1.0","cache_age":3600,"provider_name":"Flickr","provider_url":"https:\/\/www.flickr.com\/"}
            EOT,
            [
                'url' => $urlB,
                'width' => 1024,
                'height' => 742,
                'caption' => 'Birdy'
            ],
        );
        $this->assertEqualIgnoringWhitespace(
            <<<EOT
            <div style="width:1024px;"><a data-flickr-embed="true" href="https://www.flickr.com/photos/philocycler/32119532132/" title="birdbyPhilocycler,onFlickr"><img src="https://live.staticflickr.com/759/32119532132_50c3f7933f_b.jpg" width="1024" height="742" alt="bird"></a><script asyncsrc="https://embedr.flickr.com/assets/client-code.js" charset="utf-8"></script><p class="caption">Birdy</p></div>
            EOT,
            $html
        );
    }

    public function testAudio()
    {
        // not implemented in Silerstripe so will fallback to a link to $urlA
        $urlA = 'https://www.someaudioplace.com/12345';
        $urlB = 'https://www.someaudioplace.com/listen/12345';
        $html = $this->getShortcodeHtml(
            $urlA,
            $urlB,
            <<<EOT
            <link rel="alternative" type="application/json+oembed"  href="https://www.someaudioplace.com/oembed?a=12345" data-dynamic="true" />
            EOT,
            <<<EOT
            {"type":"audio","title":"Some music","author_name":"bob","html":"<audio controls><source src="https://www.someaudioplace.com/listen/12345" type="audio/ogg"></audio>"}
            EOT,
            [
                'url' => $urlB,
            ],
        );
        $this->assertEqualIgnoringWhitespace(
            <<<EOT
            <a href="https://www.someaudioplace.com/12345"></a>
            EOT,
            $html
        );
    }

    public function testFlushCachedShortcodes()
    {
        /** @var CacheInterface $cache */
        $url = 'http://www.test-service.com/abc123';
        $content = '<p>Some content with an [embed url="' . $url . '" thumbnail="https://example.com/mythumb.jpg" ' .
            'class="leftAlone ss-htmleditorfield-file embed" width="480" height="270"]' . $url . '[/embed]</p>';
        $embedHtml = '<iframe myattr="something" />';
        $parser = ShortcodeParser::get('default');

        // use reflection to access private methods
        $provider = new EmbedShortcodeProvider();
        $reflector = new \ReflectionClass(EmbedShortcodeProvider::class);
        $method = $reflector->getMethod('getCache');
        $method->setAccessible(true);
        $cache = $method->invokeArgs($provider, []);
        $method = $reflector->getMethod('deriveCacheKey');
        $method->setAccessible(true);
        $class = 'leftAlone ss-htmleditorfield-file embed';
        $width = '480';
        $height = '270';
        $key = $method->invokeArgs($provider, [$url, $class, $width, $height]);

        // assertions
        $this->assertEquals('embed-shortcode-httpwwwtest-servicecomabc123-leftAloness-htmleditorfield-fileembed-480-270', $key);
        $cache->set($key, $embedHtml);
        $this->assertTrue($cache->has($key));
        EmbedShortcodeProvider::flushCachedShortcodes($parser, $content);
        $this->assertFalse($cache->has($key));
    }
}
