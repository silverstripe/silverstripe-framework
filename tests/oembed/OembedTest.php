<?php

class OembedTest extends SapphireTest {
	public function testGetOembedFromUrl() {
		Config::inst()->update('Oembed', 'providers', array(
			'http://*.silverstripe.com/watch*'=>'http://www.silverstripe.com/oembed/'
		));
		$escapedEndpointURL = urlencode("http://www.silverstripe.com/oembed/");

		// Test with valid URL
		$result = Oembed::get_oembed_from_url('http://www.silverstripe.com/watch12345');
		$this->assertTrue($result!=false);
		$this->assertEquals($result->getOembedURL(),
			'http://www.silverstripe.com/oembed/?format=json&url='.urlencode('http://www.silverstripe.com/watch12345'),
			'Triggers on matching URL');

		// Test without www.
		$result = Oembed::get_oembed_from_url('http://silverstripe.com/watch12345');
		$this->assertTrue($result!=false);
		$this->assertEquals($result->getOembedURL(),
			'http://www.silverstripe.com/oembed/?format=json&url='.urlencode('http://silverstripe.com/watch12345'),
			'Triggers on matching URL without www');

		// Test if options make their way to the URL
		$result = Oembed::get_oembed_from_url('http://www.silverstripe.com/watch12345', false, array('foo'=>'bar'));
		$this->assertTrue($result!=false);
		$this->assertEquals($result->getOembedURL(), 'http://www.silverstripe.com/oembed/?format=json&url='
			. urlencode('http://www.silverstripe.com/watch12345').'&foo=bar',
			'Includes options');

		// Test magic.
		$result = Oembed::get_oembed_from_url('http://www.silverstripe.com/watch12345', false,
			array('height'=>'foo', 'width'=>'bar'));
		$this->assertTrue($result!=false);
		$urlParts = parse_url($result->getOembedURL());
		parse_str($urlParts['query'], $query);
		$this->assertEquals($query['maxheight'], 'foo', 'Magically creates maxheight option');
		$this->assertEquals($query['maxwidth'], 'bar', 'Magically creates maxwidth option');
	}

	public function testRequestProtocolReflectedInGetOembedFromUrl() {
		Config::inst()->update('Oembed', 'providers', array(
			'http://*.silverstripe.com/watch*'=> array(
				'http' => 'http://www.silverstripe.com/oembed/',
				'https' => 'https://www.silverstripe.com/oembed/?scheme=https',
			),
			'https://*.silverstripe.com/watch*'=> array(
				'http' => 'http://www.silverstripe.com/oembed/',
				'https' => 'https://www.silverstripe.com/oembed/?scheme=https',
			)
		));

		Config::inst()->update('Director', 'alternate_protocol', 'http');

		foreach(array('http', 'https') as $protocol) {
			$url = $protocol.'://www.silverstripe.com/watch12345';
			$result = Oembed::get_oembed_from_url($url);

			$this->assertInstanceOf('Oembed_Result', $result);
			$this->assertEquals($result->getOembedURL(),
				'http://www.silverstripe.com/oembed/?format=json&url='.urlencode($url),
				'Returns http based URLs when request is over http, regardless of source URL');
		}

		Config::inst()->update('Director', 'alternate_protocol', 'https');

		foreach(array('http', 'https') as $protocol) {
			$url = $protocol.'://www.silverstripe.com/watch12345';
			$result = Oembed::get_oembed_from_url($url);

			$this->assertInstanceOf('Oembed_Result', $result);
			$this->assertEquals($result->getOembedURL(),
				'https://www.silverstripe.com/oembed/?scheme=https&format=json&url='.urlencode($url),
				'Returns https based URLs when request is over https, regardless of source URL');
		}

		Config::inst()->update('Director', 'alternate_protocol', 'foo');

		foreach(array('http', 'https') as $protocol) {
			$url = $protocol.'://www.silverstripe.com/watch12345';
			$result = Oembed::get_oembed_from_url($url);

			$this->assertInstanceOf('Oembed_Result', $result);
			$this->assertEquals($result->getOembedURL(),
				'http://www.silverstripe.com/oembed/?format=json&url='.urlencode($url),
				'When request protocol doesn\'t have specific handler, fall back to first option');
		}
	}
}
