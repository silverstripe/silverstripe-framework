<?php

/**
 * PEG Generator - A PEG Parser for PHP
 *
 * @author Hamish Friedlander / SilverStripe
 *
 * See README.md for documentation
 * 
 */

require 'PHPBuilder.php' ;

class Flags {
	function __construct( $parent = NULL ) {
		$this->parent = $parent ;
		$this->flags = array() ;
	}

	function __set( $k, $v ) {
		$this->flags[$k] = $v ;
		return $v ;
	}

	function __get( $k ) {
		if ( isset( $this->flags[$k] ) ) return $this->flags[$k] ;
		if ( isset( $this->parent ) ) return $this->parent->$k ;
		return NULL ;
	}
}

/**
 * PHPWriter contains several code generation snippets that are used both by the Token and the Rule compiler
 */
class PHPWriter {

	static $varid = 0 ;

	function varid() {
		return '_' . (self::$varid++) ;
	}

	function function_name( $str ) {
		$str = preg_replace( '/-/', '_', $str ) ;
		$str = preg_replace( '/\$/', 'DLR', $str ) ;
		$str = preg_replace( '/\*/', 'STR', $str ) ;
		$str = preg_replace( '/[^\w]+/', '', $str ) ;
		return $str ;
	}

	function save($id) {
		return PHPBuilder::build()
			->l(
			'$res'.$id.' = $result;',
			'$pos'.$id.' = $this->pos;'
			);
	}

	function restore( $id, $remove = FALSE ) {
		$code = PHPBuilder::build()
			->l(
			'$result = $res'.$id.';',
			'$this->pos = $pos'.$id.';'
			);

		if ( $remove ) $code->l(
			'unset( $res'.$id.' );',
			'unset( $pos'.$id.' );'
		);

		return $code ;
	}

	function match_fail_conditional( $on, $match = NULL, $fail = NULL ) {
		return PHPBuilder::build()
			->b( 'if (' . $on . ')',
				$match,
				'MATCH'
			)
			->b( 'else',
				$fail,
				'FAIL'
			);
	}
	
	function match_fail_block( $code ) {
		$id = $this->varid() ;

		return PHPBuilder::build()
			->l(
			'$'.$id.' = NULL;'
			)
			->b( 'do',
				$code->replace(array(
					'MBREAK' => '$'.$id.' = TRUE; break;',
					'FBREAK' => '$'.$id.' = FALSE; break;'
				))
			)
			->l(
			'while(0);'
			)
			->b( 'if( $'.$id.' === TRUE )', 'MATCH' )
			->b( 'if( $'.$id.' === FALSE)', 'FAIL'  )
		   ;
	}
}

/**
 * A Token is any portion of a match rule. Tokens are responsible for generating the code to match against them.
 *
 * This base class provides the compile() function, which handles the token modifiers ( ? * + & ! )
 *
 * Each child class should provide the function match_code() which will generate the code to match against that specific token type.
 * In that generated code they should include the lines MATCH or FAIL when a match or a decisive failure occurs. These will
 * be overwritten when they are injected into parent Tokens or Rules. There is no requirement on where MATCH and FAIL can occur.
 * They tokens are also responsible for storing and restoring state when nessecary to handle a non-decisive failure.
 *
 * @author hamish
 *
 */
abstract class Token extends PHPWriter {
	public $optional = FALSE ;
	public $zero_or_more = FALSE ;
	public $one_or_more = FALSE ;
	public $positive_lookahead = FALSE ;
	public $negative_lookahead = FALSE ;
	public $silent = FALSE ;

	public $tag = FALSE ;

	public $type ;
	public $value ;

	function __construct( $type, $value = NULL ) {
		$this->type = $type ;
		$this->value = $value ;
	}

	// abstract protected function match_code() ;

