<?php
/**
 * A WYSIWYG editor field, powered by tinymce.
 * tinymce editor fields are created from <textarea> tags which are then converted with javascript.
 * The {@link Requirements} system is used to ensure that all necessary javascript is included.
 * Caution: Only works within the CMS with a global tinymce-menubar, see {@link CMSMain}
 * 
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField extends TextareaField {
	
	/**
	 * Includes the javascript neccesary for this field to work in the current output.
	 * NOTE: If you are loading a form that includes an HtmlEditorField via Ajax this function must be called in the requesting page, because
	 * javascript is not sent via ajax
	 */
	static function include_js() {
		Requirements::javascript(MCE_ROOT . "tiny_mce_src.js");
		Requirements::customScript(HtmlEditorConfig::get_active()->generateJS(), 'htmlEditorConfig');
	}
		
	/**
	 * Construct a new HtmlEditor field
	 */
	function __construct($name, $title = null, $rows = 30, $cols = 20, $value = "", $form = null) {
		parent::__construct($name, $title, $rows, $cols, $value, $form);
		$this->extraClass = 'typography';
	}
	
	/**
	 * Returns the a <textarea> field with tinymce="true" set on it
	 */
	function Field() {
		// Make sure the nessecary javascript is included
		self::include_js();

		// Don't allow unclosed tags - they will break the whole application ;-)		
		$cleanVal = $this->value;
		$lPos = strrpos($cleanVal,'<');
		$rPos = strrpos($cleanVal,'>');
		if(($lPos > $rPos) || ($rPos === false && $lPos !== false)) $cleanVal .= '>';
		
		// Remove broken link classes, we'll add them now
		$cleanVal = eregi_replace('class="([^"]*) *broken *( [^"]*)?"','class="\\1\\2"', $cleanVal);
		
		// Mark up broken links
		$links = HTTP::getLinksIn($cleanVal);
		if($links) {
			$links = array_unique($links);
			foreach($links as $link) {
				$originalLink = $link;
				$link = Director::makeRelative($link);
				$broken = false;
				if(ereg('^([A-Za-z0-9_\-]+)/?(#.*)?$', $link, $parts)) {
					if(!DataObject::get_one("SiteTree", "\"URLSegment\" = '$parts[1]'", false)) {
						$broken = true;
						// Prevents execution timeouts if a page has 50 identical broken links by only highlighting them once
						$alreadyHighlighted[$parts[1]] = true;
					}
				} else if($link == '' || $link[0] == '/') {
					$broken = true;
				} else if(ereg('^assets/',$link)) {
					$link = str_replace(array('%20', '%5C', '%27'), array(' ', '\\', '\''), $link);
					$link = Convert::raw2sql($link);
					if(!DataObject::get_one("File", "\"Filename\" = '$link'", false)) {
						$broken = true;
					}
				}
	
				// Add a class.  Note that this might create multiple class attributes, which are stripped below
				if($broken) $cleanVal = eregi_replace("(<a)([^>]*href=\"{$originalLink}[^\"]*\"[^>]*>)",'\\1 class="broken"\\2', $cleanVal);
			}
		}
		
		// Combined multiple classes into a single class
		while( eregi('(<a[^>]*)class="([^"]*)"([^>]*)class="([^"]*)"([^>]*>)', $cleanVal) )
			$cleanVal = eregi_replace('(<a[^>]*)class="([^"]*)"([^>]*)class="([^"]*)"([^>]*>)','\\1class="\\2 \\4"\\3\\5', $cleanVal);
		
		// We can't use htmlentities as that messes with unicode
		$cleanVal = str_replace(array("&","<",">"),array("&amp;","&lt;","&gt;"),$cleanVal);
		// 97% instead of 100% to prevent horizontal scrollbars in IE7
		$style = "width: 97%; height: " . ($this->rows * 16) . "px";
		$class = "htmleditor";
		$class = ($this->extraClass)?$class." ".$this->extraClass:$class;
		
		return "<textarea class=\"$class\" rows=\"$this->rows\" cols=\"$this->cols\" style=\"$style\" tinymce=\"true\" id=\"" . $this->id() . "\" name=\"{$this->name}\">$cleanVal</textarea>";
	}

	/**	
	 * This function has been created to explicit the functionnality.
	 */
	function setCSSClass($class){
		$this->extraClass = $class;
	}
	
	function saveInto($record) {
		if($record->escapeTypeForField($this->name) != 'xml') {
			user_error("HTMLEditorField should save into an HTMLText or HTMLVarchar field.  
				If you don't, your template won't display properly.  
				This changed in version 2.2.2, so please update 
				your database field '$this->name'", 
				E_USER_WARNING
			);
		}
		
		$content = $this->value;
		
		$content = preg_replace('/mce_real_src="[^"]+"/i', "", $content);
		
		$content = eregi_replace('(<img[^>]* )width=([0-9]+)( [^>]*>|>)','\\1width="\\2"\\3', $content);
		$content = eregi_replace('(<img[^>]* )height=([0-9]+)( [^>]*>|>)','\\1height="\\2"\\3', $content);
		$content = eregi_replace('src="([^\?]*)\?r=[0-9]+"','src="\\1"', $content);
		$content = eregi_replace('mce_src="([^\?]*)\?r=[0-9]+"','mce_src="\\1"', $content);
		
		$content = preg_replace_callback('/(<img[^>]* )(width="|height="|src=")([^"]+)("[^>]* )(width="|height="|src=")([^"]+)("[^>]* )(width="|height="|src=")([^"]+)("[^>]*>)/i', "HtmlEditorField_dataValue_processImage", $content);
		
		// If we don't have a containing block element, add a p tag.
		if(!ereg("^[ \t\r\n]*<", $content)) $content = "<p>$content</p>";

		$links = HTTP::getLinksIn($content);
		$linkedPages = array();
		
		if($links) foreach($links as $link) {
			$link = Director::makeRelative($link);
			
			if(preg_match('/^([A-Za-z0-9_-]+)\/?(#.*)?$/', $link, $parts)) {
				$candidatePage = DataObject::get_one("SiteTree", "\"URLSegment\" = '" . urldecode( $parts[1] ). "'", false);
				if($candidatePage) {
					$linkedPages[] = $candidatePage->ID;
					// This caused bugs in the publication script
					// $candidatePage->destroy();
				} else {
					$record->HasBrokenLink = 1;
				}

			} else if($link == '' || $link[0] == '/') {
				$record->HasBrokenLink = 1;

			} else if($candidateFile = DataObject::get_one("File", "\"Filename\" = '" . Convert::raw2sql(urldecode($link)) . "'", false)) {
				$linkedFiles[] = $candidateFile->ID;
				// $candidateFile->destroy();
			}
		}
		
		$images = HTTP::getImagesIn($content);
		if($images) {
			foreach($images as $image) {
				$image = Director::makeRelative($image);
				if(substr($image,0,7) == 'assets/') {
					$candidateImage = DataObject::get_one("File", "\"Filename\" = '$image'");
					if($candidateImage) $linkedFiles[] = $candidateImage->ID;
					else $record->HasBrokenFile = 1;
				}
			}
		}
				
		$fieldName = $this->name;
		if($record->ID && $record->hasMethod('LinkTracking') && $linkTracking = $record->LinkTracking()) {
			$linkTracking->removeByFilter("\"FieldName\" = '$fieldName' AND \"SiteTreeID\" = $record->ID");
			
			if(isset($linkedPages)) foreach($linkedPages as $item) {
				$linkTracking->add($item, array("FieldName" => $fieldName));
			}
			
			// $linkTracking->destroy();
		}
		if($record->ID && $record->hasMethod('ImageTracking') && $imageTracking = $record->ImageTracking()) {
			$imageTracking->removeByFilter("\"FieldName\" = '$fieldName'");
			if(isset($linkedFiles)) foreach($linkedFiles as $item) {
				$imageTracking->add($item, array("FieldName" => $fieldName));
			}
			// $imageTracking->destroy();
		}
		
		// Sometimes clients will double-escape %20.  Fix this up with this dirty hack
		$content = str_replace('%2520', '%20', $content);
			
		$record->$fieldName = $content;
	}
	
	function rewriteLink($old, $new) {
		$bases[] = "";
		$bases[] = Director::baseURL();
		$bases[] = Director::absoluteBaseURL();
		foreach($bases as $base) {
			$this->value = ereg_replace("(href=\"?)$base$old","\\1$new", $this->value);
		}
		
		$this->value = ereg_replace("(href=\"?)$base$old","\\1$new", $this->value);
		return $this->value;
	}
	
	function performReadonlyTransformation() {
		$field = new HtmlEditorField_readonly($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		$field->dontEscape = true;
		return $field;
	}
}

