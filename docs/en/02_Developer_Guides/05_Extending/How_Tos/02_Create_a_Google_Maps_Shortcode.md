title: How to Create a Google Maps Shortcode

# How to Create a Google Maps Shortcode

To demonstrate how easy it is to build custom shortcodes, we'll build one to display a Google Map based on a provided 
address. We want our CMS authors to be able to embed the map using the following code:
	
	:::php
	[googlemap,width=500,height=300]97-99 Courtenay Place, Wellington, New Zealand[/googlemap]

So we've got the address as "content" of our new `googlemap` shortcode tags, plus some `width` and `height` arguments. 
We'll add defaults to those in our shortcode parser so they're optional.

**mysite/_config.php**

	:::php
	ShortcodeParser::get('default')->register('googlemap', function($arguments, $address, $parser, $shortcode) {
		$iframeUrl = sprintf(
			'http://maps.google.com/maps?q=%s&amp;hnear=%s&amp;ie=UTF8&hq=&amp;t=m&amp;z=14&amp;output=embed',
			urlencode($address),
			urlencode($address)
		);

		$width = (isset($arguments['width']) && $arguments['width']) ? $arguments['width'] : 400;
		$height = (isset($arguments['height']) && $arguments['height']) ? $arguments['height'] : 300;

		return sprintf(
			'<iframe width="%d" height="%d" src="%s" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>',
			$width,
			$height,
			$iframeUrl
		);
	});