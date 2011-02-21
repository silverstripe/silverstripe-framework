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
		$res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text']) . "'";
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
		$res['php'] = "'" . str_replace("'", "\\'", $sub['text']) . "'";
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
   
	/* OldTPart: "_t" < "(" < QuotedString (< "," < CallArguments)? > ")" */
	function match_OldTPart ($substack = array()) {
		$result = $this->construct( "OldTPart" );
		$_222 = NULL;
		do {
			$_208 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_208->expand("_t") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_222 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == "(") {
				$this->pos += 1;
				$result["text"] .= "(";
			}
			else { $_222 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "QuotedString"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_QuotedString(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_222 = FALSE; break; }
			$res_219 = $result;
			$pos_219 = $this->pos;
			$_218 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ",") {
					$this->pos += 1;
					$result["text"] .= ",";
				}
				else { $_218 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$key = "CallArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_CallArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_218 = FALSE; break; }
				$_218 = TRUE; break;
			}
			while(0);
			if( $_218 === FALSE) {
				$result = $res_219;
				$this->pos = $pos_219;
				unset( $res_219 );
				unset( $pos_219 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ")") {
				$this->pos += 1;
				$result["text"] .= ")";
			}
			else { $_222 = FALSE; break; }
			$_222 = TRUE; break;
		}
		while(0);
		if( $_222 === TRUE ) {
			return $this->finalise( "OldTPart", $result );
		}
		if( $_222 === FALSE) { return FALSE; }
	}



	function OldTPart__construct(&$res) {
		$res['php'] = "_t(";
	}
	
	function OldTPart_QuotedString(&$res, $sub) {
		$entity = $sub['String']['text'];
		if (strpos($entity, '.') === false) {
			$res['php'] .= "\$scope->XML_val('I18NNamespace').'.$entity'";
		}
		else {
			$res['php'] .= "'$entity'";
		}
	}
	
	function OldTPart_CallArguments(&$res, $sub) {
		$res['php'] .= ',' . $sub['php'];
	}

	function OldTPart__finalise(&$res) {
		$res['php'] .= ')';
	}
	
	/* OldTTag: "<%" < OldTPart > "%>" */
	function match_OldTTag ($substack = array()) {
		$result = $this->construct( "OldTTag" );
		$_231 = NULL;
		do {
			$_224 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_224->expand("<%") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_231 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "OldTPart"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OldTPart(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_231 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_229 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_229->expand("%>") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_231 = FALSE; break; }
			$_231 = TRUE; break;
		}
		while(0);
		if( $_231 === TRUE ) {
			return $this->finalise( "OldTTag", $result );
		}
		if( $_231 === FALSE) { return FALSE; }
	}



	function OldTTag_OldTPart(&$res, $sub) {
		$res['php'] = $sub['php'];
	}
	 	  
	/* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>"  */
	function match_OldSprintfTag ($substack = array()) {
		$result = $this->construct( "OldSprintfTag" );
		$_251 = NULL;
		do {
			$_233 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_233->expand("<%") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_236 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_236->expand("sprintf") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == "(") {
				$this->pos += 1;
				$result["text"] .= "(";
			}
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "OldTPart"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OldTPart(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ",") {
				$this->pos += 1;
				$result["text"] .= ",";
			}
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "CallArguments"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_CallArguments(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ")") {
				$this->pos += 1;
				$result["text"] .= ")";
			}
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_249 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_249->expand("%>") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_251 = FALSE; break; }
			$_251 = TRUE; break;
		}
		while(0);
		if( $_251 === TRUE ) {
			return $this->finalise( "OldSprintfTag", $result );
		}
		if( $_251 === FALSE) { return FALSE; }
	}



	function OldSprintfTag__construct(&$res) {
		$res['php'] = "sprintf(";
	}
	
	function OldSprintfTag_OldTPart(&$res, $sub) {
		$res['php'] .= $sub['php'];
	}

	function OldSprintfTag_CallArguments(&$res, $sub) {
		$res['php'] .= ',' . $sub['php'] . ')';
	}
	
	/* OldI18NTag: OldSprintfTag | OldTTag */
	function match_OldI18NTag ($substack = array()) {
		$result = $this->construct( "OldI18NTag" );
		$_256 = NULL;
		do {
			$res_253 = $result;
			$pos_253 = $this->pos;
			$key = "OldSprintfTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OldSprintfTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_256 = TRUE; break;
			}
			$result = $res_253;
			$this->pos = $pos_253;
			$key = "OldTTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OldTTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_256 = TRUE; break;
			}
			$result = $res_253;
			$this->pos = $pos_253;
			$_256 = FALSE; break;
		}
		while(0);
		if( $_256 === TRUE ) {
			return $this->finalise( "OldI18NTag", $result );
		}
		if( $_256 === FALSE) { return FALSE; }
	}



	function OldI18NTag_STR(&$res, $sub) {
		$res['php'] = '$val .= ' . $sub['php'] . ';';
	}
	
	/* BlockArguments: :Argument ( < "," < :Argument)*  */
	function match_BlockArguments ($substack = array()) {
		$result = $this->construct( "BlockArguments" );
		$_265 = NULL;
		do {
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_265 = FALSE; break; }
			while (true) {
				$res_264 = $result;
				$pos_264 = $this->pos;
				$_263 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ",") {
						$this->pos += 1;
						$result["text"] .= ",";
					}
					else { $_263 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "Argument"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_263 = FALSE; break; }
					$_263 = TRUE; break;
				}
				while(0);
				if( $_263 === FALSE) {
					$result = $res_264;
					$this->pos = $pos_264;
					unset( $res_264 );
					unset( $pos_264 );
					break;
				}
			}
			$_265 = TRUE; break;
		}
		while(0);
		if( $_265 === TRUE ) {
			return $this->finalise( "BlockArguments", $result );
		}
		if( $_265 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require") ] ) */
	function match_NotBlockTag ($substack = array()) {
		$result = $this->construct( "NotBlockTag" );
		$_292 = NULL;
		do {
			$res_267 = $result;
			$pos_267 = $this->pos;
			$_268 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_268->expand("end_") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_292 = TRUE; break;
			}
			$result = $res_267;
			$this->pos = $pos_267;
			$_290 = NULL;
			do {
				$_287 = NULL;
				do {
					$_285 = NULL;
					do {
						$res_270 = $result;
						$pos_270 = $this->pos;
						$_271 = new ParserExpression( $this, $substack, $result );
						if (( $subres = $this->literal( $_271->expand("if") ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_285 = TRUE; break;
						}
						$result = $res_270;
						$this->pos = $pos_270;
						$_283 = NULL;
						do {
							$res_273 = $result;
							$pos_273 = $this->pos;
							$_274 = new ParserExpression( $this, $substack, $result );
							if (( $subres = $this->literal( $_274->expand("else_if") ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_283 = TRUE; break;
							}
							$result = $res_273;
							$this->pos = $pos_273;
							$_281 = NULL;
							do {
								$res_276 = $result;
								$pos_276 = $this->pos;
								$_277 = new ParserExpression( $this, $substack, $result );
								if (( $subres = $this->literal( $_277->expand("else") ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_281 = TRUE; break;
								}
								$result = $res_276;
								$this->pos = $pos_276;
								$_279 = new ParserExpression( $this, $substack, $result );
								if (( $subres = $this->literal( $_279->expand("require") ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_281 = TRUE; break;
								}
								$result = $res_276;
								$this->pos = $pos_276;
								$_281 = FALSE; break;
							}
							while(0);
							if( $_281 === TRUE ) { $_283 = TRUE; break; }
							$result = $res_273;
							$this->pos = $pos_273;
							$_283 = FALSE; break;
						}
						while(0);
						if( $_283 === TRUE ) { $_285 = TRUE; break; }
						$result = $res_270;
						$this->pos = $pos_270;
						$_285 = FALSE; break;
					}
					while(0);
					if( $_285 === FALSE) { $_287 = FALSE; break; }
					$_287 = TRUE; break;
				}
				while(0);
				if( $_287 === FALSE) { $_290 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_290 = FALSE; break; }
				$_290 = TRUE; break;
			}
			while(0);
			if( $_290 === TRUE ) { $_292 = TRUE; break; }
			$result = $res_267;
			$this->pos = $pos_267;
			$_292 = FALSE; break;
		}
		while(0);
		if( $_292 === TRUE ) {
			return $this->finalise( "NotBlockTag", $result );
		}
		if( $_292 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' :Template? '<%' < 'end_' '$BlockName' > '%>' */
	function match_ClosedBlock ($substack = array()) {
		$result = $this->construct( "ClosedBlock" );
		$_319 = NULL;
		do {
			$_294 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_294->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_297 = $result;
			$pos_297 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_297;
				$this->pos = $pos_297;
				$_319 = FALSE; break;
			}
			else {
				$result = $res_297;
				$this->pos = $pos_297;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_319 = FALSE; break; }
			$res_303 = $result;
			$pos_303 = $this->pos;
			$_302 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_302 = FALSE; break; }
				$key = "BlockArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_302 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_302 = FALSE; break; }
				$_302 = TRUE; break;
			}
			while(0);
			if( $_302 === FALSE) {
				$result = $res_303;
				$this->pos = $pos_303;
				unset( $res_303 );
				unset( $pos_303 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$substack[] = $result;
			$result = $this->construct( "Zap" );
			$_305 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_305->expand('%>') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Zap' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_319 = FALSE; break;
			}
			$res_308 = $result;
			$pos_308 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_308;
				$this->pos = $pos_308;
				unset( $res_308 );
				unset( $pos_308 );
			}
			$_309 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_309->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_312 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_312->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			$_314 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_314->expand('$BlockName') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_317 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_317->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			$_319 = TRUE; break;
		}
		while(0);
		if( $_319 === TRUE ) {
			return $this->finalise( "ClosedBlock", $result );
		}
		if( $_319 === FALSE) { return FALSE; }
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
		$_334 = NULL;
		do {
			$_321 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_321->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_334 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_324 = $result;
			$pos_324 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_324;
				$this->pos = $pos_324;
				$_334 = FALSE; break;
			}
			else {
				$result = $res_324;
				$this->pos = $pos_324;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_334 = FALSE; break; }
			$res_330 = $result;
			$pos_330 = $this->pos;
			$_329 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_329 = FALSE; break; }
				$key = "BlockArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_329 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_329 = FALSE; break; }
				$_329 = TRUE; break;
			}
			while(0);
			if( $_329 === FALSE) {
				$result = $res_330;
				$this->pos = $pos_330;
				unset( $res_330 );
				unset( $pos_330 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_332 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_332->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_334 = FALSE; break; }
			$_334 = TRUE; break;
		}
		while(0);
		if( $_334 === TRUE ) {
			return $this->finalise( "OpenBlock", $result );
		}
		if( $_334 === FALSE) { return FALSE; }
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
		$_345 = NULL;
		do {
			$_336 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_336->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_345 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_339 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_339->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_345 = FALSE; break; }
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_345 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_343 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_343->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_345 = FALSE; break; }
			$_345 = TRUE; break;
		}
		while(0);
		if( $_345 === TRUE ) {
			return $this->finalise( "MismatchedEndBlock", $result );
		}
		if( $_345 === FALSE) { return FALSE; }
	}



	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_'.$blockname.' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
	function match_MalformedOpenTag ($substack = array()) {
		$result = $this->construct( "MalformedOpenTag" );
		$_362 = NULL;
		do {
			$_347 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_347->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_362 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_350 = $result;
			$pos_350 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_350;
				$this->pos = $pos_350;
				$_362 = FALSE; break;
			}
			else {
				$result = $res_350;
				$this->pos = $pos_350;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_362 = FALSE; break; }
			$res_361 = $result;
			$pos_361 = $this->pos;
			$_360 = NULL;
			do {
				$res_356 = $result;
				$pos_356 = $this->pos;
				$_355 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_355 = FALSE; break; }
					$key = "BlockArguments"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_355 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_355 = FALSE; break; }
					$_355 = TRUE; break;
				}
				while(0);
				if( $_355 === FALSE) {
					$result = $res_356;
					$this->pos = $pos_356;
					unset( $res_356 );
					unset( $pos_356 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_358 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_358->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_360 = FALSE; break; }
				$_360 = TRUE; break;
			}
			while(0);
			if( $_360 === TRUE ) {
				$result = $res_361;
				$this->pos = $pos_361;
				$_362 = FALSE; break;
			}
			if( $_360 === FALSE) {
				$result = $res_361;
				$this->pos = $pos_361;
			}
			$_362 = TRUE; break;
		}
		while(0);
		if( $_362 === TRUE ) {
			return $this->finalise( "MalformedOpenTag", $result );
		}
		if( $_362 === FALSE) { return FALSE; }
	}



	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
	}
	
	/* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
	function match_MalformedCloseTag ($substack = array()) {
		$result = $this->construct( "MalformedCloseTag" );
		$_378 = NULL;
		do {
			$_364 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_364->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_378 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$substack[] = $result;
			$result = $this->construct( "Tag" );
			$_370 = NULL;
			do {
				$_367 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_367->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_370 = FALSE; break; }
				$key = "Word"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_370 = FALSE; break; }
				$_370 = TRUE; break;
			}
			while(0);
			if( $_370 === TRUE ) {
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_370 === FALSE) {
				$result = array_pop( $substack ) ;
				$_378 = FALSE; break;
			}
			$res_377 = $result;
			$pos_377 = $this->pos;
			$_376 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_374 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_374->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_376 = FALSE; break; }
				$_376 = TRUE; break;
			}
			while(0);
			if( $_376 === TRUE ) {
				$result = $res_377;
				$this->pos = $pos_377;
				$_378 = FALSE; break;
			}
			if( $_376 === FALSE) {
				$result = $res_377;
				$this->pos = $pos_377;
			}
			$_378 = TRUE; break;
		}
		while(0);
		if( $_378 === TRUE ) {
			return $this->finalise( "MalformedCloseTag", $result );
		}
		if( $_378 === FALSE) { return FALSE; }
	}



	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an argument to one?", $this);
	}
	
	/* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
	function match_MalformedBlock ($substack = array()) {
		$result = $this->construct( "MalformedBlock" );
		$_383 = NULL;
		do {
			$res_380 = $result;
			$pos_380 = $this->pos;
			$key = "MalformedOpenTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedOpenTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_383 = TRUE; break;
			}
			$result = $res_380;
			$this->pos = $pos_380;
			$key = "MalformedCloseTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedCloseTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_383 = TRUE; break;
			}
			$result = $res_380;
			$this->pos = $pos_380;
			$_383 = FALSE; break;
		}
		while(0);
		if( $_383 === TRUE ) {
			return $this->finalise( "MalformedBlock", $result );
		}
		if( $_383 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /./)+ "--%>" */
	function match_Comment ($substack = array()) {
		$result = $this->construct( "Comment" );
		$_395 = NULL;
		do {
			$_385 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_385->expand("<%--") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_395 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_392 = $result;
				$pos_392 = $this->pos;
				$_391 = NULL;
				do {
					$res_388 = $result;
					$pos_388 = $this->pos;
					$_387 = new ParserExpression( $this, $substack, $result );
					if (( $subres = $this->literal( $_387->expand("--%>") ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_388;
						$this->pos = $pos_388;
						$_391 = FALSE; break;
					}
					else {
						$result = $res_388;
						$this->pos = $pos_388;
					}
					$_389 = new ParserExpression( $this, $substack, $result );
					if (( $subres = $this->rx( $_389->expand('/./') ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_391 = FALSE; break; }
					$_391 = TRUE; break;
				}
				while(0);
				if( $_391 === FALSE) {
					$result = $res_392;
					$this->pos = $pos_392;
					unset( $res_392 );
					unset( $pos_392 );
					break;
				}
				$count += 1;
			}
			if ($count > 0) {  }
			else { $_395 = FALSE; break; }
			$_393 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_393->expand("--%>") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_395 = FALSE; break; }
			$_395 = TRUE; break;
		}
		while(0);
		if( $_395 === TRUE ) {
			return $this->finalise( "Comment", $result );
		}
		if( $_395 === FALSE) { return FALSE; }
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
		$_397 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_397->expand('/
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


	/* Template: (Comment | If | Require | OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	function match_Template ($substack = array()) {
		$result = $this->construct( "Template" );
		$count = 0;
		while (true) {
			$res_433 = $result;
			$pos_433 = $this->pos;
			$_432 = NULL;
			do {
				$_430 = NULL;
				do {
					$res_399 = $result;
					$pos_399 = $this->pos;
					$key = "Comment"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comment(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_430 = TRUE; break;
					}
					$result = $res_399;
					$this->pos = $pos_399;
					$_428 = NULL;
					do {
						$res_401 = $result;
						$pos_401 = $this->pos;
						$key = "If"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_If(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_428 = TRUE; break;
						}
						$result = $res_401;
						$this->pos = $pos_401;
						$_426 = NULL;
						do {
							$res_403 = $result;
							$pos_403 = $this->pos;
							$key = "Require"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Require(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_426 = TRUE; break;
							}
							$result = $res_403;
							$this->pos = $pos_403;
							$_424 = NULL;
							do {
								$res_405 = $result;
								$pos_405 = $this->pos;
								$key = "OldI18NTag"; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OldI18NTag(array_merge($substack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_424 = TRUE; break;
								}
								$result = $res_405;
								$this->pos = $pos_405;
								$_422 = NULL;
								do {
									$res_407 = $result;
									$pos_407 = $this->pos;
									$key = "ClosedBlock"; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ClosedBlock(array_merge($substack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_422 = TRUE; break;
									}
									$result = $res_407;
									$this->pos = $pos_407;
									$_420 = NULL;
									do {
										$res_409 = $result;
										$pos_409 = $this->pos;
										$key = "OpenBlock"; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OpenBlock(array_merge($substack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_420 = TRUE; break;
										}
										$result = $res_409;
										$this->pos = $pos_409;
										$_418 = NULL;
										do {
											$res_411 = $result;
											$pos_411 = $this->pos;
											$key = "MalformedBlock"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedBlock(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_418 = TRUE; break;
											}
											$result = $res_411;
											$this->pos = $pos_411;
											$_416 = NULL;
											do {
												$res_413 = $result;
												$pos_413 = $this->pos;
												$key = "Injection"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Injection(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_416 = TRUE; break;
												}
												$result = $res_413;
												$this->pos = $pos_413;
												$key = "Text"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Text(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_416 = TRUE; break;
												}
												$result = $res_413;
												$this->pos = $pos_413;
												$_416 = FALSE; break;
											}
											while(0);
											if( $_416 === TRUE ) { $_418 = TRUE; break; }
											$result = $res_411;
											$this->pos = $pos_411;
											$_418 = FALSE; break;
										}
										while(0);
										if( $_418 === TRUE ) { $_420 = TRUE; break; }
										$result = $res_409;
										$this->pos = $pos_409;
										$_420 = FALSE; break;
									}
									while(0);
									if( $_420 === TRUE ) { $_422 = TRUE; break; }
									$result = $res_407;
									$this->pos = $pos_407;
									$_422 = FALSE; break;
								}
								while(0);
								if( $_422 === TRUE ) { $_424 = TRUE; break; }
								$result = $res_405;
								$this->pos = $pos_405;
								$_424 = FALSE; break;
							}
							while(0);
							if( $_424 === TRUE ) { $_426 = TRUE; break; }
							$result = $res_403;
							$this->pos = $pos_403;
							$_426 = FALSE; break;
						}
						while(0);
						if( $_426 === TRUE ) { $_428 = TRUE; break; }
						$result = $res_401;
						$this->pos = $pos_401;
						$_428 = FALSE; break;
					}
					while(0);
					if( $_428 === TRUE ) { $_430 = TRUE; break; }
					$result = $res_399;
					$this->pos = $pos_399;
					$_430 = FALSE; break;
				}
				while(0);
				if( $_430 === FALSE) { $_432 = FALSE; break; }
				$_432 = TRUE; break;
			}
			while(0);
			if( $_432 === FALSE) {
				$result = $res_433;
				$this->pos = $pos_433;
				unset( $res_433 );
				unset( $pos_433 );
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
	
	/* TopTemplate: (Comment | If | Require | OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | MismatchedEndBlock | Injection | Text)+ */
	function match_TopTemplate ($substack = array()) {
		$result = $this->construct( "TopTemplate" );
		$count = 0;
		while (true) {
			$res_472 = $result;
			$pos_472 = $this->pos;
			$_471 = NULL;
			do {
				$_469 = NULL;
				do {
					$res_434 = $result;
					$pos_434 = $this->pos;
					$key = "Comment"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comment(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_469 = TRUE; break;
					}
					$result = $res_434;
					$this->pos = $pos_434;
					$_467 = NULL;
					do {
						$res_436 = $result;
						$pos_436 = $this->pos;
						$key = "If"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_If(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_467 = TRUE; break;
						}
						$result = $res_436;
						$this->pos = $pos_436;
						$_465 = NULL;
						do {
							$res_438 = $result;
							$pos_438 = $this->pos;
							$key = "Require"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Require(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_465 = TRUE; break;
							}
							$result = $res_438;
							$this->pos = $pos_438;
							$_463 = NULL;
							do {
								$res_440 = $result;
								$pos_440 = $this->pos;
								$key = "OldI18NTag"; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OldI18NTag(array_merge($substack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_463 = TRUE; break;
								}
								$result = $res_440;
								$this->pos = $pos_440;
								$_461 = NULL;
								do {
									$res_442 = $result;
									$pos_442 = $this->pos;
									$key = "ClosedBlock"; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ClosedBlock(array_merge($substack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_461 = TRUE; break;
									}
									$result = $res_442;
									$this->pos = $pos_442;
									$_459 = NULL;
									do {
										$res_444 = $result;
										$pos_444 = $this->pos;
										$key = "OpenBlock"; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OpenBlock(array_merge($substack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_459 = TRUE; break;
										}
										$result = $res_444;
										$this->pos = $pos_444;
										$_457 = NULL;
										do {
											$res_446 = $result;
											$pos_446 = $this->pos;
											$key = "MalformedBlock"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedBlock(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_457 = TRUE; break;
											}
											$result = $res_446;
											$this->pos = $pos_446;
											$_455 = NULL;
											do {
												$res_448 = $result;
												$pos_448 = $this->pos;
												$key = "MismatchedEndBlock"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MismatchedEndBlock(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_455 = TRUE; break;
												}
												$result = $res_448;
												$this->pos = $pos_448;
												$_453 = NULL;
												do {
													$res_450 = $result;
													$pos_450 = $this->pos;
													$key = "Injection"; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Injection(array_merge($substack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_453 = TRUE; break;
													}
													$result = $res_450;
													$this->pos = $pos_450;
													$key = "Text"; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Text(array_merge($substack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_453 = TRUE; break;
													}
													$result = $res_450;
													$this->pos = $pos_450;
													$_453 = FALSE; break;
												}
												while(0);
												if( $_453 === TRUE ) { $_455 = TRUE; break; }
												$result = $res_448;
												$this->pos = $pos_448;
												$_455 = FALSE; break;
											}
											while(0);
											if( $_455 === TRUE ) { $_457 = TRUE; break; }
											$result = $res_446;
											$this->pos = $pos_446;
											$_457 = FALSE; break;
										}
										while(0);
										if( $_457 === TRUE ) { $_459 = TRUE; break; }
										$result = $res_444;
										$this->pos = $pos_444;
										$_459 = FALSE; break;
									}
									while(0);
									if( $_459 === TRUE ) { $_461 = TRUE; break; }
									$result = $res_442;
									$this->pos = $pos_442;
									$_461 = FALSE; break;
								}
								while(0);
								if( $_461 === TRUE ) { $_463 = TRUE; break; }
								$result = $res_440;
								$this->pos = $pos_440;
								$_463 = FALSE; break;
							}
							while(0);
							if( $_463 === TRUE ) { $_465 = TRUE; break; }
							$result = $res_438;
							$this->pos = $pos_438;
							$_465 = FALSE; break;
						}
						while(0);
						if( $_465 === TRUE ) { $_467 = TRUE; break; }
						$result = $res_436;
						$this->pos = $pos_436;
						$_467 = FALSE; break;
					}
					while(0);
					if( $_467 === TRUE ) { $_469 = TRUE; break; }
					$result = $res_434;
					$this->pos = $pos_434;
					$_469 = FALSE; break;
				}
				while(0);
				if( $_469 === FALSE) { $_471 = FALSE; break; }
				$_471 = TRUE; break;
			}
			while(0);
			if( $_471 === FALSE) {
				$result = $res_472;
				$this->pos = $pos_472;
				unset( $res_472 );
				unset( $pos_472 );
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