/**
 * Readonly version of an {@link HTMLEditorField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_readonly extends ReadonlyField {
	function Field() {
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		return "<span class=\"readonly typography\" id=\"" . $this->id() . "\">" . ( $this->value && $this->value != '<p></p>' ? $this->value : '<i>(not set)</i>' ) . "</span><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
	function Type() {
		return 'htmleditorfield readonly';
	}
}

/**
 * Proccesses HTML images into the correct proportions from 
 * the regular expression evaluated on the save.
 */
function HtmlEditorField_dataValue_processImage($parts) {
	
	// The info could be in any order
	$info[$parts[2]] = $parts[3]; $partSource[$parts[2]] = 3;
	$info[$parts[5]] = $parts[6]; $partSource[$parts[5]] = 6;
	$info[$parts[8]] = $parts[9]; $partSource[$parts[8]] = 9;
	$src = Director::makeRelative($info['src="']);
	

	if(substr($src,0,10) == '../assets/') $src = substr($src,3);
	
	$width = $info['width="'];
	$height = $info['height="'];
	
	if(!$width || !$height) {
		user_error("Can't find width/height in $text", E_USER_ERROR);
	}
	
	// find the image inserted from the HTML editor
	$image = Image::find(urldecode($src));
	
	// If we have an image, insert the resampled one into the src attribute; otherwise, leave the img src alone.
	if($image && ($image instanceof Image) && ($image->getWidth() != $width) && ($image->getHeight() != $height)) {
		// If we have an image, generate the resized image.
		$resizedImage = $image->getFormattedImage('ResizedImage', $width, $height);
		if($resizedImage) $parts[$partSource['src="']] = $resizedImage->getRelativePath();
	}
	
	$parts[0] = "";
	$result = implode("", $parts);

	// Insert an empty alt tag if there isn't one
	if(strpos($result, "alt=") === false) {
		$result = substr_replace($result, ' alt="" />', -3);
	}
	
	// Insert an empty title tag if there isn't one (IE shows the alt as title if no title tag)
	if(strpos($result, "title=") === false) {
		$result = substr_replace($result, ' title="" />', -3);
	}

	return $result;
}