	function compile() {
		$code = $this->match_code() ;

		$id = $this->varid() ;

		if ( $this->optional ) {
			$code = PHPBuilder::build()
				->l(
				$this->save($id),
				$code->replace( array( 'FAIL' => $this->restore($id,true) ))
				);
		}

		if ( $this->zero_or_more ) {
			$code = PHPBuilder::build()
				->b( 'while (true)',
					$this->save($id),
					$code->replace( array(
						'MATCH' => NULL,
						'FAIL' =>
							$this->restore($id,true)
							->l( 'break;' )
					))
				)
				->l(
				'MATCH'
				);
		}

		if ( $this->one_or_more ) {
			$code = PHPBuilder::build()
				->l(
				'$count = 0;'
				)
				->b( 'while (true)',
					$this->save($id),
					$code->replace( array(
						'MATCH' => NULL,
						'FAIL' =>
							$this->restore($id,true)
							->l( 'break;' )
					)),
					'$count += 1;'
				)
				->b( 'if ($count > 0)', 'MATCH' )
				->b( 'else',            'FAIL' );
		}

		if ( $this->positive_lookahead ) {
			$code = PHPBuilder::build()
				->l(
				$this->save($id),
				$code->replace( array(
					'MATCH' =>
						$this->restore($id)
						->l( 'MATCH' ),
					'FAIL' =>
						$this->restore($id)
						->l( 'FAIL' )
				)));
		}

		if ( $this->negative_lookahead ) {
			$code = PHPBuilder::build()
				->l(
				$this->save($id),
				$code->replace( array(
					'MATCH' =>
						$this->restore($id)
						->l( 'FAIL' ),
					'FAIL' =>
						$this->restore($id)
						->l( 'MATCH' )
				)));
		}

		if ( $this->tag && !($this instanceof TokenRecurse ) ) {
			$rid = $this->varid() ;
			$code = PHPBuilder::build()
				->l(
				'$substack[] = $result;',
				'$result = $this->construct( "'.$this->tag.'" );',
				$code->replace(array(
					'MATCH' => PHPBuilder::build()
						->l(
						'$subres = $result ;',
						'$result = array_pop( $substack ) ;',
						'$this->store( $result, $subres, \''.$this->tag.'\' );',
						'MATCH'
						),
					'FAIL' => PHPBuilder::build()
						->l(
						'$result = array_pop( $substack ) ;',
						'FAIL'
						)
				)));
		}

		return $code ;
	}
	
}

abstract class TokenTerminal extends Token {
	function set_text( $text ) {
		return $this->silent ? NULL : '$result["text"] .= ' . $text . ';';
	}
		
	protected function match_code( $value ) {
		return $this->match_fail_conditional( '( $subres = $this->'.$this->type.'( '.$value.' ) ) !== FALSE', 
			$this->set_text('$subres')
		);
	}
}

abstract class TokenExpressionable extends TokenTerminal {

	static $expression_rx = '/\$(\w+)/' ;

	function contains_expression(){
		return preg_match(self::$expression_rx, $this->value);
	}

	function match_code( $value ) {
		if (!$this->contains_expression()) parent::match_code($value);
		
		$id = $this->varid() ;
		return PHPBuilder::build()->l(
			'$'.$id.' = new ParserExpression( $this, $substack, $result );',
			parent::match_code('$'.$id.'->expand('.$value.')')
		);
	}
}

class TokenLiteral extends TokenExpressionable {
	function __construct( $value ) {
		parent::__construct( 'literal', $value );
	}

	function match_code() {
		// We inline single-character matches for speed
		if ( strlen( eval( 'return '.  $this->value . ';' ) ) == 1 ) {
			return $this->match_fail_conditional( 'substr($this->string,$this->pos,1) == '.$this->value, 
				PHPBuilder::build()->l(
					'$this->pos += 1;',
					$this->set_text( $this->value )
				)
			);
		}
		return parent::match_code($this->value);
	}
}

