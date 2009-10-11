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
		$expected = array (
			'/?foo=bar'         => array('foo', 'bar', '/'),
			'/?baz=buz&foo=bar' => array('foo', 'bar', '/?baz=buz'),
			'/?buz=baz&foo=baz' => array('foo', 'baz', '/?foo=bar&buz=baz'),
			'/?foo=var'         => array('foo', 'var', '/?foo=&foo=bar'),
			'/?foo[test]=var'   => array('foo[test]', 'var', '/?foo[test]=another')
		);
		
		foreach($expected as $result => $args) {
			$this->assertEquals(
				call_user_func_array(array('HTTP', 'setGetVar'), $args), str_replace('&', '&amp;', $result)
			);
		}
	}
	
}
