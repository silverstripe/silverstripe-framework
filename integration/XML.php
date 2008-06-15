<?php
/**
 * Base class for XML parsers
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
		$fh = fopen($cleanFile,"w");
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