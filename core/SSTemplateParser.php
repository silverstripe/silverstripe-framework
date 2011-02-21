<?php

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/




// We want this to work when run by hand too
if (defined(THIRDPARTY_PATH)) {
	require THIRDPARTY_PATH . '/php-peg/Parser.php' ;
}
else {
	$base = dirname(__FILE__);
	require $base.'/../thirdparty/php-peg/Parser.php';
}

/**
This is the exception raised when failing to parse a template. Note that we don't currently do any static analysis, so we can't know
if the template will run, just if it's malformed. It also won't catch mistakes that still look valid.
*/
class SSTemplateParseException extends Exception {
	
	function __construct($message, $parser) {
		$prior = substr($parser->string, 0, $parser->pos);
		
		preg_match_all('/\r\n|\r|\n/', $prior, $matches);
		$line = count($matches[0])+1;
		
		parent::__construct("Parse error in template on line $line. Error was: $message");
	}
	
}

/**
This is the parser for the SilverStripe template language. It gets called on a string and uses a php-peg parser to match
that string against the language structure, building up the PHP code to execute that structure as it parses

The $result array that is built up as part of the parsing (see thirdparty/php-peg/README.md for more on how parsers 
build results) has one special member, 'php', which contains the php equivalent of that part of the template tree.
 
Some match rules generate alternate php, or other variations, so check the per-match documentation too.
 
Terms used:

Marked: A string or lookup in the template that has been explictly marked as such - lookups by prepending with "$"
(like $Foo.Bar), strings by wrapping with single or double quotes ('Foo' or "Foo")
 
Bare: The opposite of marked. An argument that has to has it's type inferred by usage and 2.4 defaults.
Example of using a bare argument for a loop block: <% loop Foo %>
 
Block: One of two SS template structures. The special characters "<%" and "%>" are used to wrap the opening and
(required or forbidden depending on which block exactly) closing block marks.

Open Block: An SS template block that doesn't wrap any content or have a closing end tag (in fact, a closing end tag is
forbidden)
 
Closed Block: An SS template block that wraps content, and requires a counterpart <% end_blockname %> tag

*/
class SSTemplateParser extends Parser {

	/**
	 * @var bool - Set true by SSTemplateParser::compileString if the template should include comments intended
	 * for debugging (template source, included files, etc)
	 */
	protected $includeDebuggingComments = false;
	
