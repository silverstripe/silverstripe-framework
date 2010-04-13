<?php
/**
 * Tests the {@link HTTP} class
 *
 * @package sapphire
 * @subpackage tests
 */
class HTTPTest extends SapphireTest {
	
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
		$currentURL = Director::absoluteURL($_SERVER['REQUEST_URI']);

		// Hackery to work around volatile URL formats in test invocation
		$expected = $currentURL;
		$expected .= (strpos($currentURL, '?') === FALSE) ? '?' : '&';
		$expected .= 'foo=bar';
		$this->assertEquals(
			$expected,
			HTTP::setGetVar('foo', 'bar'),
			'Omitting a URL falls back to current URL'
		);

		$this->assertEquals(
			Director::absoluteBaseURL() . 'relative/url?foo=bar',
			HTTP::setGetVar('foo', 'bar', 'relative/url'),
			'Relative URL without slash prefix returns URL with absolute base'
		);

		$this->assertEquals(
			Director::absoluteBaseURL() . '/relative/url?foo=bar',
			HTTP::setGetVar('foo', 'bar', '/relative/url'),
			'Relative URL with slash prefix returns URL with absolute base'
		);

		$this->assertEquals(
			Director::absoluteBaseURL() . '/relative/url?baz=buz&foo=bar',
			HTTP::setGetVar('foo', 'bar', '/relative/url?baz=buz'),
			'Relative URL with existing query params, and new added key'
		);

		$this->assertEquals(
			'http://test.com/?foo=new&buz=baz',
			HTTP::setGetVar('foo', 'new', 'http://test.com/?foo=old&buz=baz'),
			'Absolute URL without path and multipe existing query params, overwriting an existing parameter'
		);

		$this->assertEquals(
			'http://test.com/?foo=new',
			HTTP::setGetVar('foo', 'new', 'http://test.com/?foo=&foo=old'),
			'Absolute URL and empty query param'
		);

		$this->assertEquals(
			// http_build_query() escapes angular brackets, they should be correctly urldecoded by the browser client
			'http://test.com/?foo%5Btest%5D=one&foo%5Btest%5D=two',
			HTTP::setGetVar('foo[test]', 'two', 'http://test.com/?foo[test]=one'),
			'Absolute URL and PHP array query string notation'
		);
	}
	
}
