<?php
/**
 * TODO Investigate whether SSViewer will be fast enough to handle hundreds of little template files.
 * 
 * A better (more SS) way of doing the HTML code in here is to place them all in small template files
 * (eg. BBCodeParser_Code.ss contains the HTML for BBCodeParser::parseCode()), but the overhead this 
 * would cause is likely to make this very unusable, as nice as it would be.
 */
class BBCodeParser extends TextParser {
	static function usable_tags() {
		return new DataObjectSet(
			new ArrayData(array(
				"Title" => "Bold Text",
				"Example" => "[b]<b>Bold</b>[/b]"
			)),
			new ArrayData(array(
				"Title" => "Italic Text",
				"Example" => "[i]<i>Italics</i>[/i]"
			)),
			new ArrayData(array(
				"Title" => "Underlined Text",
				"Example" => "[u]<u>Underlined</u>[/u]"
			)),
			new ArrayData(array(
				"Title" => "Struck-out Text",
				"Example" => "[s]<s>Struck-out</s>[/s]"
			)),
			
			new ArrayData(array(
				"Title" => "Website link",
				"Description" => "Link to another website or URL",
				"Example" => "[url]http://www.website.com/[/url]"
			)),
			new ArrayData(array(
				"Title" => "Website link",
				"Description" => "Link to another website or URL",
				"Example" => "[url=http://www.website.com/]Some website[/url]"
			)),			
			new ArrayData(array(
				"Title" => "Email link",
				"Description" => "Create link to an email address",
				"Example" => "[email]you@yoursite.com[/email]"
			)),
				new ArrayData(array(
				"Title" => "Email link",
				"Description" => "Create link to an email address",
				"Example" => "[email=you@yoursite.com]email me[/email]"
			)),		

			new ArrayData(array(
				"Title" => "Image",
				"Description" => "Show an image in your post",
				"Example" => "[img]http://www.website.com/image.jpg[/img]"
			)),
			
			new ArrayData(array(
				"Title" => "Code Block",
				"Description" => "Unformatted code block",
				"Example" => "[code]Code block[/code]"
			)),
			new ArrayData(array(
				"Title" => "HTML Code Block",
				"Description" => "HTML-formatted code block",
				"Example" => "[html]HTML code block[/html]"
			)),
			new ArrayData(array(
				"Title" => "HTML Code Block",
				"Description" => "HTML-formatted code block",
				"Example" => "[code html]HTML code block[/code]"
			)),				
			new ArrayData(array(
				"Title" => "PHP Code Block",
				"Description" => "PHP-formatted code block",
				"Example" => "[php]PHP code block[/php]"
			)),
			new ArrayData(array(
				"Title" => "PHP Code Block",
				"Description" => "PHP-formatted code block",
				"Example" => "[code php]PHP code block[/code]"
			))			
					
		);
	}
	
	function useable_tagsHTML(){
		$useabletags = "<ul class='bbcodeExamples'>";
		foreach($this->usable_tags()->toArray() as $tag){
			$useabletags = $useabletags."<li><span>".$tag->Example."</span></li>";
		}
		return $useabletags."</ul>";
	}
	
	/**
	 * Meat of the class - finds tags to parse and offloads the parsing to member methods.
	 * 
	 * parse() will look at various [square-bracketed] tags and replace them.
	 * Complicated replacements are handled via private member methods. 
	 * Simple stuff is done inline inside the preg_replace to keep overhead low.
	 */
	function parse() {
		$this->content = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $this->content);
		
		// Parse [code X] and base [code] tags
		$this->content = preg_replace("#\[code (.+?)\](.+?)\[/code\]#ies", "\$this->parseCode('\\2', '\\1')", $this->content);
		$this->content = preg_replace("#\[code\](.+?)\[/code\]#ies", "\$this->parseCode('\\1')", $this->content);
		
		// Parse [html] and [php] tags (Shorthand [code html] and [code php] respectively)
		$this->content = preg_replace("#\[html\](.+?)\[/html\]#ies", "\$this->parseCode('\\1', 'html')", $this->content);
		$this->content = preg_replace("#\[php\](.+?)\[/php\]#ies", "\$this->parseCode('\\1', 'php')", $this->content);
		
