<?php
/**
 * Represents a variable-length string of up to 2 megabytes, designed to store raw text
 * 
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 * 	"MyDescription" => "Text",
 * );
 * </code>
 * 
 * @see HTMLText
 * @see HTMLVarchar
 * @see Varchar
 * 
 * @package framework
 * @subpackage model
 */
class Text extends StringField {

	static $casting = array(
		"AbsoluteLinks" => "Text",
		"BigSummary" => "Text",
		"ContextSummary" => "Text",
		"FirstParagraph" => "Text",
		"FirstSentence" => "Text",
		"LimitCharacters" => "Text",
		"LimitSentences" => "Text",
		"Summary" => "Text",
		'EscapeXML' => 'Text',
		'LimitWordCount' => 'Text',
		'LimitWordCountXML' => 'HTMLText',
		'NoHTML' => 'Text',
	);
	
 	/**
 	 * (non-PHPdoc)
 	 * @see DBField::requireField()
 	 */
	public function requireField() {
		$parts=Array(
			'datatype'=>'mediumtext',
			'character set'=>'utf8',
			'collate'=>'utf8_general_ci',
			'arrayValue'=>$this->arrayValue
		);
		$values=Array('type'=>'text', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values, $this->default);
	}
	
	/**
	 * Limit this field's content by a number of words.
	 * CAUTION: This is not XML safe. Please use
	 * {@link LimitWordCountXML()} instead.
	 *
	 * @param int $numWords Number of words to limit by
	 * @param string $add Ellipsis to add to the end of truncated string
	 * @return string
	 */
	public function LimitWordCount($numWords = 26, $add = '...') {
		$this->value = trim(Convert::xml2raw($this->value));
		$ret = explode(' ', $this->value, $numWords + 1);
		
		if(count($ret) <= $numWords - 1) {
			$ret = $this->value;
		} else {
			array_pop($ret);
			$ret = implode(' ', $ret) . $add;
		}
		
		return $ret;
	}
	
	/**
	 * Return the value of the field stripped of html tags
	 * @return string
	 */
	public function NoHTML() {
		return strip_tags($this->value);
	}
	/**
	 * Return the value of the field with XML tags escaped.
	 * 
	 * @deprecated 3.0 Use DBField->XML() instead.
	 * @return string
	 */
	public function EscapeXML() {
		Deprecation::notice('3.0', 'Use DBField->XML() instead.');
		return str_replace(array('&','<','>','"'), array('&amp;','&lt;','&gt;','&quot;'), $this->value);
	}
	
	/**
	 * Return the value of the field with relative links converted to absolute urls.
	 * @return string
	 */
	public function AbsoluteLinks() {
		return HTTP::absoluteURLs($this->value);
	}
	
	/**
	 * Limit the number of words of the current field's
	 * content. This is XML safe, so characters like &
	 * are converted to &amp;
	 *
	 * @param int $numWords Number of words to limit by
	 * @param string $add Ellipsis to add to the end of truncated string
	 * @return string
	 */
	public function LimitWordCountXML($numWords = 26, $add = '...') {
		$ret = $this->LimitWordCount($numWords, $add);
		return Convert::raw2xml($ret);
	}

	/**
	 * Limit sentences, can be controlled by passing an integer.
	 * @param int $sentCount The amount of sentences you want.
	 */
	public function LimitSentences($sentCount = 2) {
		if(!is_numeric($sentCount)) user_error("Text::LimitSentence() expects one numeric argument", E_USER_NOTICE);
		
		$output = array();
		$data = trim(Convert::xml2raw($this->value));
		$sentences = explode('.', $data);

		if ($sentCount == 0) return '';

		for($i = 0; $i < $sentCount; $i++) {
			if(isset($sentences[$i])) {
				$sentence = trim($sentences[$i]);
				if(!empty($sentence)) $output[] .= $sentence;		
			}
		}

		return count($output)==0 ? '' : implode($output, '. ') . '.';				
	}
	
	
	/**
	 * Caution: Not XML/HTML-safe - does not respect closing tags.
	 */
	public function FirstSentence() {
		$data = Convert::xml2raw( $this->value );
		if( !$data ) return "";
		
		
		$sentences = explode( '.', $data );
		
		if( count( $sentences ) )
			return $sentences[0] . '.';
		else
			return $this->Summary(20);
	}	

	/**
	 * Caution: Not XML/HTML-safe - does not respect closing tags.
	 */
	public function Summary($maxWords = 50) {
		// get first sentence?
		// this needs to be more robust
		$value = Convert::xml2raw( $this->value /*, true*/ );
		if(!$value) return '';
		
		// grab the first paragraph, or, failing that, the whole content
		if(strpos($value, "\n\n")) $value = substr($value, 0, strpos($value, "\n\n"));
		$sentences = explode('.', $value);	
		$count = count(explode(' ', $sentences[0]));
		
		// if the first sentence is too long, show only the first $maxWords words
		if($count > $maxWords) {
			return implode( ' ', array_slice(explode( ' ', $sentences[0] ), 0, $maxWords)) . '...';
		}

		// add each sentence while there are enough words to do so
		$result = '';
		do {
			$result .= trim(array_shift( $sentences )).'.';
			if(count($sentences) > 0) {
				$count += count(explode(' ', $sentences[0]));
			}
			
			// Ensure that we don't trim half way through a tag or a link
			$brokenLink = (
				substr_count($result,'<') != substr_count($result,'>')) ||
				(substr_count($result,'<a') != substr_count($result,'</a')
			);
		} while(($count < $maxWords || $brokenLink) && $sentences && trim( $sentences[0]));
		
		if(preg_match('/<a[^>]*>/', $result) && !preg_match( '/<\/a>/', $result)) $result .= '</a>';
		
		return Convert::raw2xml($result);
	}
	
