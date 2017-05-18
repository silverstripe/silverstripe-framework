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

	private static $casting = array(
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
	);

	/**
 	 * (non-PHPdoc)
 	 * @see DBField::requireField()
 	 */
	public function requireField() {
		$charset = Config::inst()->get('MySQLDatabase', 'charset');
		$collation = Config::inst()->get('MySQLDatabase', 'collation');

		$parts = array(
			'datatype' => 'mediumtext',
			'character set' => $charset,
			'collate' => $collation,
			'arrayValue' => $this->arrayValue
		);

		$values= array(
			'type' => 'text',
			'parts' => $parts
		);

		DB::require_field($this->tableName, $this->name, $values, $this->default);
	}

	/**
	 * Return the value of the field with relative links converted to absolute urls.
	 * @return string
	 */
	public function AbsoluteLinks() {
		return HTTP::absoluteURLs($this->RAW());
	}

	/**
	 * Limit sentences, can be controlled by passing an integer.
	 *
	 * @param int $sentCount The amount of sentences you want.
	 */
	public function LimitSentences($sentCount = 2) {
		if(!is_numeric($sentCount)) {
			user_error("Text::LimitSentence() expects one numeric argument", E_USER_NOTICE);
		}

		$output = array();
		$data = trim(Convert::xml2raw($this->RAW()));
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
		$paragraph = Convert::xml2raw( $this->RAW() );
		if( !$paragraph ) return "";

		$words = preg_split('/\s+/', $paragraph);
		foreach ($words as $i => $word) {
			if (preg_match('/(!|\?|\.)$/', $word) && !preg_match('/(Dr|Mr|Mrs|Ms|Miss|Sr|Jr|No)\.$/i', $word)) {
				return implode(' ', array_slice($words, 0, $i+1));
			}
		}

		/* If we didn't find a sentence ending, use the summary. We re-call rather than using paragraph so that
		 * Summary will limit the result this time */
		return $this->Summary(20);
	}

	/**
	 * Caution: Not XML/HTML-safe - does not respect closing tags.
	 */
	public function Summary($maxWords = 50) {
		// get first sentence?
		// this needs to be more robust
		$value = Convert::xml2raw( $this->RAW() /*, true*/ );
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
	* Performs the same function as the big summary, but doesn't trim new paragraphs off data.
	* Caution: Not XML/HTML-safe - does not respect closing tags.
	*/
	public function BigSummary($maxWords = 50, $plain = true) {
		$result = '';

		// get first sentence?
		// this needs to be more robust
		$data = $plain ? Convert::xml2raw($this->RAW(), true) : $this->RAW();

		if(!$data) return '';

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
		$value = $this->RAW();
		if($plain && $plain != 'html') {
			$data = Convert::xml2raw($value, true);
			if(!$data) return "";

			// grab the first paragraph, or, failing that, the whole content
			$pos = strpos($data, "\n\n");
			if($pos) $data = substr($data, 0, $pos);

			return $data;
		} else {
			if(strpos($value, "</p>") === false) return $value;

			$data = substr($value, 0, strpos($value, "</p>") + 4);

			if(strlen($data) < 20 && strpos($value, "</p>", strlen($data))) {
				$data = substr($value, 0, strpos( $value, "</p>", strlen($data)) + 4 );
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

		if(!$string) {
			// Use the default "Search" request variable (from SearchForm)
			$string = isset($_REQUEST['Search']) ? $_REQUEST['Search'] : '';
		}

		// Remove HTML tags so we don't have to deal with matching tags
		$text = $striphtml ? $this->NoHTML() : $this->RAW();

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
						$summary = preg_replace('/' . preg_quote($stringPiece, '/') . '/i', '<span class="highlight">$0</span>', $summary);
					}
				}
			}
		}
		$summary = trim($summary);

		if($position > 0) $summary = $prefix . $summary;
		if(strlen($this->RAW()) > ($characters + $position)) $summary = $summary . $suffix;

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
			$obj = new $parser($this->RAW());
			return $obj->parse();
		} else {
			// Fallback to using raw2xml and show a warning
			// TODO Don't kill script execution, we can continue without losing complete control of the app
			user_error("Couldn't find an appropriate TextParser sub-class to create (Looked for '$parser')."
				. "Make sure it sub-classes TextParser and that you've done ?flush=1.", E_USER_WARNING);
			return Convert::raw2xml($this->RAW());
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