		// Simple HTML tags (bold, italic, underline, strike-through) - No need to call member methods for these
		$this->content = preg_replace("#\[b\](.+?)\[/b\]#is", "<strong>\\1</strong>", $this->content);
		$this->content = preg_replace("#\[i\](.+?)\[/i\]#is", "<em>\\1</em>", $this->content);
		$this->content = preg_replace("#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $this->content);
		$this->content = preg_replace("#\[s\](.+?)\[/s\]#is", "<s>\\1</s>", $this->content);
		
		// Email tags ([email]name@domain.tld[/email] and [email=name@domain.tld]email me![/email])
		$this->content = preg_replace("#\[email\](\S+?)\[/email\]#i", "<a href=\"mailto:\\1\">\\1</a>", $this->content);
		$this->content = preg_replace("#\[email\s*=\s*([\.\w\-]+\@[\.\w\-]+\.[\w\-]+)\s*\](.*?)\[\/email\]#i", "<a href=\"mailto:\\1\">\\2</a>", $this->content);
		
		// URL tags ([url]someurl.tld[/url] and [url=someurl.tld]visit someurl.tld![/url])
		$this->content = preg_replace("#\[url\](\S+?)\[/url\]#ie", "\$this->parseURL('\\1')", $this->content);
		$this->content = preg_replace("#\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]#ie", "\$this->parseURL('\\1', '\\2')", $this->content);
		$this->content = preg_replace("#\[url\s*=\s*\&quot\;\s*(\S+?)\s*\&quot\;\s*\](.*?)\[\/url\]#ie", "\$this->parseURL('\\1', '\\2')", $this->content);
		
		// Img tags ([img]link/to/some/image.ext[/img])
		$this->content = preg_replace("#\[img\](.+?)\[/img\]#ie", "\$this->parseImg('\\1')", $this->content);

		$this->content = str_replace("\n", "<br />", $this->content);
		return $this->content;
	}
	
	/**
	 * Parses a [code] tag
	 */
	private function parseCode($text, $type="text") {

		// Wrap $text if required
		$text = wordwrap($text, 80);
		
		// Add opening tags if required for PHP
		// Assumes that if there is no opening tag, that there is also no closing tag
		if($type == 'php') {
			if(strpos($text, '&lt;?') === false) {
				$text = "&lt;?php\n$text\n?&gt;";
			}
		}
		
		switch($type) {
			case "php":
			case "html":
				return "<p class=\"code\"><strong>".strtoupper($type).":</strong></p><div class=\"code $type\">".highlight_string(Convert::js2raw(Convert::xml2raw($text)), true)."</div>";
			break;
			case "text":
			default:
				return "<p class=\"code\"><strong>".strtoupper($type).":</strong></p><div class=\"code text\">".wordwrap($text)."</div>";
			break;
		}
	}
	
	/**
	 * Parses a [url] tag
	 */
	private function parseURL($link, $text = "") {
		// If text isn't defined, make it the same as the link (for the [url][/url] tag)
		if(!$text) $text = $link;
		
		// Remove ability to influence Javascript
		$link = preg_replace("/javascript:/i", "java script", $link);
		
		// Ensure the URL starts with protocol://
		// If we don't, assume http://
		if (!preg_match("#^(\S+?)://#", $link)) {
			$link = "http://$link";
		}
		
		// Rewrite *really* long URLs to beginning...end, but only where the URL is the same as the title
		// This will make the title 43 characters long if the link is >100 characters
		if(strlen($link) > 100 && $link == $text) {
			$text = substr($link, 0, 20)."...".substr($link, -20);
		}

		$text = wordwrap($text, 78, " ", true);
		return "<a href=\"$link\" target=\"_blank\">$text</a>";
	}
	
	/**
	 * Parses a [img] tag
	 * 
	 * TODO Set a maximum-images-per-post flag and check it
	 */
	private function parseImg($url) {
		// If we don't let them put this image tag in, just show the unchanged [img] tag so it's sorta obvious that it didn't work
		$noChange = "[img]".$url."[/img]";
		
		// Disallow 'dynamic' images (dynamic being images with '?' in the url :P)
		// TODO Make this more robust, possibly allow it to be turned on/off via the CMS
		if(preg_match("/[?&;]/", $url) || preg_match("/javascript/i", $url)) return $noChange;
		
		// Replace spaces with %20 and return the image tag
		return "<img src=\"".str_replace(" ", "%20", $url)."\" border=\"0\" alt=\"\">";
	}
}
?>