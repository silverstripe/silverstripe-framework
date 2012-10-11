<?php

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/




// We want this to work when run by hand too
if (defined(THIRDPARTY_PATH)) {
	require_once(THIRDPARTY_PATH . '/php-peg/Parser.php');
}
else {
	$base = dirname(__FILE__);
	require_once($base.'/../thirdparty/php-peg/Parser.php');
}

/**
 * This is the exception raised when failing to parse a template. Note that we don't currently do any static analysis,
 * so we can't know if the template will run, just if it's malformed. It also won't catch mistakes that still look
 * valid.
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
  * This is the parser for the SilverStripe template language. It gets called on a string and uses a php-peg parser
  * to match that string against the language structure, building up the PHP code to execute that structure as it
  * parses
  * 
  * The $result array that is built up as part of the parsing (see thirdparty/php-peg/README.md for more on how
  * parsers build results) has one special member, 'php', which contains the php equivalent of that part of the
  * template tree.
  * 
  * Some match rules generate alternate php, or other variations, so check the per-match documentation too.
  * 
  * Terms used:
  * 
  * Marked: A string or lookup in the template that has been explictly marked as such - lookups by prepending with
  * "$" (like $Foo.Bar), strings by wrapping with single or double quotes ('Foo' or "Foo")
  * 
  * Bare: The opposite of marked. An argument that has to has it's type inferred by usage and 2.4 defaults.
  * 
  * Example of using a bare argument for a loop block: <% loop Foo %>
  * 
  * Block: One of two SS template structures. The special characters "<%" and "%>" are used to wrap the opening and
  * (required or forbidden depending on which block exactly) closing block marks.
  * 
  * Open Block: An SS template block that doesn't wrap any content or have a closing end tag (in fact, a closing end
  * tag is forbidden)
  * 
  * Closed Block: An SS template block that wraps content, and requires a counterpart <% end_blockname %> tag
  * 
  * Angle Bracket: angle brackets "<" and ">" are used to eat whitespace between template elements
  * N: eats white space including newlines (using in legacy _t support)
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
	
	/* Template: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
	OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_Template_typestack = array('Template');
	function match_Template ($stack = array()) {
		$matchrule = "Template"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_50 = $result;
			$pos_50 = $this->pos;
			$_49 = NULL;
			do {
				$_47 = NULL;
				do {
					$res_0 = $result;
					$pos_0 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_47 = TRUE; break;
					}
					$result = $res_0;
					$this->pos = $pos_0;
					$_45 = NULL;
					do {
						$res_2 = $result;
						$pos_2 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_45 = TRUE; break;
						}
						$result = $res_2;
						$this->pos = $pos_2;
						$_43 = NULL;
						do {
							$res_4 = $result;
							$pos_4 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_43 = TRUE; break;
							}
							$result = $res_4;
							$this->pos = $pos_4;
							$_41 = NULL;
							do {
								$res_6 = $result;
								$pos_6 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_41 = TRUE; break;
								}
								$result = $res_6;
								$this->pos = $pos_6;
								$_39 = NULL;
								do {
									$res_8 = $result;
									$pos_8 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_39 = TRUE; break;
									}
									$result = $res_8;
									$this->pos = $pos_8;
									$_37 = NULL;
									do {
										$res_10 = $result;
										$pos_10 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_37 = TRUE; break;
										}
										$result = $res_10;
										$this->pos = $pos_10;
										$_35 = NULL;
										do {
											$res_12 = $result;
											$pos_12 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_35 = TRUE; break;
											}
											$result = $res_12;
											$this->pos = $pos_12;
											$_33 = NULL;
											do {
												$res_14 = $result;
												$pos_14 = $this->pos;
												$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_33 = TRUE; break;
												}
												$result = $res_14;
												$this->pos = $pos_14;
												$_31 = NULL;
												do {
													$res_16 = $result;
													$pos_16 = $this->pos;
													$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_31 = TRUE; break;
													}
													$result = $res_16;
													$this->pos = $pos_16;
													$_29 = NULL;
													do {
														$res_18 = $result;
														$pos_18 = $this->pos;
														$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_29 = TRUE; break;
														}
														$result = $res_18;
														$this->pos = $pos_18;
														$_27 = NULL;
														do {
															$res_20 = $result;
															$pos_20 = $this->pos;
															$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_27 = TRUE; break;
															}
															$result = $res_20;
															$this->pos = $pos_20;
															$_25 = NULL;
															do {
																$res_22 = $result;
																$pos_22 = $this->pos;
																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_25 = TRUE; break;
																}
																$result = $res_22;
																$this->pos = $pos_22;
																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_25 = TRUE; break;
																}
																$result = $res_22;
																$this->pos = $pos_22;
																$_25 = FALSE; break;
															}
															while(0);
															if( $_25 === TRUE ) { $_27 = TRUE; break; }
															$result = $res_20;
															$this->pos = $pos_20;
															$_27 = FALSE; break;
														}
														while(0);
														if( $_27 === TRUE ) { $_29 = TRUE; break; }
														$result = $res_18;
														$this->pos = $pos_18;
														$_29 = FALSE; break;
													}
													while(0);
													if( $_29 === TRUE ) { $_31 = TRUE; break; }
													$result = $res_16;
													$this->pos = $pos_16;
													$_31 = FALSE; break;
												}
												while(0);
												if( $_31 === TRUE ) { $_33 = TRUE; break; }
												$result = $res_14;
												$this->pos = $pos_14;
												$_33 = FALSE; break;
											}
											while(0);
											if( $_33 === TRUE ) { $_35 = TRUE; break; }
											$result = $res_12;
											$this->pos = $pos_12;
											$_35 = FALSE; break;
										}
										while(0);
										if( $_35 === TRUE ) { $_37 = TRUE; break; }
										$result = $res_10;
										$this->pos = $pos_10;
										$_37 = FALSE; break;
									}
									while(0);
									if( $_37 === TRUE ) { $_39 = TRUE; break; }
									$result = $res_8;
									$this->pos = $pos_8;
									$_39 = FALSE; break;
								}
								while(0);
								if( $_39 === TRUE ) { $_41 = TRUE; break; }
								$result = $res_6;
								$this->pos = $pos_6;
								$_41 = FALSE; break;
							}
							while(0);
							if( $_41 === TRUE ) { $_43 = TRUE; break; }
							$result = $res_4;
							$this->pos = $pos_4;
							$_43 = FALSE; break;
						}
						while(0);
						if( $_43 === TRUE ) { $_45 = TRUE; break; }
						$result = $res_2;
						$this->pos = $pos_2;
						$_45 = FALSE; break;
					}
					while(0);
					if( $_45 === TRUE ) { $_47 = TRUE; break; }
					$result = $res_0;
					$this->pos = $pos_0;
					$_47 = FALSE; break;
				}
				while(0);
				if( $_47 === FALSE) { $_49 = FALSE; break; }
				$_49 = TRUE; break;
			}
			while(0);
			if( $_49 === FALSE) {
				$result = $res_50;
				$this->pos = $pos_50;
				unset( $res_50 );
				unset( $pos_50 );
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
		$_61 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_61 = FALSE; break; }
			while (true) {
				$res_60 = $result;
				$pos_60 = $this->pos;
				$_59 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_59 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_59 = FALSE; break; }
					$_59 = TRUE; break;
				}
				while(0);
				if( $_59 === FALSE) {
					$result = $res_60;
					$this->pos = $pos_60;
					unset( $res_60 );
					unset( $pos_60 );
					break;
				}
			}
			$_61 = TRUE; break;
		}
		while(0);
		if( $_61 === TRUE ) { return $this->finalise($result); }
		if( $_61 === FALSE) { return FALSE; }
	}




	/** 
	 * Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert
	 * strings to numbers when needed.
	 */
	function CallArguments_Argument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ', ';
		
		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] : 
			str_replace('$$FINAL', 'XML_val', $sub['php']);
	}

	/* Method: ('{' Prop:Value '}') | Prop:Word */
	protected $match_Method_typestack = array('Method');
	function match_Method ($stack = array()) {
		$matchrule = "Method"; $result = $this->construct($matchrule, $matchrule, null);
		$_70 = NULL;
		do {
			$res_63 = $result;
			$pos_63 = $this->pos;
			$_67 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == '{') {
					$this->pos += 1;
					$result["text"] .= '{';
				}
				else { $_67 = FALSE; break; }
				$matcher = 'match_'.'Value'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Prop" );
				}
				else { $_67 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '}') {
					$this->pos += 1;
					$result["text"] .= '}';
				}
				else { $_67 = FALSE; break; }
				$_67 = TRUE; break;
			}
			while(0);
			if( $_67 === TRUE ) { $_70 = TRUE; break; }
			$result = $res_63;
			$this->pos = $pos_63;
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Prop" );
				$_70 = TRUE; break;
			}
			$result = $res_63;
			$this->pos = $pos_63;
			$_70 = FALSE; break;
		}
		while(0);
		if( $_70 === TRUE ) { return $this->finalise($result); }
		if( $_70 === FALSE) { return FALSE; }
	}


	/* Call: :Method ( "(" < :CallArguments? > ")" )? */
	protected $match_Call_typestack = array('Call');
	function match_Call ($stack = array()) {
		$matchrule = "Call"; $result = $this->construct($matchrule, $matchrule, null);
		$_80 = NULL;
		do {
			$matcher = 'match_'.'Method'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Method" );
			}
			else { $_80 = FALSE; break; }
			$res_79 = $result;
			$pos_79 = $this->pos;
			$_78 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_78 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$res_75 = $result;
				$pos_75 = $this->pos;
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else {
					$result = $res_75;
					$this->pos = $pos_75;
					unset( $res_75 );
					unset( $pos_75 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
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
			}
			$_80 = TRUE; break;
		}
		while(0);
		if( $_80 === TRUE ) { return $this->finalise($result); }
		if( $_80 === FALSE) { return FALSE; }
	}


	/* LookupStep: :Call &"." */
	protected $match_LookupStep_typestack = array('LookupStep');
	function match_LookupStep ($stack = array()) {
		$matchrule = "LookupStep"; $result = $this->construct($matchrule, $matchrule, null);
		$_84 = NULL;
		do {
			$matcher = 'match_'.'Call'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Call" );
			}
			else { $_84 = FALSE; break; }
			$res_83 = $result;
			$pos_83 = $this->pos;
			if (substr($this->string,$this->pos,1) == '.') {
				$this->pos += 1;
				$result["text"] .= '.';
				$result = $res_83;
				$this->pos = $pos_83;
			}
			else {
				$result = $res_83;
				$this->pos = $pos_83;
				$_84 = FALSE; break;
			}
			$_84 = TRUE; break;
		}
		while(0);
		if( $_84 === TRUE ) { return $this->finalise($result); }
		if( $_84 === FALSE) { return FALSE; }
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
		$_98 = NULL;
		do {
			$res_87 = $result;
			$pos_87 = $this->pos;
			$_95 = NULL;
			do {
				$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_95 = FALSE; break; }
				while (true) {
					$res_92 = $result;
					$pos_92 = $this->pos;
					$_91 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == '.') {
							$this->pos += 1;
							$result["text"] .= '.';
						}
						else { $_91 = FALSE; break; }
						$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_91 = FALSE; break; }
						$_91 = TRUE; break;
					}
					while(0);
					if( $_91 === FALSE) {
						$result = $res_92;
						$this->pos = $pos_92;
						unset( $res_92 );
						unset( $pos_92 );
						break;
					}
				}
				if (substr($this->string,$this->pos,1) == '.') {
					$this->pos += 1;
					$result["text"] .= '.';
				}
				else { $_95 = FALSE; break; }
				$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_95 = FALSE; break; }
				$_95 = TRUE; break;
			}
			while(0);
			if( $_95 === TRUE ) { $_98 = TRUE; break; }
			$result = $res_87;
			$this->pos = $pos_87;
			$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_98 = TRUE; break;
			}
			$result = $res_87;
			$this->pos = $pos_87;
			$_98 = FALSE; break;
		}
		while(0);
		if( $_98 === TRUE ) { return $this->finalise($result); }
		if( $_98 === FALSE) { return FALSE; }
	}




    function Method_Prop(&$res, $sub) {
        $res['text'] = $sub['text'];
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


	/* Translate: "<%t" < Entity < (Default:QuotedString)? < (!("is" "=") < "is" < Context:QuotedString)? <
	(InjectionVariables)? > "%>" */
	protected $match_Translate_typestack = array('Translate');
	function match_Translate ($stack = array()) {
		$matchrule = "Translate"; $result = $this->construct($matchrule, $matchrule, null);
		$_124 = NULL;
		do {
			if (( $subres = $this->literal( '<%t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_124 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Entity'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_124 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_106 = $result;
			$pos_106 = $this->pos;
			$_105 = NULL;
			do {
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Default" );
				}
				else { $_105 = FALSE; break; }
				$_105 = TRUE; break;
			}
			while(0);
			if( $_105 === FALSE) {
				$result = $res_106;
				$this->pos = $pos_106;
				unset( $res_106 );
				unset( $pos_106 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_117 = $result;
			$pos_117 = $this->pos;
			$_116 = NULL;
			do {
				$res_111 = $result;
				$pos_111 = $this->pos;
				$_110 = NULL;
				do {
					if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_110 = FALSE; break; }
					if (substr($this->string,$this->pos,1) == '=') {
						$this->pos += 1;
						$result["text"] .= '=';
					}
					else { $_110 = FALSE; break; }
					$_110 = TRUE; break;
				}
				while(0);
				if( $_110 === TRUE ) {
					$result = $res_111;
					$this->pos = $pos_111;
					$_116 = FALSE; break;
				}
				if( $_110 === FALSE) {
					$result = $res_111;
					$this->pos = $pos_111;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_116 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Context" );
				}
				else { $_116 = FALSE; break; }
				$_116 = TRUE; break;
			}
			while(0);
			if( $_116 === FALSE) {
				$result = $res_117;
				$this->pos = $pos_117;
				unset( $res_117 );
				unset( $pos_117 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_121 = $result;
			$pos_121 = $this->pos;
			$_120 = NULL;
			do {
				$matcher = 'match_'.'InjectionVariables'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_120 = FALSE; break; }
				$_120 = TRUE; break;
			}
			while(0);
			if( $_120 === FALSE) {
				$result = $res_121;
				$this->pos = $pos_121;
				unset( $res_121 );
				unset( $pos_121 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_124 = FALSE; break; }
			$_124 = TRUE; break;
		}
		while(0);
		if( $_124 === TRUE ) { return $this->finalise($result); }
		if( $_124 === FALSE) { return FALSE; }
	}


	/* InjectionVariables: (< InjectionName:Word "=" Argument)+ */
	protected $match_InjectionVariables_typestack = array('InjectionVariables');
	function match_InjectionVariables ($stack = array()) {
		$matchrule = "InjectionVariables"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_131 = $result;
			$pos_131 = $this->pos;
			$_130 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "InjectionName" );
				}
				else { $_130 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '=') {
					$this->pos += 1;
					$result["text"] .= '=';
				}
				else { $_130 = FALSE; break; }
				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
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
		$_135 = NULL;
		do {
			if (substr($this->string,$this->pos,1) == '$') {
				$this->pos += 1;
				$result["text"] .= '$';
			}
			else { $_135 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_135 = FALSE; break; }
			$_135 = TRUE; break;
		}
		while(0);
		if( $_135 === TRUE ) { return $this->finalise($result); }
		if( $_135 === FALSE) { return FALSE; }
	}


	/* BracketInjection: '{$' :Lookup "}" */
	protected $match_BracketInjection_typestack = array('BracketInjection');
	function match_BracketInjection ($stack = array()) {
		$matchrule = "BracketInjection"; $result = $this->construct($matchrule, $matchrule, null);
		$_140 = NULL;
		do {
			if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_140 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_140 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '}') {
				$this->pos += 1;
				$result["text"] .= '}';
			}
			else { $_140 = FALSE; break; }
			$_140 = TRUE; break;
		}
		while(0);
		if( $_140 === TRUE ) { return $this->finalise($result); }
		if( $_140 === FALSE) { return FALSE; }
	}


	/* Injection: BracketInjection | SimpleInjection */
	protected $match_Injection_typestack = array('Injection');
	function match_Injection ($stack = array()) {
		$matchrule = "Injection"; $result = $this->construct($matchrule, $matchrule, null);
		$_145 = NULL;
		do {
			$res_142 = $result;
			$pos_142 = $this->pos;
			$matcher = 'match_'.'BracketInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_145 = TRUE; break;
			}
			$result = $res_142;
			$this->pos = $pos_142;
			$matcher = 'match_'.'SimpleInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_145 = TRUE; break;
			}
			$result = $res_142;
			$this->pos = $pos_142;
			$_145 = FALSE; break;
		}
		while(0);
		if( $_145 === TRUE ) { return $this->finalise($result); }
		if( $_145 === FALSE) { return FALSE; }
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
		$_151 = NULL;
		do {
			$stack[] = $result; $result = $this->construct( $matchrule, "q" ); 
			if (( $subres = $this->rx( '/[\'"]/' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'q' );
			}
			else {
				$result = array_pop($stack);
				$_151 = FALSE; break;
			}
			$stack[] = $result; $result = $this->construct( $matchrule, "String" ); 
			if (( $subres = $this->rx( '/ (\\\\\\\\ | \\\\. | [^'.$this->expression($result, $stack, 'q').'\\\\])* /' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'String' );
			}
			else {
				$result = array_pop($stack);
				$_151 = FALSE; break;
			}
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'q').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_151 = FALSE; break; }
			$_151 = TRUE; break;
		}
		while(0);
		if( $_151 === TRUE ) { return $this->finalise($result); }
		if( $_151 === FALSE) { return FALSE; }
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
		$_171 = NULL;
		do {
			$res_154 = $result;
			$pos_154 = $this->pos;
			$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "DollarMarkedLookup" );
				$_171 = TRUE; break;
			}
			$result = $res_154;
			$this->pos = $pos_154;
			$_169 = NULL;
			do {
				$res_156 = $result;
				$pos_156 = $this->pos;
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "QuotedString" );
					$_169 = TRUE; break;
				}
				$result = $res_156;
				$this->pos = $pos_156;
				$_167 = NULL;
				do {
					$res_158 = $result;
					$pos_158 = $this->pos;
					$_164 = NULL;
					do {
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
						}
						else { $_164 = FALSE; break; }
						$res_163 = $result;
						$pos_163 = $this->pos;
						$_162 = NULL;
						do {
							if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
							$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_162 = FALSE; break; }
							$_162 = TRUE; break;
						}
						while(0);
						if( $_162 === TRUE ) {
							$result = $res_163;
							$this->pos = $pos_163;
							$_164 = FALSE; break;
						}
						if( $_162 === FALSE) {
							$result = $res_163;
							$this->pos = $pos_163;
						}
						$_164 = TRUE; break;
					}
					while(0);
					if( $_164 === TRUE ) { $_167 = TRUE; break; }
					$result = $res_158;
					$this->pos = $pos_158;
					$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "FreeString" );
						$_167 = TRUE; break;
					}
					$result = $res_158;
					$this->pos = $pos_158;
					$_167 = FALSE; break;
				}
				while(0);
				if( $_167 === TRUE ) { $_169 = TRUE; break; }
				$result = $res_156;
				$this->pos = $pos_156;
				$_169 = FALSE; break;
			}
			while(0);
			if( $_169 === TRUE ) { $_171 = TRUE; break; }
			$result = $res_154;
			$this->pos = $pos_154;
			$_171 = FALSE; break;
		}
		while(0);
		if( $_171 === TRUE ) { return $this->finalise($result); }
		if( $_171 === FALSE) { return FALSE; }
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
		$res['php'] = "'" . str_replace("'", "\\'", trim($sub['text'])) . "'";
	}
	
	/* ComparisonOperator: "==" | "!=" | "=" */
	protected $match_ComparisonOperator_typestack = array('ComparisonOperator');
	function match_ComparisonOperator ($stack = array()) {
		$matchrule = "ComparisonOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_180 = NULL;
		do {
			$res_173 = $result;
			$pos_173 = $this->pos;
			if (( $subres = $this->literal( '==' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_180 = TRUE; break;
			}
			$result = $res_173;
			$this->pos = $pos_173;
			$_178 = NULL;
			do {
				$res_175 = $result;
				$pos_175 = $this->pos;
				if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_178 = TRUE; break;
				}
				$result = $res_175;
				$this->pos = $pos_175;
				if (substr($this->string,$this->pos,1) == '=') {
					$this->pos += 1;
					$result["text"] .= '=';
					$_178 = TRUE; break;
				}
				$result = $res_175;
				$this->pos = $pos_175;
				$_178 = FALSE; break;
			}
			while(0);
			if( $_178 === TRUE ) { $_180 = TRUE; break; }
			$result = $res_173;
			$this->pos = $pos_173;
			$_180 = FALSE; break;
		}
		while(0);
		if( $_180 === TRUE ) { return $this->finalise($result); }
		if( $_180 === FALSE) { return FALSE; }
	}


	/* Comparison: Argument < ComparisonOperator > Argument */
	protected $match_Comparison_typestack = array('Comparison');
	function match_Comparison ($stack = array()) {
		$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
		$_187 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_187 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_187 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_187 = FALSE; break; }
			$_187 = TRUE; break;
		}
		while(0);
		if( $_187 === TRUE ) { return $this->finalise($result); }
		if( $_187 === FALSE) { return FALSE; }
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
		$_194 = NULL;
		do {
			$res_192 = $result;
			$pos_192 = $this->pos;
			$_191 = NULL;
			do {
				$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
				if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Not' );
				}
				else {
					$result = array_pop($stack);
					$_191 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_191 = TRUE; break;
			}
			while(0);
			if( $_191 === FALSE) {
				$result = $res_192;
				$this->pos = $pos_192;
				unset( $res_192 );
				unset( $pos_192 );
			}
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_194 = FALSE; break; }
			$_194 = TRUE; break;
		}
		while(0);
		if( $_194 === TRUE ) { return $this->finalise($result); }
		if( $_194 === FALSE) { return FALSE; }
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
		$_199 = NULL;
		do {
			$res_196 = $result;
			$pos_196 = $this->pos;
			$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_199 = TRUE; break;
			}
			$result = $res_196;
			$this->pos = $pos_196;
			$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_199 = TRUE; break;
			}
			$result = $res_196;
			$this->pos = $pos_196;
			$_199 = FALSE; break;
		}
		while(0);
		if( $_199 === TRUE ) { return $this->finalise($result); }
		if( $_199 === FALSE) { return FALSE; }
	}



	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* BooleanOperator: "||" | "&&" */
	protected $match_BooleanOperator_typestack = array('BooleanOperator');
	function match_BooleanOperator ($stack = array()) {
		$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_204 = NULL;
		do {
			$res_201 = $result;
			$pos_201 = $this->pos;
			if (( $subres = $this->literal( '||' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_204 = TRUE; break;
			}
			$result = $res_201;
			$this->pos = $pos_201;
			if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_204 = TRUE; break;
			}
			$result = $res_201;
			$this->pos = $pos_201;
			$_204 = FALSE; break;
		}
		while(0);
		if( $_204 === TRUE ) { return $this->finalise($result); }
		if( $_204 === FALSE) { return FALSE; }
	}


	/* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
	protected $match_IfArgument_typestack = array('IfArgument');
	function match_IfArgument ($stack = array()) {
		$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_213 = NULL;
		do {
			$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgumentPortion" );
			}
			else { $_213 = FALSE; break; }
			while (true) {
				$res_212 = $result;
				$pos_212 = $this->pos;
				$_211 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BooleanOperator" );
					}
					else { $_211 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "IfArgumentPortion" );
					}
					else { $_211 = FALSE; break; }
					$_211 = TRUE; break;
				}
				while(0);
				if( $_211 === FALSE) {
					$result = $res_212;
					$this->pos = $pos_212;
					unset( $res_212 );
					unset( $pos_212 );
					break;
				}
			}
			$_213 = TRUE; break;
		}
		while(0);
		if( $_213 === TRUE ) { return $this->finalise($result); }
		if( $_213 === FALSE) { return FALSE; }
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
		$_223 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_223 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_223 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_223 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_223 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_223 = FALSE; break; }
			$res_222 = $result;
			$pos_222 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_222;
				$this->pos = $pos_222;
				unset( $res_222 );
				unset( $pos_222 );
			}
			$_223 = TRUE; break;
		}
		while(0);
		if( $_223 === TRUE ) { return $this->finalise($result); }
		if( $_223 === FALSE) { return FALSE; }
	}


	/* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher */
	protected $match_ElseIfPart_typestack = array('ElseIfPart');
	function match_ElseIfPart ($stack = array()) {
		$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_233 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_233 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_233 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_233 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_233 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_233 = FALSE; break; }
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_233 = FALSE; break; }
			$_233 = TRUE; break;
		}
		while(0);
		if( $_233 === TRUE ) { return $this->finalise($result); }
		if( $_233 === FALSE) { return FALSE; }
	}


	/* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher */
	protected $match_ElsePart_typestack = array('ElsePart');
	function match_ElsePart ($stack = array()) {
		$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
		$_241 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_241 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_241 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_241 = FALSE; break; }
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_241 = FALSE; break; }
			$_241 = TRUE; break;
		}
		while(0);
		if( $_241 === TRUE ) { return $this->finalise($result); }
		if( $_241 === FALSE) { return FALSE; }
	}


	/* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
	protected $match_If_typestack = array('If');
	function match_If ($stack = array()) {
		$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
		$_251 = NULL;
		do {
			$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_251 = FALSE; break; }
			while (true) {
				$res_244 = $result;
				$pos_244 = $this->pos;
				$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else {
					$result = $res_244;
					$this->pos = $pos_244;
					unset( $res_244 );
					unset( $pos_244 );
					break;
				}
			}
			$res_245 = $result;
			$pos_245 = $this->pos;
			$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_245;
				$this->pos = $pos_245;
				unset( $res_245 );
				unset( $pos_245 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_251 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_251 = FALSE; break; }
			$_251 = TRUE; break;
		}
		while(0);
		if( $_251 === TRUE ) { return $this->finalise($result); }
		if( $_251 === FALSE) { return FALSE; }
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
		$_267 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_267 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_267 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_267 = FALSE; break; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
			$_263 = NULL;
			do {
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Method" );
				}
				else { $_263 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_263 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else { $_263 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_263 = FALSE; break; }
				$_263 = TRUE; break;
			}
			while(0);
			if( $_263 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Call' );
			}
			if( $_263 === FALSE) {
				$result = array_pop($stack);
				$_267 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_267 = FALSE; break; }
			$_267 = TRUE; break;
		}
		while(0);
		if( $_267 === TRUE ) { return $this->finalise($result); }
		if( $_267 === FALSE) { return FALSE; }
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
		$_287 = NULL;
		do {
			$res_275 = $result;
			$pos_275 = $this->pos;
			$_274 = NULL;
			do {
				$_272 = NULL;
				do {
					$res_269 = $result;
					$pos_269 = $this->pos;
					if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_272 = TRUE; break;
					}
					$result = $res_269;
					$this->pos = $pos_269;
					if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_272 = TRUE; break;
					}
					$result = $res_269;
					$this->pos = $pos_269;
					$_272 = FALSE; break;
				}
				while(0);
				if( $_272 === FALSE) { $_274 = FALSE; break; }
				$_274 = TRUE; break;
			}
			while(0);
			if( $_274 === TRUE ) {
				$result = $res_275;
				$this->pos = $pos_275;
				$_287 = FALSE; break;
			}
			if( $_274 === FALSE) {
				$result = $res_275;
				$this->pos = $pos_275;
			}
			$_285 = NULL;
			do {
				$_283 = NULL;
				do {
					$res_276 = $result;
					$pos_276 = $this->pos;
					$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "DollarMarkedLookup" );
						$_283 = TRUE; break;
					}
					$result = $res_276;
					$this->pos = $pos_276;
					$_281 = NULL;
					do {
						$res_278 = $result;
						$pos_278 = $this->pos;
						$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "QuotedString" );
							$_281 = TRUE; break;
						}
						$result = $res_278;
						$this->pos = $pos_278;
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
							$_281 = TRUE; break;
						}
						$result = $res_278;
						$this->pos = $pos_278;
						$_281 = FALSE; break;
					}
					while(0);
					if( $_281 === TRUE ) { $_283 = TRUE; break; }
					$result = $res_276;
					$this->pos = $pos_276;
					$_283 = FALSE; break;
				}
				while(0);
				if( $_283 === FALSE) { $_285 = FALSE; break; }
				$_285 = TRUE; break;
			}
			while(0);
			if( $_285 === FALSE) { $_287 = FALSE; break; }
			$_287 = TRUE; break;
		}
		while(0);
		if( $_287 === TRUE ) { return $this->finalise($result); }
		if( $_287 === FALSE) { return FALSE; }
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
		$_296 = NULL;
		do {
			$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_296 = FALSE; break; }
			while (true) {
				$res_295 = $result;
				$pos_295 = $this->pos;
				$_294 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_294 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_294 = FALSE; break; }
					$_294 = TRUE; break;
				}
				while(0);
				if( $_294 === FALSE) {
					$result = $res_295;
					$this->pos = $pos_295;
					unset( $res_295 );
					unset( $pos_295 );
					break;
				}
			}
			$_296 = TRUE; break;
		}
		while(0);
		if( $_296 === TRUE ) { return $this->finalise($result); }
		if( $_296 === FALSE) { return FALSE; }
	}



	function CacheBlockArguments_CacheBlockArgument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ".'_'.";
		else $res['php'] = '';
		
		$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
	}
	
	/* CacheBlockTemplate: (Comment | Translate | If | Require |    OldI18NTag | Include | ClosedBlock |
	OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_CacheBlockTemplate_typestack = array('CacheBlockTemplate','Template');
	function match_CacheBlockTemplate ($stack = array()) {
		$matchrule = "CacheBlockTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'CacheRestrictedTemplate'));
		$count = 0;
		while (true) {
			$res_340 = $result;
			$pos_340 = $this->pos;
			$_339 = NULL;
			do {
				$_337 = NULL;
				do {
					$res_298 = $result;
					$pos_298 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_337 = TRUE; break;
					}
					$result = $res_298;
					$this->pos = $pos_298;
					$_335 = NULL;
					do {
						$res_300 = $result;
						$pos_300 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_335 = TRUE; break;
						}
						$result = $res_300;
						$this->pos = $pos_300;
						$_333 = NULL;
						do {
							$res_302 = $result;
							$pos_302 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_333 = TRUE; break;
							}
							$result = $res_302;
							$this->pos = $pos_302;
							$_331 = NULL;
							do {
								$res_304 = $result;
								$pos_304 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_331 = TRUE; break;
								}
								$result = $res_304;
								$this->pos = $pos_304;
								$_329 = NULL;
								do {
									$res_306 = $result;
									$pos_306 = $this->pos;
									$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_329 = TRUE; break;
									}
									$result = $res_306;
									$this->pos = $pos_306;
									$_327 = NULL;
									do {
										$res_308 = $result;
										$pos_308 = $this->pos;
										$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_327 = TRUE; break;
										}
										$result = $res_308;
										$this->pos = $pos_308;
										$_325 = NULL;
										do {
											$res_310 = $result;
											$pos_310 = $this->pos;
											$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_325 = TRUE; break;
											}
											$result = $res_310;
											$this->pos = $pos_310;
											$_323 = NULL;
											do {
												$res_312 = $result;
												$pos_312 = $this->pos;
												$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_323 = TRUE; break;
												}
												$result = $res_312;
												$this->pos = $pos_312;
												$_321 = NULL;
												do {
													$res_314 = $result;
													$pos_314 = $this->pos;
													$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_321 = TRUE; break;
													}
													$result = $res_314;
													$this->pos = $pos_314;
													$_319 = NULL;
													do {
														$res_316 = $result;
														$pos_316 = $this->pos;
														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_319 = TRUE; break;
														}
														$result = $res_316;
														$this->pos = $pos_316;
														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_319 = TRUE; break;
														}
														$result = $res_316;
														$this->pos = $pos_316;
														$_319 = FALSE; break;
													}
													while(0);
													if( $_319 === TRUE ) { $_321 = TRUE; break; }
													$result = $res_314;
													$this->pos = $pos_314;
													$_321 = FALSE; break;
												}
												while(0);
												if( $_321 === TRUE ) { $_323 = TRUE; break; }
												$result = $res_312;
												$this->pos = $pos_312;
												$_323 = FALSE; break;
											}
											while(0);
											if( $_323 === TRUE ) { $_325 = TRUE; break; }
											$result = $res_310;
											$this->pos = $pos_310;
											$_325 = FALSE; break;
										}
										while(0);
										if( $_325 === TRUE ) { $_327 = TRUE; break; }
										$result = $res_308;
										$this->pos = $pos_308;
										$_327 = FALSE; break;
									}
									while(0);
									if( $_327 === TRUE ) { $_329 = TRUE; break; }
									$result = $res_306;
									$this->pos = $pos_306;
									$_329 = FALSE; break;
								}
								while(0);
								if( $_329 === TRUE ) { $_331 = TRUE; break; }
								$result = $res_304;
								$this->pos = $pos_304;
								$_331 = FALSE; break;
							}
							while(0);
							if( $_331 === TRUE ) { $_333 = TRUE; break; }
							$result = $res_302;
							$this->pos = $pos_302;
							$_333 = FALSE; break;
						}
						while(0);
						if( $_333 === TRUE ) { $_335 = TRUE; break; }
						$result = $res_300;
						$this->pos = $pos_300;
						$_335 = FALSE; break;
					}
					while(0);
					if( $_335 === TRUE ) { $_337 = TRUE; break; }
					$result = $res_298;
					$this->pos = $pos_298;
					$_337 = FALSE; break;
				}
				while(0);
				if( $_337 === FALSE) { $_339 = FALSE; break; }
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
		$_377 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_377 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_377 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_345 = $result;
			$pos_345 = $this->pos;
			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_345;
				$this->pos = $pos_345;
				unset( $res_345 );
				unset( $pos_345 );
			}
			$res_357 = $result;
			$pos_357 = $this->pos;
			$_356 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_352 = NULL;
				do {
					$_350 = NULL;
					do {
						$res_347 = $result;
						$pos_347 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_350 = TRUE; break;
						}
						$result = $res_347;
						$this->pos = $pos_347;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_350 = TRUE; break;
						}
						$result = $res_347;
						$this->pos = $pos_347;
						$_350 = FALSE; break;
					}
					while(0);
					if( $_350 === FALSE) { $_352 = FALSE; break; }
					$_352 = TRUE; break;
				}
				while(0);
				if( $_352 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_352 === FALSE) {
					$result = array_pop($stack);
					$_356 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_356 = FALSE; break; }
				$_356 = TRUE; break;
			}
			while(0);
			if( $_356 === FALSE) {
				$result = $res_357;
				$this->pos = $pos_357;
				unset( $res_357 );
				unset( $pos_357 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_377 = FALSE; break; }
			$res_360 = $result;
			$pos_360 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_360;
				$this->pos = $pos_360;
				unset( $res_360 );
				unset( $pos_360 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_377 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_377 = FALSE; break; }
			$_373 = NULL;
			do {
				$_371 = NULL;
				do {
					$res_364 = $result;
					$pos_364 = $this->pos;
					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_371 = TRUE; break;
					}
					$result = $res_364;
					$this->pos = $pos_364;
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
					if( $_369 === TRUE ) { $_371 = TRUE; break; }
					$result = $res_364;
					$this->pos = $pos_364;
					$_371 = FALSE; break;
				}
				while(0);
				if( $_371 === FALSE) { $_373 = FALSE; break; }
				$_373 = TRUE; break;
			}
			while(0);
			if( $_373 === FALSE) { $_377 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_377 = FALSE; break; }
			$_377 = TRUE; break;
		}
		while(0);
		if( $_377 === TRUE ) { return $this->finalise($result); }
		if( $_377 === FALSE) { return FALSE; }
	}



	function UncachedBlock_Template(&$res, $sub){
		$res['php'] = $sub['php'];
	}
	
	/* CacheRestrictedTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
	OpenBlock | MalformedBlock | Injection | Text)+ */
	protected $match_CacheRestrictedTemplate_typestack = array('CacheRestrictedTemplate','Template');
	function match_CacheRestrictedTemplate ($stack = array()) {
		$matchrule = "CacheRestrictedTemplate"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_429 = $result;
			$pos_429 = $this->pos;
			$_428 = NULL;
			do {
				$_426 = NULL;
				do {
					$res_379 = $result;
					$pos_379 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_426 = TRUE; break;
					}
					$result = $res_379;
					$this->pos = $pos_379;
					$_424 = NULL;
					do {
						$res_381 = $result;
						$pos_381 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_424 = TRUE; break;
						}
						$result = $res_381;
						$this->pos = $pos_381;
						$_422 = NULL;
						do {
							$res_383 = $result;
							$pos_383 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_422 = TRUE; break;
							}
							$result = $res_383;
							$this->pos = $pos_383;
							$_420 = NULL;
							do {
								$res_385 = $result;
								$pos_385 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_420 = TRUE; break;
								}
								$result = $res_385;
								$this->pos = $pos_385;
								$_418 = NULL;
								do {
									$res_387 = $result;
									$pos_387 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_418 = TRUE; break;
									}
									$result = $res_387;
									$this->pos = $pos_387;
									$_416 = NULL;
									do {
										$res_389 = $result;
										$pos_389 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_416 = TRUE; break;
										}
										$result = $res_389;
										$this->pos = $pos_389;
										$_414 = NULL;
										do {
											$res_391 = $result;
											$pos_391 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_414 = TRUE; break;
											}
											$result = $res_391;
											$this->pos = $pos_391;
											$_412 = NULL;
											do {
												$res_393 = $result;
												$pos_393 = $this->pos;
												$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_412 = TRUE; break;
												}
												$result = $res_393;
												$this->pos = $pos_393;
												$_410 = NULL;
												do {
													$res_395 = $result;
													$pos_395 = $this->pos;
													$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_410 = TRUE; break;
													}
													$result = $res_395;
													$this->pos = $pos_395;
													$_408 = NULL;
													do {
														$res_397 = $result;
														$pos_397 = $this->pos;
														$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_408 = TRUE; break;
														}
														$result = $res_397;
														$this->pos = $pos_397;
														$_406 = NULL;
														do {
															$res_399 = $result;
															$pos_399 = $this->pos;
															$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_406 = TRUE; break;
															}
															$result = $res_399;
															$this->pos = $pos_399;
															$_404 = NULL;
															do {
																$res_401 = $result;
																$pos_401 = $this->pos;
																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_404 = TRUE; break;
																}
																$result = $res_401;
																$this->pos = $pos_401;
																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_404 = TRUE; break;
																}
																$result = $res_401;
																$this->pos = $pos_401;
																$_404 = FALSE; break;
															}
															while(0);
															if( $_404 === TRUE ) { $_406 = TRUE; break; }
															$result = $res_399;
															$this->pos = $pos_399;
															$_406 = FALSE; break;
														}
														while(0);
														if( $_406 === TRUE ) { $_408 = TRUE; break; }
														$result = $res_397;
														$this->pos = $pos_397;
														$_408 = FALSE; break;
													}
													while(0);
													if( $_408 === TRUE ) { $_410 = TRUE; break; }
													$result = $res_395;
													$this->pos = $pos_395;
													$_410 = FALSE; break;
												}
												while(0);
												if( $_410 === TRUE ) { $_412 = TRUE; break; }
												$result = $res_393;
												$this->pos = $pos_393;
												$_412 = FALSE; break;
											}
											while(0);
											if( $_412 === TRUE ) { $_414 = TRUE; break; }
											$result = $res_391;
											$this->pos = $pos_391;
											$_414 = FALSE; break;
										}
										while(0);
										if( $_414 === TRUE ) { $_416 = TRUE; break; }
										$result = $res_389;
										$this->pos = $pos_389;
										$_416 = FALSE; break;
									}
									while(0);
									if( $_416 === TRUE ) { $_418 = TRUE; break; }
									$result = $res_387;
									$this->pos = $pos_387;
									$_418 = FALSE; break;
								}
								while(0);
								if( $_418 === TRUE ) { $_420 = TRUE; break; }
								$result = $res_385;
								$this->pos = $pos_385;
								$_420 = FALSE; break;
							}
							while(0);
							if( $_420 === TRUE ) { $_422 = TRUE; break; }
							$result = $res_383;
							$this->pos = $pos_383;
							$_422 = FALSE; break;
						}
						while(0);
						if( $_422 === TRUE ) { $_424 = TRUE; break; }
						$result = $res_381;
						$this->pos = $pos_381;
						$_424 = FALSE; break;
					}
					while(0);
					if( $_424 === TRUE ) { $_426 = TRUE; break; }
					$result = $res_379;
					$this->pos = $pos_379;
					$_426 = FALSE; break;
				}
				while(0);
				if( $_426 === FALSE) { $_428 = FALSE; break; }
				$_428 = TRUE; break;
			}
			while(0);
			if( $_428 === FALSE) {
				$result = $res_429;
				$this->pos = $pos_429;
				unset( $res_429 );
				unset( $pos_429 );
				break;
			}
			$count += 1;
		}
		if ($count > 0) { return $this->finalise($result); }
		else { return FALSE; }
	}



	function CacheRestrictedTemplate_CacheBlock(&$res, $sub) { 
		throw new SSTemplateParseException('You cant have cache blocks nested within with, loop or control blocks ' .
			'that are within cache blocks', $this);
	}
	
	function CacheRestrictedTemplate_UncachedBlock(&$res, $sub) { 
		throw new SSTemplateParseException('You cant have uncache blocks nested within with, loop or control blocks ' .
			'that are within cache blocks', $this);
	}
	
	/* CacheBlock: 
	'<%' < CacheTag:("cached"|"cacheblock") < (CacheBlockArguments)? ( < Conditional:("if"|"unless") >
	Condition:IfArgument )? > '%>'
		(CacheBlock | UncachedBlock | CacheBlockTemplate)*
	'<%' < 'end_' ("cached"|"uncached"|"cacheblock") > '%>' */
	protected $match_CacheBlock_typestack = array('CacheBlock');
	function match_CacheBlock ($stack = array()) {
		$matchrule = "CacheBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_484 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_484 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
			$_437 = NULL;
			do {
				$_435 = NULL;
				do {
					$res_432 = $result;
					$pos_432 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_435 = TRUE; break;
					}
					$result = $res_432;
					$this->pos = $pos_432;
					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_435 = TRUE; break;
					}
					$result = $res_432;
					$this->pos = $pos_432;
					$_435 = FALSE; break;
				}
				while(0);
				if( $_435 === FALSE) { $_437 = FALSE; break; }
				$_437 = TRUE; break;
			}
			while(0);
			if( $_437 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'CacheTag' );
			}
			if( $_437 === FALSE) {
				$result = array_pop($stack);
				$_484 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_442 = $result;
			$pos_442 = $this->pos;
			$_441 = NULL;
			do {
				$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_441 = FALSE; break; }
				$_441 = TRUE; break;
			}
			while(0);
			if( $_441 === FALSE) {
				$result = $res_442;
				$this->pos = $pos_442;
				unset( $res_442 );
				unset( $pos_442 );
			}
			$res_454 = $result;
			$pos_454 = $this->pos;
			$_453 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_449 = NULL;
				do {
					$_447 = NULL;
					do {
						$res_444 = $result;
						$pos_444 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_447 = TRUE; break;
						}
						$result = $res_444;
						$this->pos = $pos_444;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_447 = TRUE; break;
						}
						$result = $res_444;
						$this->pos = $pos_444;
						$_447 = FALSE; break;
					}
					while(0);
					if( $_447 === FALSE) { $_449 = FALSE; break; }
					$_449 = TRUE; break;
				}
				while(0);
				if( $_449 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_449 === FALSE) {
					$result = array_pop($stack);
					$_453 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_453 = FALSE; break; }
				$_453 = TRUE; break;
			}
			while(0);
			if( $_453 === FALSE) {
				$result = $res_454;
				$this->pos = $pos_454;
				unset( $res_454 );
				unset( $pos_454 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_484 = FALSE; break; }
			while (true) {
				$res_467 = $result;
				$pos_467 = $this->pos;
				$_466 = NULL;
				do {
					$_464 = NULL;
					do {
						$res_457 = $result;
						$pos_457 = $this->pos;
						$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_464 = TRUE; break;
						}
						$result = $res_457;
						$this->pos = $pos_457;
						$_462 = NULL;
						do {
							$res_459 = $result;
							$pos_459 = $this->pos;
							$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_462 = TRUE; break;
							}
							$result = $res_459;
							$this->pos = $pos_459;
							$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
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
						if( $_462 === TRUE ) { $_464 = TRUE; break; }
						$result = $res_457;
						$this->pos = $pos_457;
						$_464 = FALSE; break;
					}
					while(0);
					if( $_464 === FALSE) { $_466 = FALSE; break; }
					$_466 = TRUE; break;
				}
				while(0);
				if( $_466 === FALSE) {
					$result = $res_467;
					$this->pos = $pos_467;
					unset( $res_467 );
					unset( $pos_467 );
					break;
				}
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_484 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_484 = FALSE; break; }
			$_480 = NULL;
			do {
				$_478 = NULL;
				do {
					$res_471 = $result;
					$pos_471 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_478 = TRUE; break;
					}
					$result = $res_471;
					$this->pos = $pos_471;
					$_476 = NULL;
					do {
						$res_473 = $result;
						$pos_473 = $this->pos;
						if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_476 = TRUE; break;
						}
						$result = $res_473;
						$this->pos = $pos_473;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_476 = TRUE; break;
						}
						$result = $res_473;
						$this->pos = $pos_473;
						$_476 = FALSE; break;
					}
					while(0);
					if( $_476 === TRUE ) { $_478 = TRUE; break; }
					$result = $res_471;
					$this->pos = $pos_471;
					$_478 = FALSE; break;
				}
				while(0);
				if( $_478 === FALSE) { $_480 = FALSE; break; }
				$_480 = TRUE; break;
			}
			while(0);
			if( $_480 === FALSE) { $_484 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_484 = FALSE; break; }
			$_484 = TRUE; break;
		}
		while(0);
		if( $_484 === TRUE ) { return $this->finalise($result); }
		if( $_484 === FALSE) { return FALSE; }
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
		// Build the key for this block from the passed cache key, the block index, and the sha hash of the template
		// itself
		$key = "'" . sha1($sub['php']) . (isset($res['key']) && $res['key'] ? "_'.sha1(".$res['key'].")" : "'") . 
			".'_$block'";
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
		$_503 = NULL;
		do {
			if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_503 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_503 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_503 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_503 = FALSE; break; }
			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_503 = FALSE; break; }
			$res_496 = $result;
			$pos_496 = $this->pos;
			$_495 = NULL;
			do {
				$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_495 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_495 = FALSE; break; }
				$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_495 = FALSE; break; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_495 = FALSE; break; }
				$_495 = TRUE; break;
			}
			while(0);
			if( $_495 === FALSE) {
				$result = $res_496;
				$this->pos = $pos_496;
				unset( $res_496 );
				unset( $pos_496 );
			}
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_503 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_503 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_503 = FALSE; break; }
			$res_502 = $result;
			$pos_502 = $this->pos;
			$_501 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_501 = FALSE; break; }
				$_501 = TRUE; break;
			}
			while(0);
			if( $_501 === FALSE) {
				$result = $res_502;
				$this->pos = $pos_502;
				unset( $res_502 );
				unset( $pos_502 );
			}
			$_503 = TRUE; break;
		}
		while(0);
		if( $_503 === TRUE ) { return $this->finalise($result); }
		if( $_503 === FALSE) { return FALSE; }
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
		$_511 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_511 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_511 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_511 = FALSE; break; }
			$_511 = TRUE; break;
		}
		while(0);
		if( $_511 === TRUE ) { return $this->finalise($result); }
		if( $_511 === FALSE) { return FALSE; }
	}



	function OldTTag_OldTPart(&$res, $sub) {
		$res['php'] = $sub['php'];
	}
	 	  
	/* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>"  */
	protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
	function match_OldSprintfTag ($stack = array()) {
		$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_528 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ',') {
				$this->pos += 1;
				$result["text"] .= ',';
			}
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_528 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_528 = FALSE; break; }
			$_528 = TRUE; break;
		}
		while(0);
		if( $_528 === TRUE ) { return $this->finalise($result); }
		if( $_528 === FALSE) { return FALSE; }
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
		$_533 = NULL;
		do {
			$res_530 = $result;
			$pos_530 = $this->pos;
			$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_533 = TRUE; break;
			}
			$result = $res_530;
			$this->pos = $pos_530;
			$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_533 = TRUE; break;
			}
			$result = $res_530;
			$this->pos = $pos_530;
			$_533 = FALSE; break;
		}
		while(0);
		if( $_533 === TRUE ) { return $this->finalise($result); }
		if( $_533 === FALSE) { return FALSE; }
	}



	function OldI18NTag_STR(&$res, $sub) {
		$res['php'] = '$val .= ' . $sub['php'] . ';';
	}

	/* NamedArgument: Name:Word "=" Value:Argument */
	protected $match_NamedArgument_typestack = array('NamedArgument');
	function match_NamedArgument ($stack = array()) {
		$matchrule = "NamedArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_538 = NULL;
		do {
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Name" );
			}
			else { $_538 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '=') {
				$this->pos += 1;
				$result["text"] .= '=';
			}
			else { $_538 = FALSE; break; }
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Value" );
			}
			else { $_538 = FALSE; break; }
			$_538 = TRUE; break;
		}
		while(0);
		if( $_538 === TRUE ) { return $this->finalise($result); }
		if( $_538 === FALSE) { return FALSE; }
	}



	function NamedArgument_Name(&$res, $sub) {
		$res['php'] = "'" . $sub['text'] . "' => ";
	}

	function NamedArgument_Value(&$res, $sub) {
		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] 
			: str_replace('$$FINAL', 'XML_val', $sub['php']);
	}

	/* Include: "<%" < "include" < Template:Word < (NamedArgument ( < "," < NamedArgument )*)? > "%>" */
	protected $match_Include_typestack = array('Include');
	function match_Include ($stack = array()) {
		$matchrule = "Include"; $result = $this->construct($matchrule, $matchrule, null);
		$_557 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_557 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'include' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_557 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_557 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_554 = $result;
			$pos_554 = $this->pos;
			$_553 = NULL;
			do {
				$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_553 = FALSE; break; }
				while (true) {
					$res_552 = $result;
					$pos_552 = $this->pos;
					$_551 = NULL;
					do {
						if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
						if (substr($this->string,$this->pos,1) == ',') {
							$this->pos += 1;
							$result["text"] .= ',';
						}
						else { $_551 = FALSE; break; }
						if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
						$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_551 = FALSE; break; }
						$_551 = TRUE; break;
					}
					while(0);
					if( $_551 === FALSE) {
						$result = $res_552;
						$this->pos = $pos_552;
						unset( $res_552 );
						unset( $pos_552 );
						break;
					}
				}
				$_553 = TRUE; break;
			}
			while(0);
			if( $_553 === FALSE) {
				$result = $res_554;
				$this->pos = $pos_554;
				unset( $res_554 );
				unset( $pos_554 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_557 = FALSE; break; }
			$_557 = TRUE; break;
		}
		while(0);
		if( $_557 === TRUE ) { return $this->finalise($result); }
		if( $_557 === FALSE) { return FALSE; }
	}



	function Include__construct(&$res){
		$res['arguments'] = array();
	}

	function Include_Template(&$res, $sub){
		$res['template'] = "'" . $sub['text'] . "'";
	}

	function Include_NamedArgument(&$res, $sub){
		$res['arguments'][] = $sub['php'];
	}

	function Include__finalise(&$res){
		$template = $res['template'];
		$arguments = $res['arguments'];

		$res['php'] = '$val .= SSViewer::execute_template('.$template.', $scope->getItem(), array(' . 
			implode(',', $arguments)."));\n";

		if($this->includeDebuggingComments) { // Add include filename comments on dev sites
			$res['php'] =
				'$val .= \'<!-- include '.addslashes($template).' -->\';'. "\n".
				$res['php'].
				'$val .= \'<!-- end include '.addslashes($template).' -->\';'. "\n";
		}
	}

	/* BlockArguments: :Argument ( < "," < :Argument)*  */
	protected $match_BlockArguments_typestack = array('BlockArguments');
	function match_BlockArguments ($stack = array()) {
		$matchrule = "BlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
		$_566 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_566 = FALSE; break; }
			while (true) {
				$res_565 = $result;
				$pos_565 = $this->pos;
				$_564 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_564 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_564 = FALSE; break; }
					$_564 = TRUE; break;
				}
				while(0);
				if( $_564 === FALSE) {
					$result = $res_565;
					$this->pos = $pos_565;
					unset( $res_565 );
					unset( $pos_565 );
					break;
				}
			}
			$_566 = TRUE; break;
		}
		while(0);
		if( $_566 === TRUE ) { return $this->finalise($result); }
		if( $_566 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")]) */
	protected $match_NotBlockTag_typestack = array('NotBlockTag');
	function match_NotBlockTag ($stack = array()) {
		$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_604 = NULL;
		do {
			$res_568 = $result;
			$pos_568 = $this->pos;
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_604 = TRUE; break;
			}
			$result = $res_568;
			$this->pos = $pos_568;
			$_602 = NULL;
			do {
				$_599 = NULL;
				do {
					$_597 = NULL;
					do {
						$res_570 = $result;
						$pos_570 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_597 = TRUE; break;
						}
						$result = $res_570;
						$this->pos = $pos_570;
						$_595 = NULL;
						do {
							$res_572 = $result;
							$pos_572 = $this->pos;
							if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_595 = TRUE; break;
							}
							$result = $res_572;
							$this->pos = $pos_572;
							$_593 = NULL;
							do {
								$res_574 = $result;
								$pos_574 = $this->pos;
								if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_593 = TRUE; break;
								}
								$result = $res_574;
								$this->pos = $pos_574;
								$_591 = NULL;
								do {
									$res_576 = $result;
									$pos_576 = $this->pos;
									if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_591 = TRUE; break;
									}
									$result = $res_576;
									$this->pos = $pos_576;
									$_589 = NULL;
									do {
										$res_578 = $result;
										$pos_578 = $this->pos;
										if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
											$result["text"] .= $subres;
											$_589 = TRUE; break;
										}
										$result = $res_578;
										$this->pos = $pos_578;
										$_587 = NULL;
										do {
											$res_580 = $result;
											$pos_580 = $this->pos;
											if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
												$result["text"] .= $subres;
												$_587 = TRUE; break;
											}
											$result = $res_580;
											$this->pos = $pos_580;
											$_585 = NULL;
											do {
												$res_582 = $result;
												$pos_582 = $this->pos;
												if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
													$result["text"] .= $subres;
													$_585 = TRUE; break;
												}
												$result = $res_582;
												$this->pos = $pos_582;
												if (( $subres = $this->literal( 'include' ) ) !== FALSE) {
													$result["text"] .= $subres;
													$_585 = TRUE; break;
												}
												$result = $res_582;
												$this->pos = $pos_582;
												$_585 = FALSE; break;
											}
											while(0);
											if( $_585 === TRUE ) { $_587 = TRUE; break; }
											$result = $res_580;
											$this->pos = $pos_580;
											$_587 = FALSE; break;
										}
										while(0);
										if( $_587 === TRUE ) { $_589 = TRUE; break; }
										$result = $res_578;
										$this->pos = $pos_578;
										$_589 = FALSE; break;
									}
									while(0);
									if( $_589 === TRUE ) { $_591 = TRUE; break; }
									$result = $res_576;
									$this->pos = $pos_576;
									$_591 = FALSE; break;
								}
								while(0);
								if( $_591 === TRUE ) { $_593 = TRUE; break; }
								$result = $res_574;
								$this->pos = $pos_574;
								$_593 = FALSE; break;
							}
							while(0);
							if( $_593 === TRUE ) { $_595 = TRUE; break; }
							$result = $res_572;
							$this->pos = $pos_572;
							$_595 = FALSE; break;
						}
						while(0);
						if( $_595 === TRUE ) { $_597 = TRUE; break; }
						$result = $res_570;
						$this->pos = $pos_570;
						$_597 = FALSE; break;
					}
					while(0);
					if( $_597 === FALSE) { $_599 = FALSE; break; }
					$_599 = TRUE; break;
				}
				while(0);
				if( $_599 === FALSE) { $_602 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_602 = FALSE; break; }
				$_602 = TRUE; break;
			}
			while(0);
			if( $_602 === TRUE ) { $_604 = TRUE; break; }
			$result = $res_568;
			$this->pos = $pos_568;
			$_604 = FALSE; break;
		}
		while(0);
		if( $_604 === TRUE ) { return $this->finalise($result); }
		if( $_604 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher? 
	'<%' < 'end_' '$BlockName' > '%>' */
	protected $match_ClosedBlock_typestack = array('ClosedBlock');
	function match_ClosedBlock ($stack = array()) {
		$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_624 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_624 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_608 = $result;
			$pos_608 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_608;
				$this->pos = $pos_608;
				$_624 = FALSE; break;
			}
			else {
				$result = $res_608;
				$this->pos = $pos_608;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_624 = FALSE; break; }
			$res_614 = $result;
			$pos_614 = $this->pos;
			$_613 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_613 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_613 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_613 = FALSE; break; }
				$_613 = TRUE; break;
			}
			while(0);
			if( $_613 === FALSE) {
				$result = $res_614;
				$this->pos = $pos_614;
				unset( $res_614 );
				unset( $pos_614 );
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
				$_624 = FALSE; break;
			}
			$res_617 = $result;
			$pos_617 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_617;
				$this->pos = $pos_617;
				unset( $res_617 );
				unset( $pos_617 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_624 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_624 = FALSE; break; }
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_624 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_624 = FALSE; break; }
			$_624 = TRUE; break;
		}
		while(0);
		if( $_624 === TRUE ) { return $this->finalise($result); }
		if( $_624 === FALSE) { return FALSE; }
	}



	
	/**
	 * As mentioned in the parser comment, block handling is kept fairly generic for extensibility. The match rule
	 * builds up two important elements in the match result array:
	 *   'ArgumentCount' - how many arguments were passed in the opening tag
	 *   'Arguments' an array of the Argument match rule result arrays
	 *
	 * Once a block has successfully been matched against, it will then look for the actual handler, which should
	 * be on this class (either defined or extended on) as ClosedBlock_Handler_Name(&$res), where Name is the
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
		
		$method = 'ClosedBlock_Handle_'.$blockname;
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown closed block "'.$blockname.'" encountered. Perhaps you are ' .
				'not supposed to close this block, or have mis-spelled it?', $this);
		}
	}

	/**
	 * This is an example of a block handler function. This one handles the loop tag.
	 */
	function ClosedBlock_Handle_Loop(&$res) {
		if ($res['ArgumentCount'] > 1) {
			throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one ' .
				'argument only.', $this);
		}

		//loop without arguments loops on the current scope
		if ($res['ArgumentCount'] == 0) {
			$on = '$scope->obj(\'Up\', null, true)->obj(\'Foo\', null, true)';
		} else {    //loop in the normal way
			$arg = $res['Arguments'][0];
			if ($arg['ArgumentMode'] == 'string') {
				throw new SSTemplateParseException('Control block cant take string as argument.', $this);
			}
			$on = str_replace('$$FINAL', 'obj', 
				($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
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
		Deprecation::notice('3.1', 'Use <% with %> or <% loop %> instead.');
		return $this->ClosedBlock_Handle_Loop($res);
	}
	
	/**
	 * The closed block handler for with blocks
	 */
	function ClosedBlock_Handle_With(&$res) {
		if ($res['ArgumentCount'] != 1) {
			throw new SSTemplateParseException('Either no or too many arguments in with block. Must be one ' .
				'argument only.', $this);
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
		$_637 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_637 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_628 = $result;
			$pos_628 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_628;
				$this->pos = $pos_628;
				$_637 = FALSE; break;
			}
			else {
				$result = $res_628;
				$this->pos = $pos_628;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_637 = FALSE; break; }
			$res_634 = $result;
			$pos_634 = $this->pos;
			$_633 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_633 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_633 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_633 = FALSE; break; }
				$_633 = TRUE; break;
			}
			while(0);
			if( $_633 === FALSE) {
				$result = $res_634;
				$this->pos = $pos_634;
				unset( $res_634 );
				unset( $pos_634 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_637 = FALSE; break; }
			$_637 = TRUE; break;
		}
		while(0);
		if( $_637 === TRUE ) { return $this->finalise($result); }
		if( $_637 === FALSE) { return FALSE; }
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
	
		$method = 'OpenBlock_Handle_'.$blockname;
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown open block "'.$blockname.'" encountered. Perhaps you missed ' .
				' the closing tag or have mis-spelled it?', $this);
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
		$_645 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_645 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_645 = FALSE; break; }
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Word" );
			}
			else { $_645 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_645 = FALSE; break; }
			$_645 = TRUE; break;
		}
		while(0);
		if( $_645 === TRUE ) { return $this->finalise($result); }
		if( $_645 === FALSE) { return FALSE; }
	}



	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_' . $blockname . 
			' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
	protected $match_MalformedOpenTag_typestack = array('MalformedOpenTag');
	function match_MalformedOpenTag ($stack = array()) {
		$matchrule = "MalformedOpenTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_660 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_660 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_649 = $result;
			$pos_649 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_649;
				$this->pos = $pos_649;
				$_660 = FALSE; break;
			}
			else {
				$result = $res_649;
				$this->pos = $pos_649;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_660 = FALSE; break; }
			$res_659 = $result;
			$pos_659 = $this->pos;
			$_658 = NULL;
			do {
				$res_655 = $result;
				$pos_655 = $this->pos;
				$_654 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_654 = FALSE; break; }
					$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_654 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_654 = FALSE; break; }
					$_654 = TRUE; break;
				}
				while(0);
				if( $_654 === FALSE) {
					$result = $res_655;
					$this->pos = $pos_655;
					unset( $res_655 );
					unset( $pos_655 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
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
		if( $_660 === TRUE ) { return $this->finalise($result); }
		if( $_660 === FALSE) { return FALSE; }
	}



	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?"
			, $this);
	}
	
	/* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
	protected $match_MalformedCloseTag_typestack = array('MalformedCloseTag');
	function match_MalformedCloseTag ($stack = array()) {
		$matchrule = "MalformedCloseTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_672 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_672 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
			$_666 = NULL;
			do {
				if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_666 = FALSE; break; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_666 = FALSE; break; }
				$_666 = TRUE; break;
			}
			while(0);
			if( $_666 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_666 === FALSE) {
				$result = array_pop($stack);
				$_672 = FALSE; break;
			}
			$res_671 = $result;
			$pos_671 = $this->pos;
			$_670 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_670 = FALSE; break; }
				$_670 = TRUE; break;
			}
			while(0);
			if( $_670 === TRUE ) {
				$result = $res_671;
				$this->pos = $pos_671;
				$_672 = FALSE; break;
			}
			if( $_670 === FALSE) {
				$result = $res_671;
				$this->pos = $pos_671;
			}
			$_672 = TRUE; break;
		}
		while(0);
		if( $_672 === TRUE ) { return $this->finalise($result); }
		if( $_672 === FALSE) { return FALSE; }
	}



	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an " .
			"argument to one?", $this);
	}
	
	/* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
	protected $match_MalformedBlock_typestack = array('MalformedBlock');
	function match_MalformedBlock ($stack = array()) {
		$matchrule = "MalformedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_677 = NULL;
		do {
			$res_674 = $result;
			$pos_674 = $this->pos;
			$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_677 = TRUE; break;
			}
			$result = $res_674;
			$this->pos = $pos_674;
			$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_677 = TRUE; break;
			}
			$result = $res_674;
			$this->pos = $pos_674;
			$_677 = FALSE; break;
		}
		while(0);
		if( $_677 === TRUE ) { return $this->finalise($result); }
		if( $_677 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /./)+ "--%>" */
	protected $match_Comment_typestack = array('Comment');
	function match_Comment ($stack = array()) {
		$matchrule = "Comment"; $result = $this->construct($matchrule, $matchrule, null);
		$_685 = NULL;
		do {
			if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_685 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_683 = $result;
				$pos_683 = $this->pos;
				$_682 = NULL;
				do {
					$res_680 = $result;
					$pos_680 = $this->pos;
					if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_680;
						$this->pos = $pos_680;
						$_682 = FALSE; break;
					}
					else {
						$result = $res_680;
						$this->pos = $pos_680;
					}
					if (( $subres = $this->rx( '/./' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_682 = FALSE; break; }
					$_682 = TRUE; break;
				}
				while(0);
				if( $_682 === FALSE) {
					$result = $res_683;
					$this->pos = $pos_683;
					unset( $res_683 );
					unset( $pos_683 );
					break;
				}
				$count += 1;
			}
			if ($count > 0) {  }
			else { $_685 = FALSE; break; }
			if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_685 = FALSE; break; }
			$_685 = TRUE; break;
		}
		while(0);
		if( $_685 === TRUE ) { return $this->finalise($result); }
		if( $_685 === FALSE) { return FALSE; }
	}



	function Comment__construct(&$res) {
		$res['php'] = '';
	}
		
	/* TopTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
	OpenBlock |  MalformedBlock | MismatchedEndBlock  | Injection | Text)+ */
	protected $match_TopTemplate_typestack = array('TopTemplate','Template');
	function match_TopTemplate ($stack = array()) {
		$matchrule = "TopTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'Template'));
		$count = 0;
		while (true) {
			$res_741 = $result;
			$pos_741 = $this->pos;
			$_740 = NULL;
			do {
				$_738 = NULL;
				do {
					$res_687 = $result;
					$pos_687 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_738 = TRUE; break;
					}
					$result = $res_687;
					$this->pos = $pos_687;
					$_736 = NULL;
					do {
						$res_689 = $result;
						$pos_689 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_736 = TRUE; break;
						}
						$result = $res_689;
						$this->pos = $pos_689;
						$_734 = NULL;
						do {
							$res_691 = $result;
							$pos_691 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_734 = TRUE; break;
							}
							$result = $res_691;
							$this->pos = $pos_691;
							$_732 = NULL;
							do {
								$res_693 = $result;
								$pos_693 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_732 = TRUE; break;
								}
								$result = $res_693;
								$this->pos = $pos_693;
								$_730 = NULL;
								do {
									$res_695 = $result;
									$pos_695 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_730 = TRUE; break;
									}
									$result = $res_695;
									$this->pos = $pos_695;
									$_728 = NULL;
									do {
										$res_697 = $result;
										$pos_697 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_728 = TRUE; break;
										}
										$result = $res_697;
										$this->pos = $pos_697;
										$_726 = NULL;
										do {
											$res_699 = $result;
											$pos_699 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_726 = TRUE; break;
											}
											$result = $res_699;
											$this->pos = $pos_699;
											$_724 = NULL;
											do {
												$res_701 = $result;
												$pos_701 = $this->pos;
												$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_724 = TRUE; break;
												}
												$result = $res_701;
												$this->pos = $pos_701;
												$_722 = NULL;
												do {
													$res_703 = $result;
													$pos_703 = $this->pos;
													$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_722 = TRUE; break;
													}
													$result = $res_703;
													$this->pos = $pos_703;
													$_720 = NULL;
													do {
														$res_705 = $result;
														$pos_705 = $this->pos;
														$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_720 = TRUE; break;
														}
														$result = $res_705;
														$this->pos = $pos_705;
														$_718 = NULL;
														do {
															$res_707 = $result;
															$pos_707 = $this->pos;
															$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_718 = TRUE; break;
															}
															$result = $res_707;
															$this->pos = $pos_707;
															$_716 = NULL;
															do {
																$res_709 = $result;
																$pos_709 = $this->pos;
																$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_716 = TRUE; break;
																}
																$result = $res_709;
																$this->pos = $pos_709;
																$_714 = NULL;
																do {
																	$res_711 = $result;
																	$pos_711 = $this->pos;
																	$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_714 = TRUE; break;
																	}
																	$result = $res_711;
																	$this->pos = $pos_711;
																	$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_714 = TRUE; break;
																	}
																	$result = $res_711;
																	$this->pos = $pos_711;
																	$_714 = FALSE; break;
																}
																while(0);
																if( $_714 === TRUE ) { $_716 = TRUE; break; }
																$result = $res_709;
																$this->pos = $pos_709;
																$_716 = FALSE; break;
															}
															while(0);
															if( $_716 === TRUE ) { $_718 = TRUE; break; }
															$result = $res_707;
															$this->pos = $pos_707;
															$_718 = FALSE; break;
														}
														while(0);
														if( $_718 === TRUE ) { $_720 = TRUE; break; }
														$result = $res_705;
														$this->pos = $pos_705;
														$_720 = FALSE; break;
													}
													while(0);
													if( $_720 === TRUE ) { $_722 = TRUE; break; }
													$result = $res_703;
													$this->pos = $pos_703;
													$_722 = FALSE; break;
												}
												while(0);
												if( $_722 === TRUE ) { $_724 = TRUE; break; }
												$result = $res_701;
												$this->pos = $pos_701;
												$_724 = FALSE; break;
											}
											while(0);
											if( $_724 === TRUE ) { $_726 = TRUE; break; }
											$result = $res_699;
											$this->pos = $pos_699;
											$_726 = FALSE; break;
										}
										while(0);
										if( $_726 === TRUE ) { $_728 = TRUE; break; }
										$result = $res_697;
										$this->pos = $pos_697;
										$_728 = FALSE; break;
									}
									while(0);
									if( $_728 === TRUE ) { $_730 = TRUE; break; }
									$result = $res_695;
									$this->pos = $pos_695;
									$_730 = FALSE; break;
								}
								while(0);
								if( $_730 === TRUE ) { $_732 = TRUE; break; }
								$result = $res_693;
								$this->pos = $pos_693;
								$_732 = FALSE; break;
							}
							while(0);
							if( $_732 === TRUE ) { $_734 = TRUE; break; }
							$result = $res_691;
							$this->pos = $pos_691;
							$_734 = FALSE; break;
						}
						while(0);
						if( $_734 === TRUE ) { $_736 = TRUE; break; }
						$result = $res_689;
						$this->pos = $pos_689;
						$_736 = FALSE; break;
					}
					while(0);
					if( $_736 === TRUE ) { $_738 = TRUE; break; }
					$result = $res_687;
					$this->pos = $pos_687;
					$_738 = FALSE; break;
				}
				while(0);
				if( $_738 === FALSE) { $_740 = FALSE; break; }
				$_740 = TRUE; break;
			}
			while(0);
			if( $_740 === FALSE) {
				$result = $res_741;
				$this->pos = $pos_741;
				unset( $res_741 );
				unset( $pos_741 );
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
			$res_780 = $result;
			$pos_780 = $this->pos;
			$_779 = NULL;
			do {
				$_777 = NULL;
				do {
					$res_742 = $result;
					$pos_742 = $this->pos;
					if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_777 = TRUE; break;
					}
					$result = $res_742;
					$this->pos = $pos_742;
					$_775 = NULL;
					do {
						$res_744 = $result;
						$pos_744 = $this->pos;
						if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_775 = TRUE; break;
						}
						$result = $res_744;
						$this->pos = $pos_744;
						$_773 = NULL;
						do {
							$res_746 = $result;
							$pos_746 = $this->pos;
							$_749 = NULL;
							do {
								if (substr($this->string,$this->pos,1) == '<') {
									$this->pos += 1;
									$result["text"] .= '<';
								}
								else { $_749 = FALSE; break; }
								$res_748 = $result;
								$pos_748 = $this->pos;
								if (substr($this->string,$this->pos,1) == '%') {
									$this->pos += 1;
									$result["text"] .= '%';
									$result = $res_748;
									$this->pos = $pos_748;
									$_749 = FALSE; break;
								}
								else {
									$result = $res_748;
									$this->pos = $pos_748;
								}
								$_749 = TRUE; break;
							}
							while(0);
							if( $_749 === TRUE ) { $_773 = TRUE; break; }
							$result = $res_746;
							$this->pos = $pos_746;
							$_771 = NULL;
							do {
								$res_751 = $result;
								$pos_751 = $this->pos;
								$_756 = NULL;
								do {
									if (substr($this->string,$this->pos,1) == '$') {
										$this->pos += 1;
										$result["text"] .= '$';
									}
									else { $_756 = FALSE; break; }
									$res_755 = $result;
									$pos_755 = $this->pos;
									$_754 = NULL;
									do {
										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_754 = FALSE; break; }
										$_754 = TRUE; break;
									}
									while(0);
									if( $_754 === TRUE ) {
										$result = $res_755;
										$this->pos = $pos_755;
										$_756 = FALSE; break;
									}
									if( $_754 === FALSE) {
										$result = $res_755;
										$this->pos = $pos_755;
									}
									$_756 = TRUE; break;
								}
								while(0);
								if( $_756 === TRUE ) { $_771 = TRUE; break; }
								$result = $res_751;
								$this->pos = $pos_751;
								$_769 = NULL;
								do {
									$res_758 = $result;
									$pos_758 = $this->pos;
									$_761 = NULL;
									do {
										if (substr($this->string,$this->pos,1) == '{') {
											$this->pos += 1;
											$result["text"] .= '{';
										}
										else { $_761 = FALSE; break; }
										$res_760 = $result;
										$pos_760 = $this->pos;
										if (substr($this->string,$this->pos,1) == '$') {
											$this->pos += 1;
											$result["text"] .= '$';
											$result = $res_760;
											$this->pos = $pos_760;
											$_761 = FALSE; break;
										}
										else {
											$result = $res_760;
											$this->pos = $pos_760;
										}
										$_761 = TRUE; break;
									}
									while(0);
									if( $_761 === TRUE ) { $_769 = TRUE; break; }
									$result = $res_758;
									$this->pos = $pos_758;
									$_767 = NULL;
									do {
										if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_767 = FALSE; break; }
										$res_766 = $result;
										$pos_766 = $this->pos;
										$_765 = NULL;
										do {
											if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
											else { $_765 = FALSE; break; }
											$_765 = TRUE; break;
										}
										while(0);
										if( $_765 === TRUE ) {
											$result = $res_766;
											$this->pos = $pos_766;
											$_767 = FALSE; break;
										}
										if( $_765 === FALSE) {
											$result = $res_766;
											$this->pos = $pos_766;
										}
										$_767 = TRUE; break;
									}
									while(0);
									if( $_767 === TRUE ) { $_769 = TRUE; break; }
									$result = $res_758;
									$this->pos = $pos_758;
									$_769 = FALSE; break;
								}
								while(0);
								if( $_769 === TRUE ) { $_771 = TRUE; break; }
								$result = $res_751;
								$this->pos = $pos_751;
								$_771 = FALSE; break;
							}
							while(0);
							if( $_771 === TRUE ) { $_773 = TRUE; break; }
							$result = $res_746;
							$this->pos = $pos_746;
							$_773 = FALSE; break;
						}
						while(0);
						if( $_773 === TRUE ) { $_775 = TRUE; break; }
						$result = $res_744;
						$this->pos = $pos_744;
						$_775 = FALSE; break;
					}
					while(0);
					if( $_775 === TRUE ) { $_777 = TRUE; break; }
					$result = $res_742;
					$this->pos = $pos_742;
					$_777 = FALSE; break;
				}
				while(0);
				if( $_777 === FALSE) { $_779 = FALSE; break; }
				$_779 = TRUE; break;
			}
			while(0);
			if( $_779 === FALSE) {
				$result = $res_780;
				$this->pos = $pos_780;
				unset( $res_780 );
				unset( $pos_780 );
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

		// TODO: This is pretty ugly & gets applied on all files not just html. I wonder if we can make this
		// non-dynamically calculated
		$text = preg_replace(
			'/href\s*\=\s*\"\#/', 
			'href="\' . (SSViewer::$options[\'rewriteHashlinks\'] ? strip_tags( $_SERVER[\'REQUEST_URI\'] ) : "") . 
				\'#',
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
	 * @param  $string The source of the template
	 * @param string $templateName The name of the template, normally the filename the template source was loaded from
	 * @param bool $includeDebuggingComments True is debugging comments should be included in the output
	 * @return mixed|string The php that, when executed (via include or exec) will behave as per the template source
	 */
	static function compileString($string, $templateName = "", $includeDebuggingComments=false) {
		if (!trim($string)) {
			$code = '';
		}
		else {
			// Construct a parser instance
			$parser = new SSTemplateParser($string);
			$parser->includeDebuggingComments = $includeDebuggingComments;
	
			// Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF
			// (and other encodings) properly
			if(substr($string, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $parser->pos = 3;
			
			// Match the source against the parser
			$result =  $parser->match_TopTemplate();
			if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $parser);
	
			// Get the result
			$code = $result['php'];
		}
		
		// Include top level debugging comments if desired
		if($includeDebuggingComments && $templateName && stripos($code, "<?xml") === false) {
			// If this template is a full HTML page, then put the comments just inside the HTML tag to prevent any IE 
			// glitches
			if(stripos($code, "<html") !== false) {
				$code = preg_replace('/(<html[^>]*>)/i', "\\1<!-- template $templateName -->", $code);
				$code = preg_replace('/(<\/html[^>]*>)/i', "<!-- end template $templateName -->\\1", $code);
			} else {
				$code = str_replace('<?php' . PHP_EOL, '<?php' . PHP_EOL . '$val .= \'<!-- template ' . $templateName .
					' -->\';' . "\n", $code);
				$code .= "\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
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
