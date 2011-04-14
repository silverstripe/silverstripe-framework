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

Angle Bracket: angle brackets "<" and ">" are used to eat whitespace between template elements

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
	
	/* Template: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_Template_typestack = array('Template');
	function match_Template ($stack = array()) {
		$matchrule = "Template"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_46 = $result;
			$pos_46 = $this->pos;
			$_45 = NULL;
			do {
				$_43 = NULL;
				do {
					$res_0 = $result;
					$pos_0 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_43 = TRUE; break;
					}
					$result = $res_0;
					$this->pos = $pos_0;
					$_41 = NULL;
					do {
						$res_2 = $result;
						$pos_2 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_41 = TRUE; break;
						}
						$result = $res_2;
						$this->pos = $pos_2;
						$_39 = NULL;
						do {
							$res_4 = $result;
							$pos_4 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_39 = TRUE; break;
							}
							$result = $res_4;
							$this->pos = $pos_4;
							$_37 = NULL;
							do {
								$res_6 = $result;
								$pos_6 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_37 = TRUE; break;
								}
								$result = $res_6;
								$this->pos = $pos_6;
								$_35 = NULL;
								do {
									$res_8 = $result;
									$pos_8 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_35 = TRUE; break;
									}
									$result = $res_8;
									$this->pos = $pos_8;
									$_33 = NULL;
									do {
										$res_10 = $result;
										$pos_10 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_33 = TRUE; break;
										}
										$result = $res_10;
										$this->pos = $pos_10;
										$_31 = NULL;
										do {
											$res_12 = $result;
											$pos_12 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_31 = TRUE; break;
											}
											$result = $res_12;
											$this->pos = $pos_12;
											$_29 = NULL;
											do {
												$res_14 = $result;
												$pos_14 = $this->pos;
												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_29 = TRUE; break;
												}
												$result = $res_14;
												$this->pos = $pos_14;
												$_27 = NULL;
												do {
													$res_16 = $result;
													$pos_16 = $this->pos;
													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_27 = TRUE; break;
													}
													$result = $res_16;
													$this->pos = $pos_16;
													$_25 = NULL;
													do {
														$res_18 = $result;
														$pos_18 = $this->pos;
														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_25 = TRUE; break;
														}
														$result = $res_18;
														$this->pos = $pos_18;
														$_23 = NULL;
														do {
															$res_20 = $result;
															$pos_20 = $this->pos;
															$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_23 = TRUE; break;
															}
															$result = $res_20;
															$this->pos = $pos_20;
															$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_23 = TRUE; break;
															}
															$result = $res_20;
															$this->pos = $pos_20;
															$_23 = FALSE; break;
														}
														while(0);
														if( $_23 === TRUE ) { $_25 = TRUE; break; }
														$result = $res_18;
														$this->pos = $pos_18;
														$_25 = FALSE; break;
													}
													while(0);
													if( $_25 === TRUE ) { $_27 = TRUE; break; }
													$result = $res_16;
													$this->pos = $pos_16;
													$_27 = FALSE; break;
												}
												while(0);
												if( $_27 === TRUE ) { $_29 = TRUE; break; }
												$result = $res_14;
												$this->pos = $pos_14;
												$_29 = FALSE; break;
											}
											while(0);
											if( $_29 === TRUE ) { $_31 = TRUE; break; }
											$result = $res_12;
											$this->pos = $pos_12;
											$_31 = FALSE; break;
										}
										while(0);
										if( $_31 === TRUE ) { $_33 = TRUE; break; }
										$result = $res_10;
										$this->pos = $pos_10;
										$_33 = FALSE; break;
									}
									while(0);
									if( $_33 === TRUE ) { $_35 = TRUE; break; }
									$result = $res_8;
									$this->pos = $pos_8;
									$_35 = FALSE; break;
								}
								while(0);
								if( $_35 === TRUE ) { $_37 = TRUE; break; }
								$result = $res_6;
								$this->pos = $pos_6;
								$_37 = FALSE; break;
							}
							while(0);
							if( $_37 === TRUE ) { $_39 = TRUE; break; }
							$result = $res_4;
							$this->pos = $pos_4;
							$_39 = FALSE; break;
						}
						while(0);
						if( $_39 === TRUE ) { $_41 = TRUE; break; }
						$result = $res_2;
						$this->pos = $pos_2;
						$_41 = FALSE; break;
					}
					while(0);
					if( $_41 === TRUE ) { $_43 = TRUE; break; }
					$result = $res_0;
					$this->pos = $pos_0;
					$_43 = FALSE; break;
				}
				while(0);
				if( $_43 === FALSE) { $_45 = FALSE; break; }
				$_45 = TRUE; break;
			}
			while(0);
			if( $_45 === FALSE) {
				$result = $res_46;
				$this->pos = $pos_46;
				unset( $res_46 );
				unset( $pos_46 );
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
		$_57 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_57 = FALSE; break; }
			while (true) {
				$res_56 = $result;
				$pos_56 = $this->pos;
				$_55 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_55 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_55 = FALSE; break; }
					$_55 = TRUE; break;
				}
				while(0);
				if( $_55 === FALSE) {
					$result = $res_56;
					$this->pos = $pos_56;
					unset( $res_56 );
					unset( $pos_56 );
					break;
				}
			}
			$_57 = TRUE; break;
		}
		while(0);
		if( $_57 === TRUE ) { return $this->finalise($result); }
		if( $_57 === FALSE) { return FALSE; }
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
		$_67 = NULL;
		do {
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Method" );
			}
			else { $_67 = FALSE; break; }
			$res_66 = $result;
			$pos_66 = $this->pos;
			$_65 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_65 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$res_62 = $result;
				$pos_62 = $this->pos;
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else {
					$result = $res_62;
					$this->pos = $pos_62;
					unset( $res_62 );
					unset( $pos_62 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_65 = FALSE; break; }
				$_65 = TRUE; break;
			}
			while(0);
			if( $_65 === FALSE) {
				$result = $res_66;
				$this->pos = $pos_66;
				unset( $res_66 );
				unset( $pos_66 );
			}
			$_67 = TRUE; break;
		}
		while(0);
		if( $_67 === TRUE ) { return $this->finalise($result); }
		if( $_67 === FALSE) { return FALSE; }
	}


	/* LookupStep: :Call &"." */
	protected $match_LookupStep_typestack = array('LookupStep');
	function match_LookupStep ($stack = array()) {
		$matchrule = "LookupStep"; $result = $this->construct($matchrule, $matchrule, null);
		$_71 = NULL;
		do {
			$matcher = 'match_'.'Call'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Call" );
			}
			else { $_71 = FALSE; break; }
			$res_70 = $result;
			$pos_70 = $this->pos;
			if (substr($this->string,$this->pos,1) == '.') {
				$this->pos += 1;
				$result["text"] .= '.';
				$result = $res_70;
				$this->pos = $pos_70;
			}
			else {
				$result = $res_70;
				$this->pos = $pos_70;
				$_71 = FALSE; break;
			}
			$_71 = TRUE; break;
		}
		while(0);
		if( $_71 === TRUE ) { return $this->finalise($result); }
		if( $_71 === FALSE) { return FALSE; }
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
		$_85 = NULL;
		do {
			$res_74 = $result;
			$pos_74 = $this->pos;
			$_82 = NULL;
			do {
				$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_82 = FALSE; break; }
				while (true) {
					$res_79 = $result;
					$pos_79 = $this->pos;
					$_78 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == '.') {
							$this->pos += 1;
							$result["text"] .= '.';
						}
						else { $_78 = FALSE; break; }
						$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_78 = FALSE; break; }
						$_78 = TRUE; break;
					}
					while(0);
					if( $_78 === FALSE) {
						$result = $res_79;
						$this->pos = $pos_79;
						unset( $res_79 );
						unset( $pos_79 );
						break;
					}
				}
				if (substr($this->string,$this->pos,1) == '.') {
					$this->pos += 1;
					$result["text"] .= '.';
				}
				else { $_82 = FALSE; break; }
				$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_82 = FALSE; break; }
				$_82 = TRUE; break;
			}
			while(0);
			if( $_82 === TRUE ) { $_85 = TRUE; break; }
			$result = $res_74;
			$this->pos = $pos_74;
			$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_85 = TRUE; break;
			}
			$result = $res_74;
			$this->pos = $pos_74;
			$_85 = FALSE; break;
		}
		while(0);
		if( $_85 === TRUE ) { return $this->finalise($result); }
		if( $_85 === FALSE) { return FALSE; }
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


	/* Translate: "<%t" < Entity < (Default:QuotedString)? < (!("is" "=") < "is" < Context:QuotedString)? < (InjectionVariables)? > "%>" */
	protected $match_Translate_typestack = array('Translate');
	function match_Translate ($stack = array()) {
		$matchrule = "Translate"; $result = $this->construct($matchrule, $matchrule, null);
		$_111 = NULL;
		do {
			if (( $subres = $this->literal( '<%t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_111 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Entity'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_111 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_93 = $result;
			$pos_93 = $this->pos;
			$_92 = NULL;
			do {
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Default" );
				}
				else { $_92 = FALSE; break; }
				$_92 = TRUE; break;
			}
			while(0);
			if( $_92 === FALSE) {
				$result = $res_93;
				$this->pos = $pos_93;
				unset( $res_93 );
				unset( $pos_93 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_104 = $result;
			$pos_104 = $this->pos;
			$_103 = NULL;
			do {
				$res_98 = $result;
				$pos_98 = $this->pos;
				$_97 = NULL;
				do {
					if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_97 = FALSE; break; }
					if (substr($this->string,$this->pos,1) == '=') {
						$this->pos += 1;
						$result["text"] .= '=';
					}
					else { $_97 = FALSE; break; }
					$_97 = TRUE; break;
				}
				while(0);
				if( $_97 === TRUE ) {
					$result = $res_98;
					$this->pos = $pos_98;
					$_103 = FALSE; break;
				}
				if( $_97 === FALSE) {
					$result = $res_98;
					$this->pos = $pos_98;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_103 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Context" );
				}
				else { $_103 = FALSE; break; }
				$_103 = TRUE; break;
			}
			while(0);
			if( $_103 === FALSE) {
				$result = $res_104;
				$this->pos = $pos_104;
				unset( $res_104 );
				unset( $pos_104 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_108 = $result;
			$pos_108 = $this->pos;
			$_107 = NULL;
			do {
				$matcher = 'match_'.'InjectionVariables'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_107 = FALSE; break; }
				$_107 = TRUE; break;
			}
			while(0);
			if( $_107 === FALSE) {
				$result = $res_108;
				$this->pos = $pos_108;
				unset( $res_108 );
				unset( $pos_108 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_111 = FALSE; break; }
			$_111 = TRUE; break;
		}
		while(0);
		if( $_111 === TRUE ) { return $this->finalise($result); }
		if( $_111 === FALSE) { return FALSE; }
	}


	/* InjectionVariables: (< InjectionName:Word "=" Argument)+ */
	protected $match_InjectionVariables_typestack = array('InjectionVariables');
	function match_InjectionVariables ($stack = array()) {
		$matchrule = "InjectionVariables"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_118 = $result;
			$pos_118 = $this->pos;
			$_117 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "InjectionName" );
				}
				else { $_117 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '=') {
					$this->pos += 1;
					$result["text"] .= '=';
				}
				else { $_117 = FALSE; break; }
				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_117 = FALSE; break; }
				$_117 = TRUE; break;
			}
			while(0);
			if( $_117 === FALSE) {
				$result = $res_118;
				$this->pos = $pos_118;
				unset( $res_118 );
				unset( $pos_118 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}


	/* Entity: / [A-Za-z_] [\w\.]* / */
	protected $match_Entity_typestack = array('Entity');
	function match_Entity ($stack = array()) {
		$matchrule = "Entity"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/ [A-Za-z_] [\w\.]* /' ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $this->finalise($result);
		}
		else { return FALSE; }
	}




	function Translate__construct(&$res) {
		$res['php'] = '$val .= _t(';
	}

	function Translate_Entity(&$res, $sub) {
		$res['php'] .= "'$sub[text]'";
	}

	function Translate_Default(&$res, $sub) {
		$res['php'] .= ",$sub[text]";
	}

	function Translate_Context(&$res, $sub) {
		$res['php'] .= ",$sub[text]";
	}

	function Translate_InjectionVariables(&$res, $sub) {
		$res['php'] .= ",$sub[php]";
	}

	function Translate__finalise(&$res) {
		$res['php'] .= ');';
	}

	function InjectionVariables__construct(&$res) {
		$res['php'] = "array(";
	}

	function InjectionVariables_InjectionName(&$res, $sub) {
		$res['php'] .= "'$sub[text]'=>";
	}

	function InjectionVariables_Argument(&$res, $sub) {
		$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']) . ',';
	}

	function InjectionVariables__finalise(&$res) {
		if (substr($res['php'], -1) == ',') $res['php'] = substr($res['php'], 0, -1); //remove last comma in the array
		$res['php'] .= ')';
	}


	/* SimpleInjection: '$' :Lookup */
	protected $match_SimpleInjection_typestack = array('SimpleInjection');
	function match_SimpleInjection ($stack = array()) {
		$matchrule = "SimpleInjection"; $result = $this->construct($matchrule, $matchrule, null);
		$_122 = NULL;
		do {
			if (substr($this->string,$this->pos,1) == '$') {
				$this->pos += 1;
				$result["text"] .= '$';
			}
			else { $_122 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_122 = FALSE; break; }
			$_122 = TRUE; break;
		}
		while(0);
		if( $_122 === TRUE ) { return $this->finalise($result); }
		if( $_122 === FALSE) { return FALSE; }
	}


	/* BracketInjection: '{$' :Lookup "}" */
	protected $match_BracketInjection_typestack = array('BracketInjection');
	function match_BracketInjection ($stack = array()) {
		$matchrule = "BracketInjection"; $result = $this->construct($matchrule, $matchrule, null);
		$_127 = NULL;
		do {
			if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_127 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_127 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '}') {
				$this->pos += 1;
				$result["text"] .= '}';
			}
			else { $_127 = FALSE; break; }
			$_127 = TRUE; break;
		}
		while(0);
		if( $_127 === TRUE ) { return $this->finalise($result); }
		if( $_127 === FALSE) { return FALSE; }
	}


	/* Injection: BracketInjection | SimpleInjection */
	protected $match_Injection_typestack = array('Injection');
	function match_Injection ($stack = array()) {
		$matchrule = "Injection"; $result = $this->construct($matchrule, $matchrule, null);
		$_132 = NULL;
		do {
			$res_129 = $result;
			$pos_129 = $this->pos;
			$matcher = 'match_'.'BracketInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_132 = TRUE; break;
			}
			$result = $res_129;
			$this->pos = $pos_129;
			$matcher = 'match_'.'SimpleInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_132 = TRUE; break;
			}
			$result = $res_129;
			$this->pos = $pos_129;
			$_132 = FALSE; break;
		}
		while(0);
		if( $_132 === TRUE ) { return $this->finalise($result); }
		if( $_132 === FALSE) { return FALSE; }
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
		$_138 = NULL;
		do {
			$stack[] = $result; $result = $this->construct( $matchrule, "q" ); 
			if (( $subres = $this->rx( '/[\'"]/' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'q' );
			}
			else {
				$result = array_pop($stack);
				$_138 = FALSE; break;
			}
			$stack[] = $result; $result = $this->construct( $matchrule, "String" ); 
			if (( $subres = $this->rx( '/ (\\\\\\\\ | \\\\. | [^'.$this->expression($result, $stack, 'q').'\\\\])* /' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'String' );
			}
			else {
				$result = array_pop($stack);
				$_138 = FALSE; break;
			}
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'q').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_138 = FALSE; break; }
			$_138 = TRUE; break;
		}
		while(0);
		if( $_138 === TRUE ) { return $this->finalise($result); }
		if( $_138 === FALSE) { return FALSE; }
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
		$_158 = NULL;
		do {
			$res_141 = $result;
			$pos_141 = $this->pos;
			$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "DollarMarkedLookup" );
				$_158 = TRUE; break;
			}
			$result = $res_141;
			$this->pos = $pos_141;
			$_156 = NULL;
			do {
				$res_143 = $result;
				$pos_143 = $this->pos;
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "QuotedString" );
					$_156 = TRUE; break;
				}
				$result = $res_143;
				$this->pos = $pos_143;
				$_154 = NULL;
				do {
					$res_145 = $result;
					$pos_145 = $this->pos;
					$_151 = NULL;
					do {
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
						}
						else { $_151 = FALSE; break; }
						$res_150 = $result;
						$pos_150 = $this->pos;
						$_149 = NULL;
						do {
							if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
							$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_149 = FALSE; break; }
							$_149 = TRUE; break;
						}
						while(0);
						if( $_149 === TRUE ) {
							$result = $res_150;
							$this->pos = $pos_150;
							$_151 = FALSE; break;
						}
						if( $_149 === FALSE) {
							$result = $res_150;
							$this->pos = $pos_150;
						}
						$_151 = TRUE; break;
					}
					while(0);
					if( $_151 === TRUE ) { $_154 = TRUE; break; }
					$result = $res_145;
					$this->pos = $pos_145;
					$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "FreeString" );
						$_154 = TRUE; break;
					}
					$result = $res_145;
					$this->pos = $pos_145;
					$_154 = FALSE; break;
				}
				while(0);
				if( $_154 === TRUE ) { $_156 = TRUE; break; }
				$result = $res_143;
				$this->pos = $pos_143;
				$_156 = FALSE; break;
			}
			while(0);
			if( $_156 === TRUE ) { $_158 = TRUE; break; }
			$result = $res_141;
			$this->pos = $pos_141;
			$_158 = FALSE; break;
		}
		while(0);
		if( $_158 === TRUE ) { return $this->finalise($result); }
		if( $_158 === FALSE) { return FALSE; }
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
		$_167 = NULL;
		do {
			$res_160 = $result;
			$pos_160 = $this->pos;
			if (( $subres = $this->literal( '==' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_167 = TRUE; break;
			}
			$result = $res_160;
			$this->pos = $pos_160;
			$_165 = NULL;
			do {
				$res_162 = $result;
				$pos_162 = $this->pos;
				if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_165 = TRUE; break;
				}
				$result = $res_162;
				$this->pos = $pos_162;
				if (substr($this->string,$this->pos,1) == '=') {
					$this->pos += 1;
					$result["text"] .= '=';
					$_165 = TRUE; break;
				}
				$result = $res_162;
				$this->pos = $pos_162;
				$_165 = FALSE; break;
			}
			while(0);
			if( $_165 === TRUE ) { $_167 = TRUE; break; }
			$result = $res_160;
			$this->pos = $pos_160;
			$_167 = FALSE; break;
		}
		while(0);
		if( $_167 === TRUE ) { return $this->finalise($result); }
		if( $_167 === FALSE) { return FALSE; }
	}


	/* Comparison: Argument < ComparisonOperator > Argument */
	protected $match_Comparison_typestack = array('Comparison');
	function match_Comparison ($stack = array()) {
		$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
		$_174 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_174 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_174 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_174 = FALSE; break; }
			$_174 = TRUE; break;
		}
		while(0);
		if( $_174 === TRUE ) { return $this->finalise($result); }
		if( $_174 === FALSE) { return FALSE; }
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
		$_181 = NULL;
		do {
			$res_179 = $result;
			$pos_179 = $this->pos;
			$_178 = NULL;
			do {
				$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
				if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Not' );
				}
				else {
					$result = array_pop($stack);
					$_178 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_178 = TRUE; break;
			}
			while(0);
			if( $_178 === FALSE) {
				$result = $res_179;
				$this->pos = $pos_179;
				unset( $res_179 );
				unset( $pos_179 );
			}
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_181 = FALSE; break; }
			$_181 = TRUE; break;
		}
		while(0);
		if( $_181 === TRUE ) { return $this->finalise($result); }
		if( $_181 === FALSE) { return FALSE; }
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
		$_186 = NULL;
		do {
			$res_183 = $result;
			$pos_183 = $this->pos;
			$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_186 = TRUE; break;
			}
			$result = $res_183;
			$this->pos = $pos_183;
			$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_186 = TRUE; break;
			}
			$result = $res_183;
			$this->pos = $pos_183;
			$_186 = FALSE; break;
		}
		while(0);
		if( $_186 === TRUE ) { return $this->finalise($result); }
		if( $_186 === FALSE) { return FALSE; }
	}



	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* BooleanOperator: "||" | "&&" */
	protected $match_BooleanOperator_typestack = array('BooleanOperator');
	function match_BooleanOperator ($stack = array()) {
		$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_191 = NULL;
		do {
			$res_188 = $result;
			$pos_188 = $this->pos;
			if (( $subres = $this->literal( '||' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_191 = TRUE; break;
			}
			$result = $res_188;
			$this->pos = $pos_188;
			if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_191 = TRUE; break;
			}
			$result = $res_188;
			$this->pos = $pos_188;
			$_191 = FALSE; break;
		}
		while(0);
		if( $_191 === TRUE ) { return $this->finalise($result); }
		if( $_191 === FALSE) { return FALSE; }
	}


	/* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
	protected $match_IfArgument_typestack = array('IfArgument');
	function match_IfArgument ($stack = array()) {
		$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_200 = NULL;
		do {
			$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgumentPortion" );
			}
			else { $_200 = FALSE; break; }
			while (true) {
				$res_199 = $result;
				$pos_199 = $this->pos;
				$_198 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BooleanOperator" );
					}
					else { $_198 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "IfArgumentPortion" );
					}
					else { $_198 = FALSE; break; }
					$_198 = TRUE; break;
				}
				while(0);
				if( $_198 === FALSE) {
					$result = $res_199;
					$this->pos = $pos_199;
					unset( $res_199 );
					unset( $pos_199 );
					break;
				}
			}
			$_200 = TRUE; break;
		}
		while(0);
		if( $_200 === TRUE ) { return $this->finalise($result); }
		if( $_200 === FALSE) { return FALSE; }
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
		$_210 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_210 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_210 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_210 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_210 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_210 = FALSE; break; }
			$res_209 = $result;
			$pos_209 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_209;
				$this->pos = $pos_209;
				unset( $res_209 );
				unset( $pos_209 );
			}
			$_210 = TRUE; break;
		}
		while(0);
		if( $_210 === TRUE ) { return $this->finalise($result); }
		if( $_210 === FALSE) { return FALSE; }
	}


	/* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher */
	protected $match_ElseIfPart_typestack = array('ElseIfPart');
	function match_ElseIfPart ($stack = array()) {
		$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_220 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_220 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_220 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_220 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_220 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_220 = FALSE; break; }
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_220 = FALSE; break; }
			$_220 = TRUE; break;
		}
		while(0);
		if( $_220 === TRUE ) { return $this->finalise($result); }
		if( $_220 === FALSE) { return FALSE; }
	}


	/* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher */
	protected $match_ElsePart_typestack = array('ElsePart');
	function match_ElsePart ($stack = array()) {
		$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
		$_228 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_228 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_228 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_228 = FALSE; break; }
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_228 = FALSE; break; }
			$_228 = TRUE; break;
		}
		while(0);
		if( $_228 === TRUE ) { return $this->finalise($result); }
		if( $_228 === FALSE) { return FALSE; }
	}


	/* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
	protected $match_If_typestack = array('If');
	function match_If ($stack = array()) {
		$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
		$_238 = NULL;
		do {
			$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_238 = FALSE; break; }
			while (true) {
				$res_231 = $result;
				$pos_231 = $this->pos;
				$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else {
					$result = $res_231;
					$this->pos = $pos_231;
					unset( $res_231 );
					unset( $pos_231 );
					break;
				}
			}
			$res_232 = $result;
			$pos_232 = $this->pos;
			$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_232;
				$this->pos = $pos_232;
				unset( $res_232 );
				unset( $pos_232 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_238 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_238 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_238 = FALSE; break; }
			$_238 = TRUE; break;
		}
		while(0);
		if( $_238 === TRUE ) { return $this->finalise($result); }
		if( $_238 === FALSE) { return FALSE; }
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
		$_254 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_254 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_254 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_254 = FALSE; break; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
			$_250 = NULL;
			do {
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Method" );
				}
				else { $_250 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_250 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else { $_250 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_250 = FALSE; break; }
				$_250 = TRUE; break;
			}
			while(0);
			if( $_250 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Call' );
			}
			if( $_250 === FALSE) {
				$result = array_pop($stack);
				$_254 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_254 = FALSE; break; }
			$_254 = TRUE; break;
		}
		while(0);
		if( $_254 === TRUE ) { return $this->finalise($result); }
		if( $_254 === FALSE) { return FALSE; }
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
		$_274 = NULL;
		do {
			$res_262 = $result;
			$pos_262 = $this->pos;
			$_261 = NULL;
			do {
				$_259 = NULL;
				do {
					$res_256 = $result;
					$pos_256 = $this->pos;
					if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_259 = TRUE; break;
					}
					$result = $res_256;
					$this->pos = $pos_256;
					if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_259 = TRUE; break;
					}
					$result = $res_256;
					$this->pos = $pos_256;
					$_259 = FALSE; break;
				}
				while(0);
				if( $_259 === FALSE) { $_261 = FALSE; break; }
				$_261 = TRUE; break;
			}
			while(0);
			if( $_261 === TRUE ) {
				$result = $res_262;
				$this->pos = $pos_262;
				$_274 = FALSE; break;
			}
			if( $_261 === FALSE) {
				$result = $res_262;
				$this->pos = $pos_262;
			}
			$_272 = NULL;
			do {
				$_270 = NULL;
				do {
					$res_263 = $result;
					$pos_263 = $this->pos;
					$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "DollarMarkedLookup" );
						$_270 = TRUE; break;
					}
					$result = $res_263;
					$this->pos = $pos_263;
					$_268 = NULL;
					do {
						$res_265 = $result;
						$pos_265 = $this->pos;
						$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "QuotedString" );
							$_268 = TRUE; break;
						}
						$result = $res_265;
						$this->pos = $pos_265;
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
							$_268 = TRUE; break;
						}
						$result = $res_265;
						$this->pos = $pos_265;
						$_268 = FALSE; break;
					}
					while(0);
					if( $_268 === TRUE ) { $_270 = TRUE; break; }
					$result = $res_263;
					$this->pos = $pos_263;
					$_270 = FALSE; break;
				}
				while(0);
				if( $_270 === FALSE) { $_272 = FALSE; break; }
				$_272 = TRUE; break;
			}
			while(0);
			if( $_272 === FALSE) { $_274 = FALSE; break; }
			$_274 = TRUE; break;
		}
		while(0);
		if( $_274 === TRUE ) { return $this->finalise($result); }
		if( $_274 === FALSE) { return FALSE; }
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
		$_283 = NULL;
		do {
			$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_283 = FALSE; break; }
			while (true) {
				$res_282 = $result;
				$pos_282 = $this->pos;
				$_281 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_281 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_281 = FALSE; break; }
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
			}
			$_283 = TRUE; break;
		}
		while(0);
		if( $_283 === TRUE ) { return $this->finalise($result); }
		if( $_283 === FALSE) { return FALSE; }
	}



	function CacheBlockArguments_CacheBlockArgument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ".'_'.";
		else $res['php'] = '';
		
		$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
	}
	
	/* CacheBlockTemplate: (Comment | Translate | If | Require |    OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_CacheBlockTemplate_typestack = array('CacheBlockTemplate','Template');
	function match_CacheBlockTemplate ($stack = array()) {
		$matchrule = "CacheBlockTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'CacheRestrictedTemplate'));
		$count = 0;
		while (true) {
			$res_323 = $result;
			$pos_323 = $this->pos;
			$_322 = NULL;
			do {
				$_320 = NULL;
				do {
					$res_285 = $result;
					$pos_285 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_320 = TRUE; break;
					}
					$result = $res_285;
					$this->pos = $pos_285;
					$_318 = NULL;
					do {
						$res_287 = $result;
						$pos_287 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_318 = TRUE; break;
						}
						$result = $res_287;
						$this->pos = $pos_287;
						$_316 = NULL;
						do {
							$res_289 = $result;
							$pos_289 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_316 = TRUE; break;
							}
							$result = $res_289;
							$this->pos = $pos_289;
							$_314 = NULL;
							do {
								$res_291 = $result;
								$pos_291 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_314 = TRUE; break;
								}
								$result = $res_291;
								$this->pos = $pos_291;
								$_312 = NULL;
								do {
									$res_293 = $result;
									$pos_293 = $this->pos;
									$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_312 = TRUE; break;
									}
									$result = $res_293;
									$this->pos = $pos_293;
									$_310 = NULL;
									do {
										$res_295 = $result;
										$pos_295 = $this->pos;
										$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_310 = TRUE; break;
										}
										$result = $res_295;
										$this->pos = $pos_295;
										$_308 = NULL;
										do {
											$res_297 = $result;
											$pos_297 = $this->pos;
											$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_308 = TRUE; break;
											}
											$result = $res_297;
											$this->pos = $pos_297;
											$_306 = NULL;
											do {
												$res_299 = $result;
												$pos_299 = $this->pos;
												$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_306 = TRUE; break;
												}
												$result = $res_299;
												$this->pos = $pos_299;
												$_304 = NULL;
												do {
													$res_301 = $result;
													$pos_301 = $this->pos;
													$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_304 = TRUE; break;
													}
													$result = $res_301;
													$this->pos = $pos_301;
													$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_304 = TRUE; break;
													}
													$result = $res_301;
													$this->pos = $pos_301;
													$_304 = FALSE; break;
												}
												while(0);
												if( $_304 === TRUE ) { $_306 = TRUE; break; }
												$result = $res_299;
												$this->pos = $pos_299;
												$_306 = FALSE; break;
											}
											while(0);
											if( $_306 === TRUE ) { $_308 = TRUE; break; }
											$result = $res_297;
											$this->pos = $pos_297;
											$_308 = FALSE; break;
										}
										while(0);
										if( $_308 === TRUE ) { $_310 = TRUE; break; }
										$result = $res_295;
										$this->pos = $pos_295;
										$_310 = FALSE; break;
									}
									while(0);
									if( $_310 === TRUE ) { $_312 = TRUE; break; }
									$result = $res_293;
									$this->pos = $pos_293;
									$_312 = FALSE; break;
								}
								while(0);
								if( $_312 === TRUE ) { $_314 = TRUE; break; }
								$result = $res_291;
								$this->pos = $pos_291;
								$_314 = FALSE; break;
							}
							while(0);
							if( $_314 === TRUE ) { $_316 = TRUE; break; }
							$result = $res_289;
							$this->pos = $pos_289;
							$_316 = FALSE; break;
						}
						while(0);
						if( $_316 === TRUE ) { $_318 = TRUE; break; }
						$result = $res_287;
						$this->pos = $pos_287;
						$_318 = FALSE; break;
					}
					while(0);
					if( $_318 === TRUE ) { $_320 = TRUE; break; }
					$result = $res_285;
					$this->pos = $pos_285;
					$_320 = FALSE; break;
				}
				while(0);
				if( $_320 === FALSE) { $_322 = FALSE; break; }
				$_322 = TRUE; break;
			}
			while(0);
			if( $_322 === FALSE) {
				$result = $res_323;
				$this->pos = $pos_323;
				unset( $res_323 );
				unset( $pos_323 );
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
		$_360 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_360 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_360 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_328 = $result;
			$pos_328 = $this->pos;
			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_328;
				$this->pos = $pos_328;
				unset( $res_328 );
				unset( $pos_328 );
			}
			$res_340 = $result;
			$pos_340 = $this->pos;
			$_339 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_335 = NULL;
				do {
					$_333 = NULL;
					do {
						$res_330 = $result;
						$pos_330 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_333 = TRUE; break;
						}
						$result = $res_330;
						$this->pos = $pos_330;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_333 = TRUE; break;
						}
						$result = $res_330;
						$this->pos = $pos_330;
						$_333 = FALSE; break;
					}
					while(0);
					if( $_333 === FALSE) { $_335 = FALSE; break; }
					$_335 = TRUE; break;
				}
				while(0);
				if( $_335 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_335 === FALSE) {
					$result = array_pop($stack);
					$_339 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_339 = FALSE; break; }
				$_339 = TRUE; break;
			}
			while(0);
			if( $_339 === FALSE) {
				$result = $res_340;
				$this->pos = $pos_340;
				unset( $res_340 );
				unset( $pos_340 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_360 = FALSE; break; }
			$res_343 = $result;
			$pos_343 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_343;
				$this->pos = $pos_343;
				unset( $res_343 );
				unset( $pos_343 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_360 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_360 = FALSE; break; }
			$_356 = NULL;
			do {
				$_354 = NULL;
				do {
					$res_347 = $result;
					$pos_347 = $this->pos;
					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_354 = TRUE; break;
					}
					$result = $res_347;
					$this->pos = $pos_347;
					$_352 = NULL;
					do {
						$res_349 = $result;
						$pos_349 = $this->pos;
						if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_352 = TRUE; break;
						}
						$result = $res_349;
						$this->pos = $pos_349;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_352 = TRUE; break;
						}
						$result = $res_349;
						$this->pos = $pos_349;
						$_352 = FALSE; break;
					}
					while(0);
					if( $_352 === TRUE ) { $_354 = TRUE; break; }
					$result = $res_347;
					$this->pos = $pos_347;
					$_354 = FALSE; break;
				}
				while(0);
				if( $_354 === FALSE) { $_356 = FALSE; break; }
				$_356 = TRUE; break;
			}
			while(0);
			if( $_356 === FALSE) { $_360 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_360 = FALSE; break; }
			$_360 = TRUE; break;
		}
		while(0);
		if( $_360 === TRUE ) { return $this->finalise($result); }
		if( $_360 === FALSE) { return FALSE; }
	}



	function UncachedBlock_Template(&$res, $sub){
		$res['php'] = $sub['php'];
	}
	
	/* CacheRestrictedTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_CacheRestrictedTemplate_typestack = array('CacheRestrictedTemplate','Template');
	function match_CacheRestrictedTemplate ($stack = array()) {
		$matchrule = "CacheRestrictedTemplate"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_408 = $result;
			$pos_408 = $this->pos;
			$_407 = NULL;
			do {
				$_405 = NULL;
				do {
					$res_362 = $result;
					$pos_362 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_405 = TRUE; break;
					}
					$result = $res_362;
					$this->pos = $pos_362;
					$_403 = NULL;
					do {
						$res_364 = $result;
						$pos_364 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_403 = TRUE; break;
						}
						$result = $res_364;
						$this->pos = $pos_364;
						$_401 = NULL;
						do {
							$res_366 = $result;
							$pos_366 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_401 = TRUE; break;
							}
							$result = $res_366;
							$this->pos = $pos_366;
							$_399 = NULL;
							do {
								$res_368 = $result;
								$pos_368 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_399 = TRUE; break;
								}
								$result = $res_368;
								$this->pos = $pos_368;
								$_397 = NULL;
								do {
									$res_370 = $result;
									$pos_370 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_397 = TRUE; break;
									}
									$result = $res_370;
									$this->pos = $pos_370;
									$_395 = NULL;
									do {
										$res_372 = $result;
										$pos_372 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_395 = TRUE; break;
										}
										$result = $res_372;
										$this->pos = $pos_372;
										$_393 = NULL;
										do {
											$res_374 = $result;
											$pos_374 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_393 = TRUE; break;
											}
											$result = $res_374;
											$this->pos = $pos_374;
											$_391 = NULL;
											do {
												$res_376 = $result;
												$pos_376 = $this->pos;
												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_391 = TRUE; break;
												}
												$result = $res_376;
												$this->pos = $pos_376;
												$_389 = NULL;
												do {
													$res_378 = $result;
													$pos_378 = $this->pos;
													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_389 = TRUE; break;
													}
													$result = $res_378;
													$this->pos = $pos_378;
													$_387 = NULL;
													do {
														$res_380 = $result;
														$pos_380 = $this->pos;
														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_387 = TRUE; break;
														}
														$result = $res_380;
														$this->pos = $pos_380;
														$_385 = NULL;
														do {
															$res_382 = $result;
															$pos_382 = $this->pos;
															$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_385 = TRUE; break;
															}
															$result = $res_382;
															$this->pos = $pos_382;
															$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_385 = TRUE; break;
															}
															$result = $res_382;
															$this->pos = $pos_382;
															$_385 = FALSE; break;
														}
														while(0);
														if( $_385 === TRUE ) { $_387 = TRUE; break; }
														$result = $res_380;
														$this->pos = $pos_380;
														$_387 = FALSE; break;
													}
													while(0);
													if( $_387 === TRUE ) { $_389 = TRUE; break; }
													$result = $res_378;
													$this->pos = $pos_378;
													$_389 = FALSE; break;
												}
												while(0);
												if( $_389 === TRUE ) { $_391 = TRUE; break; }
												$result = $res_376;
												$this->pos = $pos_376;
												$_391 = FALSE; break;
											}
											while(0);
											if( $_391 === TRUE ) { $_393 = TRUE; break; }
											$result = $res_374;
											$this->pos = $pos_374;
											$_393 = FALSE; break;
										}
										while(0);
										if( $_393 === TRUE ) { $_395 = TRUE; break; }
										$result = $res_372;
										$this->pos = $pos_372;
										$_395 = FALSE; break;
									}
									while(0);
									if( $_395 === TRUE ) { $_397 = TRUE; break; }
									$result = $res_370;
									$this->pos = $pos_370;
									$_397 = FALSE; break;
								}
								while(0);
								if( $_397 === TRUE ) { $_399 = TRUE; break; }
								$result = $res_368;
								$this->pos = $pos_368;
								$_399 = FALSE; break;
							}
							while(0);
							if( $_399 === TRUE ) { $_401 = TRUE; break; }
							$result = $res_366;
							$this->pos = $pos_366;
							$_401 = FALSE; break;
						}
						while(0);
						if( $_401 === TRUE ) { $_403 = TRUE; break; }
						$result = $res_364;
						$this->pos = $pos_364;
						$_403 = FALSE; break;
					}
					while(0);
					if( $_403 === TRUE ) { $_405 = TRUE; break; }
					$result = $res_362;
					$this->pos = $pos_362;
					$_405 = FALSE; break;
				}
				while(0);
				if( $_405 === FALSE) { $_407 = FALSE; break; }
				$_407 = TRUE; break;
			}
			while(0);
			if( $_407 === FALSE) {
				$result = $res_408;
				$this->pos = $pos_408;
				unset( $res_408 );
				unset( $pos_408 );
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
		$_463 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_463 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
			$_416 = NULL;
			do {
				$_414 = NULL;
				do {
					$res_411 = $result;
					$pos_411 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_414 = TRUE; break;
					}
					$result = $res_411;
					$this->pos = $pos_411;
					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_414 = TRUE; break;
					}
					$result = $res_411;
					$this->pos = $pos_411;
					$_414 = FALSE; break;
				}
				while(0);
				if( $_414 === FALSE) { $_416 = FALSE; break; }
				$_416 = TRUE; break;
			}
			while(0);
			if( $_416 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'CacheTag' );
			}
			if( $_416 === FALSE) {
				$result = array_pop($stack);
				$_463 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_421 = $result;
			$pos_421 = $this->pos;
			$_420 = NULL;
			do {
				$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_420 = FALSE; break; }
				$_420 = TRUE; break;
			}
			while(0);
			if( $_420 === FALSE) {
				$result = $res_421;
				$this->pos = $pos_421;
				unset( $res_421 );
				unset( $pos_421 );
			}
			$res_433 = $result;
			$pos_433 = $this->pos;
			$_432 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_428 = NULL;
				do {
					$_426 = NULL;
					do {
						$res_423 = $result;
						$pos_423 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_426 = TRUE; break;
						}
						$result = $res_423;
						$this->pos = $pos_423;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_426 = TRUE; break;
						}
						$result = $res_423;
						$this->pos = $pos_423;
						$_426 = FALSE; break;
					}
					while(0);
					if( $_426 === FALSE) { $_428 = FALSE; break; }
					$_428 = TRUE; break;
				}
				while(0);
				if( $_428 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_428 === FALSE) {
					$result = array_pop($stack);
					$_432 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_432 = FALSE; break; }
				$_432 = TRUE; break;
			}
			while(0);
			if( $_432 === FALSE) {
				$result = $res_433;
				$this->pos = $pos_433;
				unset( $res_433 );
				unset( $pos_433 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_463 = FALSE; break; }
			while (true) {
				$res_446 = $result;
				$pos_446 = $this->pos;
				$_445 = NULL;
				do {
					$_443 = NULL;
					do {
						$res_436 = $result;
						$pos_436 = $this->pos;
						$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_443 = TRUE; break;
						}
						$result = $res_436;
						$this->pos = $pos_436;
						$_441 = NULL;
						do {
							$res_438 = $result;
							$pos_438 = $this->pos;
							$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_441 = TRUE; break;
							}
							$result = $res_438;
							$this->pos = $pos_438;
							$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_441 = TRUE; break;
							}
							$result = $res_438;
							$this->pos = $pos_438;
							$_441 = FALSE; break;
						}
						while(0);
						if( $_441 === TRUE ) { $_443 = TRUE; break; }
						$result = $res_436;
						$this->pos = $pos_436;
						$_443 = FALSE; break;
					}
					while(0);
					if( $_443 === FALSE) { $_445 = FALSE; break; }
					$_445 = TRUE; break;
				}
				while(0);
				if( $_445 === FALSE) {
					$result = $res_446;
					$this->pos = $pos_446;
					unset( $res_446 );
					unset( $pos_446 );
					break;
				}
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_463 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_463 = FALSE; break; }
			$_459 = NULL;
			do {
				$_457 = NULL;
				do {
					$res_450 = $result;
					$pos_450 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_457 = TRUE; break;
					}
					$result = $res_450;
					$this->pos = $pos_450;
					$_455 = NULL;
					do {
						$res_452 = $result;
						$pos_452 = $this->pos;
						if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_455 = TRUE; break;
						}
						$result = $res_452;
						$this->pos = $pos_452;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_455 = TRUE; break;
						}
						$result = $res_452;
						$this->pos = $pos_452;
						$_455 = FALSE; break;
					}
					while(0);
					if( $_455 === TRUE ) { $_457 = TRUE; break; }
					$result = $res_450;
					$this->pos = $pos_450;
					$_457 = FALSE; break;
				}
				while(0);
				if( $_457 === FALSE) { $_459 = FALSE; break; }
				$_459 = TRUE; break;
			}
			while(0);
			if( $_459 === FALSE) { $_463 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_463 = FALSE; break; }
			$_463 = TRUE; break;
		}
		while(0);
		if( $_463 === TRUE ) { return $this->finalise($result); }
		if( $_463 === FALSE) { return FALSE; }
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
	
	/* OldTPart: "_t" N "(" N QuotedString (N "," N CallArguments)? N ")" N (";")? */
	protected $match_OldTPart_typestack = array('OldTPart');
	function match_OldTPart ($stack = array()) {
		$matchrule = "OldTPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_482 = NULL;
		do {
			if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_482 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_482 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_482 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_482 = FALSE; break; }
			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_482 = FALSE; break; }
			$res_475 = $result;
			$pos_475 = $this->pos;
			$_474 = NULL;
			do {
				$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_474 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_474 = FALSE; break; }
				$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_474 = FALSE; break; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_474 = FALSE; break; }
				$_474 = TRUE; break;
			}
			while(0);
			if( $_474 === FALSE) {
				$result = $res_475;
				$this->pos = $pos_475;
				unset( $res_475 );
				unset( $pos_475 );
			}
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_482 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_482 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_482 = FALSE; break; }
			$res_481 = $result;
			$pos_481 = $this->pos;
			$_480 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_480 = FALSE; break; }
				$_480 = TRUE; break;
			}
			while(0);
			if( $_480 === FALSE) {
				$result = $res_481;
				$this->pos = $pos_481;
				unset( $res_481 );
				unset( $pos_481 );
			}
			$_482 = TRUE; break;
		}
		while(0);
		if( $_482 === TRUE ) { return $this->finalise($result); }
		if( $_482 === FALSE) { return FALSE; }
	}


	/* N: / [\s\n]* / */
	protected $match_N_typestack = array('N');
	function match_N ($stack = array()) {
		$matchrule = "N"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/ [\s\n]* /' ) ) !== FALSE) {
			$result["text"] .= $subres;
			return $this->finalise($result);
		}
		else { return FALSE; }
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
		$_490 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_490 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_490 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_490 = FALSE; break; }
			$_490 = TRUE; break;
		}
		while(0);
		if( $_490 === TRUE ) { return $this->finalise($result); }
		if( $_490 === FALSE) { return FALSE; }
	}



	function OldTTag_OldTPart(&$res, $sub) {
		$res['php'] = $sub['php'];
	}
	 	  
	/* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>"  */
	protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
	function match_OldSprintfTag ($stack = array()) {
		$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_507 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ',') {
				$this->pos += 1;
				$result["text"] .= ',';
			}
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_507 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_507 = FALSE; break; }
			$_507 = TRUE; break;
		}
		while(0);
		if( $_507 === TRUE ) { return $this->finalise($result); }
		if( $_507 === FALSE) { return FALSE; }
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
		$_512 = NULL;
		do {
			$res_509 = $result;
			$pos_509 = $this->pos;
			$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_512 = TRUE; break;
			}
			$result = $res_509;
			$this->pos = $pos_509;
			$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_512 = TRUE; break;
			}
			$result = $res_509;
			$this->pos = $pos_509;
			$_512 = FALSE; break;
		}
		while(0);
		if( $_512 === TRUE ) { return $this->finalise($result); }
		if( $_512 === FALSE) { return FALSE; }
	}



	function OldI18NTag_STR(&$res, $sub) {
		$res['php'] = '$val .= ' . $sub['php'] . ';';
	}
	
	/* BlockArguments: :Argument ( < "," < :Argument)*  */
	protected $match_BlockArguments_typestack = array('BlockArguments');
	function match_BlockArguments ($stack = array()) {
		$matchrule = "BlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
		$_521 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_521 = FALSE; break; }
			while (true) {
				$res_520 = $result;
				$pos_520 = $this->pos;
				$_519 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_519 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_519 = FALSE; break; }
					$_519 = TRUE; break;
				}
				while(0);
				if( $_519 === FALSE) {
					$result = $res_520;
					$this->pos = $pos_520;
					unset( $res_520 );
					unset( $pos_520 );
					break;
				}
			}
			$_521 = TRUE; break;
		}
		while(0);
		if( $_521 === TRUE ) { return $this->finalise($result); }
		if( $_521 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock") ] ) */
	protected $match_NotBlockTag_typestack = array('NotBlockTag');
	function match_NotBlockTag ($stack = array()) {
		$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_555 = NULL;
		do {
			$res_523 = $result;
			$pos_523 = $this->pos;
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_555 = TRUE; break;
			}
			$result = $res_523;
			$this->pos = $pos_523;
			$_553 = NULL;
			do {
				$_550 = NULL;
				do {
					$_548 = NULL;
					do {
						$res_525 = $result;
						$pos_525 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_548 = TRUE; break;
						}
						$result = $res_525;
						$this->pos = $pos_525;
						$_546 = NULL;
						do {
							$res_527 = $result;
							$pos_527 = $this->pos;
							if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_546 = TRUE; break;
							}
							$result = $res_527;
							$this->pos = $pos_527;
							$_544 = NULL;
							do {
								$res_529 = $result;
								$pos_529 = $this->pos;
								if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_544 = TRUE; break;
								}
								$result = $res_529;
								$this->pos = $pos_529;
								$_542 = NULL;
								do {
									$res_531 = $result;
									$pos_531 = $this->pos;
									if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_542 = TRUE; break;
									}
									$result = $res_531;
									$this->pos = $pos_531;
									$_540 = NULL;
									do {
										$res_533 = $result;
										$pos_533 = $this->pos;
										if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
											$result["text"] .= $subres;
											$_540 = TRUE; break;
										}
										$result = $res_533;
										$this->pos = $pos_533;
										$_538 = NULL;
										do {
											$res_535 = $result;
											$pos_535 = $this->pos;
											if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
												$result["text"] .= $subres;
												$_538 = TRUE; break;
											}
											$result = $res_535;
											$this->pos = $pos_535;
											if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
												$result["text"] .= $subres;
												$_538 = TRUE; break;
											}
											$result = $res_535;
											$this->pos = $pos_535;
											$_538 = FALSE; break;
										}
										while(0);
										if( $_538 === TRUE ) { $_540 = TRUE; break; }
										$result = $res_533;
										$this->pos = $pos_533;
										$_540 = FALSE; break;
									}
									while(0);
									if( $_540 === TRUE ) { $_542 = TRUE; break; }
									$result = $res_531;
									$this->pos = $pos_531;
									$_542 = FALSE; break;
								}
								while(0);
								if( $_542 === TRUE ) { $_544 = TRUE; break; }
								$result = $res_529;
								$this->pos = $pos_529;
								$_544 = FALSE; break;
							}
							while(0);
							if( $_544 === TRUE ) { $_546 = TRUE; break; }
							$result = $res_527;
							$this->pos = $pos_527;
							$_546 = FALSE; break;
						}
						while(0);
						if( $_546 === TRUE ) { $_548 = TRUE; break; }
						$result = $res_525;
						$this->pos = $pos_525;
						$_548 = FALSE; break;
					}
					while(0);
					if( $_548 === FALSE) { $_550 = FALSE; break; }
					$_550 = TRUE; break;
				}
				while(0);
				if( $_550 === FALSE) { $_553 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_553 = FALSE; break; }
				$_553 = TRUE; break;
			}
			while(0);
			if( $_553 === TRUE ) { $_555 = TRUE; break; }
			$result = $res_523;
			$this->pos = $pos_523;
			$_555 = FALSE; break;
		}
		while(0);
		if( $_555 === TRUE ) { return $this->finalise($result); }
		if( $_555 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher? '<%' < 'end_' '$BlockName' > '%>' */
	protected $match_ClosedBlock_typestack = array('ClosedBlock');
	function match_ClosedBlock ($stack = array()) {
		$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_575 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_575 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_559 = $result;
			$pos_559 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_559;
				$this->pos = $pos_559;
				$_575 = FALSE; break;
			}
			else {
				$result = $res_559;
				$this->pos = $pos_559;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_575 = FALSE; break; }
			$res_565 = $result;
			$pos_565 = $this->pos;
			$_564 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_564 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_564 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_564 = FALSE; break; }
				$_564 = TRUE; break;
			}
			while(0);
			if( $_564 === FALSE) {
				$result = $res_565;
				$this->pos = $pos_565;
				unset( $res_565 );
				unset( $pos_565 );
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
				$_575 = FALSE; break;
			}
			$res_568 = $result;
			$pos_568 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_568;
				$this->pos = $pos_568;
				unset( $res_568 );
				unset( $pos_568 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_575 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_575 = FALSE; break; }
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_575 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_575 = FALSE; break; }
			$_575 = TRUE; break;
		}
		while(0);
		if( $_575 === TRUE ) { return $this->finalise($result); }
		if( $_575 === FALSE) { return FALSE; }
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
		if ($res['ArgumentCount'] > 1) {
			throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one argument only.', $this);
		}

		//loop without arguments loops on the current scope
		if ($res['ArgumentCount'] == 0) {
			$on = '$scope->obj(\'Up\', null, true)->obj(\'Foo\', null, true)';
		} else {    //loop in the normal way
			$arg = $res['Arguments'][0];
			if ($arg['ArgumentMode'] == 'string') {
				throw new SSTemplateParseException('Control block cant take string as argument.', $this);
			}
			$on = str_replace('$$FINAL', 'obj', ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		}

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
		$_588 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_588 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_579 = $result;
			$pos_579 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_579;
				$this->pos = $pos_579;
				$_588 = FALSE; break;
			}
			else {
				$result = $res_579;
				$this->pos = $pos_579;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_588 = FALSE; break; }
			$res_585 = $result;
			$pos_585 = $this->pos;
			$_584 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_584 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_584 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_584 = FALSE; break; }
				$_584 = TRUE; break;
			}
			while(0);
			if( $_584 === FALSE) {
				$result = $res_585;
				$this->pos = $pos_585;
				unset( $res_585 );
				unset( $pos_585 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_588 = FALSE; break; }
			$_588 = TRUE; break;
		}
		while(0);
		if( $_588 === TRUE ) { return $this->finalise($result); }
		if( $_588 === FALSE) { return FALSE; }
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
		$_596 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_596 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_596 = FALSE; break; }
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Word" );
			}
			else { $_596 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_596 = FALSE; break; }
			$_596 = TRUE; break;
		}
		while(0);
		if( $_596 === TRUE ) { return $this->finalise($result); }
		if( $_596 === FALSE) { return FALSE; }
	}



	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_'.$blockname.' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
	protected $match_MalformedOpenTag_typestack = array('MalformedOpenTag');
	function match_MalformedOpenTag ($stack = array()) {
		$matchrule = "MalformedOpenTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_611 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_611 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_600 = $result;
			$pos_600 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_600;
				$this->pos = $pos_600;
				$_611 = FALSE; break;
			}
			else {
				$result = $res_600;
				$this->pos = $pos_600;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_611 = FALSE; break; }
			$res_610 = $result;
			$pos_610 = $this->pos;
			$_609 = NULL;
			do {
				$res_606 = $result;
				$pos_606 = $this->pos;
				$_605 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_605 = FALSE; break; }
					$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_605 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_605 = FALSE; break; }
					$_605 = TRUE; break;
				}
				while(0);
				if( $_605 === FALSE) {
					$result = $res_606;
					$this->pos = $pos_606;
					unset( $res_606 );
					unset( $pos_606 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_609 = FALSE; break; }
				$_609 = TRUE; break;
			}
			while(0);
			if( $_609 === TRUE ) {
				$result = $res_610;
				$this->pos = $pos_610;
				$_611 = FALSE; break;
			}
			if( $_609 === FALSE) {
				$result = $res_610;
				$this->pos = $pos_610;
			}
			$_611 = TRUE; break;
		}
		while(0);
		if( $_611 === TRUE ) { return $this->finalise($result); }
		if( $_611 === FALSE) { return FALSE; }
	}



	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
	}
	
	/* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
	protected $match_MalformedCloseTag_typestack = array('MalformedCloseTag');
	function match_MalformedCloseTag ($stack = array()) {
		$matchrule = "MalformedCloseTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_623 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_623 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
			$_617 = NULL;
			do {
				if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_617 = FALSE; break; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_617 = FALSE; break; }
				$_617 = TRUE; break;
			}
			while(0);
			if( $_617 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_617 === FALSE) {
				$result = array_pop($stack);
				$_623 = FALSE; break;
			}
			$res_622 = $result;
			$pos_622 = $this->pos;
			$_621 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_621 = FALSE; break; }
				$_621 = TRUE; break;
			}
			while(0);
			if( $_621 === TRUE ) {
				$result = $res_622;
				$this->pos = $pos_622;
				$_623 = FALSE; break;
			}
			if( $_621 === FALSE) {
				$result = $res_622;
				$this->pos = $pos_622;
			}
			$_623 = TRUE; break;
		}
		while(0);
		if( $_623 === TRUE ) { return $this->finalise($result); }
		if( $_623 === FALSE) { return FALSE; }
	}



	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an argument to one?", $this);
	}
	
	/* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
	protected $match_MalformedBlock_typestack = array('MalformedBlock');
	function match_MalformedBlock ($stack = array()) {
		$matchrule = "MalformedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_628 = NULL;
		do {
			$res_625 = $result;
			$pos_625 = $this->pos;
			$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_628 = TRUE; break;
			}
			$result = $res_625;
			$this->pos = $pos_625;
			$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_628 = TRUE; break;
			}
			$result = $res_625;
			$this->pos = $pos_625;
			$_628 = FALSE; break;
		}
		while(0);
		if( $_628 === TRUE ) { return $this->finalise($result); }
		if( $_628 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /./)+ "--%>" */
	protected $match_Comment_typestack = array('Comment');
	function match_Comment ($stack = array()) {
		$matchrule = "Comment"; $result = $this->construct($matchrule, $matchrule, null);
		$_636 = NULL;
		do {
			if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_636 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_634 = $result;
				$pos_634 = $this->pos;
				$_633 = NULL;
				do {
					$res_631 = $result;
					$pos_631 = $this->pos;
					if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_631;
						$this->pos = $pos_631;
						$_633 = FALSE; break;
					}
					else {
						$result = $res_631;
						$this->pos = $pos_631;
					}
					if (( $subres = $this->rx( '/./' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_633 = FALSE; break; }
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
			if ($count > 0) {  }
			else { $_636 = FALSE; break; }
			if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_636 = FALSE; break; }
			$_636 = TRUE; break;
		}
		while(0);
		if( $_636 === TRUE ) { return $this->finalise($result); }
		if( $_636 === FALSE) { return FALSE; }
	}



	function Comment__construct(&$res) {
		$res['php'] = '';
	}
		
	/* TopTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | ClosedBlock | OpenBlock |  MalformedBlock | MismatchedEndBlock  | Injection | Text)+ */
	protected $match_TopTemplate_typestack = array('TopTemplate','Template');
	function match_TopTemplate ($stack = array()) {
		$matchrule = "TopTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'Template'));
		$count = 0;
		while (true) {
			$res_688 = $result;
			$pos_688 = $this->pos;
			$_687 = NULL;
			do {
				$_685 = NULL;
				do {
					$res_638 = $result;
					$pos_638 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_685 = TRUE; break;
					}
					$result = $res_638;
					$this->pos = $pos_638;
					$_683 = NULL;
					do {
						$res_640 = $result;
						$pos_640 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_683 = TRUE; break;
						}
						$result = $res_640;
						$this->pos = $pos_640;
						$_681 = NULL;
						do {
							$res_642 = $result;
							$pos_642 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_681 = TRUE; break;
							}
							$result = $res_642;
							$this->pos = $pos_642;
							$_679 = NULL;
							do {
								$res_644 = $result;
								$pos_644 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_679 = TRUE; break;
								}
								$result = $res_644;
								$this->pos = $pos_644;
								$_677 = NULL;
								do {
									$res_646 = $result;
									$pos_646 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_677 = TRUE; break;
									}
									$result = $res_646;
									$this->pos = $pos_646;
									$_675 = NULL;
									do {
										$res_648 = $result;
										$pos_648 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_675 = TRUE; break;
										}
										$result = $res_648;
										$this->pos = $pos_648;
										$_673 = NULL;
										do {
											$res_650 = $result;
											$pos_650 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_673 = TRUE; break;
											}
											$result = $res_650;
											$this->pos = $pos_650;
											$_671 = NULL;
											do {
												$res_652 = $result;
												$pos_652 = $this->pos;
												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_671 = TRUE; break;
												}
												$result = $res_652;
												$this->pos = $pos_652;
												$_669 = NULL;
												do {
													$res_654 = $result;
													$pos_654 = $this->pos;
													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_669 = TRUE; break;
													}
													$result = $res_654;
													$this->pos = $pos_654;
													$_667 = NULL;
													do {
														$res_656 = $result;
														$pos_656 = $this->pos;
														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_667 = TRUE; break;
														}
														$result = $res_656;
														$this->pos = $pos_656;
														$_665 = NULL;
														do {
															$res_658 = $result;
															$pos_658 = $this->pos;
															$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_665 = TRUE; break;
															}
															$result = $res_658;
															$this->pos = $pos_658;
															$_663 = NULL;
															do {
																$res_660 = $result;
																$pos_660 = $this->pos;
																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_663 = TRUE; break;
																}
																$result = $res_660;
																$this->pos = $pos_660;
																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_663 = TRUE; break;
																}
																$result = $res_660;
																$this->pos = $pos_660;
																$_663 = FALSE; break;
															}
															while(0);
															if( $_663 === TRUE ) { $_665 = TRUE; break; }
															$result = $res_658;
															$this->pos = $pos_658;
															$_665 = FALSE; break;
														}
														while(0);
														if( $_665 === TRUE ) { $_667 = TRUE; break; }
														$result = $res_656;
														$this->pos = $pos_656;
														$_667 = FALSE; break;
													}
													while(0);
													if( $_667 === TRUE ) { $_669 = TRUE; break; }
													$result = $res_654;
													$this->pos = $pos_654;
													$_669 = FALSE; break;
												}
												while(0);
												if( $_669 === TRUE ) { $_671 = TRUE; break; }
												$result = $res_652;
												$this->pos = $pos_652;
												$_671 = FALSE; break;
											}
											while(0);
											if( $_671 === TRUE ) { $_673 = TRUE; break; }
											$result = $res_650;
											$this->pos = $pos_650;
											$_673 = FALSE; break;
										}
										while(0);
										if( $_673 === TRUE ) { $_675 = TRUE; break; }
										$result = $res_648;
										$this->pos = $pos_648;
										$_675 = FALSE; break;
									}
									while(0);
									if( $_675 === TRUE ) { $_677 = TRUE; break; }
									$result = $res_646;
									$this->pos = $pos_646;
									$_677 = FALSE; break;
								}
								while(0);
								if( $_677 === TRUE ) { $_679 = TRUE; break; }
								$result = $res_644;
								$this->pos = $pos_644;
								$_679 = FALSE; break;
							}
							while(0);
							if( $_679 === TRUE ) { $_681 = TRUE; break; }
							$result = $res_642;
							$this->pos = $pos_642;
							$_681 = FALSE; break;
						}
						while(0);
						if( $_681 === TRUE ) { $_683 = TRUE; break; }
						$result = $res_640;
						$this->pos = $pos_640;
						$_683 = FALSE; break;
					}
					while(0);
					if( $_683 === TRUE ) { $_685 = TRUE; break; }
					$result = $res_638;
					$this->pos = $pos_638;
					$_685 = FALSE; break;
				}
				while(0);
				if( $_685 === FALSE) { $_687 = FALSE; break; }
				$_687 = TRUE; break;
			}
			while(0);
			if( $_687 === FALSE) {
				$result = $res_688;
				$this->pos = $pos_688;
				unset( $res_688 );
				unset( $pos_688 );
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
			$res_727 = $result;
			$pos_727 = $this->pos;
			$_726 = NULL;
			do {
				$_724 = NULL;
				do {
					$res_689 = $result;
					$pos_689 = $this->pos;
					if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_724 = TRUE; break;
					}
					$result = $res_689;
					$this->pos = $pos_689;
					$_722 = NULL;
					do {
						$res_691 = $result;
						$pos_691 = $this->pos;
						if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_722 = TRUE; break;
						}
						$result = $res_691;
						$this->pos = $pos_691;
						$_720 = NULL;
						do {
							$res_693 = $result;
							$pos_693 = $this->pos;
							$_696 = NULL;
							do {
								if (substr($this->string,$this->pos,1) == '<') {
									$this->pos += 1;
									$result["text"] .= '<';
								}
								else { $_696 = FALSE; break; }
								$res_695 = $result;
								$pos_695 = $this->pos;
								if (substr($this->string,$this->pos,1) == '%') {
									$this->pos += 1;
									$result["text"] .= '%';
									$result = $res_695;
									$this->pos = $pos_695;
									$_696 = FALSE; break;
								}
								else {
									$result = $res_695;
									$this->pos = $pos_695;
								}
								$_696 = TRUE; break;
							}
							while(0);
							if( $_696 === TRUE ) { $_720 = TRUE; break; }
							$result = $res_693;
							$this->pos = $pos_693;
							$_718 = NULL;
							do {
								$res_698 = $result;
								$pos_698 = $this->pos;
								$_703 = NULL;
								do {
									if (substr($this->string,$this->pos,1) == '$') {
										$this->pos += 1;
										$result["text"] .= '$';
									}
									else { $_703 = FALSE; break; }
									$res_702 = $result;
									$pos_702 = $this->pos;
									$_701 = NULL;
									do {
										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_701 = FALSE; break; }
										$_701 = TRUE; break;
									}
									while(0);
									if( $_701 === TRUE ) {
										$result = $res_702;
										$this->pos = $pos_702;
										$_703 = FALSE; break;
									}
									if( $_701 === FALSE) {
										$result = $res_702;
										$this->pos = $pos_702;
									}
									$_703 = TRUE; break;
								}
								while(0);
								if( $_703 === TRUE ) { $_718 = TRUE; break; }
								$result = $res_698;
								$this->pos = $pos_698;
								$_716 = NULL;
								do {
									$res_705 = $result;
									$pos_705 = $this->pos;
									$_708 = NULL;
									do {
										if (substr($this->string,$this->pos,1) == '{') {
											$this->pos += 1;
											$result["text"] .= '{';
										}
										else { $_708 = FALSE; break; }
										$res_707 = $result;
										$pos_707 = $this->pos;
										if (substr($this->string,$this->pos,1) == '$') {
											$this->pos += 1;
											$result["text"] .= '$';
											$result = $res_707;
											$this->pos = $pos_707;
											$_708 = FALSE; break;
										}
										else {
											$result = $res_707;
											$this->pos = $pos_707;
										}
										$_708 = TRUE; break;
									}
									while(0);
									if( $_708 === TRUE ) { $_716 = TRUE; break; }
									$result = $res_705;
									$this->pos = $pos_705;
									$_714 = NULL;
									do {
										if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_714 = FALSE; break; }
										$res_713 = $result;
										$pos_713 = $this->pos;
										$_712 = NULL;
										do {
											if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
											else { $_712 = FALSE; break; }
											$_712 = TRUE; break;
										}
										while(0);
										if( $_712 === TRUE ) {
											$result = $res_713;
											$this->pos = $pos_713;
											$_714 = FALSE; break;
										}
										if( $_712 === FALSE) {
											$result = $res_713;
											$this->pos = $pos_713;
										}
										$_714 = TRUE; break;
									}
									while(0);
									if( $_714 === TRUE ) { $_716 = TRUE; break; }
									$result = $res_705;
									$this->pos = $pos_705;
									$_716 = FALSE; break;
								}
								while(0);
								if( $_716 === TRUE ) { $_718 = TRUE; break; }
								$result = $res_698;
								$this->pos = $pos_698;
								$_718 = FALSE; break;
							}
							while(0);
							if( $_718 === TRUE ) { $_720 = TRUE; break; }
							$result = $res_693;
							$this->pos = $pos_693;
							$_720 = FALSE; break;
						}
						while(0);
						if( $_720 === TRUE ) { $_722 = TRUE; break; }
						$result = $res_691;
						$this->pos = $pos_691;
						$_722 = FALSE; break;
					}
					while(0);
					if( $_722 === TRUE ) { $_724 = TRUE; break; }
					$result = $res_689;
					$this->pos = $pos_689;
					$_724 = FALSE; break;
				}
				while(0);
				if( $_724 === FALSE) { $_726 = FALSE; break; }
				$_726 = TRUE; break;
			}
			while(0);
			if( $_726 === FALSE) {
				$result = $res_727;
				$this->pos = $pos_727;
				unset( $res_727 );
				unset( $pos_727 );
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
