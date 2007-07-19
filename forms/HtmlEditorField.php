<?php

/**
 * A WYSIWYG editor field, powered by tinymce.
 * tinymce editor fields are created from <textarea> tags which are then converted with javascript.
 * The {@link Requirements} system is used to ensure that all necessary javascript is included.
 */
class HtmlEditorField extends TextareaField {
	protected $rows;

	/**
	 * Construct a new HtmlEditor field
	 */
	function __construct($name, $title = "", $rows = 15, $cols = 20, $value = "", $form = null) {
		parent::__construct($name, $title, $rows, $cols, $value, $form);
		$this->extraClass = 'typography';
	}
	
	
	/**
	 * Returns the a <textarea> field with tinymce="true" set on it
	 */
	function Field() {
		Requirements::javascript(MCE_ROOT . "tiny_mce_src.js");
		Requirements::javascript("jsparty/tiny_mce_improvements.js");

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
					if(!DataObject::get_one("SiteTree", "URLSegment = '$parts[1]'", false)) $broken = true;
				} else if($link[0] == '/') {
					$broken = true;
				} else if(ereg('^assets/',$link)) {
					if(!DataObject::get_one("File", "Filename = '$link'", false)) $broken = true;
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
		
		$style = "width: 100%; height: " . ($this->rows * 16) . "px";		
		
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
		$content = $this->value;
		
		$content = preg_replace('/mce_real_src="[^"]+"/i', "", $content);
		
		$content = eregi_replace('width=([0-9]+)','width="\\1"',$content);
		$content = eregi_replace('height=([0-9]+)','height="\\1"',$content);
		
		$content = preg_replace_callback('/(<img[^>]* )(width="|height="|src=")([^"]+)("[^>]* )(width="|height="|src=")([^"]+)("[^>]* )(width="|height="|src=")([^"]+)("[^>]*>)/i', "HtmlEditorField_dataValue_processImage", $content);
		
		// If we don't have a containing block element, add a p tag.
		if(!ereg("^[ \t\r\n]*<", $content)) $content = "<p>$content</p>";

		$links = HTTP::getLinksIn($content);
		
		if($links) foreach($links as $link) {
			$link = Director::makeRelative($link);
			
			if(preg_match( '/^([A-Za-z0-9_-]+)\/?(#.*)?$/', $link, $parts ) ) {
				$candidatePage = DataObject::get_one("SiteTree", "URLSegment = '" . urldecode( $parts[1] ). "'", false);
				if($candidatePage) {
					$linkedPages[] = $candidatePage->ID;
					// This caused bugs in the publication script
					// $candidatePage->destroy();
				} else {
					$record->HasBrokenLink = 1;
				}

			} else if($link{0} == '/') {
				$record->HasBrokenLink = 1;

			} else if($candidateFile = DataObject::get_one("File", "Filename = '" . Convert::raw2sql(urldecode($link)) . "'", false)) {
				$linkedFiles[] = $candidateFile->ID;
				// $candidateFile->destroy();
			}
		}

		$images = HTTP::getImagesIn($content);
		
		if($images){
			foreach($images as $image) {
				
				$image = Director::makeRelative($image);
				if(substr($image,0,7) == 'assets/') {
					$candidateImage = DataObject::get_one("File", "Filename = '$image'");
					if($candidateImage) $linkedFiles[] = $candidateImage->ID;
					else $record->HasBrokenFile = 1;
				}
			}
		}
				
		$fieldName = $this->name;
		if($record->ID && $record->hasMethod('LinkTracking') && $linkTracking = $record->LinkTracking()) {
			$linkTracking->removeByFilter("FieldName = '$fieldName'");
			
			if(isset($linkedPages)) foreach($linkedPages as $item) {
				$linkTracking->add($item, array("FieldName" => $fieldName));
			}
			
			// $linkTracking->destroy();
		}
		if($record->ID && $record->hasMethod('ImageTracking') && $imageTracking = $record->ImageTracking()) {
			$imageTracking->removeByFilter("FieldName = '$fieldName'");
			if(isset($linkedFiles)) foreach($linkedFiles as $item) {
				$imageTracking->add($item, array("FieldName" => $fieldName));
			}
			// $imageTracking->destroy();
		}
			
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

class HtmlEditorField_readonly extends ReadonlyField {
	function Field() {
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		return "<div class=\"readonly typography\" id=\"" . $this->id() . "\">$this->value</div><br /><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
	function Type() {
		return 'readonly';
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
	$image = Image::find($src);
	
	if($image) {
		// If we have an image, generate the resized image.
		$resizedImage = $image->getFormattedImage("ResizedImage",$width, $height);
		$parts[$partSource['src="']] = $resizedImage->getRelativePath() ;
	} else {
		$parts[$partSource['src="']] = "";
	}
		
	$parts[0] = "";
	$result = implode("", $parts);
	return $result;
}


class HtmlEditorField_Toolbar extends ViewableData {
	protected $controller, $name;
	
	function __construct($controller, $name) {
		$this->controller = $controller;
		$this->name = $name;
	}
	
	function Buttons() {
		return new DataObjectSet(
			new HtmlEditorField_button("Bold","bold","Bold (Ctrl+B)"),
			new HtmlEditorField_button("Italic","italic","Italic (Ctrl+I)"),
			new HtmlEditorField_button("Underline","underline", "Underline (Ctrl+U)"),
			new HtmlEditorField_button("Strikethrough","strikethrough"),
			new HtmlEditorField_separator(),
			new HtmlEditorField_button("JustifyLeft","justifyleft","Align left"),
			new HtmlEditorField_button("JustifyCenter","justifycenter","Align center"),
			new HtmlEditorField_button("JustifyRight","justifyright","Align right"),
			new HtmlEditorField_button("JustifyFull","justifyfull","Justify"),
			
			/*new HtmlEditorField_dropdown("mceSetCSSClass", "styleSelect", array(
				"mceContentBody" => "mceContentBody",
			)),*/
			new HtmlEditorField_dropdown("FormatBlock", "formatSelect", array(
				"<p>" => "Paragraph",
				"<address>" => "Address",
				"<pre>" => "Preformatted",
				"<h1>" => "Heading 1",
				"<h2>" => "Heading 2",
				"<h3>" => "Heading 3",
				"<h4>" => "Heading 4",
				"<h5>" => "Heading 5",
				"<h6>" => "Heading 6",
			)),
			new HtmlEditorField_separator(),
			new HtmlEditorField_button("InsertUnorderedList","bullist","Bullet-point list"),
			new HtmlEditorField_button("InsertOrderedList","numlist","Numbered list"),
			new HtmlEditorField_button("Outdent","outdent","Decrease outdent"),
			new HtmlEditorField_button("Indent","indent","Increase indent"),
			new HtmlEditorField_button("inserthorizontalrule","hr","Insert horizontal line"),
			new HtmlEditorField_button("mceCharMap","charmap","Insert symbol"),
			
			new HtmlEditorField_break(),
			
			new HtmlEditorField_button("Undo","undo","Undo (Ctrl+Z)"),
			new HtmlEditorField_button("Redo","redo","Redo (Ctrl+Y)"),
			new HtmlEditorField_separator(),
			new HtmlEditorField_button("Cut","cut","Cut (Ctrl+X)"),
			new HtmlEditorField_button("Copy","copy","Copy (Ctrl+C)"),
			new HtmlEditorField_button("Paste","paste","Paste (Ctrl+V)"),
			new HtmlEditorField_separator(),

			new HtmlEditorField_button("ssImage","image","Insert image"),
			new HtmlEditorField_button("ssFlash","flash:flash","Insert flash"),
			
			new HtmlEditorField_button("ssLink","link","Insert/edit link for highlighted text"),
			new HtmlEditorField_button("unlink","unlink","Remove link"),
			new HtmlEditorField_button("mceInsertAnchor","anchor","Insert/edit anchor"),
			new HtmlEditorField_separator(),

			new HtmlEditorField_button("mceCodeEditor","code","Edit HTML Code"),
			
			// We don't need this because tinymce is good at auto-tidying
			// new HtmlEditorField_button("mceCleanup","cleanup","Clean up code"),
			
			
			new HtmlEditorField_button("mceToggleVisualAid","visualaid","Show/hide guidelines"),
			
			new HtmlEditorField_separator(),

			new HtmlEditorField_button("mceInsertTable","table:table","Insert table"),
			new HtmlEditorField_button("mceTableInsertRowBefore","table:table_insert_row_before","Insert row before"),
			new HtmlEditorField_button("mceTableInsertRowAfter","table:table_insert_row_after","Insert row after"),
			new HtmlEditorField_button("mceTableDeleteRow","table:table_delete_row","Delete row"),
			new HtmlEditorField_button("mceTableInsertColBefore","table:table_insert_col_before","Insert column before"),
			new HtmlEditorField_button("mceTableInsertColAfter","table:table_insert_col_after","Insert column after"),
			new HtmlEditorField_button("mceTableDeleteCol","table:table_delete_col","Delete column")
			
		);
	}
	
	/**
	 * Returns the form which the Link button returns. 
	 * The link functions below are shown and hidden via javascript
	 */
	function LinkForm() {
		$form = new Form(
			$this->controller,
			"{$this->name}.LinkForm", 
			new FieldSet(
				new OptionsetField("LinkType", "Link to", 
					array(
						"internal" => "Page on the site",
						"external" => "Another website",
						"email" => "Email address",
						"file" => "Download a file",			
					)
				),
				new TreeDropdownField("internal", "Page", "SiteTree", "URLSegment"),
				new TextField("external", "URL"),
				new EmailField("email", "Email address"),
				new TreeDropdownField("file","File","File", "Filename"),
				new TextField("Description", "Link description"),
				new CheckboxField("TargetBlank", "Open link in a new window?")
			),
			new FieldSet(
				new FormAction("insert", "Insert link"),
				new FormAction("remove", "Remove link"),
				new FormAction("cancel", "Cancel")
			)
		);
		$form->loadDataFrom($this);
		return $form;
	}

	function ImageForm() {
		$form = new Form($this->controller, "{$this->name}.ImageForm", 
		new FieldSet(
			new TreeDropdownField("FolderID", "Folder", "Folder"),
			new ThumbnailStripField("Image", "FolderID", "getimages"),
			new TextField("AltText", "Description", "", 80),
			new DropdownField("CSSClass", "Alignment / style", array(
				"left" => "On the left, with text wrapping around.",
				"right" => "On the right, with text wrapping around.",
				"center" => "Centred, on its own.",
			)),
			new FieldGroup("Dimensions",
				new TextField("Width", "", "", 5),
				new TextField("Height", "x", "", 5)
			)
		),
		new FieldSet(
		/*
			new FormAction("insertimage", "Insert image"),
			new FormAction("cancel", "Cancel")
		*/
		)
		);
		$form->loadDataFrom($this);
		return $form;
	}

	function FlashForm() {
		$form = new Form($this->controller, "{$this->name}.FlashForm", 
			new FieldSet(
				new TreeDropdownField("FolderID", "Folder", "Folder"),
				new ThumbnailStripField("Flash", "FolderID", "getflash"),
				new TextField("Width", "Width (px)"),
				new TextField("Height", "Height (px)")
			),
			new FieldSet()
		);
		$form->loadDataFrom($this);
		return $form;
	}
}


/**
 * These controls are used when manually constructing a toolbar, as we do in the CMS
 */
class HtmlEditorField_control extends ViewableData {
	protected $command;
	
	function Type() { return substr($this->class,strrpos($this->class,'_')+1); }
	function Command() { return $this->command; }
}

class HtmlEditorField_button extends HtmlEditorField_control {
	function __construct($command, $icon, $title = null) {
		$this->title = $title ? $title : $command;
		$this->command = $command;
		$this->icon = $icon;
		parent::__construct();
	}
	function Command() {
		return $this->command;
	}
	function Icon() {
		if(strpos($this->icon,'/') !== false) {
			return $this->icon;

		} else if(strpos($this->icon,':') !== false) {
			list($plugin,$icon) = explode(':', $this->icon, 2);
			return MCE_ROOT . 'plugins/' . $plugin . '/images/' . $icon . '.gif';
			
		} else {
			return MCE_ROOT . 'themes/advanced/images/' . $this->icon . '.gif';
		}
	}
	function Title() {
		return $this->title;
	}
	function IDSegment() {
		return $this->icon;
	}
}
class HtmlEditorField_separator extends HtmlEditorField_control {
}
class HtmlEditorField_dropdown extends HtmlEditorField_control {
	protected $options, $idSegment;
	
	function __construct($command, $idSegment, $options) {
		$this->command = $command;
		$this->options = $options;
		$this->idSegment = $idSegment;
		parent::__construct();
	}
	
	function Options() {
		$options = '';
		foreach($this->options as $k => $v) {
			$k = Convert::raw2att($k);
			$v = Convert::raw2xml($v);
			$options .= "<option value=\"$k\">$v</option>\n";
		}
		return $options;
	}
	function IDSegment() {
		return $this->idSegment;
	}
}
class HtmlEditorField_break extends HtmlEditorField_control {
}

?>