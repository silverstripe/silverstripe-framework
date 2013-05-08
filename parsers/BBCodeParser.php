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
	 * @config
	 * @var Boolean Set whether phrases starting with http:// or www. are automatically linked
	 */
	private static $autolink_urls = true;
	
	/**
	 * @config
	 * @var Boolean Set whether similies :), :(, :P are converted to images
	 */
	private static $allow_similies = false;
	
	/**
	 * @config
	 * @var string Set the location of the smiles folder. By default use the ones in framework
	 * but this can be overridden by setting  BBCodeParser::set_icon_folder('themes/yourtheme/images/');
	 */
	private static $smilies_location = null;
	
	/**
	 * @deprecated 3.2 Use the "BBCodeParser.smilies_location" config setting instead
	 */
	public static function smilies_location() {
		Deprecation::notice('3.2', 'Use the "BBCodeParser.smilies_location" config setting instead');
		if(!BBCodeParser::$smilies_location) {
			return FRAMEWORK_DIR . '/images/smilies';
		}
		return static::config()->smilies_location;
	}

	/**
	 * @deprecated 3.2 Use the "BBCodeParser.smilies_location" config setting instead
	 */
	public static function set_icon_folder($path) {
		Deprecation::notice('3.2', 'Use the "BBCodeParser.smilies_location" config setting instead');
		static::config()->smilies_location = $path;
	} 
	
	/**
	 * @deprecated 3.2 Use the "BBCodeParser.autolink_urls" config setting instead
	 */
	public static function autolinkUrls() {
		Deprecation::notice('3.2', 'Use the "BBCodeParser.autolink_urls" config setting instead');
		return static::config()->autolink_urls;
	}
	
	/**
	 * @deprecated 3.2 Use the "BBCodeParser.autolink_urls" config setting instead
	 */
	public static function disable_autolink_urls($autolink = false) {
		Deprecation::notice('3.2', 'Use the "BBCodeParser.autolink_urls" config setting instead');
		static::config()->autolink_urls = $autolink;
	}
	
	/**
	 * @deprecated 3.2 Use the "BBCodeParser.allow_smilies" config setting instead
	 */
	public static function smiliesAllowed() {
		Deprecation::notice('3.2', 'Use the "BBCodeParser.allow_smilies" config setting instead');
		return static::config()->allow_smilies;
	}
	
	/**
	 * @deprecated 3.2 Use the "BBCodeParser.allow_smilies" config setting instead
	 */
	public static function enable_smilies() {
		Deprecation::notice('3.2', 'Use the "BBCodeParser.allow_smilies" config setting instead');
		static::config()->allow_similies = true;
	}
	
	
	public static function usable_tags() {
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
	
	public function useable_tagsHTML(){
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
	public function parse() {
		$this->content = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $this->content);

		$p = new SSHTMLBBCodeParser();
		$this->content = $p->qparse($this->content);
		unset($p);

		$this->content = "<p>".$this->content."</p>";

		$this->content = preg_replace('/(<p[^>]*>)\s+/i', '\\1', $this->content);
		$this->content = preg_replace('/\s+(<\/p[^>]*>)/i', '\\1', $this->content);

		$this->content = preg_replace("/\n\s*\n/", "</p><p>", $this->content);
		$this->content = str_replace("\n", "<br />", $this->content);
				
		if($this->config()->allow_smilies) {
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
