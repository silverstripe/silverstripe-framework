<?php
/**
 * Tests the {@link HTTP} class
 *
 * @package framework
 * @subpackage tests
 */
class HTTPTest extends FunctionalTest {

	public function testAddCacheHeaders() {
		$body = "<html><head></head><body><h1>Mysite</h1></body></html>";
		$response = new SS_HTTPResponse($body, 200);
		$this->assertEmpty($response->getHeader('Cache-Control'));

		HTTP::set_cache_age(30);

		HTTP::add_cache_headers($response);
		$this->assertNotEmpty($response->getHeader('Cache-Control'));

		// Ensure max-age is zero for development.
		Config::inst()->update('Director', 'environment_type', 'dev');
		$response = new SS_HTTPResponse($body, 200);
		HTTP::add_cache_headers($response);
		$this->assertContains('max-age=0', $response->getHeader('Cache-Control'));

		// Ensure max-age setting is respected in production.
		Config::inst()->update('Director', 'environment_type', 'live');
		$response = new SS_HTTPResponse($body, 200);
		HTTP::add_cache_headers($response);
		$this->assertContains('max-age=30', explode(', ', $response->getHeader('Cache-Control')));
		$this->assertNotContains('max-age=0', $response->getHeader('Cache-Control'));

		// Still "live": Ensure header's aren't overridden if already set (using purposefully different values).
		$headers = array(
			'Vary' => '*',
			'Pragma' => 'no-cache',
			'Cache-Control' => 'max-age=0, no-cache, no-store',
		);
		$response = new SS_HTTPResponse($body, 200);
		foreach($headers as $name => $value) {
			$response->addHeader($name, $value);
		}
		HTTP::add_cache_headers($response);
		foreach($headers as $name => $value) {
			$this->assertEquals($value, $response->getHeader($name));
		}
	}


    public function testConfigVary() {
		$body = "<html><head></head><body><h1>Mysite</h1></body></html>";
		$response = new SS_HTTPResponse($body, 200);
		Config::inst()->update('Director', 'environment_type', 'live');
		HTTP::set_cache_age(30);
		HTTP::add_cache_headers($response);

		$v = $response->getHeader('Vary');
		$this->assertNotEmpty($v);

		$this->assertContains("Cookie", $v);
		$this->assertContains("X-Forwarded-Protocol", $v);
		$this->assertContains("User-Agent", $v);
		$this->assertContains("Accept", $v);

		Config::inst()->update('HTTP', 'vary', '');

		$response = new SS_HTTPResponse($body, 200);
		HTTP::add_cache_headers($response);

		$v = $response->getHeader('Vary');
		$this->assertEmpty($v);
	}

	/**
	 * Tests {@link HTTP::getLinksIn()}
	 */
	public function testGetLinksIn() {
		$content = '
			<h2><a href="/">My Cool Site</a></h2>

			<p>
				A boy went <a href="home/">home</a> to see his <span><a href="mother/">mother</a></span>. This
				involved a short <a href="$Journey">journey</a>, as well as some <a href="space travel">space travel</a>
				and <a href=unquoted>unquoted</a> events, as well as a <a href=\'single quote\'>single quote</a> from
				his <a href="/father">father</a>.
			</p>

			<p>
				There were also some elements with extra <a class=attribute href=\'attributes\'>attributes</a> which
				played a part in his <a href=journey"extra id="JourneyLink">journey</a>. HE ALSO DISCOVERED THE
				<A HREF="CAPS LOCK">KEY</a>. Later he got his <a href="quotes \'mixed\' up">mixed up</a>.
			</p>
		';

		$expected = array (
			'/', 'home/', 'mother/', '$Journey', 'space travel', 'unquoted', 'single quote', '/father', 'attributes',
			'journey', 'CAPS LOCK', 'quotes \'mixed\' up'
		);

		$result = HTTP::getLinksIn($content);

		// Results don't neccesarily come out in the order they are in the $content param.
		sort($result);
		sort($expected);

		$this->assertTrue(is_array($result));
		$this->assertEquals($expected, $result, 'Test that all links within the content are found.');
	}