class TokenRegex extends TokenExpressionable {
	static function escape( $rx ) {
		$rx = str_replace( "'", "\\'", $rx ) ;
		$rx = str_replace( '\\\\', '\\\\\\\\', $rx ) ;
		return $rx ;
	}
	
	function __construct( $value ) {
		parent::__construct('rx', self::escape($value));
	}

	function match_code() {
		return parent::match_code("'{$this->value}'");
	}
}

class TokenWhitespace extends TokenTerminal {
	function __construct( $optional ) {
		parent::__construct( 'whitespace', $optional ) ;
	}

	/* Call recursion indirectly */
	function match_code() {
		$code = parent::match_code( '' ) ;
		return $this->value ? $code->replace( array( 'FAIL' => NULL )) : $code ;
	}
}

class TokenPHP extends TokenTerminal {
	function __construct( $value ) {
		parent::__construct( 'php', $value ) ;
	}

	/* Call recursion indirectly */
	function match_code() {
		$id = $this->varid() ;
		return PHPBuilder::build()
			->l(
			'$'.$id.' = new ParserExpression( $this, $substack, $result );',
			$this->match_fail_block( '( $subres = $'.$id.'->match( \''.$this->value.'\' ) ) !== FALSE', 
				PHPBuilder::build()
					->b( 'if ( is_string( $subres ) )',
						$this->set_text('$subres')
					)
					->b( 'else',
						'$this->store($result, $subres);'
					)
			));
	}
}

class TokenRecurse extends Token {
	function __construct( $value ) {
		parent::__construct( 'recurse', $value ) ;
	}

	function match_code() {
		$function = $this->function_name( $this->value ) ;
		$storetag = $this->function_name( $this->tag ? $this->tag : $this->value ) ;

		if ( ParserCompiler::$debug ) {
			$debug_header = PHPBuilder::build()
				->l(
				'$indent = str_repeat( " ", $this->depth );',
				'$this->depth += 2;',
				'$sub = ( strlen( $this->string ) - $this->pos > 20 ) ? ( substr( $this->string, $this->pos, 20 ) . "..." ) : substr( $this->string, $this->pos );',
				'$sub = preg_replace( \'/(\r|\n)+/\', " {NL} ", $sub );',
				'print( $indent."Matching against '.$function.' (".$sub.")\n" );'
				);

			$debug_match = PHPBuilder::build()
				->l(
				'print( $indent."MATCH\n" );',
				'$this->depth -= 2;'
				);

			$debug_fail = PHPBuilder::build()
				->l(
				'print( $indent."FAIL\n" );',
				'$this->depth -= 2;'
				);
		}
		else {
			$debug_header = $debug_match = $debug_fail = NULL ;
		}

		return PHPBuilder::build()->l(
			$debug_header,
			'$key = "'.$function.'"; $pos = $this->pos;', // :{$this->pos}";',
			'$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_'.$function.'(array_merge($substack, array($result))) ) );',
			$this->match_fail_conditional( '$subres !== FALSE',
				PHPBuilder::build()->l(
					$debug_match,
					$this->tag === FALSE ?
						'$this->store( $result, $subres );' :
						'$this->store( $result, $subres, "'.$storetag.'" );'
				),
				PHPBuilder::build()->l(
					$debug_fail
				)
			));
	}
}

class TokenSequence extends Token {
	function __construct( $value ) {
		parent::__construct( 'sequence', $value ) ;
	}

	function match_code() {
		$code = PHPBuilder::build() ;
		foreach( $this->value as $token ) {
			$code->l(
				$token->compile()->replace(array(
					'MATCH' => NULL,
					'FAIL' => 'FBREAK'
				))
			);
		}
		$code->l( 'MBREAK' );

		return $this->match_fail_block( $code ) ;
	}
}

class TokenOption extends Token {
	function __construct( $opt1, $opt2 ) {
		parent::__construct( 'option', array( $opt1, $opt2 ) ) ;
	}

