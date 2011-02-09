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
*/
class SSTemplateParser extends Parser {

	protected $includeDebuggingComments = false;
	
	function construct($name) {
		$result = parent::construct($name);
		$result['tags'] = array();
		return $result;
	}
	
	function DLRBlockName() {
		return '-none-';
	}
	
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


	/* Arguments: :Argument ( < "," < :Argument )* */
	function match_Arguments ($substack = array()) {
		$result = $this->construct( "Arguments" );
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
			return $this->finalise( "Arguments", $result );
		}
		if( $_13 === FALSE) { return FALSE; }
	}




	/** Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert strings to numbers when needed */
	function Arguments_Argument(&$res, $sub) {
		if (isset($res['php'])) $res['php'] .= ', ';
		else $res['php'] = '';
		
		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] : $sub['php'];
	}

	/* Call: Method:Word ( "(" < :Arguments? > ")" )? */
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
				$key = "Arguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Arguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Arguments" );
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
		$res['php'] = '$item';
		$res['LookupSteps'] = array();
	}
	
	function Lookup_AddLookupStep(&$res, $sub, $method) {
		$res['LookupSteps'][] = $sub;
		
		$property = $sub['Call']['Method']['text'];
		
		if (isset($sub['Call']['Arguments']) && $arguments = $sub['Call']['Arguments']['php']) {
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
		$this->Lookup_AddLookupStep($res, $sub, 'XML_val');
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
		$res['php'] = '$val .= '. $sub['Lookup']['php'] . ';';
	}

	/* DollarMarkedLookup: BracketInjection | SimpleInjection */
	function match_DollarMarkedLookup ($substack = array()) {
		$result = $this->construct( "DollarMarkedLookup" );
		$_61 = NULL;
		do {
			$res_58 = $result;
			$pos_58 = $this->pos;
			$key = "BracketInjection"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BracketInjection(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_61 = TRUE; break;
			}
			$result = $res_58;
			$this->pos = $pos_58;
			$key = "SimpleInjection"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_SimpleInjection(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_61 = TRUE; break;
			}
			$result = $res_58;
			$this->pos = $pos_58;
			$_61 = FALSE; break;
		}
		while(0);
		if( $_61 === TRUE ) {
			return $this->finalise( "DollarMarkedLookup", $result );
		}
		if( $_61 === FALSE) { return FALSE; }
	}



	function DollarMarkedLookup_STR(&$res, $sub) {
		$res['Lookup'] = $sub['Lookup'];
	}

	/* QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q' */
	function match_QuotedString ($substack = array()) {
		$result = $this->construct( "QuotedString" );
		$_71 = NULL;
		do {
			$substack[] = $result;
			$result = $this->construct( "q" );
			$_63 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->rx( $_63->expand('/[\'"]/') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'q' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_71 = FALSE; break;
			}
			$substack[] = $result;
			$result = $this->construct( "String" );
			$_66 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->rx( $_66->expand('/ (\\\\\\\\ | \\\\. | [^$q\\\\])* /') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'String' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_71 = FALSE; break;
			}
			$_69 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_69->expand('$q') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_71 = FALSE; break; }
			$_71 = TRUE; break;
		}
		while(0);
		if( $_71 === TRUE ) {
			return $this->finalise( "QuotedString", $result );
		}
		if( $_71 === FALSE) { return FALSE; }
	}


	/* FreeString: /[^,)%!=|&]+/ */
	function match_FreeString ($substack = array()) {
		$result = array("name"=>"FreeString", "text"=>"");
		$_73 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_73->expand('/[^,)%!=|&]+/') ) ) !== FALSE) {
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
		$_92 = NULL;
		do {
			$res_75 = $result;
			$pos_75 = $this->pos;
			$key = "DollarMarkedLookup"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_DollarMarkedLookup(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "DollarMarkedLookup" );
				$_92 = TRUE; break;
			}
			$result = $res_75;
			$this->pos = $pos_75;
			$_90 = NULL;
			do {
				$res_77 = $result;
				$pos_77 = $this->pos;
				$key = "QuotedString"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_QuotedString(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "QuotedString" );
					$_90 = TRUE; break;
				}
				$result = $res_77;
				$this->pos = $pos_77;
				$_88 = NULL;
				do {
					$res_79 = $result;
					$pos_79 = $this->pos;
					$_85 = NULL;
					do {
						$key = "Lookup"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Lookup(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
						}
						else { $_85 = FALSE; break; }
						$res_84 = $result;
						$pos_84 = $this->pos;
						$_83 = NULL;
						do {
							if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
							$key = "FreeString"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_FreeString(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_83 = FALSE; break; }
							$_83 = TRUE; break;
						}
						while(0);
						if( $_83 === TRUE ) {
							$result = $res_84;
							$this->pos = $pos_84;
							$_85 = FALSE; break;
						}
						if( $_83 === FALSE) {
							$result = $res_84;
							$this->pos = $pos_84;
						}
						$_85 = TRUE; break;
					}
					while(0);
					if( $_85 === TRUE ) { $_88 = TRUE; break; }
					$result = $res_79;
					$this->pos = $pos_79;
					$key = "FreeString"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_FreeString(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "FreeString" );
						$_88 = TRUE; break;
					}
					$result = $res_79;
					$this->pos = $pos_79;
					$_88 = FALSE; break;
				}
				while(0);
				if( $_88 === TRUE ) { $_90 = TRUE; break; }
				$result = $res_77;
				$this->pos = $pos_77;
				$_90 = FALSE; break;
			}
			while(0);
			if( $_90 === TRUE ) { $_92 = TRUE; break; }
			$result = $res_75;
			$this->pos = $pos_75;
			$_92 = FALSE; break;
		}
		while(0);
		if( $_92 === TRUE ) {
			return $this->finalise( "Argument", $result );
		}
		if( $_92 === FALSE) { return FALSE; }
	}



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
		$_103 = NULL;
		do {
			$res_94 = $result;
			$pos_94 = $this->pos;
			$_95 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_95->expand("==") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_103 = TRUE; break;
			}
			$result = $res_94;
			$this->pos = $pos_94;
			$_101 = NULL;
			do {
				$res_97 = $result;
				$pos_97 = $this->pos;
				$_98 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_98->expand("!=") ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_101 = TRUE; break;
				}
				$result = $res_97;
				$this->pos = $pos_97;
				if (substr($this->string,$this->pos,1) == "=") {
					$this->pos += 1;
					$result["text"] .= "=";
					$_101 = TRUE; break;
				}
				$result = $res_97;
				$this->pos = $pos_97;
				$_101 = FALSE; break;
			}
			while(0);
			if( $_101 === TRUE ) { $_103 = TRUE; break; }
			$result = $res_94;
			$this->pos = $pos_94;
			$_103 = FALSE; break;
		}
		while(0);
		if( $_103 === TRUE ) {
			return $this->finalise( "ComparisonOperator", $result );
		}
		if( $_103 === FALSE) { return FALSE; }
	}


	/* Comparison: Argument < ComparisonOperator > Argument */
	function match_Comparison ($substack = array()) {
		$result = $this->construct( "Comparison" );
		$_110 = NULL;
		do {
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_110 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "ComparisonOperator"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ComparisonOperator(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_110 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_110 = FALSE; break; }
			$_110 = TRUE; break;
		}
		while(0);
		if( $_110 === TRUE ) {
			return $this->finalise( "Comparison", $result );
		}
		if( $_110 === FALSE) { return FALSE; }
	}



	function Comparison_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'default') {
			if (isset($res['php'])) $res['php'] .= $sub['string_php'];
			else $res['php'] = $sub['lookup_php'];
		}	
		else {
			if (!isset($res['php'])) $res['php'] = '';
			$res['php'] .= $sub['php'];
		}
	}

	function Comparison_ComparisonOperator(&$res, $sub) {
		$res['php'] .= ($sub['text'] == '=' ? '==' : $sub['text']);
	}

	/* PresenceCheck: Argument */
	function match_PresenceCheck ($substack = array()) {
		$result = $this->construct( "PresenceCheck" );
		$key = "Argument"; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			return $this->finalise( "PresenceCheck", $result );
		}
		else { return FALSE; }
	}



	function PresenceCheck_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'string') {
			$res['php'] = '((bool)'.$sub['php'].')';
		}
		else {
			$php = ($sub['ArgumentMode'] == 'default' ? $sub['lookup_php'] : $sub['php']);
			// TODO: kinda hacky - maybe we need a way to pass state down the parse chain so
			// Lookup_LastLookupStep and Argument_BareWord can produce hasValue instead of XML_val
			$res['php'] = str_replace('->XML_val', '->hasValue', $php);
		}
	}

	/* IfArgumentPortion: Comparison | PresenceCheck */
	function match_IfArgumentPortion ($substack = array()) {
		$result = $this->construct( "IfArgumentPortion" );
		$_116 = NULL;
		do {
			$res_113 = $result;
			$pos_113 = $this->pos;
			$key = "Comparison"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comparison(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_116 = TRUE; break;
			}
			$result = $res_113;
			$this->pos = $pos_113;
			$key = "PresenceCheck"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_PresenceCheck(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_116 = TRUE; break;
			}
			$result = $res_113;
			$this->pos = $pos_113;
			$_116 = FALSE; break;
		}
		while(0);
		if( $_116 === TRUE ) {
			return $this->finalise( "IfArgumentPortion", $result );
		}
		if( $_116 === FALSE) { return FALSE; }
	}



	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* BooleanOperator: "||" | "&&" */
	function match_BooleanOperator ($substack = array()) {
		$result = $this->construct( "BooleanOperator" );
		$_123 = NULL;
		do {
			$res_118 = $result;
			$pos_118 = $this->pos;
			$_119 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_119->expand("||") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_123 = TRUE; break;
			}
			$result = $res_118;
			$this->pos = $pos_118;
			$_121 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_121->expand("&&") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_123 = TRUE; break;
			}
			$result = $res_118;
			$this->pos = $pos_118;
			$_123 = FALSE; break;
		}
		while(0);
		if( $_123 === TRUE ) {
			return $this->finalise( "BooleanOperator", $result );
		}
		if( $_123 === FALSE) { return FALSE; }
	}


	/* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
	function match_IfArgument ($substack = array()) {
		$result = $this->construct( "IfArgument" );
		$_132 = NULL;
		do {
			$key = "IfArgumentPortion"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgumentPortion(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgumentPortion" );
			}
			else { $_132 = FALSE; break; }
			while (true) {
				$res_131 = $result;
				$pos_131 = $this->pos;
				$_130 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "BooleanOperator"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BooleanOperator(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BooleanOperator" );
					}
					else { $_130 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "IfArgumentPortion"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgumentPortion(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "IfArgumentPortion" );
					}
					else { $_130 = FALSE; break; }
					$_130 = TRUE; break;
				}
				while(0);
				if( $_130 === FALSE) {
					$result = $res_131;
					$this->pos = $pos_131;
					unset( $res_131 );
					unset( $pos_131 );
					break;
				}
			}
			$_132 = TRUE; break;
		}
		while(0);
		if( $_132 === TRUE ) {
			return $this->finalise( "IfArgument", $result );
		}
		if( $_132 === FALSE) { return FALSE; }
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

	/* IfPart: '<%' < 'if' < :IfArgument > '%>' :Template? */
	function match_IfPart ($substack = array()) {
		$result = $this->construct( "IfPart" );
		$_145 = NULL;
		do {
			$_134 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_134->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_145 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_137 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_137->expand('if') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_145 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "IfArgument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_145 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_142 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_142->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_145 = FALSE; break; }
			$res_144 = $result;
			$pos_144 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_144;
				$this->pos = $pos_144;
				unset( $res_144 );
				unset( $pos_144 );
			}
			$_145 = TRUE; break;
		}
		while(0);
		if( $_145 === TRUE ) {
			return $this->finalise( "IfPart", $result );
		}
		if( $_145 === FALSE) { return FALSE; }
	}


	/* ElseIfPart: '<%' < 'else_if' < :IfArgument > '%>' :Template? */
	function match_ElseIfPart ($substack = array()) {
		$result = $this->construct( "ElseIfPart" );
		$_158 = NULL;
		do {
			$_147 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_147->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_158 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_150 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_150->expand('else_if') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_158 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$key = "IfArgument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfArgument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_158 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_155 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_155->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_158 = FALSE; break; }
			$res_157 = $result;
			$pos_157 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_157;
				$this->pos = $pos_157;
				unset( $res_157 );
				unset( $pos_157 );
			}
			$_158 = TRUE; break;
		}
		while(0);
		if( $_158 === TRUE ) {
			return $this->finalise( "ElseIfPart", $result );
		}
		if( $_158 === FALSE) { return FALSE; }
	}


	/* ElsePart: '<%' < 'else' > '%>' :Template? */
	function match_ElsePart ($substack = array()) {
		$result = $this->construct( "ElsePart" );
		$_169 = NULL;
		do {
			$_160 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_160->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_169 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_163 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_163->expand('else') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_169 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_166 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_166->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_169 = FALSE; break; }
			$res_168 = $result;
			$pos_168 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_168;
				$this->pos = $pos_168;
				unset( $res_168 );
				unset( $pos_168 );
			}
			$_169 = TRUE; break;
		}
		while(0);
		if( $_169 === TRUE ) {
			return $this->finalise( "ElsePart", $result );
		}
		if( $_169 === FALSE) { return FALSE; }
	}


	/* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
	function match_If ($substack = array()) {
		$result = $this->construct( "If" );
		$_182 = NULL;
		do {
			$key = "IfPart"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_IfPart(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_182 = FALSE; break; }
			while (true) {
				$res_172 = $result;
				$pos_172 = $this->pos;
				$key = "ElseIfPart"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ElseIfPart(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else {
					$result = $res_172;
					$this->pos = $pos_172;
					unset( $res_172 );
					unset( $pos_172 );
					break;
				}
			}
			$res_173 = $result;
			$pos_173 = $this->pos;
			$key = "ElsePart"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ElsePart(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_173;
				$this->pos = $pos_173;
				unset( $res_173 );
				unset( $pos_173 );
			}
			$_174 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_174->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_182 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_177 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_177->expand('end_if') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_182 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_180 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_180->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_182 = FALSE; break; }
			$_182 = TRUE; break;
		}
		while(0);
		if( $_182 === TRUE ) {
			return $this->finalise( "If", $result );
		}
		if( $_182 === FALSE) { return FALSE; }
	}



	function If__construct(&$res) {
		$res['BlockName'] = 'if';
	}
	
	function If_IfPart(&$res, $sub) {
		$res['php'] = 
			'if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				$sub['Template']['php'] . PHP_EOL .
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

	/* Require: '<%' < 'require' [ Call:(Method:Word "(" < :Arguments  > ")") > '%>' */
	function match_Require ($substack = array()) {
		$result = $this->construct( "Require" );
		$_202 = NULL;
		do {
			$_184 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_184->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_202 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_187 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_187->expand('require') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_202 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_202 = FALSE; break; }
			$substack[] = $result;
			$result = $this->construct( "Call" );
			$_196 = NULL;
			do {
				$key = "Word"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Method" );
				}
				else { $_196 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == "(") {
					$this->pos += 1;
					$result["text"] .= "(";
				}
				else { $_196 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$key = "Arguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Arguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Arguments" );
				}
				else { $_196 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ")") {
					$this->pos += 1;
					$result["text"] .= ")";
				}
				else { $_196 = FALSE; break; }
				$_196 = TRUE; break;
			}
			while(0);
			if( $_196 === TRUE ) {
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Call' );
			}
			if( $_196 === FALSE) {
				$result = array_pop( $substack ) ;
				$_202 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_200 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_200->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_202 = FALSE; break; }
			$_202 = TRUE; break;
		}
		while(0);
		if( $_202 === TRUE ) {
			return $this->finalise( "Require", $result );
		}
		if( $_202 === FALSE) { return FALSE; }
	}



	function Require_Call(&$res, $sub) {
		$res['php'] = "Requirements::".$sub['Method']['text'].'('.$sub['Arguments']['php'].');';
	}
	
	/* BlockArguments: :Argument ( < "," < :Argument)*  */
	function match_BlockArguments ($substack = array()) {
		$result = $this->construct( "BlockArguments" );
		$_211 = NULL;
		do {
			$key = "Argument"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_211 = FALSE; break; }
			while (true) {
				$res_210 = $result;
				$pos_210 = $this->pos;
				$_209 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ",") {
						$this->pos += 1;
						$result["text"] .= ",";
					}
					else { $_209 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$key = "Argument"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Argument(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_209 = FALSE; break; }
					$_209 = TRUE; break;
				}
				while(0);
				if( $_209 === FALSE) {
					$result = $res_210;
					$this->pos = $pos_210;
					unset( $res_210 );
					unset( $pos_210 );
					break;
				}
			}
			$_211 = TRUE; break;
		}
		while(0);
		if( $_211 === TRUE ) {
			return $this->finalise( "BlockArguments", $result );
		}
		if( $_211 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require") ] ) */
	function match_NotBlockTag ($substack = array()) {
		$result = $this->construct( "NotBlockTag" );
		$_238 = NULL;
		do {
			$res_213 = $result;
			$pos_213 = $this->pos;
			$_214 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_214->expand("end_") ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_238 = TRUE; break;
			}
			$result = $res_213;
			$this->pos = $pos_213;
			$_236 = NULL;
			do {
				$_233 = NULL;
				do {
					$_231 = NULL;
					do {
						$res_216 = $result;
						$pos_216 = $this->pos;
						$_217 = new ParserExpression( $this, $substack, $result );
						if (( $subres = $this->literal( $_217->expand("if") ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_231 = TRUE; break;
						}
						$result = $res_216;
						$this->pos = $pos_216;
						$_229 = NULL;
						do {
							$res_219 = $result;
							$pos_219 = $this->pos;
							$_220 = new ParserExpression( $this, $substack, $result );
							if (( $subres = $this->literal( $_220->expand("else_if") ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_229 = TRUE; break;
							}
							$result = $res_219;
							$this->pos = $pos_219;
							$_227 = NULL;
							do {
								$res_222 = $result;
								$pos_222 = $this->pos;
								$_223 = new ParserExpression( $this, $substack, $result );
								if (( $subres = $this->literal( $_223->expand("else") ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_227 = TRUE; break;
								}
								$result = $res_222;
								$this->pos = $pos_222;
								$_225 = new ParserExpression( $this, $substack, $result );
								if (( $subres = $this->literal( $_225->expand("require") ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_227 = TRUE; break;
								}
								$result = $res_222;
								$this->pos = $pos_222;
								$_227 = FALSE; break;
							}
							while(0);
							if( $_227 === TRUE ) { $_229 = TRUE; break; }
							$result = $res_219;
							$this->pos = $pos_219;
							$_229 = FALSE; break;
						}
						while(0);
						if( $_229 === TRUE ) { $_231 = TRUE; break; }
						$result = $res_216;
						$this->pos = $pos_216;
						$_231 = FALSE; break;
					}
					while(0);
					if( $_231 === FALSE) { $_233 = FALSE; break; }
					$_233 = TRUE; break;
				}
				while(0);
				if( $_233 === FALSE) { $_236 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_236 = FALSE; break; }
				$_236 = TRUE; break;
			}
			while(0);
			if( $_236 === TRUE ) { $_238 = TRUE; break; }
			$result = $res_213;
			$this->pos = $pos_213;
			$_238 = FALSE; break;
		}
		while(0);
		if( $_238 === TRUE ) {
			return $this->finalise( "NotBlockTag", $result );
		}
		if( $_238 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' :Template? '<%' < 'end_' '$BlockName' > '%>' */
	function match_ClosedBlock ($substack = array()) {
		$result = $this->construct( "ClosedBlock" );
		$_265 = NULL;
		do {
			$_240 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_240->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_265 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_243 = $result;
			$pos_243 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_243;
				$this->pos = $pos_243;
				$_265 = FALSE; break;
			}
			else {
				$result = $res_243;
				$this->pos = $pos_243;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_265 = FALSE; break; }
			$res_249 = $result;
			$pos_249 = $this->pos;
			$_248 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_248 = FALSE; break; }
				$key = "BlockArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_248 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_248 = FALSE; break; }
				$_248 = TRUE; break;
			}
			while(0);
			if( $_248 === FALSE) {
				$result = $res_249;
				$this->pos = $pos_249;
				unset( $res_249 );
				unset( $pos_249 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$substack[] = $result;
			$result = $this->construct( "Zap" );
			$_251 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_251->expand('%>') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Zap' );
			}
			else {
				$result = array_pop( $substack ) ;
				$_265 = FALSE; break;
			}
			$res_254 = $result;
			$pos_254 = $this->pos;
			$key = "Template"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Template(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_254;
				$this->pos = $pos_254;
				unset( $res_254 );
				unset( $pos_254 );
			}
			$_255 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_255->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_265 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_258 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_258->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_265 = FALSE; break; }
			$_260 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_260->expand('$BlockName') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_265 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_263 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_263->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_265 = FALSE; break; }
			$_265 = TRUE; break;
		}
		while(0);
		if( $_265 === TRUE ) {
			return $this->finalise( "ClosedBlock", $result );
		}
		if( $_265 === FALSE) { return FALSE; }
	}



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

	function ClosedBlock_Handle_Control(&$res) {
		if ($res['ArgumentCount'] != 1) {
			throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one argument only.', $this);
		}
		
		$arg = $res['Arguments'][0];
		if ($arg['ArgumentMode'] == 'string') {
			throw new SSTemplateParseException('Control block cant take string as argument.', $this);
		}
		
		$on = str_replace('->XML_val', '->obj', ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		return 
			'array_push($itemStack, $item); if($loop = '.$on.') foreach($loop as $key => $item) {' . PHP_EOL .
				$res['Template']['php'] . PHP_EOL .
			'} $item = array_pop($itemStack); ';
	}
	
	/* OpenBlock: '<%' < !NotBlockTag OpenBlockName:Word ( [ :BlockArguments ] )? > '%>' */
	function match_OpenBlock ($substack = array()) {
		$result = $this->construct( "OpenBlock" );
		$_280 = NULL;
		do {
			$_267 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_267->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_280 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_270 = $result;
			$pos_270 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_270;
				$this->pos = $pos_270;
				$_280 = FALSE; break;
			}
			else {
				$result = $res_270;
				$this->pos = $pos_270;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "OpenBlockName" );
			}
			else { $_280 = FALSE; break; }
			$res_276 = $result;
			$pos_276 = $this->pos;
			$_275 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_275 = FALSE; break; }
				$key = "BlockArguments"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_275 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_275 = FALSE; break; }
				$_275 = TRUE; break;
			}
			while(0);
			if( $_275 === FALSE) {
				$result = $res_276;
				$this->pos = $pos_276;
				unset( $res_276 );
				unset( $pos_276 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_278 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_278->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_280 = FALSE; break; }
			$_280 = TRUE; break;
		}
		while(0);
		if( $_280 === TRUE ) {
			return $this->finalise( "OpenBlock", $result );
		}
		if( $_280 === FALSE) { return FALSE; }
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
		$blockname = $res['OpenBlockName']['text'];
	
		$method = 'OpenBlock_Handle_'.ucfirst(strtolower($blockname));
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown open block "'.$blockname.'" encountered. Perhaps you missed the closing tag or have mis-spelled it?', $this);
		}
	}
	
	function OpenBlock_Handle_Include(&$res) {
		if ($res['ArgumentCount'] != 1) throw new SSTemplateParseException('Include takes exactly one argument', $this);
		
		$arg = $res['Arguments'][0];
		$php = ($arg['ArgumentMode'] == 'default') ? $arg['string_php'] : $arg['php'];
		
		if($this->includeDebuggingComments) { // Add include filename comments on dev sites
			return 
				'$val .= \'<!-- include '.$php.' -->\';'. "\n".
				'$val .= SSViewer::parse_template('.$php.', $item);'. "\n".
				'$val .= \'<!-- end include '.$php.' -->\';'. "\n";
		}
		else {
			return 
				'$val .= SSViewer::execute_template('.$php.', $item);'. "\n";
		}
	}
	
	function OpenBlock_Handle_Debug(&$res) {
		if ($res['ArgumentCount'] == 0) return 'Debug::show($item);';
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

	function OpenBlock_Handle_Base_tag(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Base_tag takes no arguments', $this);
		return '$val .= SSViewer::get_base_tag($val);';
	}

	function OpenBlock_Handle_Current_page(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Current_page takes no arguments', $this);
		return '$val .= $_SERVER[SCRIPT_URL];';
	}
	
	/* MismatchedEndBlock: '<%' < 'end_' !'$BlockName' :Word > '%>' */
	function match_MismatchedEndBlock ($substack = array()) {
		$result = $this->construct( "MismatchedEndBlock" );
		$_293 = NULL;
		do {
			$_282 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_282->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_293 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_285 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_285->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_293 = FALSE; break; }
			$res_288 = $result;
			$pos_288 = $this->pos;
			$_287 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_287->expand('$BlockName') ) ) !== FALSE) {
				$result["text"] .= $subres;
				$result = $res_288;
				$this->pos = $pos_288;
				$_293 = FALSE; break;
			}
			else {
				$result = $res_288;
				$this->pos = $pos_288;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Word" );
			}
			else { $_293 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$_291 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_291->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_293 = FALSE; break; }
			$_293 = TRUE; break;
		}
		while(0);
		if( $_293 === TRUE ) {
			return $this->finalise( "MismatchedEndBlock", $result );
		}
		if( $_293 === FALSE) { return FALSE; }
	}



	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_'.$blockname.' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
	function match_MalformedOpenTag ($substack = array()) {
		$result = $this->construct( "MalformedOpenTag" );
		$_310 = NULL;
		do {
			$_295 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_295->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_310 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_298 = $result;
			$pos_298 = $this->pos;
			$key = "NotBlockTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_NotBlockTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_298;
				$this->pos = $pos_298;
				$_310 = FALSE; break;
			}
			else {
				$result = $res_298;
				$this->pos = $pos_298;
			}
			$key = "Word"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_310 = FALSE; break; }
			$res_309 = $result;
			$pos_309 = $this->pos;
			$_308 = NULL;
			do {
				$res_304 = $result;
				$pos_304 = $this->pos;
				$_303 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_303 = FALSE; break; }
					$key = "BlockArguments"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_BlockArguments(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_303 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_303 = FALSE; break; }
					$_303 = TRUE; break;
				}
				while(0);
				if( $_303 === FALSE) {
					$result = $res_304;
					$this->pos = $pos_304;
					unset( $res_304 );
					unset( $pos_304 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_306 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_306->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_308 = FALSE; break; }
				$_308 = TRUE; break;
			}
			while(0);
			if( $_308 === TRUE ) {
				$result = $res_309;
				$this->pos = $pos_309;
				$_310 = FALSE; break;
			}
			if( $_308 === FALSE) {
				$result = $res_309;
				$this->pos = $pos_309;
			}
			$_310 = TRUE; break;
		}
		while(0);
		if( $_310 === TRUE ) {
			return $this->finalise( "MalformedOpenTag", $result );
		}
		if( $_310 === FALSE) { return FALSE; }
	}



	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
	}
	
	/* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
	function match_MalformedCloseTag ($substack = array()) {
		$result = $this->construct( "MalformedCloseTag" );
		$_326 = NULL;
		do {
			$_312 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_312->expand('<%') ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_326 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$substack[] = $result;
			$result = $this->construct( "Tag" );
			$_318 = NULL;
			do {
				$_315 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_315->expand('end_') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_318 = FALSE; break; }
				$key = "Word"; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Word(array_merge($substack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_318 = FALSE; break; }
				$_318 = TRUE; break;
			}
			while(0);
			if( $_318 === TRUE ) {
				$subres = $result ;
				$result = array_pop( $substack ) ;
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_318 === FALSE) {
				$result = array_pop( $substack ) ;
				$_326 = FALSE; break;
			}
			$res_325 = $result;
			$pos_325 = $this->pos;
			$_324 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_322 = new ParserExpression( $this, $substack, $result );
				if (( $subres = $this->literal( $_322->expand('%>') ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_324 = FALSE; break; }
				$_324 = TRUE; break;
			}
			while(0);
			if( $_324 === TRUE ) {
				$result = $res_325;
				$this->pos = $pos_325;
				$_326 = FALSE; break;
			}
			if( $_324 === FALSE) {
				$result = $res_325;
				$this->pos = $pos_325;
			}
			$_326 = TRUE; break;
		}
		while(0);
		if( $_326 === TRUE ) {
			return $this->finalise( "MalformedCloseTag", $result );
		}
		if( $_326 === FALSE) { return FALSE; }
	}



	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an argument to one?", $this);
	}
	
	/* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
	function match_MalformedBlock ($substack = array()) {
		$result = $this->construct( "MalformedBlock" );
		$_331 = NULL;
		do {
			$res_328 = $result;
			$pos_328 = $this->pos;
			$key = "MalformedOpenTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedOpenTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_331 = TRUE; break;
			}
			$result = $res_328;
			$this->pos = $pos_328;
			$key = "MalformedCloseTag"; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedCloseTag(array_merge($substack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_331 = TRUE; break;
			}
			$result = $res_328;
			$this->pos = $pos_328;
			$_331 = FALSE; break;
		}
		while(0);
		if( $_331 === TRUE ) {
			return $this->finalise( "MalformedBlock", $result );
		}
		if( $_331 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /./)+ "--%>" */
	function match_Comment ($substack = array()) {
		$result = $this->construct( "Comment" );
		$_343 = NULL;
		do {
			$_333 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_333->expand("<%--") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_343 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_340 = $result;
				$pos_340 = $this->pos;
				$_339 = NULL;
				do {
					$res_336 = $result;
					$pos_336 = $this->pos;
					$_335 = new ParserExpression( $this, $substack, $result );
					if (( $subres = $this->literal( $_335->expand("--%>") ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_336;
						$this->pos = $pos_336;
						$_339 = FALSE; break;
					}
					else {
						$result = $res_336;
						$this->pos = $pos_336;
					}
					$_337 = new ParserExpression( $this, $substack, $result );
					if (( $subres = $this->rx( $_337->expand('/./') ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_339 = FALSE; break; }
					$_339 = TRUE; break;
				}
				while(0);
				if( $_339 === FALSE) {
					$result = $res_340;
					$this->pos = $pos_340;
					unset( $res_340 );
					unset( $pos_340 );
					break;
				}
				$count += 1;
			}
			if ($count > 0) {  }
			else { $_343 = FALSE; break; }
			$_341 = new ParserExpression( $this, $substack, $result );
			if (( $subres = $this->literal( $_341->expand("--%>") ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_343 = FALSE; break; }
			$_343 = TRUE; break;
		}
		while(0);
		if( $_343 === TRUE ) {
			return $this->finalise( "Comment", $result );
		}
		if( $_343 === FALSE) { return FALSE; }
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
		$_345 = new ParserExpression( $this, $substack, $result );
		if (( $subres = $this->rx( $_345->expand('/
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
			$res_377 = $result;
			$pos_377 = $this->pos;
			$_376 = NULL;
			do {
				$_374 = NULL;
				do {
					$res_347 = $result;
					$pos_347 = $this->pos;
					$key = "Comment"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comment(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_374 = TRUE; break;
					}
					$result = $res_347;
					$this->pos = $pos_347;
					$_372 = NULL;
					do {
						$res_349 = $result;
						$pos_349 = $this->pos;
						$key = "If"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_If(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_372 = TRUE; break;
						}
						$result = $res_349;
						$this->pos = $pos_349;
						$_370 = NULL;
						do {
							$res_351 = $result;
							$pos_351 = $this->pos;
							$key = "Require"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Require(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_370 = TRUE; break;
							}
							$result = $res_351;
							$this->pos = $pos_351;
							$_368 = NULL;
							do {
								$res_353 = $result;
								$pos_353 = $this->pos;
								$key = "ClosedBlock"; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ClosedBlock(array_merge($substack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_368 = TRUE; break;
								}
								$result = $res_353;
								$this->pos = $pos_353;
								$_366 = NULL;
								do {
									$res_355 = $result;
									$pos_355 = $this->pos;
									$key = "OpenBlock"; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OpenBlock(array_merge($substack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_366 = TRUE; break;
									}
									$result = $res_355;
									$this->pos = $pos_355;
									$_364 = NULL;
									do {
										$res_357 = $result;
										$pos_357 = $this->pos;
										$key = "MalformedBlock"; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedBlock(array_merge($substack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_364 = TRUE; break;
										}
										$result = $res_357;
										$this->pos = $pos_357;
										$_362 = NULL;
										do {
											$res_359 = $result;
											$pos_359 = $this->pos;
											$key = "Injection"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Injection(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_362 = TRUE; break;
											}
											$result = $res_359;
											$this->pos = $pos_359;
											$key = "Text"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Text(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_362 = TRUE; break;
											}
											$result = $res_359;
											$this->pos = $pos_359;
											$_362 = FALSE; break;
										}
										while(0);
										if( $_362 === TRUE ) { $_364 = TRUE; break; }
										$result = $res_357;
										$this->pos = $pos_357;
										$_364 = FALSE; break;
									}
									while(0);
									if( $_364 === TRUE ) { $_366 = TRUE; break; }
									$result = $res_355;
									$this->pos = $pos_355;
									$_366 = FALSE; break;
								}
								while(0);
								if( $_366 === TRUE ) { $_368 = TRUE; break; }
								$result = $res_353;
								$this->pos = $pos_353;
								$_368 = FALSE; break;
							}
							while(0);
							if( $_368 === TRUE ) { $_370 = TRUE; break; }
							$result = $res_351;
							$this->pos = $pos_351;
							$_370 = FALSE; break;
						}
						while(0);
						if( $_370 === TRUE ) { $_372 = TRUE; break; }
						$result = $res_349;
						$this->pos = $pos_349;
						$_372 = FALSE; break;
					}
					while(0);
					if( $_372 === TRUE ) { $_374 = TRUE; break; }
					$result = $res_347;
					$this->pos = $pos_347;
					$_374 = FALSE; break;
				}
				while(0);
				if( $_374 === FALSE) { $_376 = FALSE; break; }
				$_376 = TRUE; break;
			}
			while(0);
			if( $_376 === FALSE) {
				$result = $res_377;
				$this->pos = $pos_377;
				unset( $res_377 );
				unset( $pos_377 );
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
			$res_412 = $result;
			$pos_412 = $this->pos;
			$_411 = NULL;
			do {
				$_409 = NULL;
				do {
					$res_378 = $result;
					$pos_378 = $this->pos;
					$key = "Comment"; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Comment(array_merge($substack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_409 = TRUE; break;
					}
					$result = $res_378;
					$this->pos = $pos_378;
					$_407 = NULL;
					do {
						$res_380 = $result;
						$pos_380 = $this->pos;
						$key = "If"; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_If(array_merge($substack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_407 = TRUE; break;
						}
						$result = $res_380;
						$this->pos = $pos_380;
						$_405 = NULL;
						do {
							$res_382 = $result;
							$pos_382 = $this->pos;
							$key = "Require"; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Require(array_merge($substack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_405 = TRUE; break;
							}
							$result = $res_382;
							$this->pos = $pos_382;
							$_403 = NULL;
							do {
								$res_384 = $result;
								$pos_384 = $this->pos;
								$key = "ClosedBlock"; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_ClosedBlock(array_merge($substack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_403 = TRUE; break;
								}
								$result = $res_384;
								$this->pos = $pos_384;
								$_401 = NULL;
								do {
									$res_386 = $result;
									$pos_386 = $this->pos;
									$key = "OpenBlock"; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_OpenBlock(array_merge($substack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_401 = TRUE; break;
									}
									$result = $res_386;
									$this->pos = $pos_386;
									$_399 = NULL;
									do {
										$res_388 = $result;
										$pos_388 = $this->pos;
										$key = "MalformedBlock"; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MalformedBlock(array_merge($substack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_399 = TRUE; break;
										}
										$result = $res_388;
										$this->pos = $pos_388;
										$_397 = NULL;
										do {
											$res_390 = $result;
											$pos_390 = $this->pos;
											$key = "MismatchedEndBlock"; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_MismatchedEndBlock(array_merge($substack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_397 = TRUE; break;
											}
											$result = $res_390;
											$this->pos = $pos_390;
											$_395 = NULL;
											do {
												$res_392 = $result;
												$pos_392 = $this->pos;
												$key = "Injection"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Injection(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_395 = TRUE; break;
												}
												$result = $res_392;
												$this->pos = $pos_392;
												$key = "Text"; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->match_Text(array_merge($substack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_395 = TRUE; break;
												}
												$result = $res_392;
												$this->pos = $pos_392;
												$_395 = FALSE; break;
											}
											while(0);
											if( $_395 === TRUE ) { $_397 = TRUE; break; }
											$result = $res_390;
											$this->pos = $pos_390;
											$_397 = FALSE; break;
										}
										while(0);
										if( $_397 === TRUE ) { $_399 = TRUE; break; }
										$result = $res_388;
										$this->pos = $pos_388;
										$_399 = FALSE; break;
									}
									while(0);
									if( $_399 === TRUE ) { $_401 = TRUE; break; }
									$result = $res_386;
									$this->pos = $pos_386;
									$_401 = FALSE; break;
								}
								while(0);
								if( $_401 === TRUE ) { $_403 = TRUE; break; }
								$result = $res_384;
								$this->pos = $pos_384;
								$_403 = FALSE; break;
							}
							while(0);
							if( $_403 === TRUE ) { $_405 = TRUE; break; }
							$result = $res_382;
							$this->pos = $pos_382;
							$_405 = FALSE; break;
						}
						while(0);
						if( $_405 === TRUE ) { $_407 = TRUE; break; }
						$result = $res_380;
						$this->pos = $pos_380;
						$_407 = FALSE; break;
					}
					while(0);
					if( $_407 === TRUE ) { $_409 = TRUE; break; }
					$result = $res_378;
					$this->pos = $pos_378;
					$_409 = FALSE; break;
				}
				while(0);
				if( $_409 === FALSE) { $_411 = FALSE; break; }
				$_411 = TRUE; break;
			}
			while(0);
			if( $_411 === FALSE) {
				$result = $res_412;
				$this->pos = $pos_412;
				unset( $res_412 );
				unset( $pos_412 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) {
			return $this->finalise( "TopTemplate", $result );
		}
		else { return FALSE; }
	}



	function TopTemplate__construct(&$res) {
		$res['php'] = "<?php" . PHP_EOL;
	}

	function TopTemplate_Text(&$res, $sub) { return $this->Template_Text($res, $sub); }
	function TopTemplate_STR(&$res, $sub) { return $this->Template_STR($res, $sub); }

	static function compileString($string, $templateName = "", $includeDebuggingComments=false) {
		$parser = new SSTemplateParser($string);
		$parser->includeDebuggingComments = $includeDebuggingComments;

		// Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF (and other encodings) properly
		if(substr($string, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $parser->pos = 3;
		
		$result =  $parser->match_TopTemplate();
		if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $parser);

		$code = $result['php'];
		
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
	
	static function compileFile($template) {
		return self::compileString(file_get_contents($template));
	}
}
