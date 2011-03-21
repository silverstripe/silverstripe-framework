<?php

require '../Parser.php' ;

class EqualRepeat extends Packrat {
/* Any number of a followed by the same number of b and the same number of c characters
 * aabbcc - good
 * aaabbbccc - good
 * aabbc - bad
 * aabbacc - bad
 */

/*!* Grammar1
A: "a" A? "b"
B: "b" B? "c"
T: !"b"
X: &(A !"b") "a"+ B !("a" | "b" | "c")
*/
}

function match( $str ) {
	$p = new EqualRepeat( $str ) ;
	$r = $p->match_X() ;
	print "$str\n" ;
	print $r ? print_r( $r, true ) : 'No Match' ;
	print "\n\n" ;
}

match( 'aabbcc' )    ; // Should match
match( 'aaabbbccc' ) ; // Should match

match( 'aabbbccc' ) ; // Should not match
match( 'aaabbccc' ) ; // Should not match
match( 'aaabbbcc' ) ; // Should not match

match( 'aaabbbcccc' ) ; // Should not match
