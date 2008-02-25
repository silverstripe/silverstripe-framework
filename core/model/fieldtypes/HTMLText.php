<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a large text field that contains HTML content.
 * 
 * This behaves similarly to Text, but the template processor won't escape any HTML content within it.
 * @package sapphire
 * @subpackage model
 */
class HTMLText extends Text {

	/**
	 * Create a summary of the content. This will either be the first paragraph, or the first $maxWords 
	 * words, whichever is shorter
	 */
	public function Summary( $maxWords = 50 ) {
		// split the string into tags and words
		$parts = Convert::xml2array( $this->value );
		
		// store any unmatched tags
		$tagStack = array();
		
		$pIndex = 0;
		
		// find the first paragraph tag
		for( $i = 0; $i < count( $parts ); $i++ )
			if( strpos( $parts[$i], '<p' ) === 0 ) {
				$pIndex = $i;
				break;
			}
				
		$summary = '';
		$words = 0;
		
		// create the summary, keeping track of opening and closing tags
		while( $words <= $maxWords && $pIndex < count( $parts ) ) {
			if( $parts[$pIndex] == '</p>' ) {
				$summary .= $parts[$pIndex];
				break;
			}
			elseif( preg_match( '/<\/(\w+)>/', $parts[$pIndex], $endTag ) && $endTag[1] == substr( $tagStack[count($tagStack) - 1], 1, strlen( $endTag[1] ) ) ) {
				array_pop( $tagStack );
				$words++;
				$summary .= $parts[$pIndex++];
			} elseif( preg_match( '/^<\w+/', $parts[$pIndex] ) ) {
				array_push( $tagStack, $parts[$pIndex] );
				$words++;
				$summary .= $parts[$pIndex++];
			} else
				$summary .= $parts[$pIndex++] . ' ';
		}
		
		// Tags that shouldn't be closed
		$noClose = array("br", "img");
		
		// make sure that the summary is well formed XHTML by closing tags
		while( $openTag = array_pop( $tagStack ) ) {
			preg_match( '/^<(\w+)\s+/', $openTag, $tagName );
			if(sizeof($tagName) > 0) {
			    if(!in_array($tagName[1], $noClose)) {
					$summary .= "</{$tagName[1]}>";
			    }
			}
		}
		
		return $summary;
	}

}

?>