/**
 * External toolbar for the HtmlEditorField.
 * This is used by the CMS
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Toolbar extends RequestHandler {
	protected $controller, $name;
	
	function __construct($controller, $name) {
		parent::__construct();
		
		$this->controller = $controller;
		$this->name = $name;
	}
	
	/**
	 * Return a {@link Form} instance allowing a user to
	 * add links in the TinyMCE content editor.
	 *  
	 * @return Form
	 */
	function LinkForm() {
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/tiny_mce_improvements.js");

		$form = new Form(
			$this->controller,
			"{$this->name}/LinkForm", 
			new FieldSet(
				new LiteralField('Heading', '<h2><img src="cms/images/closeicon.gif" alt="' . _t('HtmlEditorField.CLOSE', 'close').'" title="' . _t('HtmlEditorField.CLOSE', 'close') . '" />' . _t('HtmlEditorField.LINK', 'Link') . '</h2>'),
				new OptionsetField(
					'LinkType',
					_t('HtmlEditorField.LINKTO', 'Link to'), 
					array(
						'internal' => _t('HtmlEditorField.LINKINTERNAL', 'Page on the site'),
						'external' => _t('HtmlEditorField.LINKEXTERNAL', 'Another website'),
						'anchor' => _t('HtmlEditorField.LINKANCHOR', 'Anchor on this page'),
						'email' => _t('HtmlEditorField.LINKEMAIL', 'Email address'),
						'file' => _t('HtmlEditorField.LINKFILE', 'Download a file'),			
					)
				),
				new TreeDropdownField('internal', _t('HtmlEditorField.PAGE', "Page"), 'SiteTree', 'URLSegment', 'MenuTitle'),
				new TextField('external', _t('HtmlEditorField.URL', 'URL'), 'http://'),
				new EmailField('email', _t('HtmlEditorField.EMAIL', 'Email address')),
				new TreeDropdownField('file', _t('HtmlEditorField.FILE', 'File'), 'File', 'Filename'),
				new TextField('Anchor', _t('HtmlEditorField.ANCHORVALUE', 'Anchor')),
				new TextField('LinkText', _t('HtmlEditorField.LINKTEXT', 'Link text')),
				new TextField('Description', _t('HtmlEditorField.LINKDESCR', 'Link description')),
				new CheckboxField('TargetBlank', _t('HtmlEditorField.LINKOPENNEWWIN', 'Open link in a new window?'))
			),
			new FieldSet(
				new FormAction('insert', _t('HtmlEditorField.BUTTONINSERTLINK', 'Insert link')),
				new FormAction('remove', _t('HtmlEditorField.BUTTONREMOVELINK', 'Remove link'))
			)
		);
		
		$form->loadDataFrom($this);
		
		return $form;
	}

	/**
	 * Return a {@link Form} instance allowing a user to
	 * add images to the TinyMCE content editor.
	 *  
	 * @return Form
	 */
	function ImageForm() {
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/tiny_mce_improvements.js");
		Requirements::css('cms/css/TinyMCEImageEnhancement.css');
		Requirements::javascript('cms/javascript/TinyMCEImageEnhancement.js');
		Requirements::javascript(THIRDPARTY_DIR . '/SWFUpload/SWFUpload.js');
		Requirements::javascript(CMS_DIR . '/javascript/Upload.js');

		$form = new Form(
			$this->controller,
			"{$this->name}/ImageForm",
			new FieldSet(
				new LiteralField('Heading', '<h2><img src="cms/images/closeicon.gif" alt="' . _t('HtmlEditorField.CLOSE', 'close') . '" title="' . _t('HtmlEditorField.CLOSE', 'close') . '" />' . _t('HtmlEditorField.IMAGE', 'Image') . '</h2>'),
				new TreeDropdownField('FolderID', _t('HtmlEditorField.FOLDER', 'Folder'), 'Folder'),
				new LiteralField('AddFolderOrUpload',
					'<div style="clear:both;"></div><div id="AddFolderGroup" style="display: none">
						<a style="" href="#" id="AddFolder" class="link">' . _t('HtmlEditorField.CREATEFOLDER','Create Folder') . '</a>
						<input style="display: none; margin-left: 2px; width: 94px;" id="NewFolderName" class="addFolder" type="text">
						<a style="display: none;" href="#" id="FolderOk" class="link addFolder">' . _t('HtmlEditorField.OK','Ok') . '</a>
						<a style="display: none;" href="#" id="FolderCancel" class="link addFolder">' . _t('HtmlEditorField.FOLDERCANCEL','Cancel') . '</a>
					</div>
					<div id="PipeSeparator" style="display: none">|</div>
					<div id="UploadGroup" class="group" style="display: none; margin-top: 2px;">
						<a href="#" id="UploadFiles" class="link">' . _t('HtmlEditorField.UPLOAD','Upload') . '</a>
					</div>'
				),
				new TextField('getimagesSearch', _t('HtmlEditorField.SEARCHFILENAME', 'Search by file name')),
				new ThumbnailStripField('FolderImages', 'FolderID', 'getimages'),
				new TextField('AltText', _t('HtmlEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image cannot be displayed'), '', 80),
				new TextField('ImageTitle', _t('HtmlEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')),
				new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
				new DropdownField(
					'CSSClass',
					_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
					array(
						'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
						'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
						'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.'),
						'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
					)
				),
				new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
					new TextField('Width', _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), 100),
					new TextField('Height', " x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), 100)
				)
			),
			new FieldSet(
				new FormAction('insertimage', _t('HtmlEditorField.BUTTONINSERTIMAGE', 'Insert image'))
			)
		);
		
		$form->disableSecurityToken();
		$form->loadDataFrom($this);
		
		return $form;
	}

	function FlashForm() {
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/tiny_mce_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . '/SWFUpload/SWFUpload.js');
		Requirements::javascript(CMS_DIR . '/javascript/Upload.js');

		$form = new Form(
			$this->controller,
			"{$this->name}/FlashForm", 
			new FieldSet(
				new LiteralField('Heading', '<h2><img src="cms/images/closeicon.gif" alt="'._t('HtmlEditorField.CLOSE', 'close').'" title="'._t('HtmlEditorField.CLOSE', 'close').'" />'._t('HtmlEditorField.FLASH', 'Flash').'</h2>'),
				new TreeDropdownField("FolderID", _t('HtmlEditorField.FOLDER'), "Folder"),
				new TextField('getflashSearch', _t('HtmlEditorField.SEARCHFILENAME', 'Search by file name')),
				new ThumbnailStripField("Flash", "FolderID", "getflash"),
				new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', "Dimensions"),
					new TextField("Width", _t('HtmlEditorField.IMAGEWIDTHPX', "Width"), 100),
					new TextField("Height", "x " . _t('HtmlEditorField.IMAGEHEIGHTPX', "Height"), 100)
				)
			),
			new FieldSet(
				new FormAction("insertflash", _t('HtmlEditorField.BUTTONINSERTFLASH', 'Insert Flash'))
			)
		);
		$form->loadDataFrom($this);
		return $form;
	}
}

