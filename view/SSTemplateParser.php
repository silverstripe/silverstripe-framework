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
	
	/**
	 * Override the function that constructs the result arrays to also prepare a 'php' item in the array
	 */
	function construct($matchrule, $name, $arguments = null) {
		$res = parent::construct($matchrule, $name, $arguments);
		if (!isset($res['php'])) $res['php'] = '';
		return $res;
	}
	
	/* Template: (Comment | If | Require | CacheBlock | UncachedBlock | OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_Template_typestack = array('Template');
	function match_Template ($stack = array()) {
		$matchrule = "Template"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_42 = $result;
			$pos_42 = $this->pos;
			$_41 = NULL;
			do {
				$_39 = NULL;
				do {
					$res_0 = $result;
					$pos_0 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_39 = TRUE; break;
					}
					$result = $res_0;
					$this->pos = $pos_0;
					$_37 = NULL;
					do {
						$res_2 = $result;
						$pos_2 = $this->pos;
						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_37 = TRUE; break;
						}
						$result = $res_2;
						$this->pos = $pos_2;
						$_35 = NULL;
						do {
							$res_4 = $result;
							$pos_4 = $this->pos;
							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_35 = TRUE; break;
							}
							$result = $res_4;
							$this->pos = $pos_4;
							$_33 = NULL;
							do {
								$res_6 = $result;
								$pos_6 = $this->pos;
								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_33 = TRUE; break;
								}
								$result = $res_6;
								$this->pos = $pos_6;
								$_31 = NULL;
								do {
									$res_8 = $result;
									$pos_8 = $this->pos;
									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_31 = TRUE; break;
									}
									$result = $res_8;
									$this->pos = $pos_8;
									$_29 = NULL;
									do {
										$res_10 = $result;
										$pos_10 = $this->pos;
										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_29 = TRUE; break;
										}
										$result = $res_10;
										$this->pos = $pos_10;
										$_27 = NULL;
										do {
											$res_12 = $result;
											$pos_12 = $this->pos;
											$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_27 = TRUE; break;
											}
											$result = $res_12;
											$this->pos = $pos_12;
											$_25 = NULL;
											do {
												$res_14 = $result;
												$pos_14 = $this->pos;
												$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_25 = TRUE; break;
												}
												$result = $res_14;
												$this->pos = $pos_14;
												$_23 = NULL;
												do {
													$res_16 = $result;
													$pos_16 = $this->pos;
													$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_23 = TRUE; break;
													}
													$result = $res_16;
													$this->pos = $pos_16;
													$_21 = NULL;
													do {
														$res_18 = $result;
														$pos_18 = $this->pos;
														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_21 = TRUE; break;
														}
														$result = $res_18;
														$this->pos = $pos_18;
														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_21 = TRUE; break;
														}
														$result = $res_18;
														$this->pos = $pos_18;
														$_21 = FALSE; break;
													}
													while(0);
													if( $_21 === TRUE ) { $_23 = TRUE; break; }
													$result = $res_16;
													$this->pos = $pos_16;
													$_23 = FALSE; break;
												}
												while(0);
												if( $_23 === TRUE ) { $_25 = TRUE; break; }
												$result = $res_14;
												$this->pos = $pos_14;
												$_25 = FALSE; break;
											}
											while(0);
											if( $_25 === TRUE ) { $_27 = TRUE; break; }
											$result = $res_12;
											$this->pos = $pos_12;
											$_27 = FALSE; break;
										}
										while(0);
										if( $_27 === TRUE ) { $_29 = TRUE; break; }
										$result = $res_10;
										$this->pos = $pos_10;
										$_29 = FALSE; break;
									}
									while(0);
									if( $_29 === TRUE ) { $_31 = TRUE; break; }
									$result = $res_8;
									$this->pos = $pos_8;
									$_31 = FALSE; break;
								}
								while(0);
								if( $_31 === TRUE ) { $_33 = TRUE; break; }
								$result = $res_6;
								$this->pos = $pos_6;
								$_33 = FALSE; break;
							}
							while(0);
							if( $_33 === TRUE ) { $_35 = TRUE; break; }
							$result = $res_4;
							$this->pos = $pos_4;
							$_35 = FALSE; break;
						}
						while(0);
						if( $_35 === TRUE ) { $_37 = TRUE; break; }
						$result = $res_2;
						$this->pos = $pos_2;
						$_37 = FALSE; break;
					}
					while(0);
					if( $_37 === TRUE ) { $_39 = TRUE; break; }
					$result = $res_0;
					$this->pos = $pos_0;
					$_39 = FALSE; break;
				}
				while(0);
				if( $_39 === FALSE) { $_41 = FALSE; break; }
				$_41 = TRUE; break;
			}
			while(0);
			if( $_41 === FALSE) {
				$result = $res_42;
				$this->pos = $pos_42;
				unset( $res_42 );
				unset( $pos_42 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}



	function Template_STR(&$res, $sub) {
		$res['php'] .= $sub['php'] . PHP_EOL ;
	}
	
	/* Word: / [A-Za-z_] [A-Za-z0-9_]* / */
	protected $match_Word_typestack = array('Word');
	function match_Word ($stack = array()) {
		$matchrule = "Word"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/ [A-Za-z_] [A-Za-z0-9_]* /' ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $this->finalise($result);
		}
		else { return FALSE; }
	}


	/* Number: / [0-9]+ / */
	protected $match_Number_typestack = array('Number');
	function match_Number ($stack = array()) {
		$matchrule = "Number"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/ [0-9]+ /' ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $this->finalise($result);
		}
		else { return FALSE; }
	}


	/* Value: / [A-Za-z0-9_]+ / */
	protected $match_Value_typestack = array('Value');
	function match_Value ($stack = array()) {
		$matchrule = "Value"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/ [A-Za-z0-9_]+ /' ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $this->finalise($result);
		}
		else { return FALSE; }
	}


	/* CallArguments: :Argument ( < "," < :Argument )* */
	protected $match_CallArguments_typestack = array('CallArguments');
	function match_CallArguments ($stack = array()) {
		$matchrule = "CallArguments"; $result = $this->construct($matchrule, $matchrule, null);
		$_53 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_53 = FALSE; break; }
			while (true) {
				$res_52 = $result;
				$pos_52 = $this->pos;
				$_51 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_51 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_51 = FALSE; break; }
					$_51 = TRUE; break;
				}
				while(0);
				if( $_51 === FALSE) {
					$result = $res_52;
					$this->pos = $pos_52;
					unset( $res_52 );
					unset( $pos_52 );
					break;
				}
			}
			$_53 = TRUE; break;
		}
		while(0);
		if( $_53 === TRUE ) { return $this->finalise($result); }
		if( $_53 === FALSE) { return FALSE; }
	}




	/** 
	 * Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert strings 
	 * to numbers when needed.
	 */
	function CallArguments_Argument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ', ';
		
		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] : str_replace('$$FINAL', 'XML_val', $sub['php']);
	}

	/* Call: Method:Word ( "(" < :CallArguments? > ")" )? */
	protected $match_Call_typestack = array('Call');
	function match_Call ($stack = array()) {
		$matchrule = "Call"; $result = $this->construct($matchrule, $matchrule, null);
		$_63 = NULL;
		do {
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Method" );
			}
			else { $_63 = FALSE; break; }
			$res_62 = $result;
			$pos_62 = $this->pos;
			$_61 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_61 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$res_58 = $result;
				$pos_58 = $this->pos;
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else {
					$result = $res_58;
					$this->pos = $pos_58;
					unset( $res_58 );
					unset( $pos_58 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_61 = FALSE; break; }
				$_61 = TRUE; break;
			}
			while(0);
			if( $_61 === FALSE) {
				$result = $res_62;
				$this->pos = $pos_62;
				unset( $res_62 );
				unset( $pos_62 );
			}
			$_63 = TRUE; break;
		}
		while(0);
		if( $_63 === TRUE ) { return $this->finalise($result); }
		if( $_63 === FALSE) { return FALSE; }
	}


	/* LookupStep: :Call &"." */
	protected $match_LookupStep_typestack = array('LookupStep');
	function match_LookupStep ($stack = array()) {
		$matchrule = "LookupStep"; $result = $this->construct($matchrule, $matchrule, null);
		$_67 = NULL;
		do {
			$matcher = 'match_'.'Call'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Call" );
			}
			else { $_67 = FALSE; break; }
			$res_66 = $result;
			$pos_66 = $this->pos;
			if (substr($this->string,$this->pos,1) == '.') {
				$this->pos += 1;
				$result["text"] .= '.';
				$result = $res_66;
				$this->pos = $pos_66;
			}
			else {
				$result = $res_66;
				$this->pos = $pos_66;
				$_67 = FALSE; break;
			}
			$_67 = TRUE; break;
		}
		while(0);
		if( $_67 === TRUE ) { return $this->finalise($result); }
		if( $_67 === FALSE) { return FALSE; }
	}


	/* LastLookupStep: :Call */
	protected $match_LastLookupStep_typestack = array('LastLookupStep');
	function match_LastLookupStep ($stack = array()) {
		$matchrule = "LastLookupStep"; $result = $this->construct($matchrule, $matchrule, null);
		$matcher = 'match_'.'Call'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "Call" );
			return $this->finalise($result);
		}
		else { return FALSE; }
	}


	/* Lookup: LookupStep ("." LookupStep)* "." LastLookupStep | LastLookupStep */
	protected $match_Lookup_typestack = array('Lookup');
	function match_Lookup ($stack = array()) {
		$matchrule = "Lookup"; $result = $this->construct($matchrule, $matchrule, null);
		$_81 = NULL;
		do {
			$res_70 = $result;
			$pos_70 = $this->pos;
			$_78 = NULL;
			do {
				$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_78 = FALSE; break; }
				while (true) {
					$res_75 = $result;
					$pos_75 = $this->pos;
					$_74 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == '.') {
							$this->pos += 1;
							$result["text"] .= '.';
						}
						else { $_74 = FALSE; break; }
						$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_74 = FALSE; break; }
						$_74 = TRUE; break;
					}
					while(0);
					if( $_74 === FALSE) {
						$result = $res_75;
						$this->pos = $pos_75;
						unset( $res_75 );
						unset( $pos_75 );
						break;
					}
				}
				if (substr($this->string,$this->pos,1) == '.') {
					$this->pos += 1;
					$result["text"] .= '.';
				}
				else { $_78 = FALSE; break; }
				$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_78 = FALSE; break; }
				$_78 = TRUE; break;
			}
			while(0);
			if( $_78 === TRUE ) { $_81 = TRUE; break; }
			$result = $res_70;
			$this->pos = $pos_70;
			$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_81 = TRUE; break;
			}
			$result = $res_70;
			$this->pos = $pos_70;
			$_81 = FALSE; break;
		}
		while(0);
		if( $_81 === TRUE ) { return $this->finalise($result); }
		if( $_81 === FALSE) { return FALSE; }
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
	protected $match_SimpleInjection_typestack = array('SimpleInjection');
	function match_SimpleInjection ($stack = array()) {
		$matchrule = "SimpleInjection"; $result = $this->construct($matchrule, $matchrule, null);
		$_85 = NULL;
		do {
			if (substr($this->string,$this->pos,1) == '$') {
				$this->pos += 1;
				$result["text"] .= '$';
			}
			else { $_85 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_85 = FALSE; break; }
			$_85 = TRUE; break;
		}
		while(0);
		if( $_85 === TRUE ) { return $this->finalise($result); }
		if( $_85 === FALSE) { return FALSE; }
	}


	/* BracketInjection: '{$' :Lookup "}" */
	protected $match_BracketInjection_typestack = array('BracketInjection');
	function match_BracketInjection ($stack = array()) {
		$matchrule = "BracketInjection"; $result = $this->construct($matchrule, $matchrule, null);
		$_90 = NULL;
		do {
			if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_90 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_90 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '}') {
				$this->pos += 1;
				$result["text"] .= '}';
			}
			else { $_90 = FALSE; break; }
			$_90 = TRUE; break;
		}
		while(0);
		if( $_90 === TRUE ) { return $this->finalise($result); }
		if( $_90 === FALSE) { return FALSE; }
	}


	/* Injection: BracketInjection | SimpleInjection */
	protected $match_Injection_typestack = array('Injection');
	function match_Injection ($stack = array()) {
		$matchrule = "Injection"; $result = $this->construct($matchrule, $matchrule, null);
		$_95 = NULL;
		do {
			$res_92 = $result;
			$pos_92 = $this->pos;
			$matcher = 'match_'.'BracketInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_95 = TRUE; break;
			}
			$result = $res_92;
			$this->pos = $pos_92;
			$matcher = 'match_'.'SimpleInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_95 = TRUE; break;
			}
			$result = $res_92;
			$this->pos = $pos_92;
			$_95 = FALSE; break;
		}
		while(0);
		if( $_95 === TRUE ) { return $this->finalise($result); }
		if( $_95 === FALSE) { return FALSE; }
	}



	function Injection_STR(&$res, $sub) {
		$res['php'] = '$val .= '. str_replace('$$FINAL', 'XML_val', $sub['Lookup']['php']) . ';';
	}

	/* DollarMarkedLookup: SimpleInjection */
	protected $match_DollarMarkedLookup_typestack = array('DollarMarkedLookup');
	function match_DollarMarkedLookup ($stack = array()) {
		$matchrule = "DollarMarkedLookup"; $result = $this->construct($matchrule, $matchrule, null);
		$matcher = 'match_'.'SimpleInjection'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			return $this->finalise($result);
		}
		else { return FALSE; }
	}



	function DollarMarkedLookup_STR(&$res, $sub) {
		$res['Lookup'] = $sub['Lookup'];
	}

	/* QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q' */
	protected $match_QuotedString_typestack = array('QuotedString');
	function match_QuotedString ($stack = array()) {
		$matchrule = "QuotedString"; $result = $this->construct($matchrule, $matchrule, null);
		$_101 = NULL;
		do {
			$stack[] = $result; $result = $this->construct( $matchrule, "q" ); 
			if (( $subres = $this->rx( '/[\'"]/' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'q' );
			}
			else {
				$result = array_pop($stack);
				$_101 = FALSE; break;
			}
			$stack[] = $result; $result = $this->construct( $matchrule, "String" ); 
			if (( $subres = $this->rx( '/ (\\\\\\\\ | \\\\. | [^'.$this->expression($result, $stack, 'q').'\\\\])* /' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'String' );
			}
			else {
				$result = array_pop($stack);
				$_101 = FALSE; break;
			}
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'q').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_101 = FALSE; break; }
			$_101 = TRUE; break;
		}
		while(0);
		if( $_101 === TRUE ) { return $this->finalise($result); }
		if( $_101 === FALSE) { return FALSE; }
	}


	/* FreeString: /[^,)%!=|&]+/ */
	protected $match_FreeString_typestack = array('FreeString');
	function match_FreeString ($stack = array()) {
		$matchrule = "FreeString"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/[^,)%!=|&]+/' ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $this->finalise($result);
		}
		else { return FALSE; }
	}


	/* Argument:
	:DollarMarkedLookup |
	:QuotedString |
	:Lookup !(< FreeString)|
	:FreeString */
	protected $match_Argument_typestack = array('Argument');
	function match_Argument ($stack = array()) {
		$matchrule = "Argument"; $result = $this->construct($matchrule, $matchrule, null);
		$_121 = NULL;
		do {
			$res_104 = $result;
			$pos_104 = $this->pos;
			$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "DollarMarkedLookup" );
				$_121 = TRUE; break;
			}
			$result = $res_104;
			$this->pos = $pos_104;
			$_119 = NULL;
			do {
				$res_106 = $result;
				$pos_106 = $this->pos;
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "QuotedString" );
					$_119 = TRUE; break;
				}
				$result = $res_106;
				$this->pos = $pos_106;
				$_117 = NULL;
				do {
					$res_108 = $result;
					$pos_108 = $this->pos;
					$_114 = NULL;
					do {
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
						}
						else { $_114 = FALSE; break; }
						$res_113 = $result;
						$pos_113 = $this->pos;
						$_112 = NULL;
						do {
							if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
							$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_112 = FALSE; break; }
							$_112 = TRUE; break;
						}
						while(0);
						if( $_112 === TRUE ) {
							$result = $res_113;
							$this->pos = $pos_113;
							$_114 = FALSE; break;
						}
						if( $_112 === FALSE) {
							$result = $res_113;
							$this->pos = $pos_113;
						}
						$_114 = TRUE; break;
					}
					while(0);
					if( $_114 === TRUE ) { $_117 = TRUE; break; }
					$result = $res_108;
					$this->pos = $pos_108;
					$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "FreeString" );
						$_117 = TRUE; break;
					}
					$result = $res_108;
					$this->pos = $pos_108;
					$_117 = FALSE; break;
				}
				while(0);
				if( $_117 === TRUE ) { $_119 = TRUE; break; }
				$result = $res_106;
				$this->pos = $pos_106;
				$_119 = FALSE; break;
			}
			while(0);
			if( $_119 === TRUE ) { $_121 = TRUE; break; }
			$result = $res_104;
			$this->pos = $pos_104;
			$_121 = FALSE; break;
		}
		while(0);
		if( $_121 === TRUE ) { return $this->finalise($result); }
		if( $_121 === FALSE) { return FALSE; }
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
	protected $match_ComparisonOperator_typestack = array('ComparisonOperator');
	function match_ComparisonOperator ($stack = array()) {
		$matchrule = "ComparisonOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_130 = NULL;
		do {
			$res_123 = $result;
			$pos_123 = $this->pos;
			if (( $subres = $this->literal( '==' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_130 = TRUE; break;
			}
			$result = $res_123;
			$this->pos = $pos_123;
			$_128 = NULL;
			do {
				$res_125 = $result;
				$pos_125 = $this->pos;
				if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_128 = TRUE; break;
				}
				$result = $res_125;
				$this->pos = $pos_125;
				if (substr($this->string,$this->pos,1) == '=') {
					$this->pos += 1;
					$result["text"] .= '=';
					$_128 = TRUE; break;
				}
				$result = $res_125;
				$this->pos = $pos_125;
				$_128 = FALSE; break;
			}
			while(0);
			if( $_128 === TRUE ) { $_130 = TRUE; break; }
			$result = $res_123;
			$this->pos = $pos_123;
			$_130 = FALSE; break;
		}
		while(0);
		if( $_130 === TRUE ) { return $this->finalise($result); }
		if( $_130 === FALSE) { return FALSE; }
	}


	/* Comparison: Argument < ComparisonOperator > Argument */
	protected $match_Comparison_typestack = array('Comparison');
	function match_Comparison ($stack = array()) {
		$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
		$_137 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_137 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_137 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_137 = FALSE; break; }
			$_137 = TRUE; break;
		}
		while(0);
		if( $_137 === TRUE ) { return $this->finalise($result); }
		if( $_137 === FALSE) { return FALSE; }
	}



	function Comparison_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'default') {
			if (!empty($res['php'])) $res['php'] .= $sub['string_php'];
			else $res['php'] = str_replace('$$FINAL', 'XML_val', $sub['lookup_php']);
		}	
		else {
			$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
		}
	}

	function Comparison_ComparisonOperator(&$res, $sub) {
		$res['php'] .= ($sub['text'] == '=' ? '==' : $sub['text']);
	}

	/* PresenceCheck: (Not:'not' <)? Argument */
	protected $match_PresenceCheck_typestack = array('PresenceCheck');
	function match_PresenceCheck ($stack = array()) {
		$matchrule = "PresenceCheck"; $result = $this->construct($matchrule, $matchrule, null);
		$_144 = NULL;
		do {
			$res_142 = $result;
			$pos_142 = $this->pos;
			$_141 = NULL;
			do {
				$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
				if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Not' );
				}
				else {
					$result = array_pop($stack);
					$_141 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_141 = TRUE; break;
			}
			while(0);
			if( $_141 === FALSE) {
				$result = $res_142;
				$this->pos = $pos_142;
				unset( $res_142 );
				unset( $pos_142 );
			}
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_144 = FALSE; break; }
			$_144 = TRUE; break;
		}
		while(0);
		if( $_144 === TRUE ) { return $this->finalise($result); }
		if( $_144 === FALSE) { return FALSE; }
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
	protected $match_IfArgumentPortion_typestack = array('IfArgumentPortion');
	function match_IfArgumentPortion ($stack = array()) {
		$matchrule = "IfArgumentPortion"; $result = $this->construct($matchrule, $matchrule, null);
		$_149 = NULL;
		do {
			$res_146 = $result;
			$pos_146 = $this->pos;
			$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_149 = TRUE; break;
			}
			$result = $res_146;
			$this->pos = $pos_146;
			$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_149 = TRUE; break;
			}
			$result = $res_146;
			$this->pos = $pos_146;
			$_149 = FALSE; break;
		}
		while(0);
		if( $_149 === TRUE ) { return $this->finalise($result); }
		if( $_149 === FALSE) { return FALSE; }
	}



	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* BooleanOperator: "||" | "&&" */
	protected $match_BooleanOperator_typestack = array('BooleanOperator');
	function match_BooleanOperator ($stack = array()) {
		$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_154 = NULL;
		do {
			$res_151 = $result;
			$pos_151 = $this->pos;
			if (( $subres = $this->literal( '||' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_154 = TRUE; break;
			}
			$result = $res_151;
			$this->pos = $pos_151;
			if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_154 = TRUE; break;
			}
			$result = $res_151;
			$this->pos = $pos_151;
			$_154 = FALSE; break;
		}
		while(0);
		if( $_154 === TRUE ) { return $this->finalise($result); }
		if( $_154 === FALSE) { return FALSE; }
	}


	/* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
	protected $match_IfArgument_typestack = array('IfArgument');
	function match_IfArgument ($stack = array()) {
		$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_163 = NULL;
		do {
			$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgumentPortion" );
			}
			else { $_163 = FALSE; break; }
			while (true) {
				$res_162 = $result;
				$pos_162 = $this->pos;
				$_161 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BooleanOperator" );
					}
					else { $_161 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "IfArgumentPortion" );
					}
					else { $_161 = FALSE; break; }
					$_161 = TRUE; break;
				}
				while(0);
				if( $_161 === FALSE) {
					$result = $res_162;
					$this->pos = $pos_162;
					unset( $res_162 );
					unset( $pos_162 );
					break;
				}
			}
			$_163 = TRUE; break;
		}
		while(0);
		if( $_163 === TRUE ) { return $this->finalise($result); }
		if( $_163 === FALSE) { return FALSE; }
	}



	function IfArgument_IfArgumentPortion(&$res, $sub) {
		$res['php'] .= $sub['php'];
	}

	function IfArgument_BooleanOperator(&$res, $sub) {
		$res['php'] .= $sub['text'];
	}

	/* IfPart: '<%' < 'if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
	protected $match_IfPart_typestack = array('IfPart');
	function match_IfPart ($stack = array()) {
		$matchrule = "IfPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_173 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_173 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_173 = FALSE; break; }
			$res_172 = $result;
			$pos_172 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_172;
				$this->pos = $pos_172;
				unset( $res_172 );
				unset( $pos_172 );
			}
			$_173 = TRUE; break;
		}
		while(0);
		if( $_173 === TRUE ) { return $this->finalise($result); }
		if( $_173 === FALSE) { return FALSE; }
	}


	/* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher */
	protected $match_ElseIfPart_typestack = array('ElseIfPart');
	function match_ElseIfPart ($stack = array()) {
		$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_183 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_183 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_183 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_183 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_183 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_183 = FALSE; break; }
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_183 = FALSE; break; }
			$_183 = TRUE; break;
		}
		while(0);
		if( $_183 === TRUE ) { return $this->finalise($result); }
		if( $_183 === FALSE) { return FALSE; }
	}


	/* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher */
	protected $match_ElsePart_typestack = array('ElsePart');
	function match_ElsePart ($stack = array()) {
		$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
		$_191 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_191 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_191 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_191 = FALSE; break; }
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_191 = FALSE; break; }
			$_191 = TRUE; break;
		}
		while(0);
		if( $_191 === TRUE ) { return $this->finalise($result); }
		if( $_191 === FALSE) { return FALSE; }
	}


	/* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
	protected $match_If_typestack = array('If');
	function match_If ($stack = array()) {
		$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
		$_201 = NULL;
		do {
			$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_201 = FALSE; break; }
			while (true) {
				$res_194 = $result;
				$pos_194 = $this->pos;
				$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else {
					$result = $res_194;
					$this->pos = $pos_194;
					unset( $res_194 );
					unset( $pos_194 );
					break;
				}
			}
			$res_195 = $result;
			$pos_195 = $this->pos;
			$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_195;
				$this->pos = $pos_195;
				unset( $res_195 );
				unset( $pos_195 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_201 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_201 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_201 = FALSE; break; }
			$_201 = TRUE; break;
		}
		while(0);
		if( $_201 === TRUE ) { return $this->finalise($result); }
		if( $_201 === FALSE) { return FALSE; }
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
	protected $match_Require_typestack = array('Require');
	function match_Require ($stack = array()) {
		$matchrule = "Require"; $result = $this->construct($matchrule, $matchrule, null);
		$_217 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_217 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_217 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_217 = FALSE; break; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
			$_213 = NULL;
			do {
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Method" );
				}
				else { $_213 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_213 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else { $_213 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_213 = FALSE; break; }
				$_213 = TRUE; break;
			}
			while(0);
			if( $_213 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Call' );
			}
			if( $_213 === FALSE) {
				$result = array_pop($stack);
				$_217 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_217 = FALSE; break; }
			$_217 = TRUE; break;
		}
		while(0);
		if( $_217 === TRUE ) { return $this->finalise($result); }
		if( $_217 === FALSE) { return FALSE; }
	}



	function Require_Call(&$res, $sub) {
		$res['php'] = "Requirements::".$sub['Method']['text'].'('.$sub['CallArguments']['php'].');';
	}
   
	
	/* CacheBlockArgument:
   !( "if " | "unless " )
	( 
      :DollarMarkedLookup |
		:QuotedString |
		:Lookup
	) */
	protected $match_CacheBlockArgument_typestack = array('CacheBlockArgument');
	function match_CacheBlockArgument ($stack = array()) {
		$matchrule = "CacheBlockArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_237 = NULL;
		do {
			$res_225 = $result;
			$pos_225 = $this->pos;
			$_224 = NULL;
			do {
				$_222 = NULL;
				do {
					$res_219 = $result;
					$pos_219 = $this->pos;
					if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_222 = TRUE; break;
					}
					$result = $res_219;
					$this->pos = $pos_219;
					if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_222 = TRUE; break;
					}
					$result = $res_219;
					$this->pos = $pos_219;
					$_222 = FALSE; break;
				}
				while(0);
				if( $_222 === FALSE) { $_224 = FALSE; break; }
				$_224 = TRUE; break;
			}
			while(0);
			if( $_224 === TRUE ) {
				$result = $res_225;
				$this->pos = $pos_225;
				$_237 = FALSE; break;
			}
			if( $_224 === FALSE) {
				$result = $res_225;
				$this->pos = $pos_225;
			}
			$_235 = NULL;
			do {
				$_233 = NULL;
				do {
					$res_226 = $result;
					$pos_226 = $this->pos;
					$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "DollarMarkedLookup" );
						$_233 = TRUE; break;
					}
					$result = $res_226;
					$this->pos = $pos_226;
					$_231 = NULL;
					do {
						$res_228 = $result;
						$pos_228 = $this->pos;
						$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "QuotedString" );
							$_231 = TRUE; break;
						}
						$result = $res_228;
						$this->pos = $pos_228;
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
							$_231 = TRUE; break;
						}
						$result = $res_228;
						$this->pos = $pos_228;
						$_231 = FALSE; break;
					}
					while(0);
					if( $_231 === TRUE ) { $_233 = TRUE; break; }
					$result = $res_226;
					$this->pos = $pos_226;
					$_233 = FALSE; break;
				}
				while(0);
				if( $_233 === FALSE) { $_235 = FALSE; break; }
				$_235 = TRUE; break;
			}
			while(0);
			if( $_235 === FALSE) { $_237 = FALSE; break; }
			$_237 = TRUE; break;
		}
		while(0);
		if( $_237 === TRUE ) { return $this->finalise($result); }
		if( $_237 === FALSE) { return FALSE; }
	}



	function CacheBlockArgument_DollarMarkedLookup(&$res, $sub) {
		$res['php'] = $sub['Lookup']['php'];
	}
	
	function CacheBlockArgument_QuotedString(&$res, $sub) {
		$res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text']) . "'";
	}
	
	function CacheBlockArgument_Lookup(&$res, $sub) {
		$res['php'] = $sub['php'];
	}
		
	/* CacheBlockArguments: CacheBlockArgument ( < "," < CacheBlockArgument )* */
	protected $match_CacheBlockArguments_typestack = array('CacheBlockArguments');
	function match_CacheBlockArguments ($stack = array()) {
		$matchrule = "CacheBlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
		$_246 = NULL;
		do {
			$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_246 = FALSE; break; }
			while (true) {
				$res_245 = $result;
				$pos_245 = $this->pos;
				$_244 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_244 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_244 = FALSE; break; }
					$_244 = TRUE; break;
				}
				while(0);
				if( $_244 === FALSE) {
					$result = $res_245;
					$this->pos = $pos_245;
					unset( $res_245 );
					unset( $pos_245 );
					break;
				}
			}
			$_246 = TRUE; break;
		}
		while(0);
		if( $_246 === TRUE ) { return $this->finalise($result); }
		if( $_246 === FALSE) { return FALSE; }
	}



	function CacheBlockArguments_CacheBlockArgument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ".'_'.";
		else $res['php'] = '';
		
		$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
	}
	
	/* CacheBlockTemplate: (Comment | If | Require |    OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_CacheBlockTemplate_typestack = array('CacheBlockTemplate','Template');
	function match_CacheBlockTemplate ($stack = array()) {
		$matchrule = "CacheBlockTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'CacheRestrictedTemplate'));
		$count = 0;
		while (true) {
			$res_282 = $result;
			$pos_282 = $this->pos;
			$_281 = NULL;
			do {
				$_279 = NULL;
				do {
					$res_248 = $result;
					$pos_248 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_279 = TRUE; break;
					}
					$result = $res_248;
					$this->pos = $pos_248;
					$_277 = NULL;
					do {
						$res_250 = $result;
						$pos_250 = $this->pos;
						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_277 = TRUE; break;
						}
						$result = $res_250;
						$this->pos = $pos_250;
						$_275 = NULL;
						do {
							$res_252 = $result;
							$pos_252 = $this->pos;
							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_275 = TRUE; break;
							}
							$result = $res_252;
							$this->pos = $pos_252;
							$_273 = NULL;
							do {
								$res_254 = $result;
								$pos_254 = $this->pos;
								$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_273 = TRUE; break;
								}
								$result = $res_254;
								$this->pos = $pos_254;
								$_271 = NULL;
								do {
									$res_256 = $result;
									$pos_256 = $this->pos;
									$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_271 = TRUE; break;
									}
									$result = $res_256;
									$this->pos = $pos_256;
									$_269 = NULL;
									do {
										$res_258 = $result;
										$pos_258 = $this->pos;
										$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_269 = TRUE; break;
										}
										$result = $res_258;
										$this->pos = $pos_258;
										$_267 = NULL;
										do {
											$res_260 = $result;
											$pos_260 = $this->pos;
											$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_267 = TRUE; break;
											}
											$result = $res_260;
											$this->pos = $pos_260;
											$_265 = NULL;
											do {
												$res_262 = $result;
												$pos_262 = $this->pos;
												$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_265 = TRUE; break;
												}
												$result = $res_262;
												$this->pos = $pos_262;
												$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_265 = TRUE; break;
												}
												$result = $res_262;
												$this->pos = $pos_262;
												$_265 = FALSE; break;
											}
											while(0);
											if( $_265 === TRUE ) { $_267 = TRUE; break; }
											$result = $res_260;
											$this->pos = $pos_260;
											$_267 = FALSE; break;
										}
										while(0);
										if( $_267 === TRUE ) { $_269 = TRUE; break; }
										$result = $res_258;
										$this->pos = $pos_258;
										$_269 = FALSE; break;
									}
									while(0);
									if( $_269 === TRUE ) { $_271 = TRUE; break; }
									$result = $res_256;
									$this->pos = $pos_256;
									$_271 = FALSE; break;
								}
								while(0);
								if( $_271 === TRUE ) { $_273 = TRUE; break; }
								$result = $res_254;
								$this->pos = $pos_254;
								$_273 = FALSE; break;
							}
							while(0);
							if( $_273 === TRUE ) { $_275 = TRUE; break; }
							$result = $res_252;
							$this->pos = $pos_252;
							$_275 = FALSE; break;
						}
						while(0);
						if( $_275 === TRUE ) { $_277 = TRUE; break; }
						$result = $res_250;
						$this->pos = $pos_250;
						$_277 = FALSE; break;
					}
					while(0);
					if( $_277 === TRUE ) { $_279 = TRUE; break; }
					$result = $res_248;
					$this->pos = $pos_248;
					$_279 = FALSE; break;
				}
				while(0);
				if( $_279 === FALSE) { $_281 = FALSE; break; }
				$_281 = TRUE; break;
			}
			while(0);
			if( $_281 === FALSE) {
				$result = $res_282;
				$this->pos = $pos_282;
				unset( $res_282 );
				unset( $pos_282 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}



		
	/* UncachedBlock: 
	'<%' < "uncached" < CacheBlockArguments? ( < Conditional:("if"|"unless") > Condition:IfArgument )? > '%>'
		Template:$TemplateMatcher?
	'<%' < 'end_' ("uncached"|"cached"|"cacheblock") > '%>' */
	protected $match_UncachedBlock_typestack = array('UncachedBlock');
	function match_UncachedBlock ($stack = array()) {
		$matchrule = "UncachedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_319 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_287 = $result;
			$pos_287 = $this->pos;
			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_287;
				$this->pos = $pos_287;
				unset( $res_287 );
				unset( $pos_287 );
			}
			$res_299 = $result;
			$pos_299 = $this->pos;
			$_298 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_294 = NULL;
				do {
					$_292 = NULL;
					do {
						$res_289 = $result;
						$pos_289 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_292 = TRUE; break;
						}
						$result = $res_289;
						$this->pos = $pos_289;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_292 = TRUE; break;
						}
						$result = $res_289;
						$this->pos = $pos_289;
						$_292 = FALSE; break;
					}
					while(0);
					if( $_292 === FALSE) { $_294 = FALSE; break; }
					$_294 = TRUE; break;
				}
				while(0);
				if( $_294 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_294 === FALSE) {
					$result = array_pop($stack);
					$_298 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_298 = FALSE; break; }
				$_298 = TRUE; break;
			}
			while(0);
			if( $_298 === FALSE) {
				$result = $res_299;
				$this->pos = $pos_299;
				unset( $res_299 );
				unset( $pos_299 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			$res_302 = $result;
			$pos_302 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_302;
				$this->pos = $pos_302;
				unset( $res_302 );
				unset( $pos_302 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			$_315 = NULL;
			do {
				$_313 = NULL;
				do {
					$res_306 = $result;
					$pos_306 = $this->pos;
					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_313 = TRUE; break;
					}
					$result = $res_306;
					$this->pos = $pos_306;
					$_311 = NULL;
					do {
						$res_308 = $result;
						$pos_308 = $this->pos;
						if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_311 = TRUE; break;
						}
						$result = $res_308;
						$this->pos = $pos_308;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_311 = TRUE; break;
						}
						$result = $res_308;
						$this->pos = $pos_308;
						$_311 = FALSE; break;
					}
					while(0);
					if( $_311 === TRUE ) { $_313 = TRUE; break; }
					$result = $res_306;
					$this->pos = $pos_306;
					$_313 = FALSE; break;
				}
				while(0);
				if( $_313 === FALSE) { $_315 = FALSE; break; }
				$_315 = TRUE; break;
			}
			while(0);
			if( $_315 === FALSE) { $_319 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_319 = FALSE; break; }
			$_319 = TRUE; break;
		}
		while(0);
		if( $_319 === TRUE ) { return $this->finalise($result); }
		if( $_319 === FALSE) { return FALSE; }
	}



	function UncachedBlock_Template(&$res, $sub){
		$res['php'] = $sub['php'];
	}
	
	/* CacheRestrictedTemplate: (Comment | If | Require | CacheBlock | UncachedBlock | OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_CacheRestrictedTemplate_typestack = array('CacheRestrictedTemplate','Template');
	function match_CacheRestrictedTemplate ($stack = array()) {
		$matchrule = "CacheRestrictedTemplate"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_363 = $result;
			$pos_363 = $this->pos;
			$_362 = NULL;
			do {
				$_360 = NULL;
				do {
					$res_321 = $result;
					$pos_321 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_360 = TRUE; break;
					}
					$result = $res_321;
					$this->pos = $pos_321;
					$_358 = NULL;
					do {
						$res_323 = $result;
						$pos_323 = $this->pos;
						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_358 = TRUE; break;
						}
						$result = $res_323;
						$this->pos = $pos_323;
						$_356 = NULL;
						do {
							$res_325 = $result;
							$pos_325 = $this->pos;
							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_356 = TRUE; break;
							}
							$result = $res_325;
							$this->pos = $pos_325;
							$_354 = NULL;
							do {
								$res_327 = $result;
								$pos_327 = $this->pos;
								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_354 = TRUE; break;
								}
								$result = $res_327;
								$this->pos = $pos_327;
								$_352 = NULL;
								do {
									$res_329 = $result;
									$pos_329 = $this->pos;
									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_352 = TRUE; break;
									}
									$result = $res_329;
									$this->pos = $pos_329;
									$_350 = NULL;
									do {
										$res_331 = $result;
										$pos_331 = $this->pos;
										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_350 = TRUE; break;
										}
										$result = $res_331;
										$this->pos = $pos_331;
										$_348 = NULL;
										do {
											$res_333 = $result;
											$pos_333 = $this->pos;
											$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_348 = TRUE; break;
											}
											$result = $res_333;
											$this->pos = $pos_333;
											$_346 = NULL;
											do {
												$res_335 = $result;
												$pos_335 = $this->pos;
												$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_346 = TRUE; break;
												}
												$result = $res_335;
												$this->pos = $pos_335;
												$_344 = NULL;
												do {
													$res_337 = $result;
													$pos_337 = $this->pos;
													$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_344 = TRUE; break;
													}
													$result = $res_337;
													$this->pos = $pos_337;
													$_342 = NULL;
													do {
														$res_339 = $result;
														$pos_339 = $this->pos;
														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_342 = TRUE; break;
														}
														$result = $res_339;
														$this->pos = $pos_339;
														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_342 = TRUE; break;
														}
														$result = $res_339;
														$this->pos = $pos_339;
														$_342 = FALSE; break;
													}
													while(0);
													if( $_342 === TRUE ) { $_344 = TRUE; break; }
													$result = $res_337;
													$this->pos = $pos_337;
													$_344 = FALSE; break;
												}
												while(0);
												if( $_344 === TRUE ) { $_346 = TRUE; break; }
												$result = $res_335;
												$this->pos = $pos_335;
												$_346 = FALSE; break;
											}
											while(0);
											if( $_346 === TRUE ) { $_348 = TRUE; break; }
											$result = $res_333;
											$this->pos = $pos_333;
											$_348 = FALSE; break;
										}
										while(0);
										if( $_348 === TRUE ) { $_350 = TRUE; break; }
										$result = $res_331;
										$this->pos = $pos_331;
										$_350 = FALSE; break;
									}
									while(0);
									if( $_350 === TRUE ) { $_352 = TRUE; break; }
									$result = $res_329;
									$this->pos = $pos_329;
									$_352 = FALSE; break;
								}
								while(0);
								if( $_352 === TRUE ) { $_354 = TRUE; break; }
								$result = $res_327;
								$this->pos = $pos_327;
								$_354 = FALSE; break;
							}
							while(0);
							if( $_354 === TRUE ) { $_356 = TRUE; break; }
							$result = $res_325;
							$this->pos = $pos_325;
							$_356 = FALSE; break;
						}
						while(0);
						if( $_356 === TRUE ) { $_358 = TRUE; break; }
						$result = $res_323;
						$this->pos = $pos_323;
						$_358 = FALSE; break;
					}
					while(0);
					if( $_358 === TRUE ) { $_360 = TRUE; break; }
					$result = $res_321;
					$this->pos = $pos_321;
					$_360 = FALSE; break;
				}
				while(0);
				if( $_360 === FALSE) { $_362 = FALSE; break; }
				$_362 = TRUE; break;
			}
			while(0);
			if( $_362 === FALSE) {
				$result = $res_363;
				$this->pos = $pos_363;
				unset( $res_363 );
				unset( $pos_363 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}



	function CacheRestrictedTemplate_CacheBlock(&$res, $sub) { 
		throw new SSTemplateParseException('You cant have cache blocks nested within with, loop or control blocks that are within cache blocks', $this);
	}
	
	function CacheRestrictedTemplate_UncachedBlock(&$res, $sub) { 
		throw new SSTemplateParseException('You cant have uncache blocks nested within with, loop or control blocks that are within cache blocks', $this);
	}
	
	/* CacheBlock: 
	'<%' < CacheTag:("cached"|"cacheblock") < (CacheBlockArguments)? ( < Conditional:("if"|"unless") > Condition:IfArgument )? > '%>'
		(CacheBlock | UncachedBlock | CacheBlockTemplate)*
	'<%' < 'end_' ("cached"|"uncached"|"cacheblock") > '%>' */
	protected $match_CacheBlock_typestack = array('CacheBlock');
	function match_CacheBlock ($stack = array()) {
		$matchrule = "CacheBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_418 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_418 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
			$_371 = NULL;
			do {
				$_369 = NULL;
				do {
					$res_366 = $result;
					$pos_366 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_369 = TRUE; break;
					}
					$result = $res_366;
					$this->pos = $pos_366;
					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_369 = TRUE; break;
					}
					$result = $res_366;
					$this->pos = $pos_366;
					$_369 = FALSE; break;
				}
				while(0);
				if( $_369 === FALSE) { $_371 = FALSE; break; }
				$_371 = TRUE; break;
			}
			while(0);
			if( $_371 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'CacheTag' );
			}
			if( $_371 === FALSE) {
				$result = array_pop($stack);
				$_418 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_376 = $result;
			$pos_376 = $this->pos;
			$_375 = NULL;
			do {
				$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_375 = FALSE; break; }
				$_375 = TRUE; break;
			}
			while(0);
			if( $_375 === FALSE) {
				$result = $res_376;
				$this->pos = $pos_376;
				unset( $res_376 );
				unset( $pos_376 );
			}
			$res_388 = $result;
			$pos_388 = $this->pos;
			$_387 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_383 = NULL;
				do {
					$_381 = NULL;
					do {
						$res_378 = $result;
						$pos_378 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_381 = TRUE; break;
						}
						$result = $res_378;
						$this->pos = $pos_378;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_381 = TRUE; break;
						}
						$result = $res_378;
						$this->pos = $pos_378;
						$_381 = FALSE; break;
					}
					while(0);
					if( $_381 === FALSE) { $_383 = FALSE; break; }
					$_383 = TRUE; break;
				}
				while(0);
				if( $_383 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_383 === FALSE) {
					$result = array_pop($stack);
					$_387 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_387 = FALSE; break; }
				$_387 = TRUE; break;
			}
			while(0);
			if( $_387 === FALSE) {
				$result = $res_388;
				$this->pos = $pos_388;
				unset( $res_388 );
				unset( $pos_388 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_418 = FALSE; break; }
			while (true) {
				$res_401 = $result;
				$pos_401 = $this->pos;
				$_400 = NULL;
				do {
					$_398 = NULL;
					do {
						$res_391 = $result;
						$pos_391 = $this->pos;
						$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_398 = TRUE; break;
						}
						$result = $res_391;
						$this->pos = $pos_391;
						$_396 = NULL;
						do {
							$res_393 = $result;
							$pos_393 = $this->pos;
							$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_396 = TRUE; break;
							}
							$result = $res_393;
							$this->pos = $pos_393;
							$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_396 = TRUE; break;
							}
							$result = $res_393;
							$this->pos = $pos_393;
							$_396 = FALSE; break;
						}
						while(0);
						if( $_396 === TRUE ) { $_398 = TRUE; break; }
						$result = $res_391;
						$this->pos = $pos_391;
						$_398 = FALSE; break;
					}
					while(0);
					if( $_398 === FALSE) { $_400 = FALSE; break; }
					$_400 = TRUE; break;
				}
				while(0);
				if( $_400 === FALSE) {
					$result = $res_401;
					$this->pos = $pos_401;
					unset( $res_401 );
					unset( $pos_401 );
					break;
				}
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_418 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_418 = FALSE; break; }
			$_414 = NULL;
			do {
				$_412 = NULL;
				do {
					$res_405 = $result;
					$pos_405 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_412 = TRUE; break;
					}
					$result = $res_405;
					$this->pos = $pos_405;
					$_410 = NULL;
					do {
						$res_407 = $result;
						$pos_407 = $this->pos;
						if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_410 = TRUE; break;
						}
						$result = $res_407;
						$this->pos = $pos_407;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_410 = TRUE; break;
						}
						$result = $res_407;
						$this->pos = $pos_407;
						$_410 = FALSE; break;
					}
					while(0);
					if( $_410 === TRUE ) { $_412 = TRUE; break; }
					$result = $res_405;
					$this->pos = $pos_405;
					$_412 = FALSE; break;
				}
				while(0);
				if( $_412 === FALSE) { $_414 = FALSE; break; }
				$_414 = TRUE; break;
			}
			while(0);
			if( $_414 === FALSE) { $_418 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_418 = FALSE; break; }
			$_418 = TRUE; break;
		}
		while(0);
		if( $_418 === TRUE ) { return $this->finalise($result); }
		if( $_418 === FALSE) { return FALSE; }
	}



	function CacheBlock__construct(&$res){
		$res['subblocks'] = 0;
	}
	
	function CacheBlock_CacheBlockArguments(&$res, $sub){
		$res['key'] = !empty($sub['php']) ? $sub['php'] : '';
	}
	
	function CacheBlock_Condition(&$res, $sub){
		$res['condition'] = ($res['Conditional']['text'] == 'if' ? '(' : '!(') . $sub['php'] . ') && ';
	}
	
	function CacheBlock_CacheBlock(&$res, $sub){
		$res['php'] .= $sub['php'];
	}
	
	function CacheBlock_UncachedBlock(&$res, $sub){
		$res['php'] .= $sub['php'];
	}
	
	function CacheBlock_CacheBlockTemplate(&$res, $sub){
		// Get the block counter
		$block = ++$res['subblocks'];
		// Build the key for this block from the passed cache key, the block index, and the sha hash of the template itself
		$key = "'" . sha1($sub['php']) . (isset($res['key']) && $res['key'] ? "_'.sha1(".$res['key'].")" : "'") . ".'_$block'";
		// Get any condition
		$condition = isset($res['condition']) ? $res['condition'] : '';
		
		$res['php'] .= 'if ('.$condition.'($partial = $cache->load('.$key.'))) $val .= $partial;' . PHP_EOL;
		$res['php'] .= 'else { $oldval = $val; $val = "";' . PHP_EOL;
		$res['php'] .= $sub['php'] . PHP_EOL;
		$res['php'] .= $condition . ' $cache->save($val); $val = $oldval . $val;' . PHP_EOL;
		$res['php'] .= '}';
	}
	
	/* OldTPart: "_t" < "(" < QuotedString (< "," < CallArguments)? > ")" */
	protected $match_OldTPart_typestack = array('OldTPart');
	function match_OldTPart ($stack = array()) {
		$matchrule = "OldTPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_433 = NULL;
		do {
			if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_433 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_433 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_433 = FALSE; break; }
			$res_430 = $result;
			$pos_430 = $this->pos;
			$_429 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_429 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_429 = FALSE; break; }
				$_429 = TRUE; break;
			}
			while(0);
			if( $_429 === FALSE) {
				$result = $res_430;
				$this->pos = $pos_430;
				unset( $res_430 );
				unset( $pos_430 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_433 = FALSE; break; }
			$_433 = TRUE; break;
		}
		while(0);
		if( $_433 === TRUE ) { return $this->finalise($result); }
		if( $_433 === FALSE) { return FALSE; }
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
	protected $match_OldTTag_typestack = array('OldTTag');
	function match_OldTTag ($stack = array()) {
		$matchrule = "OldTTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_440 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_440 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_440 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_440 = FALSE; break; }
			$_440 = TRUE; break;
		}
		while(0);
		if( $_440 === TRUE ) { return $this->finalise($result); }
		if( $_440 === FALSE) { return FALSE; }
	}



	function OldTTag_OldTPart(&$res, $sub) {
		$res['php'] = $sub['php'];
	}
	 	  
	/* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>"  */
	protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
	function match_OldSprintfTag ($stack = array()) {
		$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_457 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ',') {
				$this->pos += 1;
				$result["text"] .= ',';
			}
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_457 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_457 = FALSE; break; }
			$_457 = TRUE; break;
		}
		while(0);
		if( $_457 === TRUE ) { return $this->finalise($result); }
		if( $_457 === FALSE) { return FALSE; }
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
	protected $match_OldI18NTag_typestack = array('OldI18NTag');
	function match_OldI18NTag ($stack = array()) {
		$matchrule = "OldI18NTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_462 = NULL;
		do {
			$res_459 = $result;
			$pos_459 = $this->pos;
			$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_462 = TRUE; break;
			}
			$result = $res_459;
			$this->pos = $pos_459;
			$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_462 = TRUE; break;
			}
			$result = $res_459;
			$this->pos = $pos_459;
			$_462 = FALSE; break;
		}
		while(0);
		if( $_462 === TRUE ) { return $this->finalise($result); }
		if( $_462 === FALSE) { return FALSE; }
	}



	function OldI18NTag_STR(&$res, $sub) {
		$res['php'] = '$val .= ' . $sub['php'] . ';';
	}
	
	/* BlockArguments: :Argument ( < "," < :Argument)*  */
	protected $match_BlockArguments_typestack = array('BlockArguments');
	function match_BlockArguments ($stack = array()) {
		$matchrule = "BlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
		$_471 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_471 = FALSE; break; }
			while (true) {
				$res_470 = $result;
				$pos_470 = $this->pos;
				$_469 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_469 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_469 = FALSE; break; }
					$_469 = TRUE; break;
				}
				while(0);
				if( $_469 === FALSE) {
					$result = $res_470;
					$this->pos = $pos_470;
					unset( $res_470 );
					unset( $pos_470 );
					break;
				}
			}
			$_471 = TRUE; break;
		}
		while(0);
		if( $_471 === TRUE ) { return $this->finalise($result); }
		if( $_471 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock") ] ) */
	protected $match_NotBlockTag_typestack = array('NotBlockTag');
	function match_NotBlockTag ($stack = array()) {
		$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_505 = NULL;
		do {
			$res_473 = $result;
			$pos_473 = $this->pos;
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_505 = TRUE; break;
			}
			$result = $res_473;
			$this->pos = $pos_473;
			$_503 = NULL;
			do {
				$_500 = NULL;
				do {
					$_498 = NULL;
					do {
						$res_475 = $result;
						$pos_475 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_498 = TRUE; break;
						}
						$result = $res_475;
						$this->pos = $pos_475;
						$_496 = NULL;
						do {
							$res_477 = $result;
							$pos_477 = $this->pos;
							if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_496 = TRUE; break;
							}
							$result = $res_477;
							$this->pos = $pos_477;
							$_494 = NULL;
							do {
								$res_479 = $result;
								$pos_479 = $this->pos;
								if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_494 = TRUE; break;
								}
								$result = $res_479;
								$this->pos = $pos_479;
								$_492 = NULL;
								do {
									$res_481 = $result;
									$pos_481 = $this->pos;
									if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_492 = TRUE; break;
									}
									$result = $res_481;
									$this->pos = $pos_481;
									$_490 = NULL;
									do {
										$res_483 = $result;
										$pos_483 = $this->pos;
										if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
											$result["text"] .= $subres;
											$_490 = TRUE; break;
										}
										$result = $res_483;
										$this->pos = $pos_483;
										$_488 = NULL;
										do {
											$res_485 = $result;
											$pos_485 = $this->pos;
											if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
												$result["text"] .= $subres;
												$_488 = TRUE; break;
											}
											$result = $res_485;
											$this->pos = $pos_485;
											if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
												$result["text"] .= $subres;
												$_488 = TRUE; break;
											}
											$result = $res_485;
											$this->pos = $pos_485;
											$_488 = FALSE; break;
										}
										while(0);
										if( $_488 === TRUE ) { $_490 = TRUE; break; }
										$result = $res_483;
										$this->pos = $pos_483;
										$_490 = FALSE; break;
									}
									while(0);
									if( $_490 === TRUE ) { $_492 = TRUE; break; }
									$result = $res_481;
									$this->pos = $pos_481;
									$_492 = FALSE; break;
								}
								while(0);
								if( $_492 === TRUE ) { $_494 = TRUE; break; }
								$result = $res_479;
								$this->pos = $pos_479;
								$_494 = FALSE; break;
							}
							while(0);
							if( $_494 === TRUE ) { $_496 = TRUE; break; }
							$result = $res_477;
							$this->pos = $pos_477;
							$_496 = FALSE; break;
						}
						while(0);
						if( $_496 === TRUE ) { $_498 = TRUE; break; }
						$result = $res_475;
						$this->pos = $pos_475;
						$_498 = FALSE; break;
					}
					while(0);
					if( $_498 === FALSE) { $_500 = FALSE; break; }
					$_500 = TRUE; break;
				}
				while(0);
				if( $_500 === FALSE) { $_503 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_503 = FALSE; break; }
				$_503 = TRUE; break;
			}
			while(0);
			if( $_503 === TRUE ) { $_505 = TRUE; break; }
			$result = $res_473;
			$this->pos = $pos_473;
			$_505 = FALSE; break;
		}
		while(0);
		if( $_505 === TRUE ) { return $this->finalise($result); }
		if( $_505 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher? '<%' < 'end_' '$BlockName' > '%>' */
	protected $match_ClosedBlock_typestack = array('ClosedBlock');
	function match_ClosedBlock ($stack = array()) {
		$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_525 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_525 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_509 = $result;
			$pos_509 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_509;
				$this->pos = $pos_509;
				$_525 = FALSE; break;
			}
			else {
				$result = $res_509;
				$this->pos = $pos_509;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_525 = FALSE; break; }
			$res_515 = $result;
			$pos_515 = $this->pos;
			$_514 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_514 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_514 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_514 = FALSE; break; }
				$_514 = TRUE; break;
			}
			while(0);
			if( $_514 === FALSE) {
				$result = $res_515;
				$this->pos = $pos_515;
				unset( $res_515 );
				unset( $pos_515 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Zap" ); 
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Zap' );
			}
			else {
				$result = array_pop($stack);
				$_525 = FALSE; break;
			}
			$res_518 = $result;
			$pos_518 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_518;
				$this->pos = $pos_518;
				unset( $res_518 );
				unset( $pos_518 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_525 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_525 = FALSE; break; }
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_525 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_525 = FALSE; break; }
			$_525 = TRUE; break;
		}
		while(0);
		if( $_525 === TRUE ) { return $this->finalise($result); }
		if( $_525 === FALSE) { return FALSE; }
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
	protected $match_OpenBlock_typestack = array('OpenBlock');
	function match_OpenBlock ($stack = array()) {
		$matchrule = "OpenBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_538 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_538 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_529 = $result;
			$pos_529 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_529;
				$this->pos = $pos_529;
				$_538 = FALSE; break;
			}
			else {
				$result = $res_529;
				$this->pos = $pos_529;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_538 = FALSE; break; }
			$res_535 = $result;
			$pos_535 = $this->pos;
			$_534 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_534 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_534 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_534 = FALSE; break; }
				$_534 = TRUE; break;
			}
			while(0);
			if( $_534 === FALSE) {
				$result = $res_535;
				$this->pos = $pos_535;
				unset( $res_535 );
				unset( $pos_535 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_538 = FALSE; break; }
			$_538 = TRUE; break;
		}
		while(0);
		if( $_538 === TRUE ) { return $this->finalise($result); }
		if( $_538 === FALSE) { return FALSE; }
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
	
	/* MismatchedEndBlock: '<%' < 'end_' :Word > '%>' */
	protected $match_MismatchedEndBlock_typestack = array('MismatchedEndBlock');
	function match_MismatchedEndBlock ($stack = array()) {
		$matchrule = "MismatchedEndBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_546 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_546 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_546 = FALSE; break; }
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Word" );
			}
			else { $_546 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_546 = FALSE; break; }
			$_546 = TRUE; break;
		}
		while(0);
		if( $_546 === TRUE ) { return $this->finalise($result); }
		if( $_546 === FALSE) { return FALSE; }
	}



	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_'.$blockname.' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
	protected $match_MalformedOpenTag_typestack = array('MalformedOpenTag');
	function match_MalformedOpenTag ($stack = array()) {
		$matchrule = "MalformedOpenTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_561 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_561 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_550 = $result;
			$pos_550 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_550;
				$this->pos = $pos_550;
				$_561 = FALSE; break;
			}
			else {
				$result = $res_550;
				$this->pos = $pos_550;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_561 = FALSE; break; }
			$res_560 = $result;
			$pos_560 = $this->pos;
			$_559 = NULL;
			do {
				$res_556 = $result;
				$pos_556 = $this->pos;
				$_555 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_555 = FALSE; break; }
					$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_555 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_555 = FALSE; break; }
					$_555 = TRUE; break;
				}
				while(0);
				if( $_555 === FALSE) {
					$result = $res_556;
					$this->pos = $pos_556;
					unset( $res_556 );
					unset( $pos_556 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_559 = FALSE; break; }
				$_559 = TRUE; break;
			}
			while(0);
			if( $_559 === TRUE ) {
				$result = $res_560;
				$this->pos = $pos_560;
				$_561 = FALSE; break;
			}
			if( $_559 === FALSE) {
				$result = $res_560;
				$this->pos = $pos_560;
			}
			$_561 = TRUE; break;
		}
		while(0);
		if( $_561 === TRUE ) { return $this->finalise($result); }
		if( $_561 === FALSE) { return FALSE; }
	}



	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
	}
	
	/* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
	protected $match_MalformedCloseTag_typestack = array('MalformedCloseTag');
	function match_MalformedCloseTag ($stack = array()) {
		$matchrule = "MalformedCloseTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_573 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_573 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
			$_567 = NULL;
			do {
				if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_567 = FALSE; break; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_567 = FALSE; break; }
				$_567 = TRUE; break;
			}
			while(0);
			if( $_567 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_567 === FALSE) {
				$result = array_pop($stack);
				$_573 = FALSE; break;
			}
			$res_572 = $result;
			$pos_572 = $this->pos;
			$_571 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_571 = FALSE; break; }
				$_571 = TRUE; break;
			}
			while(0);
			if( $_571 === TRUE ) {
				$result = $res_572;
				$this->pos = $pos_572;
				$_573 = FALSE; break;
			}
			if( $_571 === FALSE) {
				$result = $res_572;
				$this->pos = $pos_572;
			}
			$_573 = TRUE; break;
		}
		while(0);
		if( $_573 === TRUE ) { return $this->finalise($result); }
		if( $_573 === FALSE) { return FALSE; }
	}



	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an argument to one?", $this);
	}
	
	/* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
	protected $match_MalformedBlock_typestack = array('MalformedBlock');
	function match_MalformedBlock ($stack = array()) {
		$matchrule = "MalformedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_578 = NULL;
		do {
			$res_575 = $result;
			$pos_575 = $this->pos;
			$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_578 = TRUE; break;
			}
			$result = $res_575;
			$this->pos = $pos_575;
			$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_578 = TRUE; break;
			}
			$result = $res_575;
			$this->pos = $pos_575;
			$_578 = FALSE; break;
		}
		while(0);
		if( $_578 === TRUE ) { return $this->finalise($result); }
		if( $_578 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /./)+ "--%>" */
	protected $match_Comment_typestack = array('Comment');
	function match_Comment ($stack = array()) {
		$matchrule = "Comment"; $result = $this->construct($matchrule, $matchrule, null);
		$_586 = NULL;
		do {
			if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_586 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_584 = $result;
				$pos_584 = $this->pos;
				$_583 = NULL;
				do {
					$res_581 = $result;
					$pos_581 = $this->pos;
					if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_581;
						$this->pos = $pos_581;
						$_583 = FALSE; break;
					}
					else {
						$result = $res_581;
						$this->pos = $pos_581;
					}
					if (( $subres = $this->rx( '/./' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_583 = FALSE; break; }
					$_583 = TRUE; break;
				}
				while(0);
				if( $_583 === FALSE) {
					$result = $res_584;
					$this->pos = $pos_584;
					unset( $res_584 );
					unset( $pos_584 );
					break;
				}
				$count += 1;
			}
			if ($count > 0) {  }
			else { $_586 = FALSE; break; }
			if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_586 = FALSE; break; }
			$_586 = TRUE; break;
		}
		while(0);
		if( $_586 === TRUE ) { return $this->finalise($result); }
		if( $_586 === FALSE) { return FALSE; }
	}



	function Comment__construct(&$res) {
		$res['php'] = '';
	}
		
	/* TopTemplate: (Comment | If | Require | CacheBlock | UncachedBlock | OldI18NTag | ClosedBlock | OpenBlock |  MalformedBlock | MismatchedEndBlock  | Injection | Text)+ */
	protected $match_TopTemplate_typestack = array('TopTemplate','Template');
	function match_TopTemplate ($stack = array()) {
		$matchrule = "TopTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'Template'));
		$count = 0;
		while (true) {
			$res_634 = $result;
			$pos_634 = $this->pos;
			$_633 = NULL;
			do {
				$_631 = NULL;
				do {
					$res_588 = $result;
					$pos_588 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_631 = TRUE; break;
					}
					$result = $res_588;
					$this->pos = $pos_588;
					$_629 = NULL;
					do {
						$res_590 = $result;
						$pos_590 = $this->pos;
						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_629 = TRUE; break;
						}
						$result = $res_590;
						$this->pos = $pos_590;
						$_627 = NULL;
						do {
							$res_592 = $result;
							$pos_592 = $this->pos;
							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_627 = TRUE; break;
							}
							$result = $res_592;
							$this->pos = $pos_592;
							$_625 = NULL;
							do {
								$res_594 = $result;
								$pos_594 = $this->pos;
								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_625 = TRUE; break;
								}
								$result = $res_594;
								$this->pos = $pos_594;
								$_623 = NULL;
								do {
									$res_596 = $result;
									$pos_596 = $this->pos;
									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_623 = TRUE; break;
									}
									$result = $res_596;
									$this->pos = $pos_596;
									$_621 = NULL;
									do {
										$res_598 = $result;
										$pos_598 = $this->pos;
										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_621 = TRUE; break;
										}
										$result = $res_598;
										$this->pos = $pos_598;
										$_619 = NULL;
										do {
											$res_600 = $result;
											$pos_600 = $this->pos;
											$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_619 = TRUE; break;
											}
											$result = $res_600;
											$this->pos = $pos_600;
											$_617 = NULL;
											do {
												$res_602 = $result;
												$pos_602 = $this->pos;
												$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_617 = TRUE; break;
												}
												$result = $res_602;
												$this->pos = $pos_602;
												$_615 = NULL;
												do {
													$res_604 = $result;
													$pos_604 = $this->pos;
													$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_615 = TRUE; break;
													}
													$result = $res_604;
													$this->pos = $pos_604;
													$_613 = NULL;
													do {
														$res_606 = $result;
														$pos_606 = $this->pos;
														$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_613 = TRUE; break;
														}
														$result = $res_606;
														$this->pos = $pos_606;
														$_611 = NULL;
														do {
															$res_608 = $result;
															$pos_608 = $this->pos;
															$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_611 = TRUE; break;
															}
															$result = $res_608;
															$this->pos = $pos_608;
															$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_611 = TRUE; break;
															}
															$result = $res_608;
															$this->pos = $pos_608;
															$_611 = FALSE; break;
														}
														while(0);
														if( $_611 === TRUE ) { $_613 = TRUE; break; }
														$result = $res_606;
														$this->pos = $pos_606;
														$_613 = FALSE; break;
													}
													while(0);
													if( $_613 === TRUE ) { $_615 = TRUE; break; }
													$result = $res_604;
													$this->pos = $pos_604;
													$_615 = FALSE; break;
												}
												while(0);
												if( $_615 === TRUE ) { $_617 = TRUE; break; }
												$result = $res_602;
												$this->pos = $pos_602;
												$_617 = FALSE; break;
											}
											while(0);
											if( $_617 === TRUE ) { $_619 = TRUE; break; }
											$result = $res_600;
											$this->pos = $pos_600;
											$_619 = FALSE; break;
										}
										while(0);
										if( $_619 === TRUE ) { $_621 = TRUE; break; }
										$result = $res_598;
										$this->pos = $pos_598;
										$_621 = FALSE; break;
									}
									while(0);
									if( $_621 === TRUE ) { $_623 = TRUE; break; }
									$result = $res_596;
									$this->pos = $pos_596;
									$_623 = FALSE; break;
								}
								while(0);
								if( $_623 === TRUE ) { $_625 = TRUE; break; }
								$result = $res_594;
								$this->pos = $pos_594;
								$_625 = FALSE; break;
							}
							while(0);
							if( $_625 === TRUE ) { $_627 = TRUE; break; }
							$result = $res_592;
							$this->pos = $pos_592;
							$_627 = FALSE; break;
						}
						while(0);
						if( $_627 === TRUE ) { $_629 = TRUE; break; }
						$result = $res_590;
						$this->pos = $pos_590;
						$_629 = FALSE; break;
					}
					while(0);
					if( $_629 === TRUE ) { $_631 = TRUE; break; }
					$result = $res_588;
					$this->pos = $pos_588;
					$_631 = FALSE; break;
				}
				while(0);
				if( $_631 === FALSE) { $_633 = FALSE; break; }
				$_633 = TRUE; break;
			}
			while(0);
			if( $_633 === FALSE) {
				$result = $res_634;
				$this->pos = $pos_634;
				unset( $res_634 );
				unset( $pos_634 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}



	
	/**
	 * The TopTemplate also includes the opening stanza to start off the template
	 */
	function TopTemplate__construct(&$res) {
		$res['php'] = "<?php" . PHP_EOL;
	}

	/* Text: (
		/ [^<${\\]+ / |
		/ (\\.) / |
		'<' !'%' |
		'$' !(/[A-Za-z_]/) |
		'{' !'$' |
		'{$' !(/[A-Za-z_]/)
	)+ */
	protected $match_Text_typestack = array('Text');
	function match_Text ($stack = array()) {
		$matchrule = "Text"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_673 = $result;
			$pos_673 = $this->pos;
			$_672 = NULL;
			do {
				$_670 = NULL;
				do {
					$res_635 = $result;
					$pos_635 = $this->pos;
					if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_670 = TRUE; break;
					}
					$result = $res_635;
					$this->pos = $pos_635;
					$_668 = NULL;
					do {
						$res_637 = $result;
						$pos_637 = $this->pos;
						if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_668 = TRUE; break;
						}
						$result = $res_637;
						$this->pos = $pos_637;
						$_666 = NULL;
						do {
							$res_639 = $result;
							$pos_639 = $this->pos;
							$_642 = NULL;
							do {
								if (substr($this->string,$this->pos,1) == '<') {
									$this->pos += 1;
									$result["text"] .= '<';
								}
								else { $_642 = FALSE; break; }
								$res_641 = $result;
								$pos_641 = $this->pos;
								if (substr($this->string,$this->pos,1) == '%') {
									$this->pos += 1;
									$result["text"] .= '%';
									$result = $res_641;
									$this->pos = $pos_641;
									$_642 = FALSE; break;
								}
								else {
									$result = $res_641;
									$this->pos = $pos_641;
								}
								$_642 = TRUE; break;
							}
							while(0);
							if( $_642 === TRUE ) { $_666 = TRUE; break; }
							$result = $res_639;
							$this->pos = $pos_639;
							$_664 = NULL;
							do {
								$res_644 = $result;
								$pos_644 = $this->pos;
								$_649 = NULL;
								do {
									if (substr($this->string,$this->pos,1) == '$') {
										$this->pos += 1;
										$result["text"] .= '$';
									}
									else { $_649 = FALSE; break; }
									$res_648 = $result;
									$pos_648 = $this->pos;
									$_647 = NULL;
									do {
										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_647 = FALSE; break; }
										$_647 = TRUE; break;
									}
									while(0);
									if( $_647 === TRUE ) {
										$result = $res_648;
										$this->pos = $pos_648;
										$_649 = FALSE; break;
									}
									if( $_647 === FALSE) {
										$result = $res_648;
										$this->pos = $pos_648;
									}
									$_649 = TRUE; break;
								}
								while(0);
								if( $_649 === TRUE ) { $_664 = TRUE; break; }
								$result = $res_644;
								$this->pos = $pos_644;
								$_662 = NULL;
								do {
									$res_651 = $result;
									$pos_651 = $this->pos;
									$_654 = NULL;
									do {
										if (substr($this->string,$this->pos,1) == '{') {
											$this->pos += 1;
											$result["text"] .= '{';
										}
										else { $_654 = FALSE; break; }
										$res_653 = $result;
										$pos_653 = $this->pos;
										if (substr($this->string,$this->pos,1) == '$') {
											$this->pos += 1;
											$result["text"] .= '$';
											$result = $res_653;
											$this->pos = $pos_653;
											$_654 = FALSE; break;
										}
										else {
											$result = $res_653;
											$this->pos = $pos_653;
										}
										$_654 = TRUE; break;
									}
									while(0);
									if( $_654 === TRUE ) { $_662 = TRUE; break; }
									$result = $res_651;
									$this->pos = $pos_651;
									$_660 = NULL;
									do {
										if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_660 = FALSE; break; }
										$res_659 = $result;
										$pos_659 = $this->pos;
										$_658 = NULL;
										do {
											if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
											else { $_658 = FALSE; break; }
											$_658 = TRUE; break;
										}
										while(0);
										if( $_658 === TRUE ) {
											$result = $res_659;
											$this->pos = $pos_659;
											$_660 = FALSE; break;
										}
										if( $_658 === FALSE) {
											$result = $res_659;
											$this->pos = $pos_659;
										}
										$_660 = TRUE; break;
									}
									while(0);
									if( $_660 === TRUE ) { $_662 = TRUE; break; }
									$result = $res_651;
									$this->pos = $pos_651;
									$_662 = FALSE; break;
								}
								while(0);
								if( $_662 === TRUE ) { $_664 = TRUE; break; }
								$result = $res_644;
								$this->pos = $pos_644;
								$_664 = FALSE; break;
							}
							while(0);
							if( $_664 === TRUE ) { $_666 = TRUE; break; }
							$result = $res_639;
							$this->pos = $pos_639;
							$_666 = FALSE; break;
						}
						while(0);
						if( $_666 === TRUE ) { $_668 = TRUE; break; }
						$result = $res_637;
						$this->pos = $pos_637;
						$_668 = FALSE; break;
					}
					while(0);
					if( $_668 === TRUE ) { $_670 = TRUE; break; }
					$result = $res_635;
					$this->pos = $pos_635;
					$_670 = FALSE; break;
				}
				while(0);
				if( $_670 === FALSE) { $_672 = FALSE; break; }
				$_672 = TRUE; break;
			}
			while(0);
			if( $_672 === FALSE) {
				$result = $res_673;
				$this->pos = $pos_673;
				unset( $res_673 );
				unset( $pos_673 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}



	
	/**
	 * We convert text 
	 */
	function Text__finalise(&$res) {
		$text = $res['text'];
		
		// Unescape any escaped characters in the text, then put back escapes for any single quotes and backslashes
		$text = stripslashes($text);
		$text = addcslashes($text, '\'\\');

		// TODO: This is pretty ugly & gets applied on all files not just html. I wonder if we can make this non-dynamically calculated
		$text = preg_replace(
			'/href\s*\=\s*\"\#/', 
			'href="\' . (SSViewer::$options[\'rewriteHashlinks\'] ? Convert::raw2att( $_SERVER[\'REQUEST_URI\'] ) : "") . \'#',
			$text
		);

		$res['php'] .= '$val .= \'' . $text . '\';' . PHP_EOL;
	}
		
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
		if (!trim($string)) {
			$code = '';
		}
		else {
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
		}
		
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
