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
 *
 * @package framework
 * @subpackage view
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
  * 
  * @package framework
  * @subpackage view
  */
class SSTemplateParser extends Parser implements TemplateParser {

	/**
	 * @var bool - Set true by SSTemplateParser::compileString if the template should include comments intended
	 * for debugging (template source, included files, etc)
	 */
	protected $includeDebuggingComments = false;

	/**
	 * Stores the user-supplied closed block extension rules in the form:
	 * array(
	 *   'name' => function (&$res) {}
	 * )
	 * See SSTemplateParser::ClosedBlock_Handle_Loop for an example of what the callable should look like
	 * @var array
	 */
	protected $closedBlocks = array();

	/**
	 * Stores the user-supplied open block extension rules in the form:
	 * array(
	 *   'name' => function (&$res) {}
	 * )
	 * See SSTemplateParser::OpenBlock_Handle_Base_tag for an example of what the callable should look like
	 * @var array
	 */
	protected $openBlocks = array();

	/**
	 * Allow the injection of new closed & open block callables
	 * @param array $closedBlocks
	 * @param array $openBlocks
	 */
	public function __construct($closedBlocks = array(), $openBlocks = array()) {
		$this->setClosedBlocks($closedBlocks);
		$this->setOpenBlocks($openBlocks);
	}

	/**
	 * Override the function that constructs the result arrays to also prepare a 'php' item in the array
	 */
	function construct($matchrule, $name, $arguments = null) {
		$res = parent::construct($matchrule, $name, $arguments);
		if (!isset($res['php'])) $res['php'] = '';
		return $res;
	}

	/**
	 * Set the closed blocks that the template parser should use
	 * 
	 * This method will delete any existing closed blocks, please use addClosedBlock if you don't
	 * want to overwrite
	 * @param array $closedBlocks
	 * @throws InvalidArgumentException
	 */
	public function setClosedBlocks($closedBlocks) {
		$this->closedBlocks = array();
		foreach ((array) $closedBlocks as $name => $callable) {
			$this->addClosedBlock($name, $callable);
		}
	}

	/**
	 * Set the open blocks that the template parser should use
	 *
	 * This method will delete any existing open blocks, please use addOpenBlock if you don't
	 * want to overwrite
	 * @param array $openBlocks
	 * @throws InvalidArgumentException
	 */
	public function setOpenBlocks($openBlocks) {
		$this->openBlocks = array();
		foreach ((array) $openBlocks as $name => $callable) {
			$this->addOpenBlock($name, $callable);
		}
	}

	/**
	 * Add a closed block callable to allow <% name %><% end_name %> syntax
	 * @param string $name The name of the token to be used in the syntax <% name %><% end_name %>
	 * @param callable $callable The function that modifies the generation of template code
	 * @throws InvalidArgumentException
	 */
	public function addClosedBlock($name, $callable) {
		$this->validateExtensionBlock($name, $callable, 'Closed block');
		$this->closedBlocks[$name] = $callable;
	}

	/**
	 * Add a closed block callable to allow <% name %> syntax
	 * @param string $name The name of the token to be used in the syntax <% name %>
	 * @param callable $callable The function that modifies the generation of template code
	 * @throws InvalidArgumentException
	 */
	public function addOpenBlock($name, $callable) {
		$this->validateExtensionBlock($name, $callable, 'Open block');
		$this->openBlocks[$name] = $callable;
	}

	/**
	 * Ensures that the arguments to addOpenBlock and addClosedBlock are valid
	 * @param $name
	 * @param $callable
	 * @param $type
	 * @throws InvalidArgumentException
	 */
	protected function validateExtensionBlock($name, $callable, $type) {
		if (!is_string($name)) {
			throw new InvalidArgumentException(
				sprintf(
					"Name argument for %s must be a string",
					$type
				)
			);
		} elseif (!is_callable($callable)) {
			throw new InvalidArgumentException(
				sprintf(
					"Callable %s argument named '%s' is not callable",
					$type,
					$name
				)
			);
		}
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

	/* Call: Method:Word ( "(" < :CallArguments? > ")" )? */
	protected $match_Call_typestack = array('Call');
	function match_Call ($stack = array()) {
		$matchrule = "Call"; $result = $this->construct($matchrule, $matchrule, null);
		$_71 = NULL;
		do {
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Method" );
			}
			else { $_71 = FALSE; break; }
			$res_70 = $result;
			$pos_70 = $this->pos;
			$_69 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_69 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$res_66 = $result;
				$pos_66 = $this->pos;
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else {
					$result = $res_66;
					$this->pos = $pos_66;
					unset( $res_66 );
					unset( $pos_66 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_69 = FALSE; break; }
				$_69 = TRUE; break;
			}
			while(0);
			if( $_69 === FALSE) {
				$result = $res_70;
				$this->pos = $pos_70;
				unset( $res_70 );
				unset( $pos_70 );
			}
			$_71 = TRUE; break;
		}
		while(0);
		if( $_71 === TRUE ) { return $this->finalise($result); }
		if( $_71 === FALSE) { return FALSE; }
	}


