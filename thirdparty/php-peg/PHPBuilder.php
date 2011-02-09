<?php

class PHPBuilder {

	static function build () {
		return new PHPBuilder() ;
	}

	function __construct() {
		$this->lines = array() ;
	}

	function l() {
		foreach ( func_get_args() as $lines ) {
			if ( !$lines ) continue ;

			if ( is_string( $lines ) ) $lines = preg_split( '/\r\n|\r|\n/', $lines ) ;
			if ( !$lines ) continue ;

			if ( $lines instanceof PHPBuilder ) $lines = $lines->lines ;
			else                                $lines = array_map( 'ltrim', $lines ) ;
			if ( !$lines ) continue ;

			$this->lines = array_merge( $this->lines, $lines ) ;
		}
		return $this ;
	}

	function b() {
		$args = func_get_args() ;
		$entry = array_shift( $args ) ;

		$block = new PHPBuilder() ;
		call_user_func_array( array( $block, 'l' ), $args ) ;

		$this->lines[] = array( $entry, $block->lines ) ;

		return $this ;
	}

	function replace( $replacements, &$array = NULL ) {
		if ( $array === NULL ) {
			unset( $array ) ;
			$array =& $this->lines ;
		}

		$i = 0 ;
		while ( $i < count( $array ) ) {

			/* Recurse into blocks */
			if ( is_array( $array[$i] ) ) {
				$this->replace( $replacements, $array[$i][1] ) ;

				if ( count( $array[$i][1] ) == 0 ) {
					$nextelse = isset( $array[$i+1] ) && is_array( $array[$i+1] ) && preg_match( '/^\s*else\s*$/i', $array[$i+1][0] ) ;

					$delete = preg_match( '/^\s*else\s*$/i', $array[$i][0] ) ;
					$delete = $delete || ( preg_match( '/^\s*if\s*\(/i', $array[$i][0] ) && !$nextelse ) ;

					if ( $delete ) {
						// Is this always safe? Not if the expression has side-effects.
						// print "/* REMOVING EMPTY BLOCK: " . $array[$i][0] . "*/\n" ;
						array_splice( $array, $i, 1 ) ;
						continue ;
					}
				}
			}

			/* Handle replacing lines with NULL to remove, or string, array of strings or PHPBuilder to replace */
			else {
				if ( array_key_exists( $array[$i], $replacements ) ) {
					$rep = $replacements[$array[$i]] ;

					if ( $rep === NULL ) {
						array_splice( $array, $i, 1 ) ;
						continue ;
					}

					if ( is_string( $rep ) ) {
						$array[$i] = $rep ;
						$i++  ;
						continue ;
					}

					if ( $rep instanceof PHPBuilder ) $rep = $rep->lines ;

					if ( is_array( $rep ) ) {
						array_splice( $array, $i, 1, $rep ) ; $i += count( $rep ) + 1 ;
						continue ;
					}

					throw 'Unknown type passed to PHPBuilder#replace' ;
				}
			}

			$i++ ;
		}

		return $this ;
	}

	function render( $array = NULL, $indent = "" ) {
		if ( $array === NULL ) $array = $this->lines ;

		$out = array() ;
		foreach( $array as $line ) {
			if ( is_array( $line ) ) {
				list( $entry, $block ) = $line ;
				$str = $this->render( $block, $indent . "\t" ) ;

				if ( strlen( $str ) < 40 ) {
					$out[] = $indent . $entry . ' { ' . ltrim( $str ) . ' }' ;
				}
				else {
					$out[] = $indent . $entry . ' {' ;
					$out[] = $str ;
					$out[] = $indent . '}' ;
				}
			}
			else {
				$out[] = $indent . $line ;
			}
		}

		return implode( PHP_EOL, $out ) ;
	}
}
