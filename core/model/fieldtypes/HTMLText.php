<?php
/**
 * Represents a large text field that contains HTML content.
 * 
 * This behaves similarly to Text, but the template processor won't escape any HTML content within it.
 * @package sapphire
 * @subpackage model
 */
class HTMLText extends Text {

	/**
	 * Limit this field's content by a number of characters.
	 * This makes use of strip_tags() to avoid malforming the
	 * HTML tags in the string of text.
	 *
	 * @param int $limit Number of characters to limit by
	 * @param string $add Ellipsis to add to the end of truncated string
	 * @return string
	 */
	function LimitCharacters($limit = 20, $add = "...") {
		$value = trim(strip_tags($this->value));
		return (strlen($value) > $limit) ? substr($value, 0, $limit) . $add : $value;
	}

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
	 * @see sapphire/core/model/fieldtypes/Text#Summary($maxWords)
	 */
	public function Summary($maxWords = 50, $flex = 15, $add = '...') {
		$str = false;

		/* First we need the text of the first paragraph, without tags. Try using SimpleXML first */
		if (class_exists('SimpleXMLElement')) {
			$doc = new DOMDocument();
			$doc->strictErrorChecking = FALSE;
			if ($doc->loadHTML('<meta content="text/html; charset=utf-8" http-equiv="Content-type"/>' . $this->value)) {
				$xml = simplexml_import_dom($doc);
				$res = $xml->xpath('//p');
				if (!empty($res)) $str = strip_tags($res[0]->asXML());
			}
		}
		
		if (!$str) {
			/* If that failed, use a simple regex + a strip_tags. We look for the first paragraph with some words in it, not just the first paragraph. 
			 * Not as good on broken HTML, and doesn't understand escaping or cdata blocks, but will probably work on even very malformed HTML */
			if (preg_match('{<p[^>]*>(.*[A-Za-z]+.*)</p>}', $this->value, $matches)) {
				$str = strip_tags($matches[1]);
			}
			/* If _that_ failed, just use the whole text with strip_tags */
			else {
				$str = strip_tags($this->value);
			}
		}
		
		/* Now split into words. If we are under the maxWords limit, just return the whole string */
		$words = preg_split('/\s+/', $str);
		if ($maxWords == -1 || count($words) <= $maxWords) return $str;

		/* Otherwise work backwards for a looking for a sentence ending (we try to avoid abbreviations, but aren't very good at it) */
		for ($i = $maxWords; $i > $maxWords - $flex; $i--) {
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
	 * @see sapphire/core/model/fieldtypes/Text#FirstSentence()
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
	
	public function scaffoldFormField($title = null, $params = null) {
		return new HtmlEditorField($this->name, $title);
	}
	
	public function scaffoldSearchField($title = null) {
		return new TextField($this->name, $title);
	}

}

?>