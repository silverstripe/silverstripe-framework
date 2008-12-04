<?php
require_once('HTML/HTMLBBCodeParser.php');
/*Seting up the PEAR bbcode parser*/  
$config = parse_ini_file('BBCodeParser.ini', true);
$options = &SSHTMLBBCodeParser::getStaticProperty('SSHTMLBBCodeParser', '_options');
$options = $config['SSHTMLBBCodeParser'];
//Debug::show($options);
unset($options);


/**
 * BBCode parser object.
 * Use on a text field in a template with $Content.Parse(BBCodeParser).
 * @package sapphire
 * @subpackage misc
 */
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
				"Title" => _t('BBCodeParser.BOLD', 'Bold Text'),
				"Example" => '[b]<b>'._t('BBCodeParser.BOLDEXAMPLE', 'Bold').'</b>[/b]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.ITALIC', 'Italic Text'),
				"Example" => '[i]<i>'._t('BBCodeParser.ITALICEXAMPLE', 'Italics').'</i>[/i]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.UNDERLINE', 'Underlined Text'),
				"Example" => '[u]<u>'._t('BBCodeParser.UNDERLINEEXAMPLE', 'Underlined').'</u>[/u]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.STRUCK', 'Struck-out Text'),
				"Example" => '[s]<s>'._t('BBCodeParser.STRUCKEXAMPLE', 'Struck-out').'</s>[/s]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.COLORED', 'Colored text'),
				"Example" => '[color=blue]'._t('BBCodeParser.COLOREDEXAMPLE', 'blue text').'[/color]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.ALIGNEMENT', 'Alignment'),
				"Example" => '[align=right]'._t('BBCodeParser.ALIGNEMENTEXAMPLE', 'right aligned').'[/align]'
			)),
						
			new ArrayData(array(
				"Title" => _t('BBCodeParser.LINK', 'Website link'),
				"Description" => _t('BBCodeParser.LINKDESCRIPTION', 'Link to another website or URL'),
				"Example" => '[url]http://www.website.com/[/url]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.LINK', 'Website link'),
				"Description" => _t('BBCodeParser.LINKDESCRIPTION', 'Link to another website or URL'),
				"Example" => "[url=http://www.website.com/]Some website[/url]"
			)),			
			new ArrayData(array(
				"Title" => _t('BBCodeParser.EMAILLINK', 'Email link'),
				"Description" => _t('BBCodeParser.EMAILLINKDESCRIPTION', 'Create link to an email address'),
				"Example" => "[email]you@yoursite.com[/email]"
			)),
				new ArrayData(array(
				"Title" => _t('BBCodeParser.EMAILLINK', 'Email link'),
				"Description" => _t('BBCodeParser.EMAILLINKDESCRIPTION', 'Create link to an email address'),
				"Example" => "[email=you@yoursite.com]email me[/email]"
			)),		

			new ArrayData(array(
				"Title" => _t('BBCodeParser.IMAGE', 'Image'),
				"Description" => _t('BBCodeParser.IMAGEDESCRIPTION', 'Show an image in your post'),
				"Example" => "[img]http://www.website.com/image.jpg[/img]"
			)),
			
			new ArrayData(array(
				"Title" => _t('BBCodeParser.CODE', 'Code Block'),
				"Description" => _t('BBCodeParser.CODEDESCRIPTION', 'Unformatted code block'),
				"Example" => '[code]'._t('BBCodeParser.CODEEXAMPLE', 'Code block').'[/code]'
			)),
			new ArrayData(array(
				"Title" => _t('BBCodeParser.UNORDERED', 'Unordered list'),
				"Description" => _t('BBCodeParser.UNORDEREDDESCRIPTION', 'Unordered list'),
				"Example" => '[ulist][*]'._t('BBCodeParser.UNORDEREDEXAMPLE1', 'unordered item 1').'[*]'._t('BBCodeParser.UNORDEREDEXAMPLE2', 'unordered item 2').'[/ulist]'
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
		$this->content = preg_replace("/\n\s*\n/", "</p><p>", $this->content);
		$this->content = str_replace("\n", "<br />", $this->content);
		return $this->content;
	}
	
}
?>