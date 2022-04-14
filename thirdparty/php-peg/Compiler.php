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
		$str = preg_replace( '/-/', '_', $str ?? '' ) ;
		$str = preg_replace( '/\$/', 'DLR', $str ?? '' ) ;
		$str = preg_replace( '/\*/', 'STR', $str ?? '' ) ;
		$str = preg_replace( '/[^\w]+/', '', $str ?? '' ) ;
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
		$code = $this->match_code($this->value) ;

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
			$code = PHPBuilder::build()
				->l(
				'$stack[] = $result; $result = $this->construct( $matchrule, "'.$this->tag.'" ); ',
				$code->replace(array(
					'MATCH' => PHPBuilder::build()
						->l(
						'$subres = $result; $result = array_pop($stack);',
						'$this->store( $result, $subres, \''.$this->tag.'\' );',
						'MATCH'
						),
					'FAIL' => PHPBuilder::build()
						->l(
						'$result = array_pop($stack);',
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

	static $expression_rx = '/ \$(\w+) | { \$(\w+) } /x';

	function contains_expression( $value ){
		return preg_match(self::$expression_rx, $value ?? '');
	}

	function expression_replace($matches) {
		return '\'.$this->expression($result, $stack, \'' . (!empty($matches[1]) ? $matches[1] : $matches[2]) . "').'";
	}
	
	function match_code( $value ) {
		$value = preg_replace_callback(self::$expression_rx, array($this, 'expression_replace'), $value ?? '');
		return parent::match_code($value);
	}
}

class TokenLiteral extends TokenExpressionable {
	function __construct( $value ) {
		parent::__construct( 'literal', "'" . substr($value ?? '',1,-1) . "'" );
	}

	function match_code( $value ) {
		// We inline single-character matches for speed
		if ( !$this->contains_expression($value) && strlen( eval( 'return '. $value . ';' ) ) == 1 ) {
			return $this->match_fail_conditional( 'substr($this->string,$this->pos,1) == '.$value,
				PHPBuilder::build()->l(
					'$this->pos += 1;',
					$this->set_text($value)
				)
			);
		}
		return parent::match_code($value);
	}
}

class TokenRegex extends TokenExpressionable {
	static function escape( $rx ) {
		$rx = str_replace( "'", "\\'", $rx ?? '' ) ;
		$rx = str_replace( '\\\\', '\\\\\\\\', $rx ?? '' ) ;
		return $rx ;
	}
	
	function __construct( $value ) {
		parent::__construct('rx', self::escape($value));
	}

	function match_code( $value ) {
		return parent::match_code("'{$value}'");
	}
}

class TokenWhitespace extends TokenTerminal {
	function __construct( $optional ) {
		parent::__construct( 'whitespace', $optional ) ;
	}

	/* Call recursion indirectly */
	function match_code( $value ) {
		$code = parent::match_code( '' ) ;
		return $value ? $code->replace( array( 'FAIL' => NULL )) : $code ;
	}
}

class TokenRecurse extends Token {
	function __construct( $value ) {
		parent::__construct( 'recurse', $value ) ;
	}

	function match_function( $value ) {
		return "'".$this->function_name($value)."'";
	}
	
	function match_code( $value ) {
		$function = $this->match_function($value) ;
		$storetag = $this->function_name( $this->tag ? $this->tag : $this->match_function($value) ) ;

		if ( ParserCompiler::$debug ) {
			$debug_header = PHPBuilder::build()
				->l(
				'$indent = str_repeat( " ", $this->depth );',
				'$this->depth += 2;',
				'$sub = ( strlen( $this->string ) - $this->pos > 20 ) ? ( substr( $this->string, $this->pos, 20 ) . "..." ) : substr( $this->string, $this->pos );',
				'$sub = preg_replace( \'/(\r|\n)+/\', " {NL} ", $sub );',
				'print( $indent."Matching against $matcher (".$sub.")\n" );'
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
			'$matcher = \'match_\'.'.$function.'; $key = $matcher; $pos = $this->pos;',
			$debug_header,
			'$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );',
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

class TokenExpressionedRecurse extends TokenRecurse {
	function match_function( $value ) {
		return '$this->expression($result, $stack, \''.$value.'\')';
	}
}

class TokenSequence extends Token {
	function __construct( $value ) {
		parent::__construct( 'sequence', $value ) ;
	}

	function match_code( $value ) {
		$code = PHPBuilder::build() ;
		foreach( $value as $token ) {
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

	function match_code( $value ) {
		$id = $this->varid() ;
		$code = PHPBuilder::build()
			->l(
			$this->save($id)
			) ;

		foreach ( $value as $opt ) {
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

	static $rule_rx = '@
	(?<name> \w+)                         # The name of the rule
	( \s+ extends \s+ (?<extends>\w+) )?  # The extends word
	( \s* \( (?<arguments>.*) \) )?       # Any variable setters
	(
		\s*(?<matchmark>:) |               # Marks the matching rule start
		\s*(?<replacemark>;) |             # Marks the replacing rule start
		\s*$
	)
	(?<rule>[\s\S]*)
	@x';

	static $argument_rx = '@
	( [^=]+ )    # Name
	=            # Seperator
	( [^=,]+ )   # Variable
	(,|$)
	@x';
	
	static $replacement_rx = '@
	( ([^=]|=[^>])+ )    # What to replace
	=>                   # The replacement mark
	( [^,]+ )            # What to replace it with
	(,|$)
	@x';
	
	static $function_rx = '@^\s+function\s+([^\s(]+)\s*(.*)@' ;

	protected $parser;
	protected $lines;
	
	public $name;
	public $extends;
	public $mode;
	public $rule;
	
	function __construct($parser, $lines) {
		$this->parser = $parser;
		$this->lines = $lines;
		
		// Find the first line (if any) that's an attached function definition. Can skip first line (unless this block is malformed)
		for ($i = 1; $i < count($lines ?? []); $i++) {
			if (preg_match(self::$function_rx, $lines[$i] ?? '')) break;
		}
		
		// Then split into the two parts
		$spec = array_slice($lines ?? [], 0, $i);
		$funcs = array_slice($lines ?? [], $i ?? 0);
		
		// Parse out the spec
		$spec = implode("\n", $spec);
		if (!preg_match(self::$rule_rx, $spec ?? '', $specmatch)) user_error('Malformed rule spec ' . $spec, E_USER_ERROR);
		
		$this->name = $specmatch['name'];
		
		if ($specmatch['extends']) {
			$this->extends = $this->parser->rules[$specmatch['extends']];
			if (!$this->extends) user_error('Extended rule '.$specmatch['extends'].' is not defined before being extended', E_USER_ERROR);
		}
		
		$this->arguments = array();
		
		if ($specmatch['arguments']) {
			preg_match_all(self::$argument_rx, $specmatch['arguments'] ?? '', $arguments, PREG_SET_ORDER);
			
			foreach ($arguments as $argument){
				$this->arguments[trim($argument[1])] = trim($argument[2] ?? '');
			}
		}
		
		$this->mode = $specmatch['matchmark'] ? 'rule' : 'replace';
		
		if ($this->mode == 'rule') {
			$this->rule = $specmatch['rule'];
			$this->parse_rule() ;
		}
		else {
			if (!$this->extends) user_error('Replace matcher, but not on an extends rule', E_USER_ERROR);
			
			$this->replacements = array();
			preg_match_all(self::$replacement_rx, $specmatch['rule'] ?? '', $replacements, PREG_SET_ORDER);
			
			$rule = $this->extends->rule;
			
			foreach ($replacements as $replacement) {
				$search = trim($replacement[1] ?? '');
				$replace = trim($replacement[3] ?? ''); if ($replace == "''" || $replace == '""') $replace = "";
				
				$rule = str_replace($search ?? '', ' '.$replace.' ', $rule ?? '');
			}
			
			$this->rule = $rule;
			$this->parse_rule() ;
		}
		
		// Parse out the functions
		
		$this->functions = array() ;

		$active_function = NULL ;

		foreach( $funcs as $line ) {
			/* Handle function definitions */
			if ( preg_match( self::$function_rx, $line ?? '', $func_match, 0 ) ) {
				$active_function = $func_match[1];
				$this->functions[$active_function] = $func_match[2] . PHP_EOL;
			}
			else $this->functions[$active_function] .= $line . PHP_EOL ;
		}
	}

	/* Manual parsing, because we can't bootstrap ourselves yet */
	function parse_rule() {
		$rule = trim( $this->rule ?? '' ) ;

		/* If this is a regex end-token, just mark it and return */
		if ( substr( $rule ?? '', 0, 1 ) == '/' ) {
			$this->parsed = new TokenRegex( $rule ) ;
		}
		else {
			$tokens = array() ;
			$this->tokenize( $rule, $tokens ) ;
			$this->parsed = ( count( $tokens ?? [] ) == 1 ? array_pop( $tokens ) : new TokenSequence( $tokens ) ) ;
		}
		
	}

	static $rx_rx = '{^/(
		((\\\\\\\\)*\\\\/) # Escaped \/, making sure to catch all the \\ first, so that we dont think \\/ is an escaped /
		|
		[^/]               # Anything except /
	)*/}xu' ;

	function tokenize( $str, &$tokens, $o = 0 ) {

		$pending = new Pending() ;

		while ( $o < strlen( $str ?? '' ) ) {
			$sub = substr( $str ?? '', $o ?? 0 ) ;

			/* Absorb white-space */
			if ( preg_match( '/^\s+/', $sub ?? '', $match ) ) {
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle expression labels */
			elseif ( preg_match( '/^(\w*):/', $sub ?? '', $match ) ) {
				$pending->set( 'tag', isset( $match[1] ) ? $match[1] : '' ) ;
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle descent token */
			elseif ( preg_match( '/^[\w-]+/', $sub ?? '', $match ) ) {
				$tokens[] = $t = new TokenRecurse( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle " quoted literals */
			elseif ( preg_match( '/^"[^"]*"/', $sub ?? '', $match ) ) {
				$tokens[] = $t = new TokenLiteral( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle ' quoted literals */
			elseif ( preg_match( "/^'[^']*'/", $sub ?? '', $match ) ) {
				$tokens[] = $t = new TokenLiteral( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle regexs */
			elseif ( preg_match( self::$rx_rx, $sub ?? '', $match ) ) {
				$tokens[] = $t = new TokenRegex( $match[0] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle $ call literals */
			elseif ( preg_match( '/^\$(\w+)/', $sub ?? '', $match ) ) {
				$tokens[] = $t = new TokenExpressionedRecurse( $match[1] ) ; $pending->apply_if_present( $t ) ;
				$o += strlen( $match[0] ?? '' ) ;
			}
			/* Handle flags */
			elseif ( preg_match( '/^\@(\w+)/', $sub ?? '', $match ) ) {
				$l = count( $tokens ?? [] ) - 1 ;
				$o += strlen( $match[0] ?? '' ) ;
				user_error( "TODO: Flags not currently supported", E_USER_WARNING ) ;
			}
			/* Handle control tokens */
			else {
				$c = substr( $sub ?? '', 0, 1 ) ;
				$l = count( $tokens ?? [] ) - 1 ;
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
	function compile($indent) {
		$function_name = $this->function_name( $this->name ) ;
		
		// Build the typestack
		$typestack = array(); $class=$this;
		do {
			$typestack[] = $this->function_name($class->name);
		}
		while($class = $class->extends);

		$typestack = "array('" . implode("','", $typestack) . "')";
		
		// Build an array of additional arguments to add to result node (if any)
		if (empty($this->arguments)) {
			$arguments = 'null';
		}
		else {
			$arguments = "array(";
			foreach ($this->arguments as $k=>$v) { $arguments .= "'$k' => '$v'"; }
			$arguments .= ")";
		}
		
		$match = PHPBuilder::build() ;
		
		$match->l("protected \$match_{$function_name}_typestack = $typestack;");

		$match->b( "function match_{$function_name} (\$stack = array())",
			'$matchrule = "'.$function_name.'"; $result = $this->construct($matchrule, $matchrule, '.$arguments.');',
			$this->parsed->compile()->replace(array(
				'MATCH' => 'return $this->finalise($result);',
				'FAIL' => 'return FALSE;'
			))
		);

		$functions = array() ;
		foreach( $this->functions as $name => $function ) {
			$function_name = $this->function_name( preg_match( '/^_/', $name ?? '' ) ? $this->name.$name : $this->name.'_'.$name ) ;
			$functions[] = implode( PHP_EOL, array(
				'function ' . $function_name . ' ' . $function
			));
		}

		// print_r( $match ) ; return '' ;
		return $match->render(NULL, $indent) . PHP_EOL . PHP_EOL . implode( PHP_EOL, $functions ) ;
	}
}

class RuleSet {
	public $rules = array();

	function addRule($indent, $lines, &$out) {
		$rule = new Rule($this, $lines) ;
		$this->rules[$rule->name] = $rule;
		
		$out[] = $indent . '/* ' . $rule->name . ':' . $rule->rule . ' */' . PHP_EOL ;
		$out[] = $rule->compile($indent) ;
		$out[] = PHP_EOL ;
	}
	
	function compile($indent, $rulestr) {
		$indentrx = '@^'.preg_quote($indent ?? '').'@';
		
		$out = array();
		$block = array();
		
		foreach (preg_split('/\r\n|\r|\n/', $rulestr ?? '') as $line) {
			// Ignore blank lines
			if (!trim($line ?? '')) continue;
			// Ignore comments
			if (preg_match('/^[\x20|\t]+#/', $line ?? '')) continue;
			
			// Strip off indent
			if (!empty($indent)) { 
				if (strpos($line ?? '', $indent ?? '') === 0) $line = substr($line ?? '', strlen($indent ?? ''));
				else user_error('Non-blank line with inconsistent index in parser block', E_USER_ERROR);
			}
			
			// Any indented line, add to current set of lines
			if (preg_match('/^\x20|\t/', $line ?? '')) $block[] = $line;
			
			// Any non-indented line marks a new block. Add a rule for the current block, then start a new block
			else {
				if (count($block ?? [])) $this->addRule($indent, $block, $out);
				$block = array($line);
			}
		}
		
		// Any unfinished block add a rule for
		if (count($block ?? [])) $this->addRule($indent, $block, $out);
		
		// And return the compiled version
		return implode( '', $out ) ;
	}
}

class ParserCompiler {

	static $parsers = array();
	
	static $debug = false;

	static $currentClass = null;

	static function create_parser( $match ) {
		/* We allow indenting of the whole rule block, but only to the level of the comment start's indent */
		$indent = $match[1];
		
		/* Get the parser name for this block */
		if     ($class = trim($match[2] ?? '')) self::$currentClass = $class;
		elseif (self::$currentClass)      $class = self::$currentClass;
		else                              $class = self::$currentClass = 'Anonymous Parser';
		
		/* Check for pragmas */
		if (strpos($class ?? '', '!') === 0) {
			switch ($class) {
				case '!silent':
					// NOP - dont output
					return '';
				case '!insert_autogen_warning':
					return $indent . implode(PHP_EOL.$indent, array(
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
		
		if (!isset(self::$parsers[$class])) self::$parsers[$class] = new RuleSet();
		
		return self::$parsers[$class]->compile($indent, $match[3]);
	}

	static function compile( $string ) {
		static $rx = '@
			^([\x20\t]*)/\*!\* (?:[\x20\t]*(!?\w*))?   # Start with some indent, a comment with the special marker, then an optional name
			((?:[^*]|\*[^/])*)                         # Any amount of "a character that isnt a star, or a star not followed by a /
			\*/                                        # The comment end
		@mx';

		return preg_replace_callback( $rx ?? '', array( 'ParserCompiler', 'create_parser' ), $string ?? '' ) ;
	}

	static function cli( $args ) {
		if ( count( $args ?? [] ) == 1 ) {
			print "Parser Compiler: A compiler for PEG parsers in PHP \n" ;
			print "(C) 2009 SilverStripe. See COPYING for redistribution rights. \n" ;
			print "\n" ;
			print "Usage: {$args[0]} infile [ outfile ]\n" ;
			print "\n" ;
		}
		else {
			$fname = ( $args[1] == '-' ? 'php://stdin' : $args[1] ) ;
			$string = file_get_contents( $fname ?? '' ) ;
			$string = self::compile( $string ) ;

			if ( !empty( $args[2] ) && $args[2] != '-' ) {
				file_put_contents( $args[2] ?? '', $string ) ;
			}
			else {
				print $string ;
			}
		}
	}
}
