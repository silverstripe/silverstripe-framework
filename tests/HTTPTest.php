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
			<h2>My page</h2>
			<p>A boy went <a href="home/">home</a> to see his <span><a href="mother/">mother</a></span>.</p>
		';
		
		$links = HTTP::getLinksIn($content);
		
		$this->assertTrue(is_array($links));
		$this->assertTrue(count($links) == 2);
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