	/**
	 * Tests {@link HTTP::setGetVar()}
	 */
	public function testSetGetVar() {
		// Hackery to work around volatile URL formats in test invocation,
		// and the inability of Director::absoluteBaseURL() to produce consistent URLs.
		$origURI = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = 'relative/url/';
				$this->assertContains(
			'relative/url/?foo=bar',
			HTTP::setGetVar('foo', 'bar'),
			'Omitting a URL falls back to current URL'
		);
		$_SERVER['REQUEST_URI'] = $origURI;

		$this->assertEquals(
			'relative/url?foo=bar',
			HTTP::setGetVar('foo', 'bar', 'relative/url'),
			'Relative URL without existing query params');

		$this->assertEquals(
			'relative/url?baz=buz&amp;foo=bar',
			HTTP::setGetVar('foo', 'bar', '/relative/url?baz=buz'),
			'Relative URL with existing query params, and new added key'
		);

		$this->assertEquals(
			'http://test.com/?foo=new&amp;buz=baz',
			HTTP::setGetVar('foo', 'new', 'http://test.com/?foo=old&buz=baz'),
			'Absolute URL without path and multipe existing query params, overwriting an existing parameter'
		);

		$this->assertContains(
			'http://test.com/?foo=new',
			HTTP::setGetVar('foo', 'new', 'http://test.com/?foo=&foo=old'),
			'Absolute URL and empty query param'
		);
		// http_build_query() escapes angular brackets, they should be correctly urldecoded by the browser client
		$this->assertEquals(
			'http://test.com/?foo%5Btest%5D=one&amp;foo%5Btest%5D=two',
			HTTP::setGetVar('foo[test]', 'two', 'http://test.com/?foo[test]=one'),
			'Absolute URL and PHP array query string notation'
		);

		$urls = array(
			'http://www.test.com:8080',
			'http://test.com:3000/',
			'http://test.com:3030/baz/',
			'http://baz:foo@test.com',
			'http://baz@test.com/',
			'http://baz:foo@test.com:8080',
			'http://baz@test.com:8080'
		);

		foreach($urls as $testURL) {
			$this->assertEquals(
				$testURL .'?foo=bar',
				HTTP::setGetVar('foo', 'bar', $testURL),
				'Absolute URL and Port Number'
			);
		}
	}