	/* LookupStep: :Call &"." */
	protected $match_LookupStep_typestack = array('LookupStep');
	function match_LookupStep ($stack = array()) {
		$matchrule = "LookupStep"; $result = $this->construct($matchrule, $matchrule, null);
		$_75 = NULL;
		do {
			$matcher = 'match_'.'Call'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Call" );
			}
			else { $_75 = FALSE; break; }
			$res_74 = $result;
			$pos_74 = $this->pos;
			if (substr($this->string,$this->pos,1) == '.') {
				$this->pos += 1;
				$result["text"] .= '.';
				$result = $res_74;
				$this->pos = $pos_74;
			}
			else {
				$result = $res_74;
				$this->pos = $pos_74;
				$_75 = FALSE; break;
			}
			$_75 = TRUE; break;
		}
		while(0);
		if( $_75 === TRUE ) { return $this->finalise($result); }
		if( $_75 === FALSE) { return FALSE; }
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
		$_89 = NULL;
		do {
			$res_78 = $result;
			$pos_78 = $this->pos;
			$_86 = NULL;
			do {
				$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_86 = FALSE; break; }
				while (true) {
					$res_83 = $result;
					$pos_83 = $this->pos;
					$_82 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == '.') {
							$this->pos += 1;
							$result["text"] .= '.';
						}
						else { $_82 = FALSE; break; }
						$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_82 = FALSE; break; }
						$_82 = TRUE; break;
					}
					while(0);
					if( $_82 === FALSE) {
						$result = $res_83;
						$this->pos = $pos_83;
						unset( $res_83 );
						unset( $pos_83 );
						break;
					}
				}
				if (substr($this->string,$this->pos,1) == '.') {
					$this->pos += 1;
					$result["text"] .= '.';
				}
				else { $_86 = FALSE; break; }
				$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_86 = FALSE; break; }
				$_86 = TRUE; break;
			}
			while(0);
			if( $_86 === TRUE ) { $_89 = TRUE; break; }
			$result = $res_78;
			$this->pos = $pos_78;
			$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_89 = TRUE; break;
			}
			$result = $res_78;
			$this->pos = $pos_78;
			$_89 = FALSE; break;
		}
		while(0);
		if( $_89 === TRUE ) { return $this->finalise($result); }
		if( $_89 === FALSE) { return FALSE; }
	}



	
	function Lookup__construct(&$res) {
		$res['php'] = '$scope->locally()';
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
		$_115 = NULL;
		do {
			if (( $subres = $this->literal( '<%t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_115 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Entity'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_115 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_97 = $result;
			$pos_97 = $this->pos;
			$_96 = NULL;
			do {
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Default" );
				}
				else { $_96 = FALSE; break; }
				$_96 = TRUE; break;
			}
			while(0);
			if( $_96 === FALSE) {
				$result = $res_97;
				$this->pos = $pos_97;
				unset( $res_97 );
				unset( $pos_97 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_108 = $result;
			$pos_108 = $this->pos;
			$_107 = NULL;
			do {
				$res_102 = $result;
				$pos_102 = $this->pos;
				$_101 = NULL;
				do {
					if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_101 = FALSE; break; }
					if (substr($this->string,$this->pos,1) == '=') {
						$this->pos += 1;
						$result["text"] .= '=';
					}
					else { $_101 = FALSE; break; }
					$_101 = TRUE; break;
				}
				while(0);
				if( $_101 === TRUE ) {
					$result = $res_102;
					$this->pos = $pos_102;
					$_107 = FALSE; break;
				}
				if( $_101 === FALSE) {
					$result = $res_102;
					$this->pos = $pos_102;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_107 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Context" );
				}
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
			$res_112 = $result;
			$pos_112 = $this->pos;
			$_111 = NULL;
			do {
				$matcher = 'match_'.'InjectionVariables'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_111 = FALSE; break; }
				$_111 = TRUE; break;
			}
			while(0);
			if( $_111 === FALSE) {
				$result = $res_112;
				$this->pos = $pos_112;
				unset( $res_112 );
				unset( $pos_112 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_115 = FALSE; break; }
			$_115 = TRUE; break;
		}
		while(0);
		if( $_115 === TRUE ) { return $this->finalise($result); }
		if( $_115 === FALSE) { return FALSE; }
	}


	/* InjectionVariables: (< InjectionName:Word "=" Argument)+ */
	protected $match_InjectionVariables_typestack = array('InjectionVariables');
	function match_InjectionVariables ($stack = array()) {
		$matchrule = "InjectionVariables"; $result = $this->construct($matchrule, $matchrule, null);
		$count = 0;
		while (true) {
			$res_122 = $result;
			$pos_122 = $this->pos;
			$_121 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "InjectionName" );
				}
				else { $_121 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '=') {
					$this->pos += 1;
					$result["text"] .= '=';
				}
				else { $_121 = FALSE; break; }
				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_121 = FALSE; break; }
				$_121 = TRUE; break;
			}
			while(0);
			if( $_121 === FALSE) {
				$result = $res_122;
				$this->pos = $pos_122;
				unset( $res_122 );
				unset( $pos_122 );
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
		$_126 = NULL;
		do {
			if (substr($this->string,$this->pos,1) == '$') {
				$this->pos += 1;
				$result["text"] .= '$';
			}
			else { $_126 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_126 = FALSE; break; }
			$_126 = TRUE; break;
		}
		while(0);
		if( $_126 === TRUE ) { return $this->finalise($result); }
		if( $_126 === FALSE) { return FALSE; }
	}


	/* BracketInjection: '{$' :Lookup "}" */
	protected $match_BracketInjection_typestack = array('BracketInjection');
	function match_BracketInjection ($stack = array()) {
		$matchrule = "BracketInjection"; $result = $this->construct($matchrule, $matchrule, null);
		$_131 = NULL;
		do {
			if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_131 = FALSE; break; }
			$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Lookup" );
			}
			else { $_131 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '}') {
				$this->pos += 1;
				$result["text"] .= '}';
			}
			else { $_131 = FALSE; break; }
			$_131 = TRUE; break;
		}
		while(0);
		if( $_131 === TRUE ) { return $this->finalise($result); }
		if( $_131 === FALSE) { return FALSE; }
	}


	/* Injection: BracketInjection | SimpleInjection */
	protected $match_Injection_typestack = array('Injection');
	function match_Injection ($stack = array()) {
		$matchrule = "Injection"; $result = $this->construct($matchrule, $matchrule, null);
		$_136 = NULL;
		do {
			$res_133 = $result;
			$pos_133 = $this->pos;
			$matcher = 'match_'.'BracketInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_136 = TRUE; break;
			}
			$result = $res_133;
			$this->pos = $pos_133;
			$matcher = 'match_'.'SimpleInjection'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_136 = TRUE; break;
			}
			$result = $res_133;
			$this->pos = $pos_133;
			$_136 = FALSE; break;
		}
		while(0);
		if( $_136 === TRUE ) { return $this->finalise($result); }
		if( $_136 === FALSE) { return FALSE; }
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
		$_142 = NULL;
		do {
			$stack[] = $result; $result = $this->construct( $matchrule, "q" ); 
			if (( $subres = $this->rx( '/[\'"]/' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'q' );
			}
			else {
				$result = array_pop($stack);
				$_142 = FALSE; break;
			}
			$stack[] = $result; $result = $this->construct( $matchrule, "String" ); 
			if (( $subres = $this->rx( '/ (\\\\\\\\ | \\\\. | [^'.$this->expression($result, $stack, 'q').'\\\\])* /' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'String' );
			}
			else {
				$result = array_pop($stack);
				$_142 = FALSE; break;
			}
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'q').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_142 = FALSE; break; }
			$_142 = TRUE; break;
		}
		while(0);
		if( $_142 === TRUE ) { return $this->finalise($result); }
		if( $_142 === FALSE) { return FALSE; }
	}


	/* FreeString: /[^,)%!=><|&]+/ */
	protected $match_FreeString_typestack = array('FreeString');
	function match_FreeString ($stack = array()) {
		$matchrule = "FreeString"; $result = $this->construct($matchrule, $matchrule, null);
		if (( $subres = $this->rx( '/[^,)%!=><|&]+/' ) ) !== FALSE) {
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
		$_162 = NULL;
		do {
			$res_145 = $result;
			$pos_145 = $this->pos;
			$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "DollarMarkedLookup" );
				$_162 = TRUE; break;
			}
			$result = $res_145;
			$this->pos = $pos_145;
			$_160 = NULL;
			do {
				$res_147 = $result;
				$pos_147 = $this->pos;
				$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "QuotedString" );
					$_160 = TRUE; break;
				}
				$result = $res_147;
				$this->pos = $pos_147;
				$_158 = NULL;
				do {
					$res_149 = $result;
					$pos_149 = $this->pos;
					$_155 = NULL;
					do {
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
						}
						else { $_155 = FALSE; break; }
						$res_154 = $result;
						$pos_154 = $this->pos;
						$_153 = NULL;
						do {
							if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
							$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
							}
							else { $_153 = FALSE; break; }
							$_153 = TRUE; break;
						}
						while(0);
						if( $_153 === TRUE ) {
							$result = $res_154;
							$this->pos = $pos_154;
							$_155 = FALSE; break;
						}
						if( $_153 === FALSE) {
							$result = $res_154;
							$this->pos = $pos_154;
						}
						$_155 = TRUE; break;
					}
					while(0);
					if( $_155 === TRUE ) { $_158 = TRUE; break; }
					$result = $res_149;
					$this->pos = $pos_149;
					$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "FreeString" );
						$_158 = TRUE; break;
					}
					$result = $res_149;
					$this->pos = $pos_149;
					$_158 = FALSE; break;
				}
				while(0);
				if( $_158 === TRUE ) { $_160 = TRUE; break; }
				$result = $res_147;
				$this->pos = $pos_147;
				$_160 = FALSE; break;
			}
			while(0);
			if( $_160 === TRUE ) { $_162 = TRUE; break; }
			$result = $res_145;
			$this->pos = $pos_145;
			$_162 = FALSE; break;
		}
		while(0);
		if( $_162 === TRUE ) { return $this->finalise($result); }
		if( $_162 === FALSE) { return FALSE; }
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
	
	/* ComparisonOperator: "!=" | "==" | ">=" | ">" | "<=" | "<" | "=" */
	protected $match_ComparisonOperator_typestack = array('ComparisonOperator');
	function match_ComparisonOperator ($stack = array()) {
		$matchrule = "ComparisonOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_187 = NULL;
		do {
			$res_164 = $result;
			$pos_164 = $this->pos;
			if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_187 = TRUE; break;
			}
			$result = $res_164;
			$this->pos = $pos_164;
			$_185 = NULL;
			do {
				$res_166 = $result;
				$pos_166 = $this->pos;
				if (( $subres = $this->literal( '==' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_185 = TRUE; break;
				}
				$result = $res_166;
				$this->pos = $pos_166;
				$_183 = NULL;
				do {
					$res_168 = $result;
					$pos_168 = $this->pos;
					if (( $subres = $this->literal( '>=' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_183 = TRUE; break;
					}
					$result = $res_168;
					$this->pos = $pos_168;
					$_181 = NULL;
					do {
						$res_170 = $result;
						$pos_170 = $this->pos;
						if (substr($this->string,$this->pos,1) == '>') {
							$this->pos += 1;
							$result["text"] .= '>';
							$_181 = TRUE; break;
						}
						$result = $res_170;
						$this->pos = $pos_170;
						$_179 = NULL;
						do {
							$res_172 = $result;
							$pos_172 = $this->pos;
							if (( $subres = $this->literal( '<=' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_179 = TRUE; break;
							}
							$result = $res_172;
							$this->pos = $pos_172;
							$_177 = NULL;
							do {
								$res_174 = $result;
								$pos_174 = $this->pos;
								if (substr($this->string,$this->pos,1) == '<') {
									$this->pos += 1;
									$result["text"] .= '<';
									$_177 = TRUE; break;
								}
								$result = $res_174;
								$this->pos = $pos_174;
								if (substr($this->string,$this->pos,1) == '=') {
									$this->pos += 1;
									$result["text"] .= '=';
									$_177 = TRUE; break;
								}
								$result = $res_174;
								$this->pos = $pos_174;
								$_177 = FALSE; break;
							}
							while(0);
							if( $_177 === TRUE ) { $_179 = TRUE; break; }
							$result = $res_172;
							$this->pos = $pos_172;
							$_179 = FALSE; break;
						}
						while(0);
						if( $_179 === TRUE ) { $_181 = TRUE; break; }
						$result = $res_170;
						$this->pos = $pos_170;
						$_181 = FALSE; break;
					}
					while(0);
					if( $_181 === TRUE ) { $_183 = TRUE; break; }
					$result = $res_168;
					$this->pos = $pos_168;
					$_183 = FALSE; break;
				}
				while(0);
				if( $_183 === TRUE ) { $_185 = TRUE; break; }
				$result = $res_166;
				$this->pos = $pos_166;
				$_185 = FALSE; break;
			}
			while(0);
			if( $_185 === TRUE ) { $_187 = TRUE; break; }
			$result = $res_164;
			$this->pos = $pos_164;
			$_187 = FALSE; break;
		}
		while(0);
		if( $_187 === TRUE ) { return $this->finalise($result); }
		if( $_187 === FALSE) { return FALSE; }
	}


	/* Comparison: Argument < ComparisonOperator > Argument */
	protected $match_Comparison_typestack = array('Comparison');
	function match_Comparison ($stack = array()) {
		$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
		$_194 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_194 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_194 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
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
		$_201 = NULL;
		do {
			$res_199 = $result;
			$pos_199 = $this->pos;
			$_198 = NULL;
			do {
				$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
				if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Not' );
				}
				else {
					$result = array_pop($stack);
					$_198 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_198 = TRUE; break;
			}
			while(0);
			if( $_198 === FALSE) {
				$result = $res_199;
				$this->pos = $pos_199;
				unset( $res_199 );
				unset( $pos_199 );
			}
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_201 = FALSE; break; }
			$_201 = TRUE; break;
		}
		while(0);
		if( $_201 === TRUE ) { return $this->finalise($result); }
		if( $_201 === FALSE) { return FALSE; }
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
		$_206 = NULL;
		do {
			$res_203 = $result;
			$pos_203 = $this->pos;
			$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_206 = TRUE; break;
			}
			$result = $res_203;
			$this->pos = $pos_203;
			$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_206 = TRUE; break;
			}
			$result = $res_203;
			$this->pos = $pos_203;
			$_206 = FALSE; break;
		}
		while(0);
		if( $_206 === TRUE ) { return $this->finalise($result); }
		if( $_206 === FALSE) { return FALSE; }
	}



	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* BooleanOperator: "||" | "&&" */
	protected $match_BooleanOperator_typestack = array('BooleanOperator');
	function match_BooleanOperator ($stack = array()) {
		$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
		$_211 = NULL;
		do {
			$res_208 = $result;
			$pos_208 = $this->pos;
			if (( $subres = $this->literal( '||' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_211 = TRUE; break;
			}
			$result = $res_208;
			$this->pos = $pos_208;
			if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_211 = TRUE; break;
			}
			$result = $res_208;
			$this->pos = $pos_208;
			$_211 = FALSE; break;
		}
		while(0);
		if( $_211 === TRUE ) { return $this->finalise($result); }
		if( $_211 === FALSE) { return FALSE; }
	}


	/* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
	protected $match_IfArgument_typestack = array('IfArgument');
	function match_IfArgument ($stack = array()) {
		$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_220 = NULL;
		do {
			$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgumentPortion" );
			}
			else { $_220 = FALSE; break; }
			while (true) {
				$res_219 = $result;
				$pos_219 = $this->pos;
				$_218 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BooleanOperator" );
					}
					else { $_218 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "IfArgumentPortion" );
					}
					else { $_218 = FALSE; break; }
					$_218 = TRUE; break;
				}
				while(0);
				if( $_218 === FALSE) {
					$result = $res_219;
					$this->pos = $pos_219;
					unset( $res_219 );
					unset( $pos_219 );
					break;
				}
			}
			$_220 = TRUE; break;
		}
		while(0);
		if( $_220 === TRUE ) { return $this->finalise($result); }
		if( $_220 === FALSE) { return FALSE; }
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
		$_230 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_230 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_230 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_230 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_230 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_230 = FALSE; break; }
			$res_229 = $result;
			$pos_229 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_229;
				$this->pos = $pos_229;
				unset( $res_229 );
				unset( $pos_229 );
			}
			$_230 = TRUE; break;
		}
		while(0);
		if( $_230 === TRUE ) { return $this->finalise($result); }
		if( $_230 === FALSE) { return FALSE; }
	}


	/* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
	protected $match_ElseIfPart_typestack = array('ElseIfPart');
	function match_ElseIfPart ($stack = array()) {
		$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
		$_240 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_240 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_240 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_240 = FALSE; break; }
			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IfArgument" );
			}
			else { $_240 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_240 = FALSE; break; }
			$res_239 = $result;
			$pos_239 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_239;
				$this->pos = $pos_239;
				unset( $res_239 );
				unset( $pos_239 );
			}
			$_240 = TRUE; break;
		}
		while(0);
		if( $_240 === TRUE ) { return $this->finalise($result); }
		if( $_240 === FALSE) { return FALSE; }
	}


	/* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher? */
	protected $match_ElsePart_typestack = array('ElsePart');
	function match_ElsePart ($stack = array()) {
		$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
		$_248 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_248 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_248 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_248 = FALSE; break; }
			$res_247 = $result;
			$pos_247 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_247;
				$this->pos = $pos_247;
				unset( $res_247 );
				unset( $pos_247 );
			}
			$_248 = TRUE; break;
		}
		while(0);
		if( $_248 === TRUE ) { return $this->finalise($result); }
		if( $_248 === FALSE) { return FALSE; }
	}


	/* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
	protected $match_If_typestack = array('If');
	function match_If ($stack = array()) {
		$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
		$_258 = NULL;
		do {
			$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_258 = FALSE; break; }
			while (true) {
				$res_251 = $result;
				$pos_251 = $this->pos;
				$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else {
					$result = $res_251;
					$this->pos = $pos_251;
					unset( $res_251 );
					unset( $pos_251 );
					break;
				}
			}
			$res_252 = $result;
			$pos_252 = $this->pos;
			$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_252;
				$this->pos = $pos_252;
				unset( $res_252 );
				unset( $pos_252 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_258 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_258 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_258 = FALSE; break; }
			$_258 = TRUE; break;
		}
		while(0);
		if( $_258 === TRUE ) { return $this->finalise($result); }
		if( $_258 === FALSE) { return FALSE; }
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
				(isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
			'}';
	}

	function If_ElsePart(&$res, $sub) {
		$res['php'] .= 
			'else { ' . PHP_EOL . 
				(isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
			'}';
	}

	/* Require: '<%' < 'require' [ Call:(Method:Word "(" < :CallArguments  > ")") > '%>' */
	protected $match_Require_typestack = array('Require');
	function match_Require ($stack = array()) {
		$matchrule = "Require"; $result = $this->construct($matchrule, $matchrule, null);
		$_274 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_274 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_274 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_274 = FALSE; break; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
			$_270 = NULL;
			do {
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Method" );
				}
				else { $_270 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == '(') {
					$this->pos += 1;
					$result["text"] .= '(';
				}
				else { $_270 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "CallArguments" );
				}
				else { $_270 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ')') {
					$this->pos += 1;
					$result["text"] .= ')';
				}
				else { $_270 = FALSE; break; }
				$_270 = TRUE; break;
			}
			while(0);
			if( $_270 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Call' );
			}
			if( $_270 === FALSE) {
				$result = array_pop($stack);
				$_274 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_274 = FALSE; break; }
			$_274 = TRUE; break;
		}
		while(0);
		if( $_274 === TRUE ) { return $this->finalise($result); }
		if( $_274 === FALSE) { return FALSE; }
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
		$_294 = NULL;
		do {
			$res_282 = $result;
			$pos_282 = $this->pos;
			$_281 = NULL;
			do {
				$_279 = NULL;
				do {
					$res_276 = $result;
					$pos_276 = $this->pos;
					if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_279 = TRUE; break;
					}
					$result = $res_276;
					$this->pos = $pos_276;
					if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_279 = TRUE; break;
					}
					$result = $res_276;
					$this->pos = $pos_276;
					$_279 = FALSE; break;
				}
				while(0);
				if( $_279 === FALSE) { $_281 = FALSE; break; }
				$_281 = TRUE; break;
			}
			while(0);
			if( $_281 === TRUE ) {
				$result = $res_282;
				$this->pos = $pos_282;
				$_294 = FALSE; break;
			}
			if( $_281 === FALSE) {
				$result = $res_282;
				$this->pos = $pos_282;
			}
			$_292 = NULL;
			do {
				$_290 = NULL;
				do {
					$res_283 = $result;
					$pos_283 = $this->pos;
					$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "DollarMarkedLookup" );
						$_290 = TRUE; break;
					}
					$result = $res_283;
					$this->pos = $pos_283;
					$_288 = NULL;
					do {
						$res_285 = $result;
						$pos_285 = $this->pos;
						$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "QuotedString" );
							$_288 = TRUE; break;
						}
						$result = $res_285;
						$this->pos = $pos_285;
						$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "Lookup" );
							$_288 = TRUE; break;
						}
						$result = $res_285;
						$this->pos = $pos_285;
						$_288 = FALSE; break;
					}
					while(0);
					if( $_288 === TRUE ) { $_290 = TRUE; break; }
					$result = $res_283;
					$this->pos = $pos_283;
					$_290 = FALSE; break;
				}
				while(0);
				if( $_290 === FALSE) { $_292 = FALSE; break; }
				$_292 = TRUE; break;
			}
			while(0);
			if( $_292 === FALSE) { $_294 = FALSE; break; }
			$_294 = TRUE; break;
		}
		while(0);
		if( $_294 === TRUE ) { return $this->finalise($result); }
		if( $_294 === FALSE) { return FALSE; }
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
		$_303 = NULL;
		do {
			$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_303 = FALSE; break; }
			while (true) {
				$res_302 = $result;
				$pos_302 = $this->pos;
				$_301 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_301 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_301 = FALSE; break; }
					$_301 = TRUE; break;
				}
				while(0);
				if( $_301 === FALSE) {
					$result = $res_302;
					$this->pos = $pos_302;
					unset( $res_302 );
					unset( $pos_302 );
					break;
				}
			}
			$_303 = TRUE; break;
		}
		while(0);
		if( $_303 === TRUE ) { return $this->finalise($result); }
		if( $_303 === FALSE) { return FALSE; }
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
			$res_347 = $result;
			$pos_347 = $this->pos;
			$_346 = NULL;
			do {
				$_344 = NULL;
				do {
					$res_305 = $result;
					$pos_305 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_344 = TRUE; break;
					}
					$result = $res_305;
					$this->pos = $pos_305;
					$_342 = NULL;
					do {
						$res_307 = $result;
						$pos_307 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_342 = TRUE; break;
						}
						$result = $res_307;
						$this->pos = $pos_307;
						$_340 = NULL;
						do {
							$res_309 = $result;
							$pos_309 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_340 = TRUE; break;
							}
							$result = $res_309;
							$this->pos = $pos_309;
							$_338 = NULL;
							do {
								$res_311 = $result;
								$pos_311 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_338 = TRUE; break;
								}
								$result = $res_311;
								$this->pos = $pos_311;
								$_336 = NULL;
								do {
									$res_313 = $result;
									$pos_313 = $this->pos;
									$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_336 = TRUE; break;
									}
									$result = $res_313;
									$this->pos = $pos_313;
									$_334 = NULL;
									do {
										$res_315 = $result;
										$pos_315 = $this->pos;
										$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_334 = TRUE; break;
										}
										$result = $res_315;
										$this->pos = $pos_315;
										$_332 = NULL;
										do {
											$res_317 = $result;
											$pos_317 = $this->pos;
											$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_332 = TRUE; break;
											}
											$result = $res_317;
											$this->pos = $pos_317;
											$_330 = NULL;
											do {
												$res_319 = $result;
												$pos_319 = $this->pos;
												$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_330 = TRUE; break;
												}
												$result = $res_319;
												$this->pos = $pos_319;
												$_328 = NULL;
												do {
													$res_321 = $result;
													$pos_321 = $this->pos;
													$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_328 = TRUE; break;
													}
													$result = $res_321;
													$this->pos = $pos_321;
													$_326 = NULL;
													do {
														$res_323 = $result;
														$pos_323 = $this->pos;
														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_326 = TRUE; break;
														}
														$result = $res_323;
														$this->pos = $pos_323;
														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_326 = TRUE; break;
														}
														$result = $res_323;
														$this->pos = $pos_323;
														$_326 = FALSE; break;
													}
													while(0);
													if( $_326 === TRUE ) { $_328 = TRUE; break; }
													$result = $res_321;
													$this->pos = $pos_321;
													$_328 = FALSE; break;
												}
												while(0);
												if( $_328 === TRUE ) { $_330 = TRUE; break; }
												$result = $res_319;
												$this->pos = $pos_319;
												$_330 = FALSE; break;
											}
											while(0);
											if( $_330 === TRUE ) { $_332 = TRUE; break; }
											$result = $res_317;
											$this->pos = $pos_317;
											$_332 = FALSE; break;
										}
										while(0);
										if( $_332 === TRUE ) { $_334 = TRUE; break; }
										$result = $res_315;
										$this->pos = $pos_315;
										$_334 = FALSE; break;
									}
									while(0);
									if( $_334 === TRUE ) { $_336 = TRUE; break; }
									$result = $res_313;
									$this->pos = $pos_313;
									$_336 = FALSE; break;
								}
								while(0);
								if( $_336 === TRUE ) { $_338 = TRUE; break; }
								$result = $res_311;
								$this->pos = $pos_311;
								$_338 = FALSE; break;
							}
							while(0);
							if( $_338 === TRUE ) { $_340 = TRUE; break; }
							$result = $res_309;
							$this->pos = $pos_309;
							$_340 = FALSE; break;
						}
						while(0);
						if( $_340 === TRUE ) { $_342 = TRUE; break; }
						$result = $res_307;
						$this->pos = $pos_307;
						$_342 = FALSE; break;
					}
					while(0);
					if( $_342 === TRUE ) { $_344 = TRUE; break; }
					$result = $res_305;
					$this->pos = $pos_305;
					$_344 = FALSE; break;
				}
				while(0);
				if( $_344 === FALSE) { $_346 = FALSE; break; }
				$_346 = TRUE; break;
			}
			while(0);
			if( $_346 === FALSE) {
				$result = $res_347;
				$this->pos = $pos_347;
				unset( $res_347 );
				unset( $pos_347 );
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
		$_384 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_384 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_384 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_352 = $result;
			$pos_352 = $this->pos;
			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else {
				$result = $res_352;
				$this->pos = $pos_352;
				unset( $res_352 );
				unset( $pos_352 );
			}
			$res_364 = $result;
			$pos_364 = $this->pos;
			$_363 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_359 = NULL;
				do {
					$_357 = NULL;
					do {
						$res_354 = $result;
						$pos_354 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_357 = TRUE; break;
						}
						$result = $res_354;
						$this->pos = $pos_354;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_357 = TRUE; break;
						}
						$result = $res_354;
						$this->pos = $pos_354;
						$_357 = FALSE; break;
					}
					while(0);
					if( $_357 === FALSE) { $_359 = FALSE; break; }
					$_359 = TRUE; break;
				}
				while(0);
				if( $_359 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_359 === FALSE) {
					$result = array_pop($stack);
					$_363 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_363 = FALSE; break; }
				$_363 = TRUE; break;
			}
			while(0);
			if( $_363 === FALSE) {
				$result = $res_364;
				$this->pos = $pos_364;
				unset( $res_364 );
				unset( $pos_364 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_384 = FALSE; break; }
			$res_367 = $result;
			$pos_367 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_367;
				$this->pos = $pos_367;
				unset( $res_367 );
				unset( $pos_367 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_384 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_384 = FALSE; break; }
			$_380 = NULL;
			do {
				$_378 = NULL;
				do {
					$res_371 = $result;
					$pos_371 = $this->pos;
					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_378 = TRUE; break;
					}
					$result = $res_371;
					$this->pos = $pos_371;
					$_376 = NULL;
					do {
						$res_373 = $result;
						$pos_373 = $this->pos;
						if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_376 = TRUE; break;
						}
						$result = $res_373;
						$this->pos = $pos_373;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_376 = TRUE; break;
						}
						$result = $res_373;
						$this->pos = $pos_373;
						$_376 = FALSE; break;
					}
					while(0);
					if( $_376 === TRUE ) { $_378 = TRUE; break; }
					$result = $res_371;
					$this->pos = $pos_371;
					$_378 = FALSE; break;
				}
				while(0);
				if( $_378 === FALSE) { $_380 = FALSE; break; }
				$_380 = TRUE; break;
			}
			while(0);
			if( $_380 === FALSE) { $_384 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_384 = FALSE; break; }
			$_384 = TRUE; break;
		}
		while(0);
		if( $_384 === TRUE ) { return $this->finalise($result); }
		if( $_384 === FALSE) { return FALSE; }
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
			$res_436 = $result;
			$pos_436 = $this->pos;
			$_435 = NULL;
			do {
				$_433 = NULL;
				do {
					$res_386 = $result;
					$pos_386 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_433 = TRUE; break;
					}
					$result = $res_386;
					$this->pos = $pos_386;
					$_431 = NULL;
					do {
						$res_388 = $result;
						$pos_388 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_431 = TRUE; break;
						}
						$result = $res_388;
						$this->pos = $pos_388;
						$_429 = NULL;
						do {
							$res_390 = $result;
							$pos_390 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_429 = TRUE; break;
							}
							$result = $res_390;
							$this->pos = $pos_390;
							$_427 = NULL;
							do {
								$res_392 = $result;
								$pos_392 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_427 = TRUE; break;
								}
								$result = $res_392;
								$this->pos = $pos_392;
								$_425 = NULL;
								do {
									$res_394 = $result;
									$pos_394 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_425 = TRUE; break;
									}
									$result = $res_394;
									$this->pos = $pos_394;
									$_423 = NULL;
									do {
										$res_396 = $result;
										$pos_396 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_423 = TRUE; break;
										}
										$result = $res_396;
										$this->pos = $pos_396;
										$_421 = NULL;
										do {
											$res_398 = $result;
											$pos_398 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_421 = TRUE; break;
											}
											$result = $res_398;
											$this->pos = $pos_398;
											$_419 = NULL;
											do {
												$res_400 = $result;
												$pos_400 = $this->pos;
												$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_419 = TRUE; break;
												}
												$result = $res_400;
												$this->pos = $pos_400;
												$_417 = NULL;
												do {
													$res_402 = $result;
													$pos_402 = $this->pos;
													$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_417 = TRUE; break;
													}
													$result = $res_402;
													$this->pos = $pos_402;
													$_415 = NULL;
													do {
														$res_404 = $result;
														$pos_404 = $this->pos;
														$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_415 = TRUE; break;
														}
														$result = $res_404;
														$this->pos = $pos_404;
														$_413 = NULL;
														do {
															$res_406 = $result;
															$pos_406 = $this->pos;
															$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_413 = TRUE; break;
															}
															$result = $res_406;
															$this->pos = $pos_406;
															$_411 = NULL;
															do {
																$res_408 = $result;
																$pos_408 = $this->pos;
																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_411 = TRUE; break;
																}
																$result = $res_408;
																$this->pos = $pos_408;
																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_411 = TRUE; break;
																}
																$result = $res_408;
																$this->pos = $pos_408;
																$_411 = FALSE; break;
															}
															while(0);
															if( $_411 === TRUE ) { $_413 = TRUE; break; }
															$result = $res_406;
															$this->pos = $pos_406;
															$_413 = FALSE; break;
														}
														while(0);
														if( $_413 === TRUE ) { $_415 = TRUE; break; }
														$result = $res_404;
														$this->pos = $pos_404;
														$_415 = FALSE; break;
													}
													while(0);
													if( $_415 === TRUE ) { $_417 = TRUE; break; }
													$result = $res_402;
													$this->pos = $pos_402;
													$_417 = FALSE; break;
												}
												while(0);
												if( $_417 === TRUE ) { $_419 = TRUE; break; }
												$result = $res_400;
												$this->pos = $pos_400;
												$_419 = FALSE; break;
											}
											while(0);
											if( $_419 === TRUE ) { $_421 = TRUE; break; }
											$result = $res_398;
											$this->pos = $pos_398;
											$_421 = FALSE; break;
										}
										while(0);
										if( $_421 === TRUE ) { $_423 = TRUE; break; }
										$result = $res_396;
										$this->pos = $pos_396;
										$_423 = FALSE; break;
									}
									while(0);
									if( $_423 === TRUE ) { $_425 = TRUE; break; }
									$result = $res_394;
									$this->pos = $pos_394;
									$_425 = FALSE; break;
								}
								while(0);
								if( $_425 === TRUE ) { $_427 = TRUE; break; }
								$result = $res_392;
								$this->pos = $pos_392;
								$_427 = FALSE; break;
							}
							while(0);
							if( $_427 === TRUE ) { $_429 = TRUE; break; }
							$result = $res_390;
							$this->pos = $pos_390;
							$_429 = FALSE; break;
						}
						while(0);
						if( $_429 === TRUE ) { $_431 = TRUE; break; }
						$result = $res_388;
						$this->pos = $pos_388;
						$_431 = FALSE; break;
					}
					while(0);
					if( $_431 === TRUE ) { $_433 = TRUE; break; }
					$result = $res_386;
					$this->pos = $pos_386;
					$_433 = FALSE; break;
				}
				while(0);
				if( $_433 === FALSE) { $_435 = FALSE; break; }
				$_435 = TRUE; break;
			}
			while(0);
			if( $_435 === FALSE) {
				$result = $res_436;
				$this->pos = $pos_436;
				unset( $res_436 );
				unset( $pos_436 );
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
		$_491 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_491 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
			$_444 = NULL;
			do {
				$_442 = NULL;
				do {
					$res_439 = $result;
					$pos_439 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_442 = TRUE; break;
					}
					$result = $res_439;
					$this->pos = $pos_439;
					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_442 = TRUE; break;
					}
					$result = $res_439;
					$this->pos = $pos_439;
					$_442 = FALSE; break;
				}
				while(0);
				if( $_442 === FALSE) { $_444 = FALSE; break; }
				$_444 = TRUE; break;
			}
			while(0);
			if( $_444 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'CacheTag' );
			}
			if( $_444 === FALSE) {
				$result = array_pop($stack);
				$_491 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_449 = $result;
			$pos_449 = $this->pos;
			$_448 = NULL;
			do {
				$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_448 = FALSE; break; }
				$_448 = TRUE; break;
			}
			while(0);
			if( $_448 === FALSE) {
				$result = $res_449;
				$this->pos = $pos_449;
				unset( $res_449 );
				unset( $pos_449 );
			}
			$res_461 = $result;
			$pos_461 = $this->pos;
			$_460 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
				$_456 = NULL;
				do {
					$_454 = NULL;
					do {
						$res_451 = $result;
						$pos_451 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_454 = TRUE; break;
						}
						$result = $res_451;
						$this->pos = $pos_451;
						if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_454 = TRUE; break;
						}
						$result = $res_451;
						$this->pos = $pos_451;
						$_454 = FALSE; break;
					}
					while(0);
					if( $_454 === FALSE) { $_456 = FALSE; break; }
					$_456 = TRUE; break;
				}
				while(0);
				if( $_456 === TRUE ) {
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'Conditional' );
				}
				if( $_456 === FALSE) {
					$result = array_pop($stack);
					$_460 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Condition" );
				}
				else { $_460 = FALSE; break; }
				$_460 = TRUE; break;
			}
			while(0);
			if( $_460 === FALSE) {
				$result = $res_461;
				$this->pos = $pos_461;
				unset( $res_461 );
				unset( $pos_461 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_491 = FALSE; break; }
			while (true) {
				$res_474 = $result;
				$pos_474 = $this->pos;
				$_473 = NULL;
				do {
					$_471 = NULL;
					do {
						$res_464 = $result;
						$pos_464 = $this->pos;
						$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_471 = TRUE; break;
						}
						$result = $res_464;
						$this->pos = $pos_464;
						$_469 = NULL;
						do {
							$res_466 = $result;
							$pos_466 = $this->pos;
							$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_469 = TRUE; break;
							}
							$result = $res_466;
							$this->pos = $pos_466;
							$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_469 = TRUE; break;
							}
							$result = $res_466;
							$this->pos = $pos_466;
							$_469 = FALSE; break;
						}
						while(0);
						if( $_469 === TRUE ) { $_471 = TRUE; break; }
						$result = $res_464;
						$this->pos = $pos_464;
						$_471 = FALSE; break;
					}
					while(0);
					if( $_471 === FALSE) { $_473 = FALSE; break; }
					$_473 = TRUE; break;
				}
				while(0);
				if( $_473 === FALSE) {
					$result = $res_474;
					$this->pos = $pos_474;
					unset( $res_474 );
					unset( $pos_474 );
					break;
				}
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_491 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_491 = FALSE; break; }
			$_487 = NULL;
			do {
				$_485 = NULL;
				do {
					$res_478 = $result;
					$pos_478 = $this->pos;
					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_485 = TRUE; break;
					}
					$result = $res_478;
					$this->pos = $pos_478;
					$_483 = NULL;
					do {
						$res_480 = $result;
						$pos_480 = $this->pos;
						if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_483 = TRUE; break;
						}
						$result = $res_480;
						$this->pos = $pos_480;
						if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_483 = TRUE; break;
						}
						$result = $res_480;
						$this->pos = $pos_480;
						$_483 = FALSE; break;
					}
					while(0);
					if( $_483 === TRUE ) { $_485 = TRUE; break; }
					$result = $res_478;
					$this->pos = $pos_478;
					$_485 = FALSE; break;
				}
				while(0);
				if( $_485 === FALSE) { $_487 = FALSE; break; }
				$_487 = TRUE; break;
			}
			while(0);
			if( $_487 === FALSE) { $_491 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_491 = FALSE; break; }
			$_491 = TRUE; break;
		}
		while(0);
		if( $_491 === TRUE ) { return $this->finalise($result); }
		if( $_491 === FALSE) { return FALSE; }
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
		$_510 = NULL;
		do {
			if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_510 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_510 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_510 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_510 = FALSE; break; }
			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_510 = FALSE; break; }
			$res_503 = $result;
			$pos_503 = $this->pos;
			$_502 = NULL;
			do {
				$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_502 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_502 = FALSE; break; }
				$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_502 = FALSE; break; }
				$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_502 = FALSE; break; }
				$_502 = TRUE; break;
			}
			while(0);
			if( $_502 === FALSE) {
				$result = $res_503;
				$this->pos = $pos_503;
				unset( $res_503 );
				unset( $pos_503 );
			}
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_510 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_510 = FALSE; break; }
			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_510 = FALSE; break; }
			$res_509 = $result;
			$pos_509 = $this->pos;
			$_508 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == ';') {
					$this->pos += 1;
					$result["text"] .= ';';
				}
				else { $_508 = FALSE; break; }
				$_508 = TRUE; break;
			}
			while(0);
			if( $_508 === FALSE) {
				$result = $res_509;
				$this->pos = $pos_509;
				unset( $res_509 );
				unset( $pos_509 );
			}
			$_510 = TRUE; break;
		}
		while(0);
		if( $_510 === TRUE ) { return $this->finalise($result); }
		if( $_510 === FALSE) { return FALSE; }
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
		$_518 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_518 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_518 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_518 = FALSE; break; }
			$_518 = TRUE; break;
		}
		while(0);
		if( $_518 === TRUE ) { return $this->finalise($result); }
		if( $_518 === FALSE) { return FALSE; }
	}



	function OldTTag_OldTPart(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>"  */
	protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
	function match_OldSprintfTag ($stack = array()) {
		$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_535 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '(') {
				$this->pos += 1;
				$result["text"] .= '(';
			}
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ',') {
				$this->pos += 1;
				$result["text"] .= ',';
			}
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ')') {
				$this->pos += 1;
				$result["text"] .= ')';
			}
			else { $_535 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_535 = FALSE; break; }
			$_535 = TRUE; break;
		}
		while(0);
		if( $_535 === TRUE ) { return $this->finalise($result); }
		if( $_535 === FALSE) { return FALSE; }
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
		$_540 = NULL;
		do {
			$res_537 = $result;
			$pos_537 = $this->pos;
			$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_540 = TRUE; break;
			}
			$result = $res_537;
			$this->pos = $pos_537;
			$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_540 = TRUE; break;
			}
			$result = $res_537;
			$this->pos = $pos_537;
			$_540 = FALSE; break;
		}
		while(0);
		if( $_540 === TRUE ) { return $this->finalise($result); }
		if( $_540 === FALSE) { return FALSE; }
	}



	function OldI18NTag_STR(&$res, $sub) {
		$res['php'] = '$val .= ' . $sub['php'] . ';';
	}

	/* NamedArgument: Name:Word "=" Value:Argument */
	protected $match_NamedArgument_typestack = array('NamedArgument');
	function match_NamedArgument ($stack = array()) {
		$matchrule = "NamedArgument"; $result = $this->construct($matchrule, $matchrule, null);
		$_545 = NULL;
		do {
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Name" );
			}
			else { $_545 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '=') {
				$this->pos += 1;
				$result["text"] .= '=';
			}
			else { $_545 = FALSE; break; }
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Value" );
			}
			else { $_545 = FALSE; break; }
			$_545 = TRUE; break;
		}
		while(0);
		if( $_545 === TRUE ) { return $this->finalise($result); }
		if( $_545 === FALSE) { return FALSE; }
	}



	function NamedArgument_Name(&$res, $sub) {
		$res['php'] = "'" . $sub['text'] . "' => ";
	}

	function NamedArgument_Value(&$res, $sub) {
		switch($sub['ArgumentMode']) {
			case 'string':
				$res['php'] .= $sub['php'];
				break;

			case 'default':
				$res['php'] .= $sub['string_php'];
				break;

			default:
				$res['php'] .= str_replace('$$FINAL', 'obj', $sub['php']) . '->self()';
				break;
		}
	}

	/* Include: "<%" < "include" < Template:Word < (NamedArgument ( < "," < NamedArgument )*)? > "%>" */
	protected $match_Include_typestack = array('Include');
	function match_Include ($stack = array()) {
		$matchrule = "Include"; $result = $this->construct($matchrule, $matchrule, null);
		$_564 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_564 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'include' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_564 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else { $_564 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_561 = $result;
			$pos_561 = $this->pos;
			$_560 = NULL;
			do {
				$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_560 = FALSE; break; }
				while (true) {
					$res_559 = $result;
					$pos_559 = $this->pos;
					$_558 = NULL;
					do {
						if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
						if (substr($this->string,$this->pos,1) == ',') {
							$this->pos += 1;
							$result["text"] .= ',';
						}
						else { $_558 = FALSE; break; }
						if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
						$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_558 = FALSE; break; }
						$_558 = TRUE; break;
					}
					while(0);
					if( $_558 === FALSE) {
						$result = $res_559;
						$this->pos = $pos_559;
						unset( $res_559 );
						unset( $pos_559 );
						break;
					}
				}
				$_560 = TRUE; break;
			}
			while(0);
			if( $_560 === FALSE) {
				$result = $res_561;
				$this->pos = $pos_561;
				unset( $res_561 );
				unset( $pos_561 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_564 = FALSE; break; }
			$_564 = TRUE; break;
		}
		while(0);
		if( $_564 === TRUE ) { return $this->finalise($result); }
		if( $_564 === FALSE) { return FALSE; }
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
			implode(',', $arguments)."), \$scope);\n";

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
		$_573 = NULL;
		do {
			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Argument" );
			}
			else { $_573 = FALSE; break; }
			while (true) {
				$res_572 = $result;
				$pos_572 = $this->pos;
				$_571 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					if (substr($this->string,$this->pos,1) == ',') {
						$this->pos += 1;
						$result["text"] .= ',';
					}
					else { $_571 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Argument" );
					}
					else { $_571 = FALSE; break; }
					$_571 = TRUE; break;
				}
				while(0);
				if( $_571 === FALSE) {
					$result = $res_572;
					$this->pos = $pos_572;
					unset( $res_572 );
					unset( $pos_572 );
					break;
				}
			}
			$_573 = TRUE; break;
		}
		while(0);
		if( $_573 === TRUE ) { return $this->finalise($result); }
		if( $_573 === FALSE) { return FALSE; }
	}


	/* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")]) */
	protected $match_NotBlockTag_typestack = array('NotBlockTag');
	function match_NotBlockTag ($stack = array()) {
		$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
		$_611 = NULL;
		do {
			$res_575 = $result;
			$pos_575 = $this->pos;
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_611 = TRUE; break;
			}
			$result = $res_575;
			$this->pos = $pos_575;
			$_609 = NULL;
			do {
				$_606 = NULL;
				do {
					$_604 = NULL;
					do {
						$res_577 = $result;
						$pos_577 = $this->pos;
						if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_604 = TRUE; break;
						}
						$result = $res_577;
						$this->pos = $pos_577;
						$_602 = NULL;
						do {
							$res_579 = $result;
							$pos_579 = $this->pos;
							if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_602 = TRUE; break;
							}
							$result = $res_579;
							$this->pos = $pos_579;
							$_600 = NULL;
							do {
								$res_581 = $result;
								$pos_581 = $this->pos;
								if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_600 = TRUE; break;
								}
								$result = $res_581;
								$this->pos = $pos_581;
								$_598 = NULL;
								do {
									$res_583 = $result;
									$pos_583 = $this->pos;
									if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_598 = TRUE; break;
									}
									$result = $res_583;
									$this->pos = $pos_583;
									$_596 = NULL;
									do {
										$res_585 = $result;
										$pos_585 = $this->pos;
										if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
											$result["text"] .= $subres;
											$_596 = TRUE; break;
										}
										$result = $res_585;
										$this->pos = $pos_585;
										$_594 = NULL;
										do {
											$res_587 = $result;
											$pos_587 = $this->pos;
											if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
												$result["text"] .= $subres;
												$_594 = TRUE; break;
											}
											$result = $res_587;
											$this->pos = $pos_587;
											$_592 = NULL;
											do {
												$res_589 = $result;
												$pos_589 = $this->pos;
												if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
													$result["text"] .= $subres;
													$_592 = TRUE; break;
												}
												$result = $res_589;
												$this->pos = $pos_589;
												if (( $subres = $this->literal( 'include' ) ) !== FALSE) {
													$result["text"] .= $subres;
													$_592 = TRUE; break;
												}
												$result = $res_589;
												$this->pos = $pos_589;
												$_592 = FALSE; break;
											}
											while(0);
											if( $_592 === TRUE ) { $_594 = TRUE; break; }
											$result = $res_587;
											$this->pos = $pos_587;
											$_594 = FALSE; break;
										}
										while(0);
										if( $_594 === TRUE ) { $_596 = TRUE; break; }
										$result = $res_585;
										$this->pos = $pos_585;
										$_596 = FALSE; break;
									}
									while(0);
									if( $_596 === TRUE ) { $_598 = TRUE; break; }
									$result = $res_583;
									$this->pos = $pos_583;
									$_598 = FALSE; break;
								}
								while(0);
								if( $_598 === TRUE ) { $_600 = TRUE; break; }
								$result = $res_581;
								$this->pos = $pos_581;
								$_600 = FALSE; break;
							}
							while(0);
							if( $_600 === TRUE ) { $_602 = TRUE; break; }
							$result = $res_579;
							$this->pos = $pos_579;
							$_602 = FALSE; break;
						}
						while(0);
						if( $_602 === TRUE ) { $_604 = TRUE; break; }
						$result = $res_577;
						$this->pos = $pos_577;
						$_604 = FALSE; break;
					}
					while(0);
					if( $_604 === FALSE) { $_606 = FALSE; break; }
					$_606 = TRUE; break;
				}
				while(0);
				if( $_606 === FALSE) { $_609 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_609 = FALSE; break; }
				$_609 = TRUE; break;
			}
			while(0);
			if( $_609 === TRUE ) { $_611 = TRUE; break; }
			$result = $res_575;
			$this->pos = $pos_575;
			$_611 = FALSE; break;
		}
		while(0);
		if( $_611 === TRUE ) { return $this->finalise($result); }
		if( $_611 === FALSE) { return FALSE; }
	}


	/* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher? 
	'<%' < 'end_' '$BlockName' > '%>' */
	protected $match_ClosedBlock_typestack = array('ClosedBlock');
	function match_ClosedBlock ($stack = array()) {
		$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
		$_631 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_631 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_615 = $result;
			$pos_615 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_615;
				$this->pos = $pos_615;
				$_631 = FALSE; break;
			}
			else {
				$result = $res_615;
				$this->pos = $pos_615;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_631 = FALSE; break; }
			$res_621 = $result;
			$pos_621 = $this->pos;
			$_620 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_620 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_620 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_620 = FALSE; break; }
				$_620 = TRUE; break;
			}
			while(0);
			if( $_620 === FALSE) {
				$result = $res_621;
				$this->pos = $pos_621;
				unset( $res_621 );
				unset( $pos_621 );
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
				$_631 = FALSE; break;
			}
			$res_624 = $result;
			$pos_624 = $this->pos;
			$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Template" );
			}
			else {
				$result = $res_624;
				$this->pos = $pos_624;
				unset( $res_624 );
				unset( $pos_624 );
			}
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_631 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_631 = FALSE; break; }
			if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_631 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_631 = FALSE; break; }
			$_631 = TRUE; break;
		}
		while(0);
		if( $_631 === TRUE ) { return $this->finalise($result); }
		if( $_631 === FALSE) { return FALSE; }
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
		if (method_exists($this, $method)) {
			$res['php'] = $this->$method($res);
		} else if (isset($this->closedBlocks[$blockname])) {
			$res['php'] = call_user_func($this->closedBlocks[$blockname], $res);
		} else {
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
		Deprecation::notice('3.1', '<% control %> is deprecated. Use <% with %> or <% loop %> instead.');
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
		$_644 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_644 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_635 = $result;
			$pos_635 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_635;
				$this->pos = $pos_635;
				$_644 = FALSE; break;
			}
			else {
				$result = $res_635;
				$this->pos = $pos_635;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "BlockName" );
			}
			else { $_644 = FALSE; break; }
			$res_641 = $result;
			$pos_641 = $this->pos;
			$_640 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_640 = FALSE; break; }
				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "BlockArguments" );
				}
				else { $_640 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_640 = FALSE; break; }
				$_640 = TRUE; break;
			}
			while(0);
			if( $_640 === FALSE) {
				$result = $res_641;
				$this->pos = $pos_641;
				unset( $res_641 );
				unset( $pos_641 );
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_644 = FALSE; break; }
			$_644 = TRUE; break;
		}
		while(0);
		if( $_644 === TRUE ) { return $this->finalise($result); }
		if( $_644 === FALSE) { return FALSE; }
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
		if (method_exists($this, $method)) {
			$res['php'] = $this->$method($res);
		} elseif (isset($this->openBlocks[$blockname])) {
			$res['php'] = call_user_func($this->openBlocks[$blockname], $res);
		} else {
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
		$_652 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_652 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_652 = FALSE; break; }
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Word" );
			}
			else { $_652 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_652 = FALSE; break; }
			$_652 = TRUE; break;
		}
		while(0);
		if( $_652 === TRUE ) { return $this->finalise($result); }
		if( $_652 === FALSE) { return FALSE; }
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
		$_667 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_667 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$res_656 = $result;
			$pos_656 = $this->pos;
			$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_656;
				$this->pos = $pos_656;
				$_667 = FALSE; break;
			}
			else {
				$result = $res_656;
				$this->pos = $pos_656;
			}
			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "Tag" );
			}
			else { $_667 = FALSE; break; }
			$res_666 = $result;
			$pos_666 = $this->pos;
			$_665 = NULL;
			do {
				$res_662 = $result;
				$pos_662 = $this->pos;
				$_661 = NULL;
				do {
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_661 = FALSE; break; }
					$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "BlockArguments" );
					}
					else { $_661 = FALSE; break; }
					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_661 = FALSE; break; }
					$_661 = TRUE; break;
				}
				while(0);
				if( $_661 === FALSE) {
					$result = $res_662;
					$this->pos = $pos_662;
					unset( $res_662 );
					unset( $pos_662 );
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_665 = FALSE; break; }
				$_665 = TRUE; break;
			}
			while(0);
			if( $_665 === TRUE ) {
				$result = $res_666;
				$this->pos = $pos_666;
				$_667 = FALSE; break;
			}
			if( $_665 === FALSE) {
				$result = $res_666;
				$this->pos = $pos_666;
			}
			$_667 = TRUE; break;
		}
		while(0);
		if( $_667 === TRUE ) { return $this->finalise($result); }
		if( $_667 === FALSE) { return FALSE; }
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
		$_679 = NULL;
		do {
			if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_679 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
			$_673 = NULL;
			do {
				if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_673 = FALSE; break; }
				$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Word" );
				}
				else { $_673 = FALSE; break; }
				$_673 = TRUE; break;
			}
			while(0);
			if( $_673 === TRUE ) {
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'Tag' );
			}
			if( $_673 === FALSE) {
				$result = array_pop($stack);
				$_679 = FALSE; break;
			}
			$res_678 = $result;
			$pos_678 = $this->pos;
			$_677 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_677 = FALSE; break; }
				$_677 = TRUE; break;
			}
			while(0);
			if( $_677 === TRUE ) {
				$result = $res_678;
				$this->pos = $pos_678;
				$_679 = FALSE; break;
			}
			if( $_677 === FALSE) {
				$result = $res_678;
				$this->pos = $pos_678;
			}
			$_679 = TRUE; break;
		}
		while(0);
		if( $_679 === TRUE ) { return $this->finalise($result); }
		if( $_679 === FALSE) { return FALSE; }
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
		$_684 = NULL;
		do {
			$res_681 = $result;
			$pos_681 = $this->pos;
			$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_684 = TRUE; break;
			}
			$result = $res_681;
			$this->pos = $pos_681;
			$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$_684 = TRUE; break;
			}
			$result = $res_681;
			$this->pos = $pos_681;
			$_684 = FALSE; break;
		}
		while(0);
		if( $_684 === TRUE ) { return $this->finalise($result); }
		if( $_684 === FALSE) { return FALSE; }
	}




	/* Comment: "<%--" (!"--%>" /(?s)./)+ "--%>" */
	protected $match_Comment_typestack = array('Comment');
	function match_Comment ($stack = array()) {
		$matchrule = "Comment"; $result = $this->construct($matchrule, $matchrule, null);
		$_692 = NULL;
		do {
			if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_692 = FALSE; break; }
			$count = 0;
			while (true) {
				$res_690 = $result;
				$pos_690 = $this->pos;
				$_689 = NULL;
				do {
					$res_687 = $result;
					$pos_687 = $this->pos;
					if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$result = $res_687;
						$this->pos = $pos_687;
						$_689 = FALSE; break;
					}
					else {
						$result = $res_687;
						$this->pos = $pos_687;
					}
					if (( $subres = $this->rx( '/(?s)./' ) ) !== FALSE) { $result["text"] .= $subres; }
					else { $_689 = FALSE; break; }
					$_689 = TRUE; break;
				}
				while(0);
				if( $_689 === FALSE) {
					$result = $res_690;
					$this->pos = $pos_690;
					unset( $res_690 );
					unset( $pos_690 );
					break;
				}
				$count += 1;
			}
			if ($count > 0) {  }
			else { $_692 = FALSE; break; }
			if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_692 = FALSE; break; }
			$_692 = TRUE; break;
		}
		while(0);
		if( $_692 === TRUE ) { return $this->finalise($result); }
		if( $_692 === FALSE) { return FALSE; }
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
			$res_748 = $result;
			$pos_748 = $this->pos;
			$_747 = NULL;
			do {
				$_745 = NULL;
				do {
					$res_694 = $result;
					$pos_694 = $this->pos;
					$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_745 = TRUE; break;
					}
					$result = $res_694;
					$this->pos = $pos_694;
					$_743 = NULL;
					do {
						$res_696 = $result;
						$pos_696 = $this->pos;
						$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
							$_743 = TRUE; break;
						}
						$result = $res_696;
						$this->pos = $pos_696;
						$_741 = NULL;
						do {
							$res_698 = $result;
							$pos_698 = $this->pos;
							$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres );
								$_741 = TRUE; break;
							}
							$result = $res_698;
							$this->pos = $pos_698;
							$_739 = NULL;
							do {
								$res_700 = $result;
								$pos_700 = $this->pos;
								$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_739 = TRUE; break;
								}
								$result = $res_700;
								$this->pos = $pos_700;
								$_737 = NULL;
								do {
									$res_702 = $result;
									$pos_702 = $this->pos;
									$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_737 = TRUE; break;
									}
									$result = $res_702;
									$this->pos = $pos_702;
									$_735 = NULL;
									do {
										$res_704 = $result;
										$pos_704 = $this->pos;
										$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_735 = TRUE; break;
										}
										$result = $res_704;
										$this->pos = $pos_704;
										$_733 = NULL;
										do {
											$res_706 = $result;
											$pos_706 = $this->pos;
											$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_733 = TRUE; break;
											}
											$result = $res_706;
											$this->pos = $pos_706;
											$_731 = NULL;
											do {
												$res_708 = $result;
												$pos_708 = $this->pos;
												$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_731 = TRUE; break;
												}
												$result = $res_708;
												$this->pos = $pos_708;
												$_729 = NULL;
												do {
													$res_710 = $result;
													$pos_710 = $this->pos;
													$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_729 = TRUE; break;
													}
													$result = $res_710;
													$this->pos = $pos_710;
													$_727 = NULL;
													do {
														$res_712 = $result;
														$pos_712 = $this->pos;
														$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_727 = TRUE; break;
														}
														$result = $res_712;
														$this->pos = $pos_712;
														$_725 = NULL;
														do {
															$res_714 = $result;
															$pos_714 = $this->pos;
															$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_725 = TRUE; break;
															}
															$result = $res_714;
															$this->pos = $pos_714;
															$_723 = NULL;
															do {
																$res_716 = $result;
																$pos_716 = $this->pos;
																$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_723 = TRUE; break;
																}
																$result = $res_716;
																$this->pos = $pos_716;
																$_721 = NULL;
																do {
																	$res_718 = $result;
																	$pos_718 = $this->pos;
																	$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_721 = TRUE; break;
																	}
																	$result = $res_718;
																	$this->pos = $pos_718;
																	$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_721 = TRUE; break;
																	}
																	$result = $res_718;
																	$this->pos = $pos_718;
																	$_721 = FALSE; break;
																}
																while(0);
																if( $_721 === TRUE ) { $_723 = TRUE; break; }
																$result = $res_716;
																$this->pos = $pos_716;
																$_723 = FALSE; break;
															}
															while(0);
															if( $_723 === TRUE ) { $_725 = TRUE; break; }
															$result = $res_714;
															$this->pos = $pos_714;
															$_725 = FALSE; break;
														}
														while(0);
														if( $_725 === TRUE ) { $_727 = TRUE; break; }
														$result = $res_712;
														$this->pos = $pos_712;
														$_727 = FALSE; break;
													}
													while(0);
													if( $_727 === TRUE ) { $_729 = TRUE; break; }
													$result = $res_710;
													$this->pos = $pos_710;
													$_729 = FALSE; break;
												}
												while(0);
												if( $_729 === TRUE ) { $_731 = TRUE; break; }
												$result = $res_708;
												$this->pos = $pos_708;
												$_731 = FALSE; break;
											}
											while(0);
											if( $_731 === TRUE ) { $_733 = TRUE; break; }
											$result = $res_706;
											$this->pos = $pos_706;
											$_733 = FALSE; break;
										}
										while(0);
										if( $_733 === TRUE ) { $_735 = TRUE; break; }
										$result = $res_704;
										$this->pos = $pos_704;
										$_735 = FALSE; break;
									}
									while(0);
									if( $_735 === TRUE ) { $_737 = TRUE; break; }
									$result = $res_702;
									$this->pos = $pos_702;
									$_737 = FALSE; break;
								}
								while(0);
								if( $_737 === TRUE ) { $_739 = TRUE; break; }
								$result = $res_700;
								$this->pos = $pos_700;
								$_739 = FALSE; break;
							}
							while(0);
							if( $_739 === TRUE ) { $_741 = TRUE; break; }
							$result = $res_698;
							$this->pos = $pos_698;
							$_741 = FALSE; break;
						}
						while(0);
						if( $_741 === TRUE ) { $_743 = TRUE; break; }
						$result = $res_696;
						$this->pos = $pos_696;
						$_743 = FALSE; break;
					}
					while(0);
					if( $_743 === TRUE ) { $_745 = TRUE; break; }
					$result = $res_694;
					$this->pos = $pos_694;
					$_745 = FALSE; break;
				}
				while(0);
				if( $_745 === FALSE) { $_747 = FALSE; break; }
				$_747 = TRUE; break;
			}
			while(0);
			if( $_747 === FALSE) {
				$result = $res_748;
				$this->pos = $pos_748;
				unset( $res_748 );
				unset( $pos_748 );
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
			$res_787 = $result;
			$pos_787 = $this->pos;
			$_786 = NULL;
			do {
				$_784 = NULL;
				do {
					$res_749 = $result;
					$pos_749 = $this->pos;
					if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$_784 = TRUE; break;
					}
					$result = $res_749;
					$this->pos = $pos_749;
					$_782 = NULL;
					do {
						$res_751 = $result;
						$pos_751 = $this->pos;
						if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
							$result["text"] .= $subres;
							$_782 = TRUE; break;
						}
						$result = $res_751;
						$this->pos = $pos_751;
						$_780 = NULL;
						do {
							$res_753 = $result;
							$pos_753 = $this->pos;
							$_756 = NULL;
							do {
								if (substr($this->string,$this->pos,1) == '<') {
									$this->pos += 1;
									$result["text"] .= '<';
								}
								else { $_756 = FALSE; break; }
								$res_755 = $result;
								$pos_755 = $this->pos;
								if (substr($this->string,$this->pos,1) == '%') {
									$this->pos += 1;
									$result["text"] .= '%';
									$result = $res_755;
									$this->pos = $pos_755;
									$_756 = FALSE; break;
								}
								else {
									$result = $res_755;
									$this->pos = $pos_755;
								}
								$_756 = TRUE; break;
							}
							while(0);
							if( $_756 === TRUE ) { $_780 = TRUE; break; }
							$result = $res_753;
							$this->pos = $pos_753;
							$_778 = NULL;
							do {
								$res_758 = $result;
								$pos_758 = $this->pos;
								$_763 = NULL;
								do {
									if (substr($this->string,$this->pos,1) == '$') {
										$this->pos += 1;
										$result["text"] .= '$';
									}
									else { $_763 = FALSE; break; }
									$res_762 = $result;
									$pos_762 = $this->pos;
									$_761 = NULL;
									do {
										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_761 = FALSE; break; }
										$_761 = TRUE; break;
									}
									while(0);
									if( $_761 === TRUE ) {
										$result = $res_762;
										$this->pos = $pos_762;
										$_763 = FALSE; break;
									}
									if( $_761 === FALSE) {
										$result = $res_762;
										$this->pos = $pos_762;
									}
									$_763 = TRUE; break;
								}
								while(0);
								if( $_763 === TRUE ) { $_778 = TRUE; break; }
								$result = $res_758;
								$this->pos = $pos_758;
								$_776 = NULL;
								do {
									$res_765 = $result;
									$pos_765 = $this->pos;
									$_768 = NULL;
									do {
										if (substr($this->string,$this->pos,1) == '{') {
											$this->pos += 1;
											$result["text"] .= '{';
										}
										else { $_768 = FALSE; break; }
										$res_767 = $result;
										$pos_767 = $this->pos;
										if (substr($this->string,$this->pos,1) == '$') {
											$this->pos += 1;
											$result["text"] .= '$';
											$result = $res_767;
											$this->pos = $pos_767;
											$_768 = FALSE; break;
										}
										else {
											$result = $res_767;
											$this->pos = $pos_767;
										}
										$_768 = TRUE; break;
									}
									while(0);
									if( $_768 === TRUE ) { $_776 = TRUE; break; }
									$result = $res_765;
									$this->pos = $pos_765;
									$_774 = NULL;
									do {
										if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
										else { $_774 = FALSE; break; }
										$res_773 = $result;
										$pos_773 = $this->pos;
										$_772 = NULL;
										do {
											if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) { $result["text"] .= $subres; }
											else { $_772 = FALSE; break; }
											$_772 = TRUE; break;
										}
										while(0);
										if( $_772 === TRUE ) {
											$result = $res_773;
											$this->pos = $pos_773;
											$_774 = FALSE; break;
										}
										if( $_772 === FALSE) {
											$result = $res_773;
											$this->pos = $pos_773;
										}
										$_774 = TRUE; break;
									}
									while(0);
									if( $_774 === TRUE ) { $_776 = TRUE; break; }
									$result = $res_765;
									$this->pos = $pos_765;
									$_776 = FALSE; break;
								}
								while(0);
								if( $_776 === TRUE ) { $_778 = TRUE; break; }
								$result = $res_758;
								$this->pos = $pos_758;
								$_778 = FALSE; break;
							}
							while(0);
							if( $_778 === TRUE ) { $_780 = TRUE; break; }
							$result = $res_753;
							$this->pos = $pos_753;
							$_780 = FALSE; break;
						}
						while(0);
						if( $_780 === TRUE ) { $_782 = TRUE; break; }
						$result = $res_751;
						$this->pos = $pos_751;
						$_782 = FALSE; break;
					}
					while(0);
					if( $_782 === TRUE ) { $_784 = TRUE; break; }
					$result = $res_749;
					$this->pos = $pos_749;
					$_784 = FALSE; break;
				}
				while(0);
				if( $_784 === FALSE) { $_786 = FALSE; break; }
				$_786 = TRUE; break;
			}
			while(0);
			if( $_786 === FALSE) {
				$result = $res_787;
				$this->pos = $pos_787;
				unset( $res_787 );
				unset( $pos_787 );
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
			'href="\' . (Config::inst()->get(\'SSViewer\', \'rewrite_hash_links\') ?' .
			' strip_tags( $_SERVER[\'REQUEST_URI\'] ) : "") . 
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
	 * @throws SSTemplateParseException
	 * @param  $string The source of the template
	 * @param string $templateName The name of the template, normally the filename the template source was loaded from
	 * @param bool $includeDebuggingComments True is debugging comments should be included in the output
	 * @return mixed|string The php that, when executed (via include or exec) will behave as per the template source
	 */
	public function compileString($string, $templateName = "", $includeDebuggingComments=false) {
		if (!trim($string)) {
			$code = '';
		}
		else {
			parent::__construct($string);
			
			$this->includeDebuggingComments = $includeDebuggingComments;
	
			// Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF
			// (and other encodings) properly
			if(substr($string, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $this->pos = 3;
			
			// Match the source against the parser
			$result =  $this->match_TopTemplate();
			if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $this);
	
			// Get the result
			$code = $result['php'];
		}

		// Include top level debugging comments if desired
		if($includeDebuggingComments && $templateName && stripos($code, "<?xml") === false) {
			$code = $this->includeDebuggingComments($code, $templateName);
		}	
		
		return $code;
	}

	/**
	 * @param string $code
	 * @return string $code
	 */
	protected function includeDebuggingComments($code, $templateName) {
		// If this template contains a doctype, put it right after it,
		// if not, put it after the <html> tag to avoid IE glitches
		if(stripos($code, "<!doctype") !== false) {
			$code = preg_replace('/(<!doctype[^>]*("[^"]")*[^>]*>)/im', "$1\r\n<!-- template $templateName -->", $code);
			$code .= "\r\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
		} elseif(stripos($code, "<html") !== false) {
			$code = preg_replace_callback('/(.*)(<html[^>]*>)(.*)/i', function($matches) use ($templateName) {
				if (stripos($matches[3], '<!--') === false && stripos($matches[3], '-->') !== false) {
					// after this <html> tag there is a comment close but no comment has been opened
					// this most likely means that this <html> tag is inside a comment
					// we should not add a comment inside a comment (invalid html)
					// lets append it at the end of the comment
					// an example case for this is the html5boilerplate: <!--[if IE]><html class="ie"><![endif]-->
					return $matches[0];
				} else {
					// all other cases, add the comment and return it
					return "{$matches[1]}{$matches[2]}<!-- template $templateName -->{$matches[3]}";
				}
			}, $code);
			$code = preg_replace('/(<\/html[^>]*>)/i', "<!-- end template $templateName -->$1", $code);
		} else {
			$code = str_replace('<?php' . PHP_EOL, '<?php' . PHP_EOL . '$val .= \'<!-- template ' . $templateName .
				' -->\';' . "\r\n", $code);
			$code .= "\r\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
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
	public function compileFile($template) {
		return $this->compileString(file_get_contents($template), $template);
	}
}
