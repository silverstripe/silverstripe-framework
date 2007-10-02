<?php


require_once('HTML/HTMLBBCodeParser.php');
/*Seting up the PEAR bbcode parser*/  
$config = parse_ini_file('BBCodeParser.ini', true);
$options = &SSHTMLBBCodeParser::getStaticProperty('SSHTMLBBCodeParser', '_options');
$options = $config['SSHTMLBBCodeParser'];
//Debug::show($options);
unset($options);


class BBCodeParser extends TextParser {

	protected static $autolinkUrls = true;
	
	static function autolinkUrls() {
		return (self::$autolinkUrls != null) ? true : false;
	}
	
	static function disable_autolink_urls($autolink = false) {
		BBCodeParser::$autolinkUrls = $autolink;
	}
	
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
				"Title" => "Colored text",
				"Example" => "[color=blue]blue text[/color]"
			)),
			new ArrayData(array(
				"Title" => "Alignment",
				"Example" => "[align=right]right aligned[/align]"
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
				"Title" => "Unordered list",
				"Description" => "Unordered list",
				"Example" => "[ulist][*]unordered item 1[*]unordered item 2[/ulist]"
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
	
	function parse() {
		$this->content = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $this->content);
		$this->content = SSHTMLBBCodeParser::staticQparse($this->content);
		$this->content = "<p>".$this->content."</p>";
		$this->content = str_replace("\n\n", "</p><p>", $this->content);		
		$this->content = str_replace("\n", "<br />", $this->content);
		return $this->content;
	}
	
}
?>