	function match_code() {
		$id = $this->varid() ;
		$code = PHPBuilder::build()
			->l(
			$this->save($id)
			) ;

		foreach ( $this->value as $opt ) {
			$code->l(
				$opt->compile()->replace(array(
					'MATCH' => 'MBREAK',
					'FAIL' => NULL
				)),
				$this->restore($id)
			);
		}
		$code->l( 'FBREAK' ) ;

		return $this->match_fail_block( $code ) ;
	}
}


/**
 * Handles storing of information for an expression that applys to the <i>next</i> token, and deletion of that
 * information after applying
 *
 * @author Hamish Friedlander
 */
class Pending {
	function __construct() {
		$this->what = NULL ;
	}

	function set( $what, $val = TRUE ) {
		$this->what = $what ;
		$this->val = $val ;
	}

	function apply_if_present( $on ) {
		if ( $this->what !== NULL ) {
			$what = $this->what ;
			$on->$what = $this->val ;

			$this->what = NULL ;
		}
	}
}

/**
 * Rule parsing and code generation
 *
 * A rule is the basic unit of a PEG. This parses one rule, and generates a function that will match on a string
 *
 * @author Hamish Friedlander
 */
class Rule extends PHPWriter {

	static $rule_rx = '@^[\x20\t]+(.*)@' ;
	static $func_rx = '@^[\x20\t]+function\s+([^\s(]+)\s*\(([^)]*)\)@' ;

	function __construct( $indent, $rules, $match ) {
		$this->indent = $indent;
		$this->name = $match[1][0] ;
		$this->rule = $match[2][0] ;
		$this->functions = array() ;

		$active_function = NULL ;

		/* Find all the lines following the rule start which are indented */
		$offset = $match[0][1] + strlen( $match[0][0] ) ;
		$lines = preg_split( '/\r\n|\r|\n/', substr( $rules, $offset ) ) ;

		$rule_rx = '@^'.preg_quote($indent).'[\x20\t]+(.*)@' ;
		$func_rx = '@^'.preg_quote($indent).'[\x20\t]+function\s+([^\s(]+)\s*\(([^)]*)\)@' ;
		
		foreach( $lines as $line ) {
			if ( !trim( $line ) ) continue ;
			if ( !preg_match( $rule_rx, $line, $match ) ) break ;

			/* Handle function definitions */
			if ( preg_match( $func_rx, $line, $func_match, 0 ) ) {
				$active_function = $func_match[1] ;
				$this->functions[$active_function] = array( $func_match[2], "" ) ;
			}
			else {
				if ( $active_function ) $this->functions[$active_function][1] .= $line . PHP_EOL ;
				else                    $this->rule .= PHP_EOL . trim($line) ;
			}
		}

		$this->parse_rule() ;
	}

	/* Manual parsing, because we can't bootstrap ourselves yet */
	function parse_rule() {
		$rule = trim( $this->rule ) ;

		/* If this is a regex end-token, just mark it and return */
		if ( substr( $rule, 0, 1 ) == '/' ) {
			$this->parsed = new TokenRegex( $rule ) ;
		}
		else {
			$tokens = array() ;
			$this->tokenize( $rule, $tokens ) ;
			$this->parsed = ( count( $tokens ) == 1 ? array_pop( $tokens ) : new TokenSequence( $tokens ) ) ;
		}
	}

	static $rx_rx = '{^/(
		((\\\\\\\\)*\\\\/) # Escaped \/, making sure to catch all the \\ first, so that we dont think \\/ is an escaped /
		|
		[^/]               # Anything except /
	)*/}xu' ;

	function tokenize( $str, &$tokens, $o = 0 ) {

		$pending = new Pending() ;

		while ( $o < strlen( $str ) ) {
			$sub = substr( $str, $o ) ;

			/* Absorb white-space */
			if ( preg_match( '/^\s+/', $sub, $match ) ) {
				$o += strlen( $match[0] ) ;
			}
			/* Handle expression labels */
			elseif ( preg_match( '/^(\w*):/', $sub, $match ) ) {
				$pending->set( 'tag', isset( $match[1] ) ? $match[1] : '' ) ;
				$o += strlen( $match[0] ) ;
			}
			/* Handle descent token */
			elseif ( preg_match( '/^[\w-]+/', $sub, $match ) ) {
				$tokens[] = $t = new TokenRecurse( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ) ;
			}
			/* Handle " quoted literals */
			elseif ( preg_match( '/^"[^"]*"/', $sub, $match ) ) {
				$tokens[] = $t = new TokenLiteral( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ) ;
			}
			/* Handle ' quoted literals */
			elseif ( preg_match( "/^'[^']*'/", $sub, $match ) ) {
				$tokens[] = $t = new TokenLiteral( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ) ;
			}
			/* Handle regexs */
			elseif ( preg_match( self::$rx_rx, $sub, $match ) ) {
				$tokens[] = $t = new TokenRegex( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ) ;
			}
			/* Handle $ call literals */
			elseif ( preg_match( '/^\$(\w+)/', $sub, $match ) ) {
				$tokens[] = $t = new TokenPHP( $match[1] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ) ;
			}
			/* Handle flags */
			elseif ( preg_match( '/^\@(\w+)/', $sub, $match ) ) {
				$l = count( $tokens ) - 1 ;
				$o += strlen( $match[0] ) ;
				user_error( "TODO: Flags not currently supported", E_USER_WARNING ) ;
			}
			/* Handle control tokens */
			else {
				$c = substr( $sub, 0, 1 ) ;
				$l = count( $tokens ) - 1 ;
				$o += 1 ;
				switch( $c ) {
					case '?':
						$tokens[$l]->optional = TRUE ;
						break ;
					case '*':
						$tokens[$l]->zero_or_more = TRUE ;
						break ;
					case '+':
						$tokens[$l]->one_or_more = TRUE ;
						break ;

					case '&':
						$pending->set( 'positive_lookahead' ) ;
						break ;
					case '!':
						$pending->set( 'negative_lookahead' ) ;
						break ;
						
					case '.':
						$pending->set( 'silent' );
						break;

					case '[':
					case ']':
						$tokens[] = new TokenWhitespace( FALSE ) ;
						break ;
					case '<':
					case '>':
						$tokens[] = new TokenWhitespace( TRUE ) ;
						break ;

					case '(':
						$subtokens = array() ;
						$o = $this->tokenize( $str, $subtokens, $o ) ;
						$tokens[] = $t = new TokenSequence( $subtokens ) ; $pending->apply_if_present( $t ) ;
						break ;
					case ')':
						return $o ;

					case '|':
						$option1 = $tokens ;
						$option2 = array() ;
						$o = $this->tokenize( $str, $option2, $o ) ;

						$option1 = (count($option1) == 1) ? $option1[0] : new TokenSequence( $option1 );
						$option2 = (count($option2) == 1) ? $option2[0] : new TokenSequence( $option2 );

						$pending->apply_if_present( $option2 ) ;

						$tokens = array( new TokenOption( $option1, $option2 ) ) ;
						return $o ;

					default:
						user_error( "Can't parser $c - attempting to skip", E_USER_WARNING ) ;
				}
			}
		}

		return $o ;
	}

	/**
	 * Generate the PHP code for a function to match against a string for this rule
	 */
	function compile() {
		$function_name = $this->function_name( $this->name ) ;

		$match = PHPBuilder::build() ;

		if ( $this->parsed instanceof TokenRegex ) {
			$match->b( "function match_{$function_name} (\$substack = array())",
				'$result = array("name"=>"'.$function_name.'", "text"=>"");',
				$this->parsed->compile()->replace(array(
					'MATCH' => 'return $result;',
					'FAIL' => 'return FALSE;'
				))
			);
		}
		else {
			$match->b( "function match_{$function_name} (\$substack = array())",
				'$result = $this->construct( "'.$function_name.'" );',
				$this->parsed->compile()->replace(array(
					'MATCH' => 'return $this->finalise( "'.$function_name.'", $result );',
					'FAIL' => 'return FALSE;'
				))
			);
		}

		$functions = array() ;
		foreach( $this->functions as $name => $function ) {
			$function_name = $this->function_name( preg_match( '/^_/', $name ) ? $this->name.$name : $this->name.'_'.$name ) ;
			$functions[] = implode( PHP_EOL, array(
				'function ' . $function_name . ' ( ' . $function[0] . ' ) { ',
				$function[1],
			));
		}

		// print_r( $match ) ; return '' ;
		return $match->render(NULL, $this->indent) . PHP_EOL . PHP_EOL . implode( PHP_EOL, $functions ) ;
	}
}

class ParserCompiler {

	static $debug = false;

	static $currentClass = null;

	static function create_parser( $match ) {
		/* We allow indenting of the whole rule block, but only to the level of the comment start's indent */
		$indent = $match[1];
		
		/* The regex to match a rule */
		$rx = '@^'.preg_quote($indent).'([\w\-]+):(.*)$@m' ;

		/* Class isn't actually used ATM. Eventually it might be used for rule inlineing optimization */
		if     ($class = trim($match[2])) self::$currentClass = $class;
		elseif (self::$currentClass)      $class = self::$currentClass;
		else                              $class = self::$currentClass = 'Anonymous Parser';
		
		/* Get the actual body of the parser rule set */
		$rulestr = $match[3] ;
		
		/* Check for pragmas */
		if (strpos($class, '!') === 0) {
			switch ($class) {
				case '!silent':
					// NOP - dont output
					return '';
				case '!insert_autogen_warning':
					return $ident . implode(PHP_EOL.$ident, array(
						'/*',
						'WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.',
						'*/'
					)) . PHP_EOL;
				case '!debug':
					self::$debug = true;
					return '';
			}
			
			throw new Exception("Unknown pragma $class encountered when compiling parser");
		}
		
		$rules = array();

		preg_match_all( $rx, $rulestr, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ;
		foreach ( $matches as $match ) {
			$rules[] = new Rule( $indent, $rulestr, $match ) ;
		}

		$out = array() ;

		foreach ( $rules as $rule ) {
			$out[] = $indent . '/* ' . $rule->name . ':' . $rule->rule . ' */' . PHP_EOL ;
			$out[] = $rule->compile() ;
			$out[] = PHP_EOL ;
		}

		return implode( '', $out ) ;
	}

	static function compile( $string ) {
		static $rx = '@
			^([\x20\t]*)/\*!\* (?:[\x20\t]*(!?\w*))?   # Start with some indent, a comment with the special marker, then an optional name
			((?:[^*]|\*[^/])*)                         # Any amount of "a character that isnt a star, or a star not followed by a /
			\*/                                        # The comment end
		@mx';

		return preg_replace_callback( $rx, array( 'ParserCompiler', 'create_parser' ), $string ) ;
	}

	static function cli( $args ) {
		if ( count( $args ) == 1 ) {
			print "Parser Compiler: A compiler for PEG parsers in PHP \n" ;
			print "(C) 2009 SilverStripe. See COPYING for redistribution rights. \n" ;
			print "\n" ;
			print "Usage: {$args[0]} infile [ outfile ]\n" ;
			print "\n" ;
		}
		else {
			$fname = ( $args[1] == '-' ? 'php://stdin' : $args[1] ) ;
			$string = file_get_contents( $fname ) ;
			$string = self::compile( $string ) ;

			if ( !empty( $args[2] ) && $args[2] != '-' ) {
				file_put_contents( $args[2], $string ) ;
			}
			else {
				print $string ;
			}
		}
	}
}