	/**
	* Performs the same function as the big summary, but doesnt trim new paragraphs off data.
	* Caution: Not XML/HTML-safe - does not respect closing tags.
	*/
	public function BigSummary($maxWords = 50, $plain = 1) {
		$result = "";
		
		// get first sentence?
		// this needs to be more robust
		if($plain) $data = Convert::xml2raw($this->value, true);
		
		if(!$data) return "";
			
		$sentences = explode('.', $data);	
		$count = count(explode(' ', $sentences[0]));
		
		// if the first sentence is too long, show only the first $maxWords words
		if($count > $maxWords) {
			return implode(' ', array_slice(explode( ' ', $sentences[0] ), 0, $maxWords)) . '...';
		}

		// add each sentence while there are enough words to do so
		do {
			$result .= trim(array_shift($sentences));
			if($sentences) {
				$result .= '. ';
				$count += count(explode(' ', $sentences[0]));
			}
			
			// Ensure that we don't trim half way through a tag or a link
			$brokenLink = (
				substr_count($result,'<') != substr_count($result,'>')) ||
				(substr_count($result,'<a') != substr_count($result,'</a')
			);
		} while(($count < $maxWords || $brokenLink) && $sentences && trim($sentences[0]));
		
		if(preg_match( '/<a[^>]*>/', $result) && !preg_match( '/<\/a>/', $result)) {
			$result .= '</a>';
		}
		
		return $result;
	}
	
	/**
	 * Caution: Not XML/HTML-safe - does not respect closing tags.
	 */
	public function FirstParagraph($plain = 1) {
		// get first sentence?
		// this needs to be more robust
		if($plain && $plain != 'html') {
			$data = Convert::xml2raw($this->value, true);
			if(!$data) return "";
		
			// grab the first paragraph, or, failing that, the whole content
			$pos = strpos($data, "\n\n");
			if($pos) $data = substr($data, 0, $pos);

			return $data;
		} else {
			if(strpos($this->value, "</p>") === false) return $this->value;
			
			$data = substr($this->value, 0, strpos($this->value, "</p>") + 4);

			if(strlen($data) < 20 && strpos($this->value, "</p>", strlen($data))) {
				$data = substr($this->value, 0, strpos( $this->value, "</p>", strlen($data)) + 4 );
			}
			
			return $data;			
		}
	}
	
	/**
	 * Perform context searching to give some context to searches, optionally
	 * highlighting the search term.
	 * 
	 * @param int $characters Number of characters in the summary
	 * @param boolean $string Supplied string ("keywords")
	 * @param boolean $striphtml Strip HTML?
	 * @param boolean $highlight Add a highlight <span> element around search query?
	 * @param String prefix text
	 * @param String suffix 
	 * 
	 * @return string
	 */
	public function ContextSummary($characters = 500, $string = false, $striphtml = true, $highlight = true,
			$prefix = "... ", $suffix = "...") {

		if(!$string) $string = $_REQUEST['Search'];	// Use the default "Search" request variable (from SearchForm)

		// Remove HTML tags so we don't have to deal with matching tags
		$text = $striphtml ? $this->NoHTML() : $this->value;
		
		// Find the search string
		$position = (int) stripos($text, $string);
		
		// We want to search string to be in the middle of our block to give it some context
		$position = max(0, $position - ($characters / 2));

		if($position > 0) {
			// We don't want to start mid-word
			$position = max((int) strrpos(substr($text, 0, $position), ' '),
				(int) strrpos(substr($text, 0, $position), "\n"));
		}

		$summary = substr($text, $position, $characters);
		$stringPieces = explode(' ', $string);
		
		if($highlight) {
			// Add a span around all key words from the search term as well
			if($stringPieces) {
			
				foreach($stringPieces as $stringPiece) {
					if(strlen($stringPiece) > 2) {
						$summary = str_ireplace($stringPiece, "<span class=\"highlight\">$stringPiece</span>",
							$summary);
					}
				}
			}
		}
		$summary = trim($summary);
		
		if($position > 0) $summary = $prefix . $summary;
		if(strlen($this->value) > ($characters + $position)) $summary = $summary . $suffix;
		
		return $summary;
	}
	
	/**
	 * Allows a sub-class of TextParser to be rendered.
	 * 
	 * @see TextParser for implementation details.
	 * @return string
	 */
	public function Parse($parser = "TextParser") {
		if($parser == "TextParser" || is_subclass_of($parser, "TextParser")) {
			$obj = new $parser($this->value);
			return $obj->parse();
		} else {
			// Fallback to using raw2xml and show a warning
			// TODO Don't kill script execution, we can continue without losing complete control of the app
			user_error("Couldn't find an appropriate TextParser sub-class to create (Looked for '$parser')."
				. "Make sure it sub-classes TextParser and that you've done ?flush=1.", E_USER_WARNING);
			return Convert::raw2xml($this->value);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DBField::scaffoldFormField()
	 */
	public function scaffoldFormField($title = null, $params = null) {
		if(!$this->nullifyEmpty) {
			// Allow the user to select if it's null instead of automatically assuming empty string is
			return new NullableField(new TextareaField($this->name, $title));
		} else {
			// Automatically determine null (empty string)
			return new TextareaField($this->name, $title);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DBField::scaffoldSearchField()
	 */
	public function scaffoldSearchField($title = null, $params = null) {
		return new TextField($this->name, $title);
	}
}


