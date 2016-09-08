<?php

namespace SilverStripe\View\Parsers;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ArrayData;
use HTML_BBCodeParser2;

/**
 * BBCode parser object.
 * Use on a text field in a template with $Content.Parse(BBCodeParser).
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
	 * Set the location of the smiles folder. By default use the ones in framework
	 * but this can be overridden by setting this via config API
	 *
	 * @config
	 * @var string
	 */
	private static $smilies_location = null;

	public static function usable_tags() {
		/** @skipUpgrade */
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

	public function useable_tagsHTML() {
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
	 * @return DBField
	 */
	public function parse() {
		// Convert content to plain text
		/** @var DBHTMLText $fragment */
		$fragment = DBField::create_field('HTMLFragment', $this->content);
		$this->content = $fragment->Plain();

		// Get options
		$config = parse_ini_file('BBCodeParser.ini', true);
		$options = $config['SSHTMLBBCodeParser'];

		$p = new HTML_BBCodeParser2($options);
		$this->content = $p->qparse($this->content);
		unset($p);

		if($this->config()->allow_smilies) {
			$smiliesLocation = $this->config()->get('smilies_location');
			$smilies = array(
				'#(?<!\w):D(?!\w)#i'         => " <img src='{$smiliesLocation}/grin.gif'> ", // :D
				'#(?<!\w):\)(?!\w)#i'        => " <img src='{$smiliesLocation}/smile.gif'> ", // :)
				'#(?<!\w):-\)(?!\w)#i'        => " <img src='{$smiliesLocation}/smile.gif'> ", // :-)
				'#(?<!\w):\((?!\w)#i'        => " <img src='{$smiliesLocation}/sad.gif'> ", // :(
				'#(?<!\w):-\((?!\w)#i'        => " <img src='{$smiliesLocation}/sad.gif'> ", // :-(
				'#(?<!\w):p(?!\w)#i'         => " <img src='{$smiliesLocation}/tongue.gif'> ", // :p
				'#(?<!\w)8-\)(?!\w)#i'     => " <img src='{$smiliesLocation}/cool.gif'> ", // 8-)
				'#(?<!\w):\^\)(?!\w)#i' => " <img src='{$smiliesLocation}/confused.gif'> " // :^)
			);
			$this->content = preg_replace(array_keys($smilies), array_values($smilies), $this->content);
		}

		// Ensure to return cast value
		return DBField::create_field('HTMLFragment', $this->content);
	}

}
