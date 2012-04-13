<?php
/**
 * Represents a large text field that contains HTML content.
 * This behaves similarly to {@link Text}, but the template processor won't escape any HTML content within it.
 * 
 * @see HTMLVarchar
 * @see Text
 * @see Varchar
 * 
 * @package framework
 * @subpackage model
 */
class HTMLText extends Text {
	
	public static $escape_type = 'xml';

	static $casting = array(
		"AbsoluteLinks" => "HTMLText",
		"BigSummary" => "HTMLText",
		"ContextSummary" => "HTMLText",
		"FirstParagraph" => "HTMLText",
		"FirstSentence" => "HTMLText",
		"LimitCharacters" => "HTMLText",
		"LimitSentences" => "HTMLText",
		"Lower" => "HTMLText",
		"LowerCase" => "HTMLText",
		"Summary" => "HTMLText",
		"Upper" => "HTMLText",
		"UpperCase" => "HTMLText",
		'EscapeXML' => 'HTMLText',
		'LimitWordCount' => 'HTMLText',
		'LimitWordCountXML' => 'HTMLText',
		'NoHTML' => 'Text',
	);

	/**
	 * Create a summary of the content. This will be some section of the first paragraph, limited by
	 * $maxWords. All internal tags are stripped out - the return value is a string
	 * 
	 * This is sort of the HTML aware equivilent to Text#Summary, although the logic for summarising is not exactly the same
	 * 
	 * @param int $maxWords Maximum number of words to return - may return less, but never more. Pass -1 for no limit
	 * @param int $flex Number of words to search through when looking for a nice cut point 
	 * @param string $add What to add to the end of the summary if we cut at a less-than-ideal cut point
	 * @return string A nice(ish) summary with no html tags (but possibly still some html entities)
	 * 
	 * @see framework/core/model/fieldtypes/Text#Summary($maxWords)
	 */
	public function Summary($maxWords = 50, $flex = 15, $add = '...') {
		$str = false;

		/* First we need the text of the first paragraph, without tags. Try using SimpleXML first */
		if (class_exists('SimpleXMLElement')) {
			$doc = new DOMDocument();
			
			/* Catch warnings thrown by loadHTML and turn them into a failure boolean rather than a SilverStripe error */
			set_error_handler(create_function('$no, $str', 'throw new Exception("HTML Parse Error: ".$str);'), E_ALL);
			//  Nonbreaking spaces get converted into weird characters, so strip them
			$value = str_replace('&nbsp;', ' ', $this->value);
			try { $res = $doc->loadHTML('<meta content="text/html; charset=utf-8" http-equiv="Content-type"/>' . $value); }
			catch (Exception $e) { $res = false; }
			restore_error_handler();
			
			if ($res) {
				$xml = simplexml_import_dom($doc);
				$res = $xml->xpath('//p');
				if (!empty($res)) $str = strip_tags($res[0]->asXML());
			}
		}
		
		/* If that failed, most likely the passed HTML is broken. use a simple regex + a custom more brutal strip_tags. We don't use strip_tags because
		 * that does very badly on broken HTML*/
		if (!$str) {
			/* See if we can pull a paragraph out*/

			// Strip out any images in case there's one at the beginning. Not doing this will return a blank paragraph
			$str = preg_replace('{^\s*(<.+?>)*<img[^>]*>}', '', $this->value);
			if (preg_match('{<p(\s[^<>]*)?>(.*[A-Za-z]+.*)</p>}', $str, $matches)) $str = $matches[2];

			/* If _that_ failed, just use the whole text */
			if (!$str) $str = $this->value;
			
			/* Now pull out all the html-alike stuff */
			$str = preg_replace('{</?[a-zA-Z]+[^<>]*>}', '', $str); /* Take out anything that is obviously a tag */
			$str = preg_replace('{</|<|>}', '', $str); /* Strip out any left over looking bits. Textual < or > should already be encoded to &lt; or &gt; */
		}
		
		/* Now split into words. If we are under the maxWords limit, just return the whole string (re-implode for whitespace normalization) */
		$words = preg_split('/\s+/', $str);
		if ($maxWords == -1 || count($words) <= $maxWords) return implode(' ', $words);

		/* Otherwise work backwards for a looking for a sentence ending (we try to avoid abbreviations, but aren't very good at it) */
		for ($i = $maxWords; $i >= $maxWords - $flex && $i >= 0; $i--) {
			if (preg_match('/\.$/', $words[$i]) && !preg_match('/(Dr|Mr|Mrs|Ms|Miss|Sr|Jr|No)\.$/i', $words[$i])) {
				return implode(' ', array_slice($words, 0, $i+1));
			}
		}
		
		/* If we didn't find a sentence ending quickly enough, just cut at the maxWords point and add '...' to the end */
		return implode(' ', array_slice($words, 0, $maxWords)) . $add;
	}
	
	/**
	 * Returns the first sentence from the first paragraph. If it can't figure out what the first paragraph is (or there isn't one)
	 * it returns the same as Summary()
	 * 
	 * This is the HTML aware equivilent to Text#FirstSentence
	 * 
	 * @see framework/core/model/fieldtypes/Text#FirstSentence()
	 */
	function FirstSentence() {
		/* Use summary's html processing logic to get the first paragraph */
		$paragraph = $this->Summary(-1);
		
		/* Then look for the first sentence ending. We could probably use a nice regex, but for now this will do */
		$words = preg_split('/\s+/', $paragraph);
		foreach ($words as $i => $word) {
			if (preg_match('/\.$/', $word) && !preg_match('/(Dr|Mr|Mrs|Ms|Miss|Sr|Jr|No)\.$/i', $word)) {
				return implode(' ', array_slice($words, 0, $i+1));
			}
		}
		
		/* If we didn't find a sentence ending, use the summary. We re-call rather than using paragraph so that Summary will limit the result this time */
		return $this->Summary();
	}	
	
	public function forTemplate() {
		return ShortcodeParser::get_active()->parse($this->value);
	}
	
	public function exists() {
		return parent::exists() && $this->value != '<p></p>';
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new HtmlEditorField($this->name, $title);
	}
	
	public function scaffoldSearchField($title = null, $params = null) {
		return new TextField($this->name, $title);
	}

}


