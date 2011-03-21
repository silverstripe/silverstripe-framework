<?php

require '../Parser.php' ;

class CalculatedLiterals extends Parser {

/*!* CalculatedLiterals

string: ( /\\./ | /[^${parent.q}]/ )*

simplequote: q:/['"]/ string '$q'

freequote-matched: "qq" q:/[{\[(<]/ string '$matched'
	function $matched( $res ) {
		$a = array( '{' => '}', '[' => ']', '(' => ')', '<' => '>' ) ;
		return $a[$res['q']] ;
	}

freequote-unmatched: "qq" q:/./ string '$q'

quoted-string: freequote-matched | freequote-unmatched | simplequote

*/

}
