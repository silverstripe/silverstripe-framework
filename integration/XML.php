<?php
/**
 * XML Class handles parsing xml data within SilverStripe. For reading a RESTFul Service such as flickr or last.fm you should use RestfulService which provides a nicer interface to managing external RSS Feeds. 
 * 
 * 
 * <b>Building XML parsers with the XML class</b>
 * 
 * To use the XML parser, you need to create a subclass.  Then you define process_XXX functions to process different tags.  The parser walks sequentially through the file and calls the process_XXX functions when it hits different tags.
 * 
 *   * **process_(tagname):** This will be called when the parser finds the start tag.  It will be passed the attributes of the tag.
 *   * **process_(tagname)_end:** This will be called when the parser finds the closng tag.  It will be passed the attributes and the content of the tag.
 *   
 *   * **process_tag :** This will be called if it is implemented and a method has not been created already for the tag being parsed. It is passed the tag name and attributes of the tag.
 * 
 *   * **process_tag_end:** This will be called if it is implemented and a method has not been created already for the tag being parsed. It is passed the tag name, the content of the tag and the attributes of the tag.
 * 
 * 
 * The idea is that within this function, you build up $this->result with a useful representation of the XML data.  It could be an array structure, an object, or something else.
 * 
 * There are a couple of methods on the XML object that will help with 
 * 
 *   * **$this->inContext('(tag)', '(tag).(class)'):** This will return true if the current tag has the specified tags as ancestors, in the order that you've specified.
 * 
 * Finally, there are public methods that can be called on an instantiated XML subclass.  This is how you will make use of your new parser.
 * 
 *   * **$parser->tidyXHTML($content):** This will run "tidy -asxhtml" on your content.  This is useful if you're wanting to use the XML parser to parse HTML that may or may not be XML compliant.
 *   * **$parser->parse($content):** This will call the parser on the given XML content, and return the $this->result object that gets built.
 * 
 * <b>Example</b>
 * 
 * <code>
 * class DeliciousHtmlParser extends XML {
 * 	protected $currentItem = 0;
 * 	
 * 	function process_li($attributes) {
 * 		if($attributes['class'] == "post") {
 * 			$this->currentItem = sizeof($this->parsed);
 * 		}
 * 	}
 * 	
 * 	function process_a_end($content, $attributes) {
 * 		if($this->inContext('li.post','h4.desc')) {
 * 			$this->parsed[$this->currentItem][link] = $attributes[href];
 * 			$this->parsed[$this->currentItem][title] = $content;
 * 		
 * 		} else if($this->inContext('li.post','div.meta') && $attributes['class'] == 'tag') {
 * 			$this->parsed[$this->currentItem][tags][] = $content;
 * 		}
 * 	}
 * }
 * 
 * $html = file_get_contents("http://del.icio.us/$user/?setcount=100");
 * $parser = new DeliciousHtmlParser();
 * $tidyHtml = $parser->tidyXHTML($html);
 * $result = $parser->parse($tidyHtml);
 * </code>
 * 
 * @package sapphire
 * @subpackage misc
 */
class XML extends Object {
	protected $parser;
	protected $context, $attributeStore;	
	protected $parsed;	
	protected $collatedText;
	
	
	function tidyXHTML($content) {
		$cleanFile = TEMP_FOLDER . "/cleaner.tmp";
		$fh = fopen($cleanFile,"wb");
		fwrite($fh, $content);
		fclose($fh);
		
		if(file_exists($cleanFile)) {
			$result = `tidy -asxhtml $cleanFile`;
			unlink($cleanFile);
			return $result;
		}
	}
	
	function parse($content, $recursive = false) {
		$this->parser = xml_parser_create('UTF-8');
		
		// Andrew keeps giving me the wrong FSKING encoding! :-P
		$content = ereg_replace('encoding="[^"]+"','encoding="utf-8"', $content);
		
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		//xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
		
		$this->parsed = null;
		$this->context = array();
		$this->attributeStore = array();
		
		xml_parse($this->parser, $content);
		
		// Handle a bad encoding type by forcing ISO-8859-15
		if(xml_get_error_code($this->parser) == 32 && !$recursive) {
			$content = ereg_replace('encoding="[^"]+"','encoding="utf-8"', $content);
			return $this->parse($content, true);
		}
		
		if($err = xml_get_error_code($this->parser)) {
			user_error("XML parser broke with error $err:" . xml_error_string($err), E_USER_ERROR);
		}

		return $this->parsed;
	}

	function inContext() {
		$items = func_get_args();
		$i=0;
		foreach($items as $item) {
			while($i < sizeof($this->context)) {
				if($this->context[$i] == $item) break;
				$i++;
			}
			if($this->context[$i] != $item) return false;
		}
		return true;
	}	
	
	function stackActionFor($tag) {
		for($i=sizeof($this->contextStack)-1;$i>=0;$i--) {
			if($this->context[$i]['tag'] == $tag) return $this->contextStack[$i]['action'];
		}
	}
	
	function tag_open($parser, $tag, $attributes) {
		// Strip namespaces out of tags and attributes
		$tag = ereg_replace('[^:]+:','',$tag);
		if($attributes) foreach($attributes as $k => $v) $newAttributes[ereg_replace('[^:]+:','',$k)] = $v;
		$attributes = isset($newAttributes) ? $newAttributes : $attributes;
		
		
		if(isset($attributes['class'])) {
			$this->context[] = "$tag.{$attributes['class']}";
		} else {
			$this->context[] = $tag;
		}
		$this->attributeStore[] = $attributes;
		
		$this->collatedText = "";

		$tagProcessorFunc = "process_$tag";
		if($this->hasMethod($tagProcessorFunc)) {
			$this->$tagProcessorFunc($attributes);
		}elseif($this->hasMethod($tagProcessorFunc = "process_tag")){
			$this->$tagProcessorFunc($tag, $attributes);	
		}
		
		
		if($attributes) foreach($attributes as $k => $v) {
			$attProcessorFunc = "processatt_$k";
			if($this->hasMethod($attProcessorFunc)) {
				$this->$attProcessorFunc($tag, $attributes);
			}
		}
	}

	function tag_close($parser, $tag) {
		$tag = ereg_replace('[^:]+:','',$tag);

		array_pop($this->context);
		$attributes = array_pop($this->attributeStore);
		
		if(method_exists($this, $funcName = "process_{$tag}_end")) {
			$this->$funcName($this->collatedText, $attributes);
		}elseif(method_exists($this,$funcName = "process_tag_end")){ 
			// else run default method
				$this->$funcName($tag,$this->collatedText, $attributes);	
		}
		
		$this->collatedText = "";
	}
	
	function cdata($parser, $cdata) {
		$this->collatedText .= $cdata;
	}
}