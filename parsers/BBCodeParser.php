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
 * @package framework
 * @subpackage misc
 */
class BBCodeParser extends TextParser {

	/**
	 * Set whether phrases starting with http:// or www. are automatically linked
	 * @var Boolean
	 */
	protected static $autolinkUrls = true;
	
	/**
	 * Set whether similies :), :(, :P are converted to images
	 * @var Boolean
	 */
	protected static $allowSimilies = false;
	 
	/**
	 * Set the location of the smiles folder. By default use the ones in framework
	 * but this can be overridden by setting  BBCodeParser::set_icon_folder('themes/yourtheme/images/');
	 * @var string
	 */
	protected static $smilies_location = null;
	
	static function smilies_location() {
		if(!BBCodeParser::$smilies_location) {
			return FRAMEWORK_DIR . '/images/smilies';
		}
		return BBCodeParser::$smilies_location;
	}
	static function set_icon_folder($path) {
		BBCodeParser::$smilies_location = $path;
	} 
	
	static function autolinkUrls() {
		return (self::$autolinkUrls != null) ? true : false;
	}
	
	static function disable_autolink_urls($autolink = false) {
		BBCodeParser::$autolinkUrls = $autolink;
	}
	
	static function smiliesAllowed() {
		return (self::$allowSimilies != null) ? true : false;
	}
	
	static function enable_smilies() {
		BBCodeParser::$allowSimilies = true;
	}
	
	
	static function usable_tags() {
		return new ArrayList(
			array(
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
					"Title" => _t('BBCodeParser.CODE', 'Code Block'),
					"Description" => _t('BBCodeParser.CODEDESCRIPTION', 'Unformatted code block'),
					"Example" => '[code]'._t('BBCodeParser.CODEEXAMPLE', 'Code block').'[/code]'
				)),
				new ArrayData(array(
					"Title" => _t('BBCodeParser.EMAILLINK', 'Email link'),
					"Description" => _t('BBCodeParser.EMAILLINKDESCRIPTION', 'Create link to an email address'),
					"Example" => "[email]you@yoursite.com[/email]"
				)),
				new ArrayData(array(
					"Title" => _t('BBCodeParser.EMAILLINK', 'Email link'),
					"Description" => _t('BBCodeParser.EMAILLINKDESCRIPTION', 'Create link to an email address'),
					"Example" => "[email=you@yoursite.com]Email[/email]"
				)),
				new ArrayData(array(
					"Title" => _t('BBCodeParser.UNORDERED', 'Unordered list'),
					"Description" => _t('BBCodeParser.UNORDEREDDESCRIPTION', 'Unordered list'),
					"Example" => '[ulist][*]'._t('BBCodeParser.UNORDEREDEXAMPLE1', 'unordered item 1').'[/ulist]'
				)),			
				new ArrayData(array(
					"Title" => _t('BBCodeParser.IMAGE', 'Image'),
					"Description" => _t('BBCodeParser.IMAGEDESCRIPTION', 'Show an image in your post'),
					"Example" => "[img]http://www.website.com/image.jpg[/img]"
				)),
				new ArrayData(array(
					"Title" => _t('BBCodeParser.LINK', 'Website link'),
					"Description" => _t('BBCodeParser.LINKDESCRIPTION', 'Link to another website or URL'),
					"Example" => '[url]http://www.website.com/[/url]'
				)),
				new ArrayData(array(
					"Title" => _t('BBCodeParser.LINK', 'Website link'),
					"Description" => _t('BBCodeParser.LINKDESCRIPTION', 'Link to another website or URL'),
					"Example" => "[url=http://www.website.com/]Website[/url]"
				))		
			)
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
	 * Main BBCode parser method. This takes plain jane content and
	 * runs it through so many filters 
	 *
	 * @return Text
	 */
	function parse() {
		$this->content = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $this->content);

		$p = new SSHTMLBBCodeParser();
		$this->content = $p->qparse($this->content);
		unset($p);

		$this->content = "<p>".$this->content."</p>";

		$this->content = preg_replace('/(<p[^>]*>)\s+/i', '\\1', $this->content);
		$this->content = preg_replace('/\s+(<\/p[^>]*>)/i', '\\1', $this->content);

		$this->content = preg_replace("/\n\s*\n/", "</p><p>", $this->content);
		$this->content = str_replace("\n", "<br />", $this->content);
				
		if(BBCodeParser::smiliesAllowed()) {
			$smilies = array(
				'#(?<!\w):D(?!\w)#i'         => " <img src='".BBCodeParser::smilies_location(). "/grin.gif'> ", // :D
				'#(?<!\w):\)(?!\w)#i'        => " <img src='".BBCodeParser::smilies_location(). "/smile.gif'> ", // :)
				'#(?<!\w):-\)(?!\w)#i'        => " <img src='".BBCodeParser::smilies_location(). "/smile.gif'> ", // :-)
				'#(?<!\w):\((?!\w)#i'        => " <img src='".BBCodeParser::smilies_location(). "/sad.gif'> ", // :(
				'#(?<!\w):-\((?!\w)#i'        => " <img src='".BBCodeParser::smilies_location(). "/sad.gif'> ", // :-(
				'#(?<!\w):p(?!\w)#i'         => " <img src='".BBCodeParser::smilies_location(). "/tongue.gif'> ", // :p
			 	'#(?<!\w)8-\)(?!\w)#i'     => " <img src='".BBCodeParser::smilies_location(). "/cool.gif'> ", // 8-)
				'#(?<!\w):\^\)(?!\w)#i' => " <img src='".BBCodeParser::smilies_location(). "/confused.gif'> " // :^)
			);
			$this->content = preg_replace(array_keys($smilies), array_values($smilies), $this->content);
		}
		return $this->content;
	}
	
}