	/**
	 * Test that the the get_mime_type() works correctly
	 *
	 */
	public function testGetMimeType() {
		$this->assertEquals('text/plain', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.csv'));
		$this->assertEquals('image/gif', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.gif'));
		$this->assertEquals('text/html', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.html'));
		$this->assertEquals('image/jpeg', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.jpg'));
		$this->assertEquals('image/jpeg', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/upperfile.JPG'));
		$this->assertEquals('image/png', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.png'));
		$this->assertEquals('image/vnd.adobe.photoshop',
			HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.psd'));
		$this->assertEquals('audio/x-wav', HTTP::get_mime_type(FRAMEWORK_DIR.'/tests/control/files/file.wav'));
	}

	/**
	 * Test that absoluteURLs correctly transforms urls within CSS to absolute
	 */
	public function testAbsoluteURLsCSS() {
		$this->withBaseURL('http://www.silverstripe.org/', function($test){

			// background-image
			// Note that using /./ in urls is absolutely acceptable
			$test->assertEquals(
				'<div style="background-image: url(\'http://www.silverstripe.org/./images/mybackground.gif\');">'.
				'Content</div>',
				HTTP::absoluteURLs('<div style="background-image: url(\'./images/mybackground.gif\');">Content</div>')
			);

			// background
			$test->assertEquals(
				'<div style="background: url(\'http://www.silverstripe.org/images/mybackground.gif\');">Content</div>',
				HTTP::absoluteURLs('<div style="background: url(\'images/mybackground.gif\');">Content</div>')
			);

			// list-style-image
			$test->assertEquals(
				'<div style=\'background: url(http://www.silverstripe.org/list.png);\'>Content</div>',
				HTTP::absoluteURLs('<div style=\'background: url(list.png);\'>Content</div>')
			);

			// list-style
			$test->assertEquals(
				'<div style=\'background: url("http://www.silverstripe.org/./assets/list.png");\'>Content</div>',
				HTTP::absoluteURLs('<div style=\'background: url("./assets/list.png");\'>Content</div>')
			);
		});
	}

	/**
	 * Test that absoluteURLs correctly transforms urls within html attributes to absolute
	 */
	public function testAbsoluteURLsAttributes() {
		$this->withBaseURL('http://www.silverstripe.org/', function($test){
			//empty links
			$test->assertEquals(
				'<a href="http://www.silverstripe.org/">test</a>',
				HTTP::absoluteURLs('<a href="">test</a>')
			);

			$test->assertEquals(
				'<a href="http://www.silverstripe.org/">test</a>',
				HTTP::absoluteURLs('<a href="/">test</a>')
			);

			//relative
			$test->assertEquals(
				'<a href="http://www.silverstripe.org/">test</a>',
				HTTP::absoluteURLs('<a href="./">test</a>')
			);
			$test->assertEquals(
				'<a href="http://www.silverstripe.org/">test</a>',
				HTTP::absoluteURLs('<a href=".">test</a>')
			);

			// links
			$test->assertEquals(
				'<a href=\'http://www.silverstripe.org/blog/\'>SS Blog</a>',
				HTTP::absoluteURLs('<a href=\'/blog/\'>SS Blog</a>')
			);

			// background
			// Note that using /./ in urls is absolutely acceptable
			$test->assertEquals(
				'<div background="http://www.silverstripe.org/./themes/silverstripe/images/nav-bg-repeat-2.png">'.
				'SS Blog</div>',
				HTTP::absoluteURLs('<div background="./themes/silverstripe/images/nav-bg-repeat-2.png">SS Blog</div>')
			);

			//check dot segments
			// Assumption: dots are not removed
				//if they were, the url should be: http://www.silverstripe.org/abc
			$test->assertEquals(
				'<a href="http://www.silverstripe.org/test/page/../../abc">Test</a>',
				HTTP::absoluteURLs('<a href="test/page/../../abc">Test</a>')
			);

			// image
			$test->assertEquals(
				'<img src=\'http://www.silverstripe.org/themes/silverstripe/images/logo-org.png\' />',
				HTTP::absoluteURLs('<img src=\'themes/silverstripe/images/logo-org.png\' />')
			);

			// link
			$test->assertEquals(
				'<link href=http://www.silverstripe.org/base.css />',
				HTTP::absoluteURLs('<link href=base.css />')
			);
		});
	}

	/**
	 * 	Make sure URI schemes are not rewritten
	 */
	public function testURISchemes() {
		$this->withBaseURL('http://www.silverstripe.org/', function($test){

			// mailto
			$test->assertEquals(
				'<a href=\'mailto:admin@silverstripe.org\'>Email Us</a>',
				HTTP::absoluteURLs('<a href=\'mailto:admin@silverstripe.org\'>Email Us</a>'),
				'Email links are not rewritten'
			);

			// data uri
			$test->assertEquals(
				'<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38'.
				'GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="Red dot" />',
				HTTP::absoluteURLs('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAH'.
					'ElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="Red dot" />'),
				'Data URI links are not rewritten'
			);

			// call
			$test->assertEquals(
				'<a href="callto:12345678" />',
				HTTP::absoluteURLs('<a href="callto:12345678" />'),
				'Call to links are not rewritten'
			);
		});
	}

}