	/* Word: / [A-Za-z_] [A-Za-z0-9_]* / */
	function match_Word ($substack = array()) {
		$result = array("name"=>"Word", "text"=>"");
		$_0 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_0->expand('/ [A-Za-z_] [A-Za-z0-9_]* /') ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $result;
		}
		else { return FALSE; }
	}


	/* Number: / [0-9]+ / */
	function match_Number ($substack = array()) {
		$result = array("name"=>"Number", "text"=>"");
		$_2 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_2->expand('/ [0-9]+ /') ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $result;
		}
		else { return FALSE; }
	}


	/* Value: / [A-Za-z0-9_]+ / */
	function match_Value ($substack = array()) {
		$result = array("name"=>"Value", "text"=>"");
		$_4 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_4->expand('/ [A-Za-z0-9_]+ /') ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $result;
		}
		else { return FALSE; }
	}


	/* CallArguments: :Argument ( < "," < :Argument )* */
	function match_CallArguments ($substack = array()) {
		$result = $this->construct( "CallArguments" );
		$_13 = NULL;
		do {
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_13 = FALSE; break; }
			while (true) {
				$res_12 = $result;
				$pos_12 = $this->pos;
				$_11 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ",") {
						$this->pos += 1;
						$result["text"] .= ",";
					}
					else { $_11 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "Argument"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_11 = FALSE; break; }
					$_11 = TRUE; break;
				}
				while(0);
				if( $_11 === FALSE) {
					$result = $res_12;
					$this->pos = $pos_12;
					unset( $res_12 );
					unset( $pos_12 );
					break;
				}
			}
			$_13 = TRUE; break;
		}
		while(0);
		if( $_13 === TRUE ) {
			return $this->finalise( "CallArguments", $result );
		}
		if( $_13 === FALSE) { return FALSE; }
	}




	/** 
	 * Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert strings 
	 * to numbers when needed.
	 */
	function CallArguments_Argument(&$res, $sub) {
		if (isset($res['php'])) $res['php'] .= ', ';
		else $res['php'] = '';
		
		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] : str_replace('$$FINAL', 'XML_val', $sub['php']);
	}

	/* Call: Method:Word ( "(" < :CallArguments? > ")" )? */
	function match_Call ($substack = array()) {
		$result = $this->construct( "Call" );
		$_23 = NULL;
		do {
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Method" );
			}
			else { $_23 = FALSE; break; }
			$res_22 = $result;
			$pos_22 = $this->pos;
			$_21 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == "(") {
					$this->pos += 1;
					$result["text"] .= "(";
				}
				else { $_21 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$res_18 = $result;
				$pos_18 = $this->pos;
				$key = "CallArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_CallArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else {
					$result = $res_18;
					$this->pos = $pos_18;
					unset( $res_18 );
					unset( $pos_18 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ")") {
					$this->pos += 1;
					$result["text"] .= ")";
				}
				else { $_21 = FALSE; break; }
				$_21 = TRUE; break;
			}
			while(0);
			if( $_21 === FALSE) {
				$result = $res_22;
				$this->pos = $pos_22;
				unset( $res_22 );
				unset( $pos_22 );
			}
			$_23 = TRUE; break;
		}
		while(0);
		if( $_23 === TRUE ) {
			return $this->finalise( "Call", $result );
		}
		if( $_23 === FALSE) { return FALSE; }
	}


	/* LookupStep: :Call &"." */
	function match_LookupStep ($substack = array()) {
		$result = $this->construct( "LookupStep" );
		$_27 = NULL;
		do {
			$key = "Call"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Call(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Call" );
			}
			else { $_27 = FALSE; break; }
			$res_26 = $result;
			$pos_26 = $this->pos;
			if (substr($this->string,$this->pos,1) == ".") {
				$this->pos += 1;
				$result["text"] .= ".";
				$result = $res_26;
				$this->pos = $pos_26;
			}
			else {
				$result = $res_26;
				$this->pos = $pos_26;
				$_27 = FALSE; break;
			}
			$_27 = TRUE; break;
		}
		while(0);
		if( $_27 === TRUE ) {
			return $this->finalise( "LookupStep", $result );
		}
		if( $_27 === FALSE) { return FALSE; }
	}


	/* LastLookupStep: :Call */
	function match_LastLookupStep ($substack = array()) {
		$result = $this->construct( "LastLookupStep" );
		$key = "Call"; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Call(array_merge($substack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "Call" );
			return $this->finalise( "LastLookupStep", $result );
		}
		else { return FALSE; }
	}


	/* Lookup: LookupStep ("." LookupStep)* "." LastLookupStep | LastLookupStep */
	function match_Lookup ($substack = array()) {
		$result = $this->construct( "Lookup" );
		$_41 = NULL;
		do {
			$res_30 = $result;
			$pos_30 = $this->pos;
			$_38 = NULL;
			do {
				$key = "LookupStep"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_LookupStep(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_38 = FALSE; break; }
				while (true) {
					$res_35 = $result;
					$pos_35 = $this->pos;
					$_34 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == ".") {
							$this->pos += 1;
							$result["text"] .= ".";
						}
						else { $_34 = FALSE; break; }
						$key = "LookupStep"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_LookupStep(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_34 = FALSE; break; }
						$_34 = TRUE; break;
					}
					while(0);
					if( $_34 === FALSE) {
						$result = $res_35;
						$this->pos = $pos_35;
						unset( $res_35 );
						unset( $pos_35 );
						break;
					}
				}
				if (substr($this->string,$this->pos,1) == ".") {
					$this->pos += 1;
					$result["text"] .= ".";
				}
				else { $_38 = FALSE; break; }
				$key = "LastLookupStep"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_LastLookupStep(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_38 = FALSE; break; }
				$_38 = TRUE; break;
			}
			while(0);
			if( $_38 === TRUE ) { $_41 = TRUE; break; }
			$result = $res_30;
			$this->pos = $pos_30;
			$key = "LastLookupStep"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_LastLookupStep(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_41 = TRUE; break;
			}
			$result = $res_30;
			$this->pos = $pos_30;
			$_41 = FALSE; break;
		}
		while(0);
		if( $_41 === TRUE ) {
			return $this->finalise( "Lookup", $result );
		}
		if( $_41 === FALSE) { return FALSE; }
	}



	
	function Lookup__construct(&$res) {
		$res['php'] = '$scope';
		$res['LookupSteps'] = array();
	}
	
	/** 
	 * The basic generated PHP of LookupStep and LastLookupStep is the same, except that LookupStep calls 'obj' to 
	 * get the next ViewableData in the sequence, and LastLookupStep calls different methods (XML_val, hasValue, obj)
	 * depending on the context the lookup is used in.
	 */
	function Lookup_AddLookupStep(&$res, $sub, $method) {
		$res['LookupSteps'][] = $sub;
		
		$property = $sub['Call']['Method']['text'];
		
		if (isset($sub['Call']['CallArguments']) && $arguments = $sub['Call']['CallArguments']['php']) {
			$res['php'] .= "->$method('$property', array($arguments), true)";
		}
		else {
			$res['php'] .= "->$method('$property', null, true)";
		}
	}

	function Lookup_LookupStep(&$res, $sub) {
		$this->Lookup_AddLookupStep($res, $sub, 'obj');
	}

	function Lookup_LastLookupStep(&$res, $sub) {
		$this->Lookup_AddLookupStep($res, $sub, '$$FINAL');
	}

	/* SimpleInjection: '$' :Lookup */
	function match_SimpleInjection ($substack = array()) {
		$result = $this->construct( "SimpleInjection" );
		$_45 = NULL;
		do {
			if (substr($this->string,$this->pos,1) == '$') {
				$this->pos += 1;
				$result["text"] .= '$';
			}
			else { $_45 = FALSE; break; }
			$key = "Lookup"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Lookup(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_45 = FALSE; break; }
			$_45 = TRUE; break;
		}
		while(0);
		if( $_45 === TRUE ) {
			return $this->finalise( "SimpleInjection", $result );
		}
		if( $_45 === FALSE) { return FALSE; }
	}


	/* BracketInjection: '{$' :Lookup "}" */
	function match_BracketInjection ($substack = array()) {
		$result = $this->construct( "BracketInjection" );
		$_51 = NULL;
		do {
			$_47 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_47->expand('{$') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_51 = FALSE; break; }
			$key = "Lookup"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Lookup(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_51 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == "}") {
				$this->pos += 1;
				$result["text"] .= "}";
			}
			else { $_51 = FALSE; break; }
			$_51 = TRUE; break;
		}
		while(0);
		if( $_51 === TRUE ) {
			return $this->finalise( "BracketInjection", $result );
		}
		if( $_51 === FALSE) { return FALSE; }
	}


	/* Injection: BracketInjection | SimpleInjection */
	function match_Injection ($substack = array()) {
		$result = $this->construct( "Injection" );
		$_56 = NULL;
		do {
			$res_53 = $result;
			$pos_53 = $this->pos;
			$key = "BracketInjection"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BracketInjection(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_56 = TRUE; break;
			}
			$result = $res_53;
			$this->pos = $pos_53;
			$key = "SimpleInjection"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_SimpleInjection(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_56 = TRUE; break;
			}
			$result = $res_53;
			$this->pos = $pos_53;
			$_56 = FALSE; break;
		}
		while(0);
		if( $_56 === TRUE ) {
			return $this->finalise( "Injection", $result );
		}
		if( $_56 === FALSE) { return FALSE; }
	}



	function Injection_STR(&$res, $sub) {
		$res['php'] = '$val .= '. str_replace('$$FINAL', 'XML_val', $sub['Lookup']['php']) . ';';
	}

	/* DollarMarkedLookup: SimpleInjection */
	function match_DollarMarkedLookup ($substack = array()) {
		$result = $this->construct( "DollarMarkedLookup" );
		$key = "SimpleInjection"; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_SimpleInjection(array_merge($substack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			return $this->finalise( "DollarMarkedLookup", $result );
		}
		else { return FALSE; }
	}



	function DollarMarkedLookup_STR(&$res, $sub) {
		$res['Lookup'] = $sub['Lookup'];
	}

	/* QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q' */
	function match_QuotedString ($substack = array()) {
		$result = $this->construct( "QuotedString" );
		$_67 = NULL;
		do {
			$substack[] = $result;
			$result = $this->construct( "q" );
			$_59 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->rx( $_59->expand('/[\'"]/') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'q' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_67 = FALSE; break;
			}
			$substack[] = $result;
			$result = $this->construct( "String" );
			$_62 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->rx( $_62->expand('/ (\\\\\\\\ | \\\\. | [^$q\\\\])* /') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'String' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_67 = FALSE; break;
			}
			$_65 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_65->expand('$q') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_67 = FALSE; break; }
			$_67 = TRUE; break;
		}
		while(0);
		if( $_67 === TRUE ) {
			return $this->finalise( "QuotedString", $result );
		}
		if( $_67 === FALSE) { return FALSE; }
	}


	/* FreeString: /[^,)%!=|&]+/ */
	function match_FreeString ($substack = array()) {
		$result = array("name"=>"FreeString", "text"=>"");
		$_69 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_69->expand('/[^,)%!=|&]+/') ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $result;
		}
		else { return FALSE; }
	}


	/* Argument:
:DollarMarkedLookup |
:QuotedString |
:Lookup !(< FreeString)|
:FreeString */
	function match_Argument ($substack = array()) {
		$result = $this->construct( "Argument" );
		$_88 = NULL;
		do {
			$res_71 = $result;
			$pos_71 = $this->pos;
			$key = "DollarMarkedLookup"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_DollarMarkedLookup(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "DollarMarkedLookup" );
				$_88 = TRUE; break;
			}
			$result = $res_71;
			$this->pos = $pos_71;
			$_86 = NULL;
			do {
				$res_73 = $result;
				$pos_73 = $this->pos;
				$key = "QuotedString"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_QuotedString(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "QuotedString" );
					$_86 = TRUE; break;
				}
				$result = $res_73;
				$this->pos = $pos_73;
				$_84 = NULL;
				do {
					$res_75 = $result;
					$pos_75 = $this->pos;
					$_81 = NULL;
					do {
						$key = "Lookup"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Lookup(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
						}
						else { $_81 = FALSE; break; }
						$res_80 = $result;
						$pos_80 = $this->pos;
						$_79 = NULL;
						do {
							if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
							$key = "FreeString"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_FreeString(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_79 = FALSE; break; }
							$_79 = TRUE; break;
						}
						while(0);
						if( $_79 === TRUE ) {
							$result = $res_80;
							$this->pos = $pos_80;
							$_81 = FALSE; break;
						}
						if( $_79 === FALSE) {
							$result = $res_80;
							$this->pos = $pos_80;
						}
						$_81 = TRUE; break;
					}
					while(0);
					if( $_81 === TRUE ) { $_84 = TRUE; break; }
					$result = $res_75;
					$this->pos = $pos_75;
					$key = "FreeString"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_FreeString(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "FreeString" );
						$_84 = TRUE; break;
					}
					$result = $res_75;
					$this->pos = $pos_75;
					$_84 = FALSE; break;
				}
				while(0);
				if( $_84 === TRUE ) { $_86 = TRUE; break; }
				$result = $res_73;
				$this->pos = $pos_73;
				$_86 = FALSE; break;
			}
			while(0);
			if( $_86 === TRUE ) { $_88 = TRUE; break; }
			$result = $res_71;
			$this->pos = $pos_71;
			$_88 = FALSE; break;
		}
		while(0);
		if( $_88 === TRUE ) {
			return $this->finalise( "Argument", $result );
		}
		if( $_88 === FALSE) { return FALSE; }
	}



	
	/**
	 * If we get a bare value, we don't know enough to determine exactly what php would be the translation, because
	 * we don't know if the position of use indicates a lookup or a string argument.
	 * 
	 * Instead, we record 'ArgumentMode' as a member of this matches results node, which can be:
	 *   - lookup if this argument was unambiguously a lookup (marked as such)
	 *   - string is this argument was unambiguously a string (marked as such, or impossible to parse as lookup)
	 *   - default if this argument needs to be handled as per 2.4
	 * 
	 * In the case of 'default', there is no php member of the results node, but instead 'lookup_php', which
	 * should be used by the parent if the context indicates a lookup, and 'string_php' which should be used
	 * if the context indicates a string
	 */
	
	function Argument_DollarMarkedLookup(&$res, $sub) {
		$res['ArgumentMode'] = 'lookup';
		$res['php'] = $sub['Lookup']['php'];
	}

	function Argument_QuotedString(&$res, $sub) {
		$res['ArgumentMode'] = 'string';
		$res['php'] = "'" . $sub['String']['text'] . "'";
	}

	function Argument_Lookup(&$res, $sub) {
		if (count($sub['LookupSteps']) == 1 && !isset($sub['LookupSteps'][0]['Call']['Arguments'])) {
			$res['ArgumentMode'] = 'default';
			$res['lookup_php'] = $sub['php'];
			$res['string_php'] = "'".$sub['LookupSteps'][0]['Call']['Method']['text']."'";
		}
		else {
			$res['ArgumentMode'] = 'lookup';
			$res['php'] = $sub['php'];
		}
	}
	
	function Argument_FreeString(&$res, $sub) {
		$res['ArgumentMode'] = 'string';
		$res['php'] = "'" . $sub['text'] . "'";
	}
	
	/* ComparisonOperator: "==" | "!=" | "=" */
	function match_ComparisonOperator ($substack = array()) {
		$result = $this->construct( "ComparisonOperator" );
		$_99 = NULL;
		do {
			$res_90 = $result;
			$pos_90 = $this->pos;
			$_91 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_91->expand("==") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_99 = TRUE; break;
			}
			$result = $res_90;
			$this->pos = $pos_90;
			$_97 = NULL;
			do {
				$res_93 = $result;
				$pos_93 = $this->pos;
				$_94 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_94->expand("!=") ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_97 = TRUE; break;
				}
				$result = $res_93;
				$this->pos = $pos_93;
				if (substr($this->string,$this->pos,1) == "=") {
					$this->pos += 1;
					$result["text"] .= "=";
					$_97 = TRUE; break;
				}
				$result = $res_93;
				$this->pos = $pos_93;
				$_97 = FALSE; break;
			}
			while(0);
			if( $_97 === TRUE ) { $_99 = TRUE; break; }
			$result = $res_90;
			$this->pos = $pos_90;
			$_99 = FALSE; break;
		}
		while(0);
		if( $_99 === TRUE ) {
			return $this->finalise( "ComparisonOperator", $result );
		}
		if( $_99 === FALSE) { return FALSE; }
	}


	/* Comparison: Argument < ComparisonOperator > Argument */
	function match_Comparison ($substack = array()) {
		$result = $this->construct( "Comparison" );
		$_106 = NULL;
		do {
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_106 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "ComparisonOperator"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ComparisonOperator(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_106 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_106 = FALSE; break; }
			$_106 = TRUE; break;
		}
		while(0);
		if( $_106 === TRUE ) {
			return $this->finalise( "Comparison", $result );
		}
		if( $_106 === FALSE) { return FALSE; }
	}



	function Comparison_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'default') {
			if (isset($res['php'])) $res['php'] .= $sub['string_php'];
			else $res['php'] = str_replace('$$FINAL', 'XML_val', $sub['lookup_php']);
		}	
		else {
			if (!isset($res['php'])) $res['php'] = '';
			$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
		}
	}

	function Comparison_ComparisonOperator(&$res, $sub) {
		$res['php'] .= ($sub['text'] == '=' ? '==' : $sub['text']);
	}

	/* PresenceCheck: (Not:'not' <)? Argument */
	function match_PresenceCheck ($substack = array()) {
		$result = $this->construct( "PresenceCheck" );
		$_115 = NULL;
		do {
			$res_113 = $result;
			$pos_113 = $this->pos;
			$_112 = NULL;
			do {
				$substack[] = $result;
				$result = $this->construct( "Not" );
				$_108 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_108->expand('not') ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result ;
					$result = array_pop( $substack ) ;
					$this->store( $result, $subres, 'Not' );
				}
				else {
					$result = array_pop( $substack ) ;
					$_112 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_112 = TRUE; break;
			}
			while(0);
			if( $_112 === FALSE) {
				$result = $res_113;
				$this->pos = $pos_113;
				unset( $res_113 );
				unset( $pos_113 );
			}
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_115 = FALSE; break; }
			$_115 = TRUE; break;
		}
		while(0);
		if( $_115 === TRUE ) {
			return $this->finalise( "PresenceCheck", $result );
		}
		if( $_115 === FALSE) { return FALSE; }
	}



	function PresenceCheck__construct(&$res) {
		$res['php'] = '';
	}
	
	function PresenceCheck_Not(&$res, $sub) {
		$res['php'] = '!';
	}
	
	function PresenceCheck_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'string') {
			$res['php'] .= '((bool)'.$sub['php'].')';
		}
		else {
			$php = ($sub['ArgumentMode'] == 'default' ? $sub['lookup_php'] : $sub['php']);
			// TODO: kinda hacky - maybe we need a way to pass state down the parse chain so
			// Lookup_LastLookupStep and Argument_BareWord can produce hasValue instead of XML_val
			$res['php'] .= str_replace('$$FINAL', 'hasValue', $php);
		}
	}

	/* IfArgumentPortion: Comparison | PresenceCheck */
	function match_IfArgumentPortion ($substack = array()) {
		$result = $this->construct( "IfArgumentPortion" );
		$_120 = NULL;
		do {
			$res_117 = $result;
			$pos_117 = $this->pos;
			$key = "Comparison"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comparison(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_120 = TRUE; break;
			}
			$result = $res_117;
			$this->pos = $pos_117;
			$key = "PresenceCheck"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_PresenceCheck(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_120 = TRUE; break;
			}
			$result = $res_117;
			$this->pos = $pos_117;
			$_120 = FALSE; break;
		}
		while(0);
		if( $_120 === TRUE ) {
			return $this->finalise( "IfArgumentPortion", $result );
		}
		if( $_120 === FALSE) { return FALSE; }
	}



	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* BooleanOperator: "||" | "&&" */
	function match_BooleanOperator ($substack = array()) {
		$result = $this->construct( "BooleanOperator" );
		$_127 = NULL;
		do {
			$res_122 = $result;
			$pos_122 = $this->pos;
			$_123 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_123->expand("||") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_127 = TRUE; break;
			}
			$result = $res_122;
			$this->pos = $pos_122;
			$_125 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_125->expand("&&") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_127 = TRUE; break;
			}
			$result = $res_122;
			$this->pos = $pos_122;
			$_127 = FALSE; break;
		}
		while(0);
		if( $_127 === TRUE ) {
			return $this->finalise( "BooleanOperator", $result );
		}
		if( $_127 === FALSE) { return FALSE; }
	}


	/* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
	function match_IfArgument ($substack = array()) {
		$result = $this->construct( "IfArgument" );
		$_136 = NULL;
		do {
			$key = "IfArgumentPortion"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgumentPortion(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgumentPortion" );
			}
			else { $_136 = FALSE; break; }
			while (true) {
				$res_135 = $result;
				$pos_135 = $this->pos;
				$_134 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "BooleanOperator"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BooleanOperator(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BooleanOperator" );
					}
					else { $_134 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "IfArgumentPortion"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgumentPortion(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "IfArgumentPortion" );
					}
					else { $_134 = FALSE; break; }
					$_134 = TRUE; break;
				}
				while(0);
				if( $_134 === FALSE) {
					$result = $res_135;
					$this->pos = $pos_135;
					unset( $res_135 );
					unset( $pos_135 );
					break;
				}
			}
			$_136 = TRUE; break;
		}
		while(0);
		if( $_136 === TRUE ) {
			return $this->finalise( "IfArgument", $result );
		}
		if( $_136 === FALSE) { return FALSE; }
	}



	function IfArgument__construct(&$res){
		$res['php'] = '';
	}
	function IfArgument_IfArgumentPortion(&$res, $sub) {
		$res['php'] .= $sub['php'];
	}

	function IfArgument_BooleanOperator(&$res, $sub) {
		$res['php'] .= $sub['text'];
	}

	/* IfPart: '<%' < 'if' [ :IfArgument > '%>' :Template? */
	function match_IfPart ($substack = array()) {
		$result = $this->construct( "IfPart" );
		$_149 = NULL;
		do {
			$_138 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_138->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_149 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_141 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_141->expand('if') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_149 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_149 = FALSE; break; }
			$key = "IfArgument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_149 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_146 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_146->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_149 = FALSE; break; }
			$res_148 = $result;
			$pos_148 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_148;
				$this->pos = $pos_148;
				unset( $res_148 );
				unset( $pos_148 );
			}
			$_149 = TRUE; break;
		}
		while(0);
		if( $_149 === TRUE ) {
			return $this->finalise( "IfPart", $result );
		}
		if( $_149 === FALSE) { return FALSE; }
	}


	/* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' :Template */
	function match_ElseIfPart ($substack = array()) {
		$result = $this->construct( "ElseIfPart" );
		$_162 = NULL;
		do {
			$_151 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_151->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_162 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_154 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_154->expand('else_if') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_162 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_162 = FALSE; break; }
			$key = "IfArgument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_162 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_159 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_159->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_162 = FALSE; break; }
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_162 = FALSE; break; }
			$_162 = TRUE; break;
		}
		while(0);
		if( $_162 === TRUE ) {
			return $this->finalise( "ElseIfPart", $result );
		}
		if( $_162 === FALSE) { return FALSE; }
	}


	/* ElsePart: '<%' < 'else' > '%>' :Template */
	function match_ElsePart ($substack = array()) {
		$result = $this->construct( "ElsePart" );
		$_173 = NULL;
		do {
			$_164 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_164->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_167 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_167->expand('else') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_170 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_170->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_173 = FALSE; break; }
			$_173 = TRUE; break;
		}
		while(0);
		if( $_173 === TRUE ) {
			return $this->finalise( "ElsePart", $result );
		}
		if( $_173 === FALSE) { return FALSE; }
	}


	/* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
	function match_If ($substack = array()) {
		$result = $this->construct( "If" );
		$_186 = NULL;
		do {
			$key = "IfPart"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfPart(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_186 = FALSE; break; }
			while (true) {
				$res_176 = $result;
				$pos_176 = $this->pos;
				$key = "ElseIfPart"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ElseIfPart(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else {
					$result = $res_176;
					$this->pos = $pos_176;
					unset( $res_176 );
					unset( $pos_176 );
					break;
				}
			}
			$res_177 = $result;
			$pos_177 = $this->pos;
			$key = "ElsePart"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ElsePart(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_177;
				$this->pos = $pos_177;
				unset( $res_177 );
				unset( $pos_177 );
			}
			$_178 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_178->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_186 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_181 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_181->expand('end_if') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_186 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_184 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_184->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_186 = FALSE; break; }
			$_186 = TRUE; break;
		}
		while(0);
		if( $_186 === TRUE ) {
			return $this->finalise( "If", $result );
		}
		if( $_186 === FALSE) { return FALSE; }
	}



	function If_IfPart(&$res, $sub) {
		$res['php'] = 
			'if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				(isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
			'}';
	} 

	function If_ElseIfPart(&$res, $sub) {
		$res['php'] .= 
			'else if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				$sub['Template']['php'] . PHP_EOL . 
			'}';
	}

	function If_ElsePart(&$res, $sub) {
		$res['php'] .= 
			'else { ' . PHP_EOL . 
				$sub['Template']['php'] . PHP_EOL . 
			'}';
	}

	/* Require: '<%' < 'require' [ Call:(Method:Word "(" < :CallArguments  > ")") > '%>' */
	function match_Require ($substack = array()) {
		$result = $this->construct( "Require" );
		$_206 = NULL;
		do {
			$_188 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_188->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_206 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_191 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_191->expand('require') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_206 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_206 = FALSE; break; }
			$substack[] = $result;
			$result = $this->construct( "Call" );
			$_200 = NULL;
			do {
				$key = "Word"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Method" );
				}
				else { $_200 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == "(") {
					$this->pos += 1;
					$result["text"] .= "(";
				}
				else { $_200 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$key = "CallArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_CallArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else { $_200 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ")") {
					$this->pos += 1;
					$result["text"] .= ")";
				}
				else { $_200 = FALSE; break; }
				$_200 = TRUE; break;
			}
			while(0);
			if( $_200 === TRUE ) {
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Call' );
			}
			if( $_200 === FALSE) {
				$result = array_pop( $substack ) ;
				$_206 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_204 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_204->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_206 = FALSE; break; }
			$_206 = TRUE; break;
		}
		while(0);
		if( $_206 === TRUE ) {
			return $this->finalise( "Require", $result );
		}
		if( $_206 === FALSE) { return FALSE; }
	}



	function Require_Call(&$res, $sub) {
		$res['php'] = "Requirements::".$sub['Method']['text'].'('.$sub['CallArguments']['php'].');';
	}
	
	/* BlockArguments: :Argument ( < "," < :Argument)*  */
	function match_BlockArguments ($substack = array()) {
		$result = $this->construct( "BlockArguments" );
		$_215 = NULL;
		do {
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_215 = FALSE; break; }
			while (true) {
				$res_214 = $result;
				$pos_214 = $this->pos;
				$_213 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ",") {
						$this->pos += 1;
						$result["text"] .= ",";
					}
					else { $_213 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "Argument"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_213 = FALSE; break; }
					$_213 = TRUE; break;
				}
				while(0);
				if( $_213 === FALSE) {
					$result = $res_214;
					$this->pos = $pos_214;
					unset( $res_214 );
					unset( $pos_214 );
					break;
				}
			}
			$_215 = TRUE; break;
		}
		while(0);
		if( $_215 === TRUE ) {
			return $this->finalise( "BlockArguments", $result );
		}
		if( $_215 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require") ] ) */
	function match_NotBlockTag ($substack = array()) {
		$result = $this->construct( "NotBlockTag" );
		$_242 = NULL;
		do {
			$res_217 = $result;
			$pos_217 = $this->pos;
			$_218 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_218->expand("end_") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_242 = TRUE; break;
			}
			$result = $res_217;
			$this->pos = $pos_217;
			$_240 = NULL;
			do {
				$_237 = NULL;
				do {
					$_235 = NULL;
					do {
						$res_220 = $result;
						$pos_220 = $this->pos;
						$_221 = new ParserExpression( $this, $substack, $result );
						if (( $subres = $this->literal( $_221->expand("if") ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_235 = TRUE; break;
						}
						$result = $res_220;
						$this->pos = $pos_220;
						$_233 = NULL;
						do {
							$res_223 = $result;
							$pos_223 = $this->pos;
							$_224 = new ParserExpression( $this, $substack, $result );
							if (( $subres = $this->literal( $_224->expand("else_if") ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_233 = TRUE; break;
							}
							$result = $res_223;
							$this->pos = $pos_223;
							$_231 = NULL;
							do {
								$res_226 = $result;
								$pos_226 = $this->pos;
								$_227 = new ParserExpression( $this, $substack, $result );
								if (( $subres = $this->literal( $_227->expand("else") ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_231 = TRUE; break;
								}
								$result = $res_226;
								$this->pos = $pos_226;
								$_229 = new ParserExpression( $this, $substack, $result );
								if (( $subres = $this->literal( $_229->expand("require") ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_231 = TRUE; break;
								}
								$result = $res_226;
								$this->pos = $pos_226;
								$_231 = FALSE; break;
							}
							while(0);
							if( $_231 === TRUE ) { $_233 = TRUE; break; }
							$result = $res_223;
							$this->pos = $pos_223;
							$_233 = FALSE; break;
						}
						while(0);
						if( $_233 === TRUE ) { $_235 = TRUE; break; }
						$result = $res_220;
						$this->pos = $pos_220;
						$_235 = FALSE; break;
					}
					while(0);
					if( $_235 === FALSE) { $_237 = FALSE; break; }
					$_237 = TRUE; break;
				}
				while(0);
				if( $_237 === FALSE) { $_240 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_240 = FALSE; break; }
				$_240 = TRUE; break;
			}
			while(0);
			if( $_240 === TRUE ) { $_242 = TRUE; break; }
			$result = $res_217;
			$this->pos = $pos_217;
			$_242 = FALSE; break;
		}
		while(0);
		if( $_242 === TRUE ) {
			return $this->finalise( "NotBlockTag", $result );
		}
		if( $_242 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' :Template? '<%' < 'end_' '$BlockName' > '%>' */
	function match_ClosedBlock ($substack = array()) {
		$result = $this->construct( "ClosedBlock" );
		$_269 = NULL;
		do {
			$_244 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_244->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_269 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_247 = $result;
			$pos_247 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_247;
				$this->pos = $pos_247;
				$_269 = FALSE; break;
			}
			else {
				$result = $res_247;
				$this->pos = $pos_247;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_269 = FALSE; break; }
			$res_253 = $result;
			$pos_253 = $this->pos;
			$_252 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_252 = FALSE; break; }
				$key = "BlockArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_252 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_252 = FALSE; break; }
				$_252 = TRUE; break;
			}
			while(0);
			if( $_252 === FALSE) {
				$result = $res_253;
				$this->pos = $pos_253;
				unset( $res_253 );
				unset( $pos_253 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$substack[] = $result;
			$result = $this->construct( "Zap" );
			$_255 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_255->expand('%>') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Zap' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_269 = FALSE; break;
			}
			$res_258 = $result;
			$pos_258 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_258;
				$this->pos = $pos_258;
				unset( $res_258 );
				unset( $pos_258 );
			}
			$_259 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_259->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_269 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_262 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_262->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_269 = FALSE; break; }
			$_264 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_264->expand('$BlockName') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_269 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_267 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_267->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_269 = FALSE; break; }
			$_269 = TRUE; break;
		}
		while(0);
		if( $_269 === TRUE ) {
			return $this->finalise( "ClosedBlock", $result );
		}
		if( $_269 === FALSE) { return FALSE; }
	}



	
	/**
	 * As mentioned in the parser comment, block handling is kept fairly generic for extensibility. The match rule
	 * builds up two important elements in the match result array:
	 *   'ArgumentCount' - how many arguments were passed in the opening tag
	 *   'Arguments' an array of the Argument match rule result arrays
	 *
	 * Once a block has successfully been matched against, it will then look for the actual handler, which should
	 * be on this class (either defined or decorated on) as ClosedBlock_Handler_Name(&$res), where Name is the
	 * tag name, first letter captialized (i.e Control, Loop, With, etc).
	 * 
	 * This function will be called with the match rule result array as it's first argument. It should return
	 * the php result of this block as it's return value, or throw an error if incorrect arguments were passed.
	 */
	
	function ClosedBlock__construct(&$res) {
		$res['ArgumentCount'] = 0;
	}
	
	function ClosedBlock_BlockArguments(&$res, $sub) {
		if (isset($sub['Argument']['ArgumentMode'])) {
			$res['Arguments'] = array($sub['Argument']);
			$res['ArgumentCount'] = 1;
		}
		else {
			$res['Arguments'] = $sub['Argument'];
			$res['ArgumentCount'] = count($res['Arguments']);
		}
	}
	
	function ClosedBlock__finalise(&$res) {
		$blockname = $res['BlockName']['text'];
		
		$method = 'ClosedBlock_Handle_'.ucfirst(strtolower($blockname));
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown closed block "'.$blockname.'" encountered. Perhaps you are not supposed to close this block, or have mis-spelled it?', $this);
		}
	}

	/**
	 * This is an example of a block handler function. This one handles the loop tag.
	 */
	function ClosedBlock_Handle_Loop(&$res) {
		if ($res['ArgumentCount'] != 1) {
			throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one argument only.', $this);
		}
		
		$arg = $res['Arguments'][0];
		if ($arg['ArgumentMode'] == 'string') {
			throw new SSTemplateParseException('Control block cant take string as argument.', $this);
		}
		
		$on = str_replace('$$FINAL', 'obj', ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		return 
			$on . '; $scope->pushScope(); while (($key = $scope->next()) !== false) {' . PHP_EOL .
				$res['Template']['php'] . PHP_EOL .
			'}; $scope->popScope(); ';
	}

	/**
	 * The deprecated closed block handler for control blocks
	 * @deprecated
	 */
	function ClosedBlock_Handle_Control(&$res) {
		return $this->ClosedBlock_Handle_Loop($res);
	}
	
	/**
	 * The closed block handler for with blocks
	 */
	function ClosedBlock_Handle_With(&$res) {
		if ($res['ArgumentCount'] != 1) {
			throw new SSTemplateParseException('Either no or too many arguments in with block. Must be one argument only.', $this);
		}
		
		$arg = $res['Arguments'][0];
		if ($arg['ArgumentMode'] == 'string') {
			throw new SSTemplateParseException('Control block cant take string as argument.', $this);
		}
		
		$on = str_replace('$$FINAL', 'obj', ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		return 
			$on . '; $scope->pushScope();' . PHP_EOL .
				$res['Template']['php'] . PHP_EOL .
			'; $scope->popScope(); ';
	}
	
	/* OpenBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > '%>' */
	function match_OpenBlock ($substack = array()) {
		$result = $this->construct( "OpenBlock" );
		$_284 = NULL;
		do {
			$_271 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_271->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_284 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_274 = $result;
			$pos_274 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_274;
				$this->pos = $pos_274;
				$_284 = FALSE; break;
			}
			else {
				$result = $res_274;
				$this->pos = $pos_274;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_284 = FALSE; break; }
			$res_280 = $result;
			$pos_280 = $this->pos;
			$_279 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_279 = FALSE; break; }
				$key = "BlockArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_279 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_279 = FALSE; break; }
				$_279 = TRUE; break;
			}
			while(0);
			if( $_279 === FALSE) {
				$result = $res_280;
				$this->pos = $pos_280;
				unset( $res_280 );
				unset( $pos_280 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_282 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_282->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_284 = FALSE; break; }
			$_284 = TRUE; break;
		}
		while(0);
		if( $_284 === TRUE ) {
			return $this->finalise( "OpenBlock", $result );
		}
		if( $_284 === FALSE) { return FALSE; }
	}



	function OpenBlock__construct(&$res) {
		$res['ArgumentCount'] = 0;
	}
	
	function OpenBlock_BlockArguments(&$res, $sub) {
		if (isset($sub['Argument']['ArgumentMode'])) {
			$res['Arguments'] = array($sub['Argument']);
			$res['ArgumentCount'] = 1;
		}
		else {
			$res['Arguments'] = $sub['Argument'];
			$res['ArgumentCount'] = count($res['Arguments']);
		}
	}
	
	function OpenBlock__finalise(&$res) {
		$blockname = $res['BlockName']['text'];
	
		$method = 'OpenBlock_Handle_'.ucfirst(strtolower($blockname));
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown open block "'.$blockname.'" encountered. Perhaps you missed the closing tag or have mis-spelled it?', $this);
		}
	}
	
	/**
	 * This is an open block handler, for the <% include %> tag
	 */
	function OpenBlock_Handle_Include(&$res) {
		if ($res['ArgumentCount'] != 1) throw new SSTemplateParseException('Include takes exactly one argument', $this);
		
		$arg = $res['Arguments'][0];
		$php = ($arg['ArgumentMode'] == 'default') ? $arg['string_php'] : $arg['php'];
		
		if($this->includeDebuggingComments) { // Add include filename comments on dev sites
			return 
				'$val .= \'<!-- include '.$php.' -->\';'. "\n".
				'$val .= SSViewer::parse_template('.$php.', $scope->getItem());'. "\n".
				'$val .= \'<!-- end include '.$php.' -->\';'. "\n";
		}
		else {
			return 
				'$val .= SSViewer::execute_template('.$php.', $scope->getItem());'. "\n";
		}
	}
	
	/**
	 * This is an open block handler, for the <% debug %> utility tag
	 */
	function OpenBlock_Handle_Debug(&$res) {
		if ($res['ArgumentCount'] == 0) return '$scope->debug();';
		else if ($res['ArgumentCount'] == 1) {
			$arg = $res['Arguments'][0];
			
			if ($arg['ArgumentMode'] == 'string') return 'Debug::show('.$arg['php'].');';
			
			$php = ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php'];
			return '$val .= Debug::show('.str_replace('FINALGET!', 'cachedCall', $php).');';
		}
		else {
			throw new SSTemplateParseException('Debug takes 0 or 1 argument only.', $this);
		}
	}

	/**
	 * This is an open block handler, for the <% base_tag %> tag
	 */
	function OpenBlock_Handle_Base_tag(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Base_tag takes no arguments', $this);
		return '$val .= SSViewer::get_base_tag($val);';
	}

	/**
	 * This is an open block handler, for the <% current_page %> tag
	 */
	function OpenBlock_Handle_Current_page(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Current_page takes no arguments', $this);
		return '$val .= $_SERVER[SCRIPT_URL];';
	}
	
	/* MismatchedEndBlock: '<%' < 'end_' Word > '%>' */
	function match_MismatchedEndBlock ($substack = array()) {
		$result = $this->construct( "MismatchedEndBlock" );
		$_295 = NULL;
		do {
			$_286 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_286->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_295 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_289 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_289->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_295 = FALSE; break; }
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_295 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_293 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_293->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_295 = FALSE; break; }
			$_295 = TRUE; break;
		}
		while(0);
		if( $_295 === TRUE ) {
			return $this->finalise( "MismatchedEndBlock", $result );
		}
		if( $_295 === FALSE) { return FALSE; }
	}



	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_'.$blockname.' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
	function match_MalformedOpenTag ($substack = array()) {
		$result = $this->construct( "MalformedOpenTag" );
		$_312 = NULL;
		do {
			$_297 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_297->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_312 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_300 = $result;
			$pos_300 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_300;
				$this->pos = $pos_300;
				$_312 = FALSE; break;
			}
			else {
				$result = $res_300;
				$this->pos = $pos_300;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_312 = FALSE; break; }
			$res_311 = $result;
			$pos_311 = $this->pos;
			$_310 = NULL;
			do {
				$res_306 = $result;
				$pos_306 = $this->pos;
				$_305 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_305 = FALSE; break; }
					$key = "BlockArguments"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_305 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_305 = FALSE; break; }
					$_305 = TRUE; break;
				}
				while(0);
				if( $_305 === FALSE) {
					$result = $res_306;
					$this->pos = $pos_306;
					unset( $res_306 );
					unset( $pos_306 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_308 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_308->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_310 = FALSE; break; }
				$_310 = TRUE; break;
			}
			while(0);
			if( $_310 === TRUE ) {
				$result = $res_311;
				$this->pos = $pos_311;
				$_312 = FALSE; break;
			}
			if( $_310 === FALSE) {
				$result = $res_311;
				$this->pos = $pos_311;
			}
			$_312 = TRUE; break;
		}
		while(0);
		if( $_312 === TRUE ) {
			return $this->finalise( "MalformedOpenTag", $result );
		}
		if( $_312 === FALSE) { return FALSE; }
	}



	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
	}
	
	/* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
	function match_MalformedCloseTag ($substack = array()) {
		$result = $this->construct( "MalformedCloseTag" );
		$_328 = NULL;
		do {
			$_314 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_314->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_328 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$substack[] = $result;
			$result = $this->construct( "Tag" );
			$_320 = NULL;
			do {
				$_317 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_317->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_320 = FALSE; break; }
				$key = "Word"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_320 = FALSE; break; }
				$_320 = TRUE; break;
			}
			while(0);
			if( $_320 === TRUE ) {
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_320 === FALSE) {
				$result = array_pop( $substack ) ;
				$_328 = FALSE; break;
			}
			$res_327 = $result;
			$pos_327 = $this->pos;
			$_326 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_324 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_324->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_326 = FALSE; break; }
				$_326 = TRUE; break;
			}
			while(0);
			if( $_326 === TRUE ) {
				$result = $res_327;
				$this->pos = $pos_327;
				$_328 = FALSE; break;
			}
			if( $_326 === FALSE) {
				$result = $res_327;
				$this->pos = $pos_327;
			}
			$_328 = TRUE; break;
		}
		while(0);
		if( $_328 === TRUE ) {
			return $this->finalise( "MalformedCloseTag", $result );
		}
		if( $_328 === FALSE) { return FALSE; }
	}



	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an argument to one?", $this);
	}
	
	/* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
	function match_MalformedBlock ($substack = array()) {
		$result = $this->construct( "MalformedBlock" );
		$_333 = NULL;
		do {
			$res_330 = $result;
			$pos_330 = $this->pos;
			$key = "MalformedOpenTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedOpenTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_333 = TRUE; break;
			}
			$result = $res_330;
			$this->pos = $pos_330;
			$key = "MalformedCloseTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedCloseTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_333 = TRUE; break;
			}
			$result = $res_330;
			$this->pos = $pos_330;
			$_333 = FALSE; break;
		}
		while(0);
		if( $_333 === TRUE ) {
			return $this->finalise( "MalformedBlock", $result );
		}
		if( $_333 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /./)+ "--%>" */
	function match_Comment ($substack = array()) {
		$result = $this->construct( "Comment" );
		$_345 = NULL;
		do {
			$_335 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_335->expand("<%--") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_345 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_342 = $result;
				$pos_342 = $this->pos;
				$_341 = NULL;
				do {
					$res_338 = $result;
					$pos_338 = $this->pos;
					$_337 = new ParserExpression( $this, $substack, $result );
					if (( $subres = $this->literal( $_337->expand("--%>") ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_338;
						$this->pos = $pos_338;
						$_341 = FALSE; break;
					}
					else {
						$result = $res_338;
						$this->pos = $pos_338;
					}
					$_339 = new ParserExpression( $this, $substack, $result );
					if (( $subres = $this->rx( $_339->expand('/./') ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_341 = FALSE; break; }
					$_341 = TRUE; break;
				}
				while(0);
				if( $_341 === FALSE) {
					$result = $res_342;
					$this->pos = $pos_342;
					unset( $res_342 );
					unset( $pos_342 );
					break;
				}
				$count += 1;
			}
			if ($count > 0) {  }
			else { $_345 = FALSE; break; }
			$_343 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_343->expand("--%>") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_345 = FALSE; break; }
			$_345 = TRUE; break;
		}
		while(0);
		if( $_345 === TRUE ) {
			return $this->finalise( "Comment", $result );
		}
		if( $_345 === FALSE) { return FALSE; }
	}



	function Comment__construct(&$res) {
		$res['php'] = '';
	}
	
	/* Text: /
(
(\\.) |              # Any escaped character
([^<${]) |           # Any character that isn't <, $ or {
(<[^%]) |            # < if not followed by %
($[^A-Za-z_]) |      # $ if not followed by A-Z, a-z or _
({[^$]) |            # { if not followed by $
({$[^A-Za-z_])       # {$ if not followed A-Z, a-z or _
)+
/ */
	function match_Text ($substack = array()) {
		$result = array("name"=>"Text", "text"=>"");
		$_347 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_347->expand('/
(
(\\\\.) |              # Any escaped character
([^<${]) |           # Any character that isn\'t <, $ or {
(<[^%]) |            # < if not followed by %
($[^A-Za-z_]) |      # $ if not followed by A-Z, a-z or _
({[^$]) |            # { if not followed by $
({$[^A-Za-z_])       # {$ if not followed A-Z, a-z or _
)+
/') ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $result;
		}
		else { return FALSE; }
	}


	/* Template: (Comment | If | Require | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	function match_Template ($substack = array()) {
		$result = $this->construct( "Template" );
		$count = 0;
		while (true) {
			$res_379 = $result;
			$pos_379 = $this->pos;
			$_378 = NULL;
			do {
				$_376 = NULL;
				do {
					$res_349 = $result;
					$pos_349 = $this->pos;
					$key = "Comment"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comment(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_376 = TRUE; break;
					}
					$result = $res_349;
					$this->pos = $pos_349;
					$_374 = NULL;
					do {
						$res_351 = $result;
						$pos_351 = $this->pos;
						$key = "If"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_If(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_374 = TRUE; break;
						}
						$result = $res_351;
						$this->pos = $pos_351;
						$_372 = NULL;
						do {
							$res_353 = $result;
							$pos_353 = $this->pos;
							$key = "Require"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Require(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_372 = TRUE; break;
							}
							$result = $res_353;
							$this->pos = $pos_353;
							$_370 = NULL;
							do {
								$res_355 = $result;
								$pos_355 = $this->pos;
								$key = "ClosedBlock"; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ClosedBlock(array_merge($substack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_370 = TRUE; break;
								}
								$result = $res_355;
								$this->pos = $pos_355;
								$_368 = NULL;
								do {
									$res_357 = $result;
									$pos_357 = $this->pos;
									$key = "OpenBlock"; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OpenBlock(array_merge($substack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_368 = TRUE; break;
									}
									$result = $res_357;
									$this->pos = $pos_357;
									$_366 = NULL;
									do {
										$res_359 = $result;
										$pos_359 = $this->pos;
										$key = "MalformedBlock"; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedBlock(array_merge($substack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_366 = TRUE; break;
										}
										$result = $res_359;
										$this->pos = $pos_359;
										$_364 = NULL;
										do {
											$res_361 = $result;
											$pos_361 = $this->pos;
											$key = "Injection"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Injection(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_364 = TRUE; break;
											}
											$result = $res_361;
											$this->pos = $pos_361;
											$key = "Text"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Text(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_364 = TRUE; break;
											}
											$result = $res_361;
											$this->pos = $pos_361;
											$_364 = FALSE; break;
										}
										while(0);
										if( $_364 === TRUE ) { $_366 = TRUE; break; }
										$result = $res_359;
										$this->pos = $pos_359;
										$_366 = FALSE; break;
									}
									while(0);
									if( $_366 === TRUE ) { $_368 = TRUE; break; }
									$result = $res_357;
									$this->pos = $pos_357;
									$_368 = FALSE; break;
								}
								while(0);
								if( $_368 === TRUE ) { $_370 = TRUE; break; }
								$result = $res_355;
								$this->pos = $pos_355;
								$_370 = FALSE; break;
							}
							while(0);
							if( $_370 === TRUE ) { $_372 = TRUE; break; }
							$result = $res_353;
							$this->pos = $pos_353;
							$_372 = FALSE; break;
						}
						while(0);
						if( $_372 === TRUE ) { $_374 = TRUE; break; }
						$result = $res_351;
						$this->pos = $pos_351;
						$_374 = FALSE; break;
					}
					while(0);
					if( $_374 === TRUE ) { $_376 = TRUE; break; }
					$result = $res_349;
					$this->pos = $pos_349;
					$_376 = FALSE; break;
				}
				while(0);
				if( $_376 === FALSE) { $_378 = FALSE; break; }
				$_378 = TRUE; break;
			}
			while(0);
			if( $_378 === FALSE) {
				$result = $res_379;
				$this->pos = $pos_379;
				unset( $res_379 );
				unset( $pos_379 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) {
			return $this->finalise( "Template", $result );
		}
		else { return FALSE; }
	}



	function Template__construct(&$res) {
		$res['php'] = '';
	}

	function Template_Text(&$res, $sub) {
		$text = $sub['text'];
		$text = preg_replace(
			'/href\s*\=\s*\"\#/', 
			'href="<?= SSViewer::{dlr}options[\'rewriteHashlinks\'] ? Convert::raw2att( {dlr}_SERVER[\'REQUEST_URI\'] ) : "" ?>#', 
			$text
		);

		// TODO: using heredocs means any left over $ symbols will trigger PHP lookups, as will any escapes
		// Will it break backwards compatibility to use ' quoted strings, and escape just the ' characters?
		
		$res['php'] .=
			'$val .= <<<SSVIEWER' . PHP_EOL .
				$text . PHP_EOL .
			'SSVIEWER;' . PHP_EOL ;				
	}
	
	function Template_STR(&$res, $sub) {
		$res['php'] .= $sub['php'] . PHP_EOL ;
	}
	
	/* TopTemplate: (Comment | If | Require | ClosedBlock | OpenBlock | MalformedBlock | MismatchedEndBlock | Injection | Text)+ */
	function match_TopTemplate ($substack = array()) {
		$result = $this->construct( "TopTemplate" );
		$count = 0;
		while (true) {
			$res_414 = $result;
			$pos_414 = $this->pos;
			$_413 = NULL;
			do {
				$_411 = NULL;
				do {
					$res_380 = $result;
					$pos_380 = $this->pos;
					$key = "Comment"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comment(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_411 = TRUE; break;
					}
					$result = $res_380;
					$this->pos = $pos_380;
					$_409 = NULL;
					do {
						$res_382 = $result;
						$pos_382 = $this->pos;
						$key = "If"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_If(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_409 = TRUE; break;
						}
						$result = $res_382;
						$this->pos = $pos_382;
						$_407 = NULL;
						do {
							$res_384 = $result;
							$pos_384 = $this->pos;
							$key = "Require"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Require(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_407 = TRUE; break;
							}
							$result = $res_384;
							$this->pos = $pos_384;
							$_405 = NULL;
							do {
								$res_386 = $result;
								$pos_386 = $this->pos;
								$key = "ClosedBlock"; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ClosedBlock(array_merge($substack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_405 = TRUE; break;
								}
								$result = $res_386;
								$this->pos = $pos_386;
								$_403 = NULL;
								do {
									$res_388 = $result;
									$pos_388 = $this->pos;
									$key = "OpenBlock"; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OpenBlock(array_merge($substack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_403 = TRUE; break;
									}
									$result = $res_388;
									$this->pos = $pos_388;
									$_401 = NULL;
									do {
										$res_390 = $result;
										$pos_390 = $this->pos;
										$key = "MalformedBlock"; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedBlock(array_merge($substack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_401 = TRUE; break;
										}
										$result = $res_390;
										$this->pos = $pos_390;
										$_399 = NULL;
										do {
											$res_392 = $result;
											$pos_392 = $this->pos;
											$key = "MismatchedEndBlock"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MismatchedEndBlock(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_399 = TRUE; break;
											}
											$result = $res_392;
											$this->pos = $pos_392;
											$_397 = NULL;
											do {
												$res_394 = $result;
												$pos_394 = $this->pos;
												$key = "Injection"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Injection(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_397 = TRUE; break;
												}
												$result = $res_394;
												$this->pos = $pos_394;
												$key = "Text"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Text(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_397 = TRUE; break;
												}
												$result = $res_394;
												$this->pos = $pos_394;
												$_397 = FALSE; break;
											}
											while(0);
											if( $_397 === TRUE ) { $_399 = TRUE; break; }
											$result = $res_392;
											$this->pos = $pos_392;
											$_399 = FALSE; break;
										}
										while(0);
										if( $_399 === TRUE ) { $_401 = TRUE; break; }
										$result = $res_390;
										$this->pos = $pos_390;
										$_401 = FALSE; break;
									}
									while(0);
									if( $_401 === TRUE ) { $_403 = TRUE; break; }
									$result = $res_388;
									$this->pos = $pos_388;
									$_403 = FALSE; break;
								}
								while(0);
								if( $_403 === TRUE ) { $_405 = TRUE; break; }
								$result = $res_386;
								$this->pos = $pos_386;
								$_405 = FALSE; break;
							}
							while(0);
							if( $_405 === TRUE ) { $_407 = TRUE; break; }
							$result = $res_384;
							$this->pos = $pos_384;
							$_407 = FALSE; break;
						}
						while(0);
						if( $_407 === TRUE ) { $_409 = TRUE; break; }
						$result = $res_382;
						$this->pos = $pos_382;
						$_409 = FALSE; break;
					}
					while(0);
					if( $_409 === TRUE ) { $_411 = TRUE; break; }
					$result = $res_380;
					$this->pos = $pos_380;
					$_411 = FALSE; break;
				}
				while(0);
				if( $_411 === FALSE) { $_413 = FALSE; break; }
				$_413 = TRUE; break;
			}
			while(0);
			if( $_413 === FALSE) {
				$result = $res_414;
				$this->pos = $pos_414;
				unset( $res_414 );
				unset( $pos_414 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) {
			return $this->finalise( "TopTemplate", $result );
		}
		else { return FALSE; }
	}



	
	/**
	 * The TopTemplate also includes the opening stanza to start off the template
	 */
	function TopTemplate__construct(&$res) {
		$res['php'] = "<?php" . PHP_EOL;
	}

	/**
	 * But otherwise handles producing the php the same as every other template block
	 */
	function TopTemplate_Text(&$res, $sub) { return $this->Template_Text($res, $sub); }
	function TopTemplate_STR(&$res, $sub) { return $this->Template_STR($res, $sub); }

	
	/******************
	 * Here ends the parser itself. Below are utility methods to use the parser
	 */
	
	/**
	 * Compiles some passed template source code into the php code that will execute as per the template source.
	 * 
	 * @static
	 * @throws SSTemplateParseException
	 * @param  $string - The source of the template
	 * @param string $templateName - The name of the template, normally the filename the template source was loaded from
	 * @param bool $includeDebuggingComments - True is debugging comments should be included in the output
	 * @return mixed|string - The php that, when executed (via include or exec) will behave as per the template source
	 */
	static function compileString($string, $templateName = "", $includeDebuggingComments=false) {
		// Construct a parser instance
		$parser = new SSTemplateParser($string);
		$parser->includeDebuggingComments = $includeDebuggingComments;

		// Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF (and other encodings) properly
		if(substr($string, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $parser->pos = 3;
		
		// Match the source against the parser
		$result =  $parser->match_TopTemplate();
		if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $parser);

		// Get the result
		$code = $result['php'];
		
		// Include top level debugging comments if desired
		if($includeDebuggingComments && $templateName && stripos($code, "<?xml") === false) {
			// If this template is a full HTML page, then put the comments just inside the HTML tag to prevent any IE glitches
			if(stripos($code, "<html") !== false) {
				$code = preg_replace('/(<html[^>]*>)/i', "\\1<!-- template $templateName -->", $code);
				$code = preg_replace('/(<\/html[^>]*>)/i', "<!-- end template $templateName -->\\1", $code);
			} else {
				$code = "<!-- template $templateName -->\n" . $code . "\n<!-- end template $templateName -->";
			}
		}	
		
		return $code;
	}
	
	/**
	 * Compiles some file that contains template source code, and returns the php code that will execute as per that
	 * source
	 * 
	 * @static
	 * @param  $template - A file path that contains template source code
	 * @return mixed|string - The php that, when executed (via include or exec) will behave as per the template source
	 */
	static function compileFile($template) {
		return self::compileString(file_get_contents($template), $template);
	}
}
