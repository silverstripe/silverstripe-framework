<?php

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/




namespace SilverStripe\View;

use SilverStripe\Core\Injector\Injector;
use Parser;
use InvalidArgumentException;

// We want this to work when run by hand too
if (defined('THIRDPARTY_PATH')) {
    require_once(THIRDPARTY_PATH . '/php-peg/Parser.php');
} else {
    $base = dirname(__FILE__);
    require_once($base.'/../thirdparty/php-peg/Parser.php');
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
  * Marked: A string or lookup in the template that has been explicitly marked as such - lookups by prepending with
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
class SSTemplateParser extends Parser implements TemplateParser
{

    /**
     * @var bool - Set true by SSTemplateParser::compileString if the template should include comments intended
     * for debugging (template source, included files, etc)
     */
    protected $includeDebuggingComments = false;

    /**
     * Stores the user-supplied closed block extension rules in the form:
     * [
     *   'name' => function (&$res) {}
     * ]
     * See SSTemplateParser::ClosedBlock_Handle_Loop for an example of what the callable should look like
     * @var array
     */
    protected $closedBlocks = [];

    /**
     * Stores the user-supplied open block extension rules in the form:
     * [
     *   'name' => function (&$res) {}
     * ]
     * See SSTemplateParser::OpenBlock_Handle_Base_tag for an example of what the callable should look like
     * @var array
     */
    protected $openBlocks = [];

    /**
     * Allow the injection of new closed & open block callables
     * @param array $closedBlocks
     * @param array $openBlocks
     */
    public function __construct($closedBlocks = [], $openBlocks = [])
    {
        parent::__construct(null);
        $this->setClosedBlocks($closedBlocks);
        $this->setOpenBlocks($openBlocks);
    }

    /**
     * Override the function that constructs the result arrays to also prepare a 'php' item in the array
     */
    function construct($matchrule, $name, $arguments = null)
    {
        $res = parent::construct($matchrule, $name, $arguments);
        if (!isset($res['php'])) {
            $res['php'] = '';
        }
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
    public function setClosedBlocks($closedBlocks)
    {
        $this->closedBlocks = [];
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
    public function setOpenBlocks($openBlocks)
    {
        $this->openBlocks = [];
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
    public function addClosedBlock($name, $callable)
    {
        $this->validateExtensionBlock($name, $callable, 'Closed block');
        $this->closedBlocks[$name] = $callable;
    }

    /**
     * Add a closed block callable to allow <% name %> syntax
     * @param string $name The name of the token to be used in the syntax <% name %>
     * @param callable $callable The function that modifies the generation of template code
     * @throws InvalidArgumentException
     */
    public function addOpenBlock($name, $callable)
    {
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
    protected function validateExtensionBlock($name, $callable, $type)
    {
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
    OpenBlock | MalformedBlock | MalformedBracketInjection | Injection | Text)+ */
    protected $match_Template_typestack = array('Template');
    function match_Template ($stack = array()) {
    	$matchrule = "Template"; $result = $this->construct($matchrule, $matchrule, null);
    	$count = 0;
    	while (true) {
    		$res_54 = $result;
    		$pos_54 = $this->pos;
    		$_53 = NULL;
    		do {
    			$_51 = NULL;
    			do {
    				$res_0 = $result;
    				$pos_0 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_51 = TRUE; break;
    				}
    				$result = $res_0;
    				$this->pos = $pos_0;
    				$_49 = NULL;
    				do {
    					$res_2 = $result;
    					$pos_2 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_49 = TRUE; break;
    					}
    					$result = $res_2;
    					$this->pos = $pos_2;
    					$_47 = NULL;
    					do {
    						$res_4 = $result;
    						$pos_4 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_47 = TRUE; break;
    						}
    						$result = $res_4;
    						$this->pos = $pos_4;
    						$_45 = NULL;
    						do {
    							$res_6 = $result;
    							$pos_6 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_45 = TRUE; break;
    							}
    							$result = $res_6;
    							$this->pos = $pos_6;
    							$_43 = NULL;
    							do {
    								$res_8 = $result;
    								$pos_8 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_43 = TRUE; break;
    								}
    								$result = $res_8;
    								$this->pos = $pos_8;
    								$_41 = NULL;
    								do {
    									$res_10 = $result;
    									$pos_10 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_41 = TRUE; break;
    									}
    									$result = $res_10;
    									$this->pos = $pos_10;
    									$_39 = NULL;
    									do {
    										$res_12 = $result;
    										$pos_12 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_39 = TRUE; break;
    										}
    										$result = $res_12;
    										$this->pos = $pos_12;
    										$_37 = NULL;
    										do {
    											$res_14 = $result;
    											$pos_14 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_37 = TRUE; break;
    											}
    											$result = $res_14;
    											$this->pos = $pos_14;
    											$_35 = NULL;
    											do {
    												$res_16 = $result;
    												$pos_16 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_35 = TRUE; break;
    												}
    												$result = $res_16;
    												$this->pos = $pos_16;
    												$_33 = NULL;
    												do {
    													$res_18 = $result;
    													$pos_18 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_33 = TRUE; break;
    													}
    													$result = $res_18;
    													$this->pos = $pos_18;
    													$_31 = NULL;
    													do {
    														$res_20 = $result;
    														$pos_20 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_31 = TRUE; break;
    														}
    														$result = $res_20;
    														$this->pos = $pos_20;
    														$_29 = NULL;
    														do {
    															$res_22 = $result;
    															$pos_22 = $this->pos;
    															$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_29 = TRUE; break;
    															}
    															$result = $res_22;
    															$this->pos = $pos_22;
    															$_27 = NULL;
    															do {
    																$res_24 = $result;
    																$pos_24 = $this->pos;
    																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_27 = TRUE; break;
    																}
    																$result = $res_24;
    																$this->pos = $pos_24;
    																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_27 = TRUE; break;
    																}
    																$result = $res_24;
    																$this->pos = $pos_24;
    																$_27 = FALSE; break;
    															}
    															while(0);
    															if( $_27 === TRUE ) { $_29 = TRUE; break; }
    															$result = $res_22;
    															$this->pos = $pos_22;
    															$_29 = FALSE; break;
    														}
    														while(0);
    														if( $_29 === TRUE ) { $_31 = TRUE; break; }
    														$result = $res_20;
    														$this->pos = $pos_20;
    														$_31 = FALSE; break;
    													}
    													while(0);
    													if( $_31 === TRUE ) { $_33 = TRUE; break; }
    													$result = $res_18;
    													$this->pos = $pos_18;
    													$_33 = FALSE; break;
    												}
    												while(0);
    												if( $_33 === TRUE ) { $_35 = TRUE; break; }
    												$result = $res_16;
    												$this->pos = $pos_16;
    												$_35 = FALSE; break;
    											}
    											while(0);
    											if( $_35 === TRUE ) { $_37 = TRUE; break; }
    											$result = $res_14;
    											$this->pos = $pos_14;
    											$_37 = FALSE; break;
    										}
    										while(0);
    										if( $_37 === TRUE ) { $_39 = TRUE; break; }
    										$result = $res_12;
    										$this->pos = $pos_12;
    										$_39 = FALSE; break;
    									}
    									while(0);
    									if( $_39 === TRUE ) { $_41 = TRUE; break; }
    									$result = $res_10;
    									$this->pos = $pos_10;
    									$_41 = FALSE; break;
    								}
    								while(0);
    								if( $_41 === TRUE ) { $_43 = TRUE; break; }
    								$result = $res_8;
    								$this->pos = $pos_8;
    								$_43 = FALSE; break;
    							}
    							while(0);
    							if( $_43 === TRUE ) { $_45 = TRUE; break; }
    							$result = $res_6;
    							$this->pos = $pos_6;
    							$_45 = FALSE; break;
    						}
    						while(0);
    						if( $_45 === TRUE ) { $_47 = TRUE; break; }
    						$result = $res_4;
    						$this->pos = $pos_4;
    						$_47 = FALSE; break;
    					}
    					while(0);
    					if( $_47 === TRUE ) { $_49 = TRUE; break; }
    					$result = $res_2;
    					$this->pos = $pos_2;
    					$_49 = FALSE; break;
    				}
    				while(0);
    				if( $_49 === TRUE ) { $_51 = TRUE; break; }
    				$result = $res_0;
    				$this->pos = $pos_0;
    				$_51 = FALSE; break;
    			}
    			while(0);
    			if( $_51 === FALSE) { $_53 = FALSE; break; }
    			$_53 = TRUE; break;
    		}
    		while(0);
    		if( $_53 === FALSE) {
    			$result = $res_54;
    			$this->pos = $pos_54;
    			unset( $res_54 );
    			unset( $pos_54 );
    			break;
    		}
    		$count += 1;
    	}
    	if ($count > 0) { return $this->finalise($result); }
    	else { return FALSE; }
    }



    function Template_STR(&$res, $sub)
    {
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


    /* NamespacedWord: / [A-Za-z_\/\\] [A-Za-z0-9_\/\\]* / */
    protected $match_NamespacedWord_typestack = array('NamespacedWord');
    function match_NamespacedWord ($stack = array()) {
    	$matchrule = "NamespacedWord"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ [A-Za-z_\/\\\\] [A-Za-z0-9_\/\\\\]* /' ) ) !== FALSE) {
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
    	$_66 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Argument" );
    		}
    		else { $_66 = FALSE; break; }
    		while (true) {
    			$res_65 = $result;
    			$pos_65 = $this->pos;
    			$_64 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_64 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "Argument" );
    				}
    				else { $_64 = FALSE; break; }
    				$_64 = TRUE; break;
    			}
    			while(0);
    			if( $_64 === FALSE) {
    				$result = $res_65;
    				$this->pos = $pos_65;
    				unset( $res_65 );
    				unset( $pos_65 );
    				break;
    			}
    		}
    		$_66 = TRUE; break;
    	}
    	while(0);
    	if( $_66 === TRUE ) { return $this->finalise($result); }
    	if( $_66 === FALSE) { return FALSE; }
    }




    /**
     * Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert
     * strings to numbers when needed.
     */
    function CallArguments_Argument(&$res, $sub)
    {
        if (!empty($res['php'])) {
            $res['php'] .= ', ';
        }

        $res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] :
            str_replace('$$FINAL', 'XML_val', $sub['php'] ?? '');
    }

    /* Call: Method:Word ( "(" < :CallArguments? > ")" )? */
    protected $match_Call_typestack = array('Call');
    function match_Call ($stack = array()) {
    	$matchrule = "Call"; $result = $this->construct($matchrule, $matchrule, null);
    	$_76 = NULL;
    	do {
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Method" );
    		}
    		else { $_76 = FALSE; break; }
    		$res_75 = $result;
    		$pos_75 = $this->pos;
    		$_74 = NULL;
    		do {
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    				$this->pos += 1;
    				$result["text"] .= '(';
    			}
    			else { $_74 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$res_71 = $result;
    			$pos_71 = $this->pos;
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "CallArguments" );
    			}
    			else {
    				$result = $res_71;
    				$this->pos = $pos_71;
    				unset( $res_71 );
    				unset( $pos_71 );
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    				$this->pos += 1;
    				$result["text"] .= ')';
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
    		}
    		$_76 = TRUE; break;
    	}
    	while(0);
    	if( $_76 === TRUE ) { return $this->finalise($result); }
    	if( $_76 === FALSE) { return FALSE; }
    }


    /* LookupStep: :Call &"." */
    protected $match_LookupStep_typestack = array('LookupStep');
    function match_LookupStep ($stack = array()) {
    	$matchrule = "LookupStep"; $result = $this->construct($matchrule, $matchrule, null);
    	$_80 = NULL;
    	do {
    		$matcher = 'match_'.'Call'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Call" );
    		}
    		else { $_80 = FALSE; break; }
    		$res_79 = $result;
    		$pos_79 = $this->pos;
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '.') {
    			$this->pos += 1;
    			$result["text"] .= '.';
    			$result = $res_79;
    			$this->pos = $pos_79;
    		}
    		else {
    			$result = $res_79;
    			$this->pos = $pos_79;
    			$_80 = FALSE; break;
    		}
    		$_80 = TRUE; break;
    	}
    	while(0);
    	if( $_80 === TRUE ) { return $this->finalise($result); }
    	if( $_80 === FALSE) { return FALSE; }
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
    	$_94 = NULL;
    	do {
    		$res_83 = $result;
    		$pos_83 = $this->pos;
    		$_91 = NULL;
    		do {
    			$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_91 = FALSE; break; }
    			while (true) {
    				$res_88 = $result;
    				$pos_88 = $this->pos;
    				$_87 = NULL;
    				do {
    					if (substr($this->string ?? '',$this->pos ?? 0,1) == '.') {
    						$this->pos += 1;
    						$result["text"] .= '.';
    					}
    					else { $_87 = FALSE; break; }
    					$matcher = 'match_'.'LookupStep'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    					}
    					else { $_87 = FALSE; break; }
    					$_87 = TRUE; break;
    				}
    				while(0);
    				if( $_87 === FALSE) {
    					$result = $res_88;
    					$this->pos = $pos_88;
    					unset( $res_88 );
    					unset( $pos_88 );
    					break;
    				}
    			}
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == '.') {
    				$this->pos += 1;
    				$result["text"] .= '.';
    			}
    			else { $_91 = FALSE; break; }
    			$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_91 = FALSE; break; }
    			$_91 = TRUE; break;
    		}
    		while(0);
    		if( $_91 === TRUE ) { $_94 = TRUE; break; }
    		$result = $res_83;
    		$this->pos = $pos_83;
    		$matcher = 'match_'.'LastLookupStep'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_94 = TRUE; break;
    		}
    		$result = $res_83;
    		$this->pos = $pos_83;
    		$_94 = FALSE; break;
    	}
    	while(0);
    	if( $_94 === TRUE ) { return $this->finalise($result); }
    	if( $_94 === FALSE) { return FALSE; }
    }




    function Lookup__construct(&$res)
    {
        $res['php'] = '$scope->locally()';
        $res['LookupSteps'] = [];
    }

    /**
     * The basic generated PHP of LookupStep and LastLookupStep is the same, except that LookupStep calls 'obj' to
     * get the next ViewableData in the sequence, and LastLookupStep calls different methods (XML_val, hasValue, obj)
     * depending on the context the lookup is used in.
     */
    function Lookup_AddLookupStep(&$res, $sub, $method)
    {
        $res['LookupSteps'][] = $sub;

        $property = $sub['Call']['Method']['text'];

        if (isset($sub['Call']['CallArguments']) && $arguments = $sub['Call']['CallArguments']['php']) {
            $res['php'] .= "->$method('$property', [$arguments], true)";
        } else {
            $res['php'] .= "->$method('$property', null, true)";
        }
    }

    function Lookup_LookupStep(&$res, $sub)
    {
        $this->Lookup_AddLookupStep($res, $sub, 'obj');
    }

    function Lookup_LastLookupStep(&$res, $sub)
    {
        $this->Lookup_AddLookupStep($res, $sub, '$$FINAL');
    }


    /* Translate: "<%t" < Entity < (Default:QuotedString)? < (!("is" "=") < "is" < Context:QuotedString)? <
    (InjectionVariables)? > "%>" */
    protected $match_Translate_typestack = array('Translate');
    function match_Translate ($stack = array()) {
    	$matchrule = "Translate"; $result = $this->construct($matchrule, $matchrule, null);
    	$_120 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%t' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_120 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'Entity'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_120 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_102 = $result;
    		$pos_102 = $this->pos;
    		$_101 = NULL;
    		do {
    			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Default" );
    			}
    			else { $_101 = FALSE; break; }
    			$_101 = TRUE; break;
    		}
    		while(0);
    		if( $_101 === FALSE) {
    			$result = $res_102;
    			$this->pos = $pos_102;
    			unset( $res_102 );
    			unset( $pos_102 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_113 = $result;
    		$pos_113 = $this->pos;
    		$_112 = NULL;
    		do {
    			$res_107 = $result;
    			$pos_107 = $this->pos;
    			$_106 = NULL;
    			do {
    				if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_106 = FALSE; break; }
    				if (substr($this->string ?? '',$this->pos ?? 0,1) == '=') {
    					$this->pos += 1;
    					$result["text"] .= '=';
    				}
    				else { $_106 = FALSE; break; }
    				$_106 = TRUE; break;
    			}
    			while(0);
    			if( $_106 === TRUE ) {
    				$result = $res_107;
    				$this->pos = $pos_107;
    				$_112 = FALSE; break;
    			}
    			if( $_106 === FALSE) {
    				$result = $res_107;
    				$this->pos = $pos_107;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( 'is' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_112 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Context" );
    			}
    			else { $_112 = FALSE; break; }
    			$_112 = TRUE; break;
    		}
    		while(0);
    		if( $_112 === FALSE) {
    			$result = $res_113;
    			$this->pos = $pos_113;
    			unset( $res_113 );
    			unset( $pos_113 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_117 = $result;
    		$pos_117 = $this->pos;
    		$_116 = NULL;
    		do {
    			$matcher = 'match_'.'InjectionVariables'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
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
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_120 = FALSE; break; }
    		$_120 = TRUE; break;
    	}
    	while(0);
    	if( $_120 === TRUE ) { return $this->finalise($result); }
    	if( $_120 === FALSE) { return FALSE; }
    }


    /* InjectionVariables: (< InjectionName:Word "=" Argument)+ */
    protected $match_InjectionVariables_typestack = array('InjectionVariables');
    function match_InjectionVariables ($stack = array()) {
    	$matchrule = "InjectionVariables"; $result = $this->construct($matchrule, $matchrule, null);
    	$count = 0;
    	while (true) {
    		$res_127 = $result;
    		$pos_127 = $this->pos;
    		$_126 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "InjectionName" );
    			}
    			else { $_126 = FALSE; break; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == '=') {
    				$this->pos += 1;
    				$result["text"] .= '=';
    			}
    			else { $_126 = FALSE; break; }
    			$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_126 = FALSE; break; }
    			$_126 = TRUE; break;
    		}
    		while(0);
    		if( $_126 === FALSE) {
    			$result = $res_127;
    			$this->pos = $pos_127;
    			unset( $res_127 );
    			unset( $pos_127 );
    			break;
    		}
    		$count += 1;
    	}
    	if ($count > 0) { return $this->finalise($result); }
    	else { return FALSE; }
    }


    /* Entity: / [A-Za-z_\\] [\w\.\\]* / */
    protected $match_Entity_typestack = array('Entity');
    function match_Entity ($stack = array()) {
    	$matchrule = "Entity"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ [A-Za-z_\\\\] [\w\.\\\\]* /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }




    function Translate__construct(&$res)
    {
        $res['php'] = '$val .= _t(';
    }

    function Translate_Entity(&$res, $sub)
    {
        $res['php'] .= "'$sub[text]'";
    }

    function Translate_Default(&$res, $sub)
    {
        $res['php'] .= ",$sub[text]";
    }

    function Translate_Context(&$res, $sub)
    {
        $res['php'] .= ",$sub[text]";
    }

    function Translate_InjectionVariables(&$res, $sub)
    {
        $res['php'] .= ",$sub[php]";
    }

    function Translate__finalise(&$res)
    {
        $res['php'] .= ');';
    }

    function InjectionVariables__construct(&$res)
    {
        $res['php'] = "[";
    }

    function InjectionVariables_InjectionName(&$res, $sub)
    {
        $res['php'] .= "'$sub[text]'=>";
    }

    function InjectionVariables_Argument(&$res, $sub)
    {
        $res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php'] ?? '') . ',';
    }

    function InjectionVariables__finalise(&$res)
    {
        if (substr($res['php'] ?? '', -1) == ',') {
            $res['php'] = substr($res['php'] ?? '', 0, -1); //remove last comma in the array
        }
        $res['php'] .= ']';
    }

    /* MalformedBracketInjection: "{$" :Lookup !( "}" ) */
    protected $match_MalformedBracketInjection_typestack = array('MalformedBracketInjection');
    function match_MalformedBracketInjection ($stack = array()) {
    	$matchrule = "MalformedBracketInjection"; $result = $this->construct($matchrule, $matchrule, null);
    	$_134 = NULL;
    	do {
    		if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_134 = FALSE; break; }
    		$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Lookup" );
    		}
    		else { $_134 = FALSE; break; }
    		$res_133 = $result;
    		$pos_133 = $this->pos;
    		$_132 = NULL;
    		do {
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == '}') {
    				$this->pos += 1;
    				$result["text"] .= '}';
    			}
    			else { $_132 = FALSE; break; }
    			$_132 = TRUE; break;
    		}
    		while(0);
    		if( $_132 === TRUE ) {
    			$result = $res_133;
    			$this->pos = $pos_133;
    			$_134 = FALSE; break;
    		}
    		if( $_132 === FALSE) {
    			$result = $res_133;
    			$this->pos = $pos_133;
    		}
    		$_134 = TRUE; break;
    	}
    	while(0);
    	if( $_134 === TRUE ) { return $this->finalise($result); }
    	if( $_134 === FALSE) { return FALSE; }
    }



    function MalformedBracketInjection__finalise(&$res)
    {
        $lookup = $res['text'];
        throw new SSTemplateParseException("Malformed bracket injection $lookup. Perhaps you have forgotten the " .
            "closing bracket (})?", $this);
    }

    /* SimpleInjection: '$' :Lookup */
    protected $match_SimpleInjection_typestack = array('SimpleInjection');
    function match_SimpleInjection ($stack = array()) {
    	$matchrule = "SimpleInjection"; $result = $this->construct($matchrule, $matchrule, null);
    	$_138 = NULL;
    	do {
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '$') {
    			$this->pos += 1;
    			$result["text"] .= '$';
    		}
    		else { $_138 = FALSE; break; }
    		$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Lookup" );
    		}
    		else { $_138 = FALSE; break; }
    		$_138 = TRUE; break;
    	}
    	while(0);
    	if( $_138 === TRUE ) { return $this->finalise($result); }
    	if( $_138 === FALSE) { return FALSE; }
    }


    /* BracketInjection: '{$' :Lookup "}" */
    protected $match_BracketInjection_typestack = array('BracketInjection');
    function match_BracketInjection ($stack = array()) {
    	$matchrule = "BracketInjection"; $result = $this->construct($matchrule, $matchrule, null);
    	$_143 = NULL;
    	do {
    		if (( $subres = $this->literal( '{$' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_143 = FALSE; break; }
    		$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Lookup" );
    		}
    		else { $_143 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '}') {
    			$this->pos += 1;
    			$result["text"] .= '}';
    		}
    		else { $_143 = FALSE; break; }
    		$_143 = TRUE; break;
    	}
    	while(0);
    	if( $_143 === TRUE ) { return $this->finalise($result); }
    	if( $_143 === FALSE) { return FALSE; }
    }


    /* Injection: BracketInjection | SimpleInjection */
    protected $match_Injection_typestack = array('Injection');
    function match_Injection ($stack = array()) {
    	$matchrule = "Injection"; $result = $this->construct($matchrule, $matchrule, null);
    	$_148 = NULL;
    	do {
    		$res_145 = $result;
    		$pos_145 = $this->pos;
    		$matcher = 'match_'.'BracketInjection'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_148 = TRUE; break;
    		}
    		$result = $res_145;
    		$this->pos = $pos_145;
    		$matcher = 'match_'.'SimpleInjection'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_148 = TRUE; break;
    		}
    		$result = $res_145;
    		$this->pos = $pos_145;
    		$_148 = FALSE; break;
    	}
    	while(0);
    	if( $_148 === TRUE ) { return $this->finalise($result); }
    	if( $_148 === FALSE) { return FALSE; }
    }



    function Injection_STR(&$res, $sub)
    {
        $res['php'] = '$val .= '. str_replace('$$FINAL', 'XML_val', $sub['Lookup']['php'] ?? '') . ';';
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



    function DollarMarkedLookup_STR(&$res, $sub)
    {
        $res['Lookup'] = $sub['Lookup'];
    }

    /* QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q' */
    protected $match_QuotedString_typestack = array('QuotedString');
    function match_QuotedString ($stack = array()) {
    	$matchrule = "QuotedString"; $result = $this->construct($matchrule, $matchrule, null);
    	$_154 = NULL;
    	do {
    		$stack[] = $result; $result = $this->construct( $matchrule, "q" ); 
    		if (( $subres = $this->rx( '/[\'"]/' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'q' );
    		}
    		else {
    			$result = array_pop($stack);
    			$_154 = FALSE; break;
    		}
    		$stack[] = $result; $result = $this->construct( $matchrule, "String" ); 
    		if (( $subres = $this->rx( '/ (\\\\\\\\ | \\\\. | [^'.$this->expression($result, $stack, 'q').'\\\\])* /' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'String' );
    		}
    		else {
    			$result = array_pop($stack);
    			$_154 = FALSE; break;
    		}
    		if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'q').'' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_154 = FALSE; break; }
    		$_154 = TRUE; break;
    	}
    	while(0);
    	if( $_154 === TRUE ) { return $this->finalise($result); }
    	if( $_154 === FALSE) { return FALSE; }
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
    	$_174 = NULL;
    	do {
    		$res_157 = $result;
    		$pos_157 = $this->pos;
    		$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "DollarMarkedLookup" );
    			$_174 = TRUE; break;
    		}
    		$result = $res_157;
    		$this->pos = $pos_157;
    		$_172 = NULL;
    		do {
    			$res_159 = $result;
    			$pos_159 = $this->pos;
    			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "QuotedString" );
    				$_172 = TRUE; break;
    			}
    			$result = $res_159;
    			$this->pos = $pos_159;
    			$_170 = NULL;
    			do {
    				$res_161 = $result;
    				$pos_161 = $this->pos;
    				$_167 = NULL;
    				do {
    					$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "Lookup" );
    					}
    					else { $_167 = FALSE; break; }
    					$res_166 = $result;
    					$pos_166 = $this->pos;
    					$_165 = NULL;
    					do {
    						if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    						$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    						}
    						else { $_165 = FALSE; break; }
    						$_165 = TRUE; break;
    					}
    					while(0);
    					if( $_165 === TRUE ) {
    						$result = $res_166;
    						$this->pos = $pos_166;
    						$_167 = FALSE; break;
    					}
    					if( $_165 === FALSE) {
    						$result = $res_166;
    						$this->pos = $pos_166;
    					}
    					$_167 = TRUE; break;
    				}
    				while(0);
    				if( $_167 === TRUE ) { $_170 = TRUE; break; }
    				$result = $res_161;
    				$this->pos = $pos_161;
    				$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "FreeString" );
    					$_170 = TRUE; break;
    				}
    				$result = $res_161;
    				$this->pos = $pos_161;
    				$_170 = FALSE; break;
    			}
    			while(0);
    			if( $_170 === TRUE ) { $_172 = TRUE; break; }
    			$result = $res_159;
    			$this->pos = $pos_159;
    			$_172 = FALSE; break;
    		}
    		while(0);
    		if( $_172 === TRUE ) { $_174 = TRUE; break; }
    		$result = $res_157;
    		$this->pos = $pos_157;
    		$_174 = FALSE; break;
    	}
    	while(0);
    	if( $_174 === TRUE ) { return $this->finalise($result); }
    	if( $_174 === FALSE) { return FALSE; }
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

    function Argument_DollarMarkedLookup(&$res, $sub)
    {
        $res['ArgumentMode'] = 'lookup';
        $res['php'] = $sub['Lookup']['php'];
    }

    function Argument_QuotedString(&$res, $sub)
    {
        $res['ArgumentMode'] = 'string';
        $res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text'] ?? '') . "'";
    }

    function Argument_Lookup(&$res, $sub)
    {
        if (count($sub['LookupSteps'] ?? []) == 1 && !isset($sub['LookupSteps'][0]['Call']['Arguments'])) {
            $res['ArgumentMode'] = 'default';
            $res['lookup_php'] = $sub['php'];
            $res['string_php'] = "'".$sub['LookupSteps'][0]['Call']['Method']['text']."'";
        } else {
            $res['ArgumentMode'] = 'lookup';
            $res['php'] = $sub['php'];
        }
    }

    function Argument_FreeString(&$res, $sub)
    {
        $res['ArgumentMode'] = 'string';
        $res['php'] = "'" . str_replace("'", "\\'", trim($sub['text'] ?? '')) . "'";
    }

    /* ComparisonOperator: "!=" | "==" | ">=" | ">" | "<=" | "<" | "=" */
    protected $match_ComparisonOperator_typestack = array('ComparisonOperator');
    function match_ComparisonOperator ($stack = array()) {
    	$matchrule = "ComparisonOperator"; $result = $this->construct($matchrule, $matchrule, null);
    	$_199 = NULL;
    	do {
    		$res_176 = $result;
    		$pos_176 = $this->pos;
    		if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_199 = TRUE; break;
    		}
    		$result = $res_176;
    		$this->pos = $pos_176;
    		$_197 = NULL;
    		do {
    			$res_178 = $result;
    			$pos_178 = $this->pos;
    			if (( $subres = $this->literal( '==' ) ) !== FALSE) {
    				$result["text"] .= $subres;
    				$_197 = TRUE; break;
    			}
    			$result = $res_178;
    			$this->pos = $pos_178;
    			$_195 = NULL;
    			do {
    				$res_180 = $result;
    				$pos_180 = $this->pos;
    				if (( $subres = $this->literal( '>=' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_195 = TRUE; break;
    				}
    				$result = $res_180;
    				$this->pos = $pos_180;
    				$_193 = NULL;
    				do {
    					$res_182 = $result;
    					$pos_182 = $this->pos;
    					if (substr($this->string ?? '',$this->pos ?? 0,1) == '>') {
    						$this->pos += 1;
    						$result["text"] .= '>';
    						$_193 = TRUE; break;
    					}
    					$result = $res_182;
    					$this->pos = $pos_182;
    					$_191 = NULL;
    					do {
    						$res_184 = $result;
    						$pos_184 = $this->pos;
    						if (( $subres = $this->literal( '<=' ) ) !== FALSE) {
    							$result["text"] .= $subres;
    							$_191 = TRUE; break;
    						}
    						$result = $res_184;
    						$this->pos = $pos_184;
    						$_189 = NULL;
    						do {
    							$res_186 = $result;
    							$pos_186 = $this->pos;
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '<') {
    								$this->pos += 1;
    								$result["text"] .= '<';
    								$_189 = TRUE; break;
    							}
    							$result = $res_186;
    							$this->pos = $pos_186;
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '=') {
    								$this->pos += 1;
    								$result["text"] .= '=';
    								$_189 = TRUE; break;
    							}
    							$result = $res_186;
    							$this->pos = $pos_186;
    							$_189 = FALSE; break;
    						}
    						while(0);
    						if( $_189 === TRUE ) { $_191 = TRUE; break; }
    						$result = $res_184;
    						$this->pos = $pos_184;
    						$_191 = FALSE; break;
    					}
    					while(0);
    					if( $_191 === TRUE ) { $_193 = TRUE; break; }
    					$result = $res_182;
    					$this->pos = $pos_182;
    					$_193 = FALSE; break;
    				}
    				while(0);
    				if( $_193 === TRUE ) { $_195 = TRUE; break; }
    				$result = $res_180;
    				$this->pos = $pos_180;
    				$_195 = FALSE; break;
    			}
    			while(0);
    			if( $_195 === TRUE ) { $_197 = TRUE; break; }
    			$result = $res_178;
    			$this->pos = $pos_178;
    			$_197 = FALSE; break;
    		}
    		while(0);
    		if( $_197 === TRUE ) { $_199 = TRUE; break; }
    		$result = $res_176;
    		$this->pos = $pos_176;
    		$_199 = FALSE; break;
    	}
    	while(0);
    	if( $_199 === TRUE ) { return $this->finalise($result); }
    	if( $_199 === FALSE) { return FALSE; }
    }


    /* Comparison: Argument < ComparisonOperator > Argument */
    protected $match_Comparison_typestack = array('Comparison');
    function match_Comparison ($stack = array()) {
    	$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
    	$_206 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_206 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_206 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_206 = FALSE; break; }
    		$_206 = TRUE; break;
    	}
    	while(0);
    	if( $_206 === TRUE ) { return $this->finalise($result); }
    	if( $_206 === FALSE) { return FALSE; }
    }



    function Comparison_Argument(&$res, $sub)
    {
        if ($sub['ArgumentMode'] == 'default') {
            if (!empty($res['php'])) {
                $res['php'] .= $sub['string_php'];
            } else {
                $res['php'] = str_replace('$$FINAL', 'XML_val', $sub['lookup_php'] ?? '');
            }
        } else {
            $res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php'] ?? '');
        }
    }

    function Comparison_ComparisonOperator(&$res, $sub)
    {
        $res['php'] .= ($sub['text'] == '=' ? '==' : $sub['text']);
    }

    /* PresenceCheck: (Not:'not' <)? Argument */
    protected $match_PresenceCheck_typestack = array('PresenceCheck');
    function match_PresenceCheck ($stack = array()) {
    	$matchrule = "PresenceCheck"; $result = $this->construct($matchrule, $matchrule, null);
    	$_213 = NULL;
    	do {
    		$res_211 = $result;
    		$pos_211 = $this->pos;
    		$_210 = NULL;
    		do {
    			$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
    			if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
    				$result["text"] .= $subres;
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Not' );
    			}
    			else {
    				$result = array_pop($stack);
    				$_210 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$_210 = TRUE; break;
    		}
    		while(0);
    		if( $_210 === FALSE) {
    			$result = $res_211;
    			$this->pos = $pos_211;
    			unset( $res_211 );
    			unset( $pos_211 );
    		}
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_213 = FALSE; break; }
    		$_213 = TRUE; break;
    	}
    	while(0);
    	if( $_213 === TRUE ) { return $this->finalise($result); }
    	if( $_213 === FALSE) { return FALSE; }
    }



    function PresenceCheck_Not(&$res, $sub)
    {
        $res['php'] = '!';
    }

    function PresenceCheck_Argument(&$res, $sub)
    {
        if ($sub['ArgumentMode'] == 'string') {
            $res['php'] .= '((bool)'.$sub['php'].')';
        } else {
            $php = ($sub['ArgumentMode'] == 'default' ? $sub['lookup_php'] : $sub['php']);
            // TODO: kinda hacky - maybe we need a way to pass state down the parse chain so
            // Lookup_LastLookupStep and Argument_BareWord can produce hasValue instead of XML_val
            $res['php'] .= str_replace('$$FINAL', 'hasValue', $php ?? '');
        }
    }

    /* IfArgumentPortion: Comparison | PresenceCheck */
    protected $match_IfArgumentPortion_typestack = array('IfArgumentPortion');
    function match_IfArgumentPortion ($stack = array()) {
    	$matchrule = "IfArgumentPortion"; $result = $this->construct($matchrule, $matchrule, null);
    	$_218 = NULL;
    	do {
    		$res_215 = $result;
    		$pos_215 = $this->pos;
    		$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_218 = TRUE; break;
    		}
    		$result = $res_215;
    		$this->pos = $pos_215;
    		$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_218 = TRUE; break;
    		}
    		$result = $res_215;
    		$this->pos = $pos_215;
    		$_218 = FALSE; break;
    	}
    	while(0);
    	if( $_218 === TRUE ) { return $this->finalise($result); }
    	if( $_218 === FALSE) { return FALSE; }
    }



    function IfArgumentPortion_STR(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* BooleanOperator: "||" | "&&" */
    protected $match_BooleanOperator_typestack = array('BooleanOperator');
    function match_BooleanOperator ($stack = array()) {
    	$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
    	$_223 = NULL;
    	do {
    		$res_220 = $result;
    		$pos_220 = $this->pos;
    		if (( $subres = $this->literal( '||' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_223 = TRUE; break;
    		}
    		$result = $res_220;
    		$this->pos = $pos_220;
    		if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_223 = TRUE; break;
    		}
    		$result = $res_220;
    		$this->pos = $pos_220;
    		$_223 = FALSE; break;
    	}
    	while(0);
    	if( $_223 === TRUE ) { return $this->finalise($result); }
    	if( $_223 === FALSE) { return FALSE; }
    }


    /* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
    protected $match_IfArgument_typestack = array('IfArgument');
    function match_IfArgument ($stack = array()) {
    	$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_232 = NULL;
    	do {
    		$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgumentPortion" );
    		}
    		else { $_232 = FALSE; break; }
    		while (true) {
    			$res_231 = $result;
    			$pos_231 = $this->pos;
    			$_230 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "BooleanOperator" );
    				}
    				else { $_230 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "IfArgumentPortion" );
    				}
    				else { $_230 = FALSE; break; }
    				$_230 = TRUE; break;
    			}
    			while(0);
    			if( $_230 === FALSE) {
    				$result = $res_231;
    				$this->pos = $pos_231;
    				unset( $res_231 );
    				unset( $pos_231 );
    				break;
    			}
    		}
    		$_232 = TRUE; break;
    	}
    	while(0);
    	if( $_232 === TRUE ) { return $this->finalise($result); }
    	if( $_232 === FALSE) { return FALSE; }
    }



    function IfArgument_IfArgumentPortion(&$res, $sub)
    {
        $res['php'] .= $sub['php'];
    }

    function IfArgument_BooleanOperator(&$res, $sub)
    {
        $res['php'] .= $sub['text'];
    }

    /* IfPart: '<%' < 'if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
    protected $match_IfPart_typestack = array('IfPart');
    function match_IfPart ($stack = array()) {
    	$matchrule = "IfPart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_242 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_242 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_242 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_242 = FALSE; break; }
    		$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgument" );
    		}
    		else { $_242 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_242 = FALSE; break; }
    		$res_241 = $result;
    		$pos_241 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_241;
    			$this->pos = $pos_241;
    			unset( $res_241 );
    			unset( $pos_241 );
    		}
    		$_242 = TRUE; break;
    	}
    	while(0);
    	if( $_242 === TRUE ) { return $this->finalise($result); }
    	if( $_242 === FALSE) { return FALSE; }
    }


    /* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
    protected $match_ElseIfPart_typestack = array('ElseIfPart');
    function match_ElseIfPart ($stack = array()) {
    	$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_252 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_252 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_252 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_252 = FALSE; break; }
    		$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgument" );
    		}
    		else { $_252 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_252 = FALSE; break; }
    		$res_251 = $result;
    		$pos_251 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_251;
    			$this->pos = $pos_251;
    			unset( $res_251 );
    			unset( $pos_251 );
    		}
    		$_252 = TRUE; break;
    	}
    	while(0);
    	if( $_252 === TRUE ) { return $this->finalise($result); }
    	if( $_252 === FALSE) { return FALSE; }
    }


    /* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher? */
    protected $match_ElsePart_typestack = array('ElsePart');
    function match_ElsePart ($stack = array()) {
    	$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_260 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_260 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_260 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_260 = FALSE; break; }
    		$res_259 = $result;
    		$pos_259 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_259;
    			$this->pos = $pos_259;
    			unset( $res_259 );
    			unset( $pos_259 );
    		}
    		$_260 = TRUE; break;
    	}
    	while(0);
    	if( $_260 === TRUE ) { return $this->finalise($result); }
    	if( $_260 === FALSE) { return FALSE; }
    }


    /* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
    protected $match_If_typestack = array('If');
    function match_If ($stack = array()) {
    	$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
    	$_270 = NULL;
    	do {
    		$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_270 = FALSE; break; }
    		while (true) {
    			$res_263 = $result;
    			$pos_263 = $this->pos;
    			$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else {
    				$result = $res_263;
    				$this->pos = $pos_263;
    				unset( $res_263 );
    				unset( $pos_263 );
    				break;
    			}
    		}
    		$res_264 = $result;
    		$pos_264 = $this->pos;
    		$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else {
    			$result = $res_264;
    			$this->pos = $pos_264;
    			unset( $res_264 );
    			unset( $pos_264 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_270 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_270 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_270 = FALSE; break; }
    		$_270 = TRUE; break;
    	}
    	while(0);
    	if( $_270 === TRUE ) { return $this->finalise($result); }
    	if( $_270 === FALSE) { return FALSE; }
    }



    function If_IfPart(&$res, $sub)
    {
        $res['php'] =
            'if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
                (isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
            '}';
    }

    function If_ElseIfPart(&$res, $sub)
    {
        $res['php'] .=
            'else if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
                (isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
            '}';
    }

    function If_ElsePart(&$res, $sub)
    {
        $res['php'] .=
            'else { ' . PHP_EOL .
                (isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
            '}';
    }

    /* Require: '<%' < 'require' [ Call:(Method:Word "(" < :CallArguments  > ")") > '%>' */
    protected $match_Require_typestack = array('Require');
    function match_Require ($stack = array()) {
    	$matchrule = "Require"; $result = $this->construct($matchrule, $matchrule, null);
    	$_286 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
    		$_282 = NULL;
    		do {
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Method" );
    			}
    			else { $_282 = FALSE; break; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    				$this->pos += 1;
    				$result["text"] .= '(';
    			}
    			else { $_282 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "CallArguments" );
    			}
    			else { $_282 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    				$this->pos += 1;
    				$result["text"] .= ')';
    			}
    			else { $_282 = FALSE; break; }
    			$_282 = TRUE; break;
    		}
    		while(0);
    		if( $_282 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Call' );
    		}
    		if( $_282 === FALSE) {
    			$result = array_pop($stack);
    			$_286 = FALSE; break;
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		$_286 = TRUE; break;
    	}
    	while(0);
    	if( $_286 === TRUE ) { return $this->finalise($result); }
    	if( $_286 === FALSE) { return FALSE; }
    }



    function Require_Call(&$res, $sub)
    {
        $requirements = '\\SilverStripe\\View\\Requirements';
        $res['php'] = "{$requirements}::".$sub['Method']['text'].'('.$sub['CallArguments']['php'].');';
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
    	$_306 = NULL;
    	do {
    		$res_294 = $result;
    		$pos_294 = $this->pos;
    		$_293 = NULL;
    		do {
    			$_291 = NULL;
    			do {
    				$res_288 = $result;
    				$pos_288 = $this->pos;
    				if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_291 = TRUE; break;
    				}
    				$result = $res_288;
    				$this->pos = $pos_288;
    				if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_291 = TRUE; break;
    				}
    				$result = $res_288;
    				$this->pos = $pos_288;
    				$_291 = FALSE; break;
    			}
    			while(0);
    			if( $_291 === FALSE) { $_293 = FALSE; break; }
    			$_293 = TRUE; break;
    		}
    		while(0);
    		if( $_293 === TRUE ) {
    			$result = $res_294;
    			$this->pos = $pos_294;
    			$_306 = FALSE; break;
    		}
    		if( $_293 === FALSE) {
    			$result = $res_294;
    			$this->pos = $pos_294;
    		}
    		$_304 = NULL;
    		do {
    			$_302 = NULL;
    			do {
    				$res_295 = $result;
    				$pos_295 = $this->pos;
    				$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "DollarMarkedLookup" );
    					$_302 = TRUE; break;
    				}
    				$result = $res_295;
    				$this->pos = $pos_295;
    				$_300 = NULL;
    				do {
    					$res_297 = $result;
    					$pos_297 = $this->pos;
    					$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "QuotedString" );
    						$_300 = TRUE; break;
    					}
    					$result = $res_297;
    					$this->pos = $pos_297;
    					$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "Lookup" );
    						$_300 = TRUE; break;
    					}
    					$result = $res_297;
    					$this->pos = $pos_297;
    					$_300 = FALSE; break;
    				}
    				while(0);
    				if( $_300 === TRUE ) { $_302 = TRUE; break; }
    				$result = $res_295;
    				$this->pos = $pos_295;
    				$_302 = FALSE; break;
    			}
    			while(0);
    			if( $_302 === FALSE) { $_304 = FALSE; break; }
    			$_304 = TRUE; break;
    		}
    		while(0);
    		if( $_304 === FALSE) { $_306 = FALSE; break; }
    		$_306 = TRUE; break;
    	}
    	while(0);
    	if( $_306 === TRUE ) { return $this->finalise($result); }
    	if( $_306 === FALSE) { return FALSE; }
    }



    function CacheBlockArgument_DollarMarkedLookup(&$res, $sub)
    {
        $res['php'] = $sub['Lookup']['php'];
    }

    function CacheBlockArgument_QuotedString(&$res, $sub)
    {
        $res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text'] ?? '') . "'";
    }

    function CacheBlockArgument_Lookup(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* CacheBlockArguments: CacheBlockArgument ( < "," < CacheBlockArgument )* */
    protected $match_CacheBlockArguments_typestack = array('CacheBlockArguments');
    function match_CacheBlockArguments ($stack = array()) {
    	$matchrule = "CacheBlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
    	$_315 = NULL;
    	do {
    		$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_315 = FALSE; break; }
    		while (true) {
    			$res_314 = $result;
    			$pos_314 = $this->pos;
    			$_313 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_313 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    				}
    				else { $_313 = FALSE; break; }
    				$_313 = TRUE; break;
    			}
    			while(0);
    			if( $_313 === FALSE) {
    				$result = $res_314;
    				$this->pos = $pos_314;
    				unset( $res_314 );
    				unset( $pos_314 );
    				break;
    			}
    		}
    		$_315 = TRUE; break;
    	}
    	while(0);
    	if( $_315 === TRUE ) { return $this->finalise($result); }
    	if( $_315 === FALSE) { return FALSE; }
    }



    function CacheBlockArguments_CacheBlockArgument(&$res, $sub)
    {
        if (!empty($res['php'])) {
            $res['php'] .= ".'_'.";
        } else {
            $res['php'] = '';
        }

        $res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php'] ?? '');
    }

    /* CacheBlockTemplate: (Comment | Translate | If | Require |    OldI18NTag | Include | ClosedBlock |
    OpenBlock | MalformedBlock | MalformedBracketInjection | Injection | Text)+ */
    protected $match_CacheBlockTemplate_typestack = array('CacheBlockTemplate','Template');
    function match_CacheBlockTemplate ($stack = array()) {
    	$matchrule = "CacheBlockTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'CacheRestrictedTemplate'));
    	$count = 0;
    	while (true) {
    		$res_363 = $result;
    		$pos_363 = $this->pos;
    		$_362 = NULL;
    		do {
    			$_360 = NULL;
    			do {
    				$res_317 = $result;
    				$pos_317 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_360 = TRUE; break;
    				}
    				$result = $res_317;
    				$this->pos = $pos_317;
    				$_358 = NULL;
    				do {
    					$res_319 = $result;
    					$pos_319 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_358 = TRUE; break;
    					}
    					$result = $res_319;
    					$this->pos = $pos_319;
    					$_356 = NULL;
    					do {
    						$res_321 = $result;
    						$pos_321 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_356 = TRUE; break;
    						}
    						$result = $res_321;
    						$this->pos = $pos_321;
    						$_354 = NULL;
    						do {
    							$res_323 = $result;
    							$pos_323 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_354 = TRUE; break;
    							}
    							$result = $res_323;
    							$this->pos = $pos_323;
    							$_352 = NULL;
    							do {
    								$res_325 = $result;
    								$pos_325 = $this->pos;
    								$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_352 = TRUE; break;
    								}
    								$result = $res_325;
    								$this->pos = $pos_325;
    								$_350 = NULL;
    								do {
    									$res_327 = $result;
    									$pos_327 = $this->pos;
    									$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_350 = TRUE; break;
    									}
    									$result = $res_327;
    									$this->pos = $pos_327;
    									$_348 = NULL;
    									do {
    										$res_329 = $result;
    										$pos_329 = $this->pos;
    										$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_348 = TRUE; break;
    										}
    										$result = $res_329;
    										$this->pos = $pos_329;
    										$_346 = NULL;
    										do {
    											$res_331 = $result;
    											$pos_331 = $this->pos;
    											$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_346 = TRUE; break;
    											}
    											$result = $res_331;
    											$this->pos = $pos_331;
    											$_344 = NULL;
    											do {
    												$res_333 = $result;
    												$pos_333 = $this->pos;
    												$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_344 = TRUE; break;
    												}
    												$result = $res_333;
    												$this->pos = $pos_333;
    												$_342 = NULL;
    												do {
    													$res_335 = $result;
    													$pos_335 = $this->pos;
    													$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_342 = TRUE; break;
    													}
    													$result = $res_335;
    													$this->pos = $pos_335;
    													$_340 = NULL;
    													do {
    														$res_337 = $result;
    														$pos_337 = $this->pos;
    														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_340 = TRUE; break;
    														}
    														$result = $res_337;
    														$this->pos = $pos_337;
    														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_340 = TRUE; break;
    														}
    														$result = $res_337;
    														$this->pos = $pos_337;
    														$_340 = FALSE; break;
    													}
    													while(0);
    													if( $_340 === TRUE ) { $_342 = TRUE; break; }
    													$result = $res_335;
    													$this->pos = $pos_335;
    													$_342 = FALSE; break;
    												}
    												while(0);
    												if( $_342 === TRUE ) { $_344 = TRUE; break; }
    												$result = $res_333;
    												$this->pos = $pos_333;
    												$_344 = FALSE; break;
    											}
    											while(0);
    											if( $_344 === TRUE ) { $_346 = TRUE; break; }
    											$result = $res_331;
    											$this->pos = $pos_331;
    											$_346 = FALSE; break;
    										}
    										while(0);
    										if( $_346 === TRUE ) { $_348 = TRUE; break; }
    										$result = $res_329;
    										$this->pos = $pos_329;
    										$_348 = FALSE; break;
    									}
    									while(0);
    									if( $_348 === TRUE ) { $_350 = TRUE; break; }
    									$result = $res_327;
    									$this->pos = $pos_327;
    									$_350 = FALSE; break;
    								}
    								while(0);
    								if( $_350 === TRUE ) { $_352 = TRUE; break; }
    								$result = $res_325;
    								$this->pos = $pos_325;
    								$_352 = FALSE; break;
    							}
    							while(0);
    							if( $_352 === TRUE ) { $_354 = TRUE; break; }
    							$result = $res_323;
    							$this->pos = $pos_323;
    							$_354 = FALSE; break;
    						}
    						while(0);
    						if( $_354 === TRUE ) { $_356 = TRUE; break; }
    						$result = $res_321;
    						$this->pos = $pos_321;
    						$_356 = FALSE; break;
    					}
    					while(0);
    					if( $_356 === TRUE ) { $_358 = TRUE; break; }
    					$result = $res_319;
    					$this->pos = $pos_319;
    					$_358 = FALSE; break;
    				}
    				while(0);
    				if( $_358 === TRUE ) { $_360 = TRUE; break; }
    				$result = $res_317;
    				$this->pos = $pos_317;
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




    /* UncachedBlock:
    '<%' < "uncached" < CacheBlockArguments? ( < Conditional:("if"|"unless") > Condition:IfArgument )? > '%>'
        Template:$TemplateMatcher?
        '<%' < 'end_' ("uncached"|"cached"|"cacheblock") > '%>' */
    protected $match_UncachedBlock_typestack = array('UncachedBlock');
    function match_UncachedBlock ($stack = array()) {
    	$matchrule = "UncachedBlock"; $result = $this->construct($matchrule, $matchrule, null);
    	$_400 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_400 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_400 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_368 = $result;
    		$pos_368 = $this->pos;
    		$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else {
    			$result = $res_368;
    			$this->pos = $pos_368;
    			unset( $res_368 );
    			unset( $pos_368 );
    		}
    		$res_380 = $result;
    		$pos_380 = $this->pos;
    		$_379 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
    			$_375 = NULL;
    			do {
    				$_373 = NULL;
    				do {
    					$res_370 = $result;
    					$pos_370 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_373 = TRUE; break;
    					}
    					$result = $res_370;
    					$this->pos = $pos_370;
    					if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_373 = TRUE; break;
    					}
    					$result = $res_370;
    					$this->pos = $pos_370;
    					$_373 = FALSE; break;
    				}
    				while(0);
    				if( $_373 === FALSE) { $_375 = FALSE; break; }
    				$_375 = TRUE; break;
    			}
    			while(0);
    			if( $_375 === TRUE ) {
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Conditional' );
    			}
    			if( $_375 === FALSE) {
    				$result = array_pop($stack);
    				$_379 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Condition" );
    			}
    			else { $_379 = FALSE; break; }
    			$_379 = TRUE; break;
    		}
    		while(0);
    		if( $_379 === FALSE) {
    			$result = $res_380;
    			$this->pos = $pos_380;
    			unset( $res_380 );
    			unset( $pos_380 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_400 = FALSE; break; }
    		$res_383 = $result;
    		$pos_383 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_383;
    			$this->pos = $pos_383;
    			unset( $res_383 );
    			unset( $pos_383 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_400 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_400 = FALSE; break; }
    		$_396 = NULL;
    		do {
    			$_394 = NULL;
    			do {
    				$res_387 = $result;
    				$pos_387 = $this->pos;
    				if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_394 = TRUE; break;
    				}
    				$result = $res_387;
    				$this->pos = $pos_387;
    				$_392 = NULL;
    				do {
    					$res_389 = $result;
    					$pos_389 = $this->pos;
    					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_392 = TRUE; break;
    					}
    					$result = $res_389;
    					$this->pos = $pos_389;
    					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_392 = TRUE; break;
    					}
    					$result = $res_389;
    					$this->pos = $pos_389;
    					$_392 = FALSE; break;
    				}
    				while(0);
    				if( $_392 === TRUE ) { $_394 = TRUE; break; }
    				$result = $res_387;
    				$this->pos = $pos_387;
    				$_394 = FALSE; break;
    			}
    			while(0);
    			if( $_394 === FALSE) { $_396 = FALSE; break; }
    			$_396 = TRUE; break;
    		}
    		while(0);
    		if( $_396 === FALSE) { $_400 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_400 = FALSE; break; }
    		$_400 = TRUE; break;
    	}
    	while(0);
    	if( $_400 === TRUE ) { return $this->finalise($result); }
    	if( $_400 === FALSE) { return FALSE; }
    }



    function UncachedBlock_Template(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* CacheRestrictedTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
    OpenBlock | MalformedBlock | MalformedBracketInjection | Injection | Text)+ */
    protected $match_CacheRestrictedTemplate_typestack = array('CacheRestrictedTemplate','Template');
    function match_CacheRestrictedTemplate ($stack = array()) {
    	$matchrule = "CacheRestrictedTemplate"; $result = $this->construct($matchrule, $matchrule, null);
    	$count = 0;
    	while (true) {
    		$res_456 = $result;
    		$pos_456 = $this->pos;
    		$_455 = NULL;
    		do {
    			$_453 = NULL;
    			do {
    				$res_402 = $result;
    				$pos_402 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_453 = TRUE; break;
    				}
    				$result = $res_402;
    				$this->pos = $pos_402;
    				$_451 = NULL;
    				do {
    					$res_404 = $result;
    					$pos_404 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_451 = TRUE; break;
    					}
    					$result = $res_404;
    					$this->pos = $pos_404;
    					$_449 = NULL;
    					do {
    						$res_406 = $result;
    						$pos_406 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_449 = TRUE; break;
    						}
    						$result = $res_406;
    						$this->pos = $pos_406;
    						$_447 = NULL;
    						do {
    							$res_408 = $result;
    							$pos_408 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_447 = TRUE; break;
    							}
    							$result = $res_408;
    							$this->pos = $pos_408;
    							$_445 = NULL;
    							do {
    								$res_410 = $result;
    								$pos_410 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_445 = TRUE; break;
    								}
    								$result = $res_410;
    								$this->pos = $pos_410;
    								$_443 = NULL;
    								do {
    									$res_412 = $result;
    									$pos_412 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_443 = TRUE; break;
    									}
    									$result = $res_412;
    									$this->pos = $pos_412;
    									$_441 = NULL;
    									do {
    										$res_414 = $result;
    										$pos_414 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_441 = TRUE; break;
    										}
    										$result = $res_414;
    										$this->pos = $pos_414;
    										$_439 = NULL;
    										do {
    											$res_416 = $result;
    											$pos_416 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_439 = TRUE; break;
    											}
    											$result = $res_416;
    											$this->pos = $pos_416;
    											$_437 = NULL;
    											do {
    												$res_418 = $result;
    												$pos_418 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_437 = TRUE; break;
    												}
    												$result = $res_418;
    												$this->pos = $pos_418;
    												$_435 = NULL;
    												do {
    													$res_420 = $result;
    													$pos_420 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_435 = TRUE; break;
    													}
    													$result = $res_420;
    													$this->pos = $pos_420;
    													$_433 = NULL;
    													do {
    														$res_422 = $result;
    														$pos_422 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_433 = TRUE; break;
    														}
    														$result = $res_422;
    														$this->pos = $pos_422;
    														$_431 = NULL;
    														do {
    															$res_424 = $result;
    															$pos_424 = $this->pos;
    															$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_431 = TRUE; break;
    															}
    															$result = $res_424;
    															$this->pos = $pos_424;
    															$_429 = NULL;
    															do {
    																$res_426 = $result;
    																$pos_426 = $this->pos;
    																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_429 = TRUE; break;
    																}
    																$result = $res_426;
    																$this->pos = $pos_426;
    																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_429 = TRUE; break;
    																}
    																$result = $res_426;
    																$this->pos = $pos_426;
    																$_429 = FALSE; break;
    															}
    															while(0);
    															if( $_429 === TRUE ) {
    																$_431 = TRUE; break;
    															}
    															$result = $res_424;
    															$this->pos = $pos_424;
    															$_431 = FALSE; break;
    														}
    														while(0);
    														if( $_431 === TRUE ) { $_433 = TRUE; break; }
    														$result = $res_422;
    														$this->pos = $pos_422;
    														$_433 = FALSE; break;
    													}
    													while(0);
    													if( $_433 === TRUE ) { $_435 = TRUE; break; }
    													$result = $res_420;
    													$this->pos = $pos_420;
    													$_435 = FALSE; break;
    												}
    												while(0);
    												if( $_435 === TRUE ) { $_437 = TRUE; break; }
    												$result = $res_418;
    												$this->pos = $pos_418;
    												$_437 = FALSE; break;
    											}
    											while(0);
    											if( $_437 === TRUE ) { $_439 = TRUE; break; }
    											$result = $res_416;
    											$this->pos = $pos_416;
    											$_439 = FALSE; break;
    										}
    										while(0);
    										if( $_439 === TRUE ) { $_441 = TRUE; break; }
    										$result = $res_414;
    										$this->pos = $pos_414;
    										$_441 = FALSE; break;
    									}
    									while(0);
    									if( $_441 === TRUE ) { $_443 = TRUE; break; }
    									$result = $res_412;
    									$this->pos = $pos_412;
    									$_443 = FALSE; break;
    								}
    								while(0);
    								if( $_443 === TRUE ) { $_445 = TRUE; break; }
    								$result = $res_410;
    								$this->pos = $pos_410;
    								$_445 = FALSE; break;
    							}
    							while(0);
    							if( $_445 === TRUE ) { $_447 = TRUE; break; }
    							$result = $res_408;
    							$this->pos = $pos_408;
    							$_447 = FALSE; break;
    						}
    						while(0);
    						if( $_447 === TRUE ) { $_449 = TRUE; break; }
    						$result = $res_406;
    						$this->pos = $pos_406;
    						$_449 = FALSE; break;
    					}
    					while(0);
    					if( $_449 === TRUE ) { $_451 = TRUE; break; }
    					$result = $res_404;
    					$this->pos = $pos_404;
    					$_451 = FALSE; break;
    				}
    				while(0);
    				if( $_451 === TRUE ) { $_453 = TRUE; break; }
    				$result = $res_402;
    				$this->pos = $pos_402;
    				$_453 = FALSE; break;
    			}
    			while(0);
    			if( $_453 === FALSE) { $_455 = FALSE; break; }
    			$_455 = TRUE; break;
    		}
    		while(0);
    		if( $_455 === FALSE) {
    			$result = $res_456;
    			$this->pos = $pos_456;
    			unset( $res_456 );
    			unset( $pos_456 );
    			break;
    		}
    		$count += 1;
    	}
    	if ($count > 0) { return $this->finalise($result); }
    	else { return FALSE; }
    }



    function CacheRestrictedTemplate_CacheBlock(&$res, $sub)
    {
        throw new SSTemplateParseException('You cant have cache blocks nested within with, loop or control blocks ' .
            'that are within cache blocks', $this);
    }

    function CacheRestrictedTemplate_UncachedBlock(&$res, $sub)
    {
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
    	$_511 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_511 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
    		$_464 = NULL;
    		do {
    			$_462 = NULL;
    			do {
    				$res_459 = $result;
    				$pos_459 = $this->pos;
    				if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_462 = TRUE; break;
    				}
    				$result = $res_459;
    				$this->pos = $pos_459;
    				if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_462 = TRUE; break;
    				}
    				$result = $res_459;
    				$this->pos = $pos_459;
    				$_462 = FALSE; break;
    			}
    			while(0);
    			if( $_462 === FALSE) { $_464 = FALSE; break; }
    			$_464 = TRUE; break;
    		}
    		while(0);
    		if( $_464 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'CacheTag' );
    		}
    		if( $_464 === FALSE) {
    			$result = array_pop($stack);
    			$_511 = FALSE; break;
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_469 = $result;
    		$pos_469 = $this->pos;
    		$_468 = NULL;
    		do {
    			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_468 = FALSE; break; }
    			$_468 = TRUE; break;
    		}
    		while(0);
    		if( $_468 === FALSE) {
    			$result = $res_469;
    			$this->pos = $pos_469;
    			unset( $res_469 );
    			unset( $pos_469 );
    		}
    		$res_481 = $result;
    		$pos_481 = $this->pos;
    		$_480 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
    			$_476 = NULL;
    			do {
    				$_474 = NULL;
    				do {
    					$res_471 = $result;
    					$pos_471 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_474 = TRUE; break;
    					}
    					$result = $res_471;
    					$this->pos = $pos_471;
    					if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_474 = TRUE; break;
    					}
    					$result = $res_471;
    					$this->pos = $pos_471;
    					$_474 = FALSE; break;
    				}
    				while(0);
    				if( $_474 === FALSE) { $_476 = FALSE; break; }
    				$_476 = TRUE; break;
    			}
    			while(0);
    			if( $_476 === TRUE ) {
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Conditional' );
    			}
    			if( $_476 === FALSE) {
    				$result = array_pop($stack);
    				$_480 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Condition" );
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
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_511 = FALSE; break; }
    		while (true) {
    			$res_494 = $result;
    			$pos_494 = $this->pos;
    			$_493 = NULL;
    			do {
    				$_491 = NULL;
    				do {
    					$res_484 = $result;
    					$pos_484 = $this->pos;
    					$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_491 = TRUE; break;
    					}
    					$result = $res_484;
    					$this->pos = $pos_484;
    					$_489 = NULL;
    					do {
    						$res_486 = $result;
    						$pos_486 = $this->pos;
    						$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_489 = TRUE; break;
    						}
    						$result = $res_486;
    						$this->pos = $pos_486;
    						$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_489 = TRUE; break;
    						}
    						$result = $res_486;
    						$this->pos = $pos_486;
    						$_489 = FALSE; break;
    					}
    					while(0);
    					if( $_489 === TRUE ) { $_491 = TRUE; break; }
    					$result = $res_484;
    					$this->pos = $pos_484;
    					$_491 = FALSE; break;
    				}
    				while(0);
    				if( $_491 === FALSE) { $_493 = FALSE; break; }
    				$_493 = TRUE; break;
    			}
    			while(0);
    			if( $_493 === FALSE) {
    				$result = $res_494;
    				$this->pos = $pos_494;
    				unset( $res_494 );
    				unset( $pos_494 );
    				break;
    			}
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_511 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_511 = FALSE; break; }
    		$_507 = NULL;
    		do {
    			$_505 = NULL;
    			do {
    				$res_498 = $result;
    				$pos_498 = $this->pos;
    				if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_505 = TRUE; break;
    				}
    				$result = $res_498;
    				$this->pos = $pos_498;
    				$_503 = NULL;
    				do {
    					$res_500 = $result;
    					$pos_500 = $this->pos;
    					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_503 = TRUE; break;
    					}
    					$result = $res_500;
    					$this->pos = $pos_500;
    					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_503 = TRUE; break;
    					}
    					$result = $res_500;
    					$this->pos = $pos_500;
    					$_503 = FALSE; break;
    				}
    				while(0);
    				if( $_503 === TRUE ) { $_505 = TRUE; break; }
    				$result = $res_498;
    				$this->pos = $pos_498;
    				$_505 = FALSE; break;
    			}
    			while(0);
    			if( $_505 === FALSE) { $_507 = FALSE; break; }
    			$_507 = TRUE; break;
    		}
    		while(0);
    		if( $_507 === FALSE) { $_511 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_511 = FALSE; break; }
    		$_511 = TRUE; break;
    	}
    	while(0);
    	if( $_511 === TRUE ) { return $this->finalise($result); }
    	if( $_511 === FALSE) { return FALSE; }
    }



    function CacheBlock__construct(&$res)
    {
        $res['subblocks'] = 0;
    }

    function CacheBlock_CacheBlockArguments(&$res, $sub)
    {
        $res['key'] = !empty($sub['php']) ? $sub['php'] : '';
    }

    function CacheBlock_Condition(&$res, $sub)
    {
        $res['condition'] = ($res['Conditional']['text'] == 'if' ? '(' : '!(') . $sub['php'] . ') && ';
    }

    function CacheBlock_CacheBlock(&$res, $sub)
    {
        $res['php'] .= $sub['php'];
    }

    function CacheBlock_UncachedBlock(&$res, $sub)
    {
        $res['php'] .= $sub['php'];
    }

    function CacheBlock_CacheBlockTemplate(&$res, $sub)
    {
        // Get the block counter
        $block = ++$res['subblocks'];
        // Build the key for this block from the global key (evaluated in a closure within the template),
        // the passed cache key, the block index, and the sha hash of the template.
        $res['php'] .= '$keyExpression = function() use ($scope, $cache) {' . PHP_EOL;
        $res['php'] .= '$val = \'\';' . PHP_EOL;
        if ($globalKey = SSViewer::config()->get('global_key')) {
            // Embed the code necessary to evaluate the globalKey directly into the template,
            // so that SSTemplateParser only needs to be called during template regeneration.
            // Warning: If the global key is changed, it's necessary to flush the template cache.
            $parser = Injector::inst()->get(__CLASS__, false);
            $result = $parser->compileString($globalKey, '', false, false);
            if (!$result) {
                throw new SSTemplateParseException('Unexpected problem parsing template', $parser);
            }
            $res['php'] .= $result . PHP_EOL;
        }
        $res['php'] .= 'return $val;' . PHP_EOL;
        $res['php'] .= '};' . PHP_EOL;
        $key = 'sha1($keyExpression())' // Global key
            . '.\'_' . sha1($sub['php'] ?? '') // sha of template
            . (isset($res['key']) && $res['key'] ? "_'.sha1(".$res['key'].")" : "'") // Passed key
            . ".'_$block'"; // block index
        // Get any condition
        $condition = isset($res['condition']) ? $res['condition'] : '';

        $res['php'] .= 'if ('.$condition.'($partial = $cache->get('.$key.'))) $val .= $partial;' . PHP_EOL;
        $res['php'] .= 'else { $oldval = $val; $val = "";' . PHP_EOL;
        $res['php'] .= $sub['php'] . PHP_EOL;
        $res['php'] .= $condition . ' $cache->set('.$key.', $val); $val = $oldval . $val;' . PHP_EOL;
        $res['php'] .= '}';
    }

    /* OldTPart: "_t" N "(" N QuotedString (N "," N CallArguments)? N ")" N (";")? */
    protected $match_OldTPart_typestack = array('OldTPart');
    function match_OldTPart ($stack = array()) {
    	$matchrule = "OldTPart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_530 = NULL;
    	do {
    		if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_530 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_530 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    			$this->pos += 1;
    			$result["text"] .= '(';
    		}
    		else { $_530 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_530 = FALSE; break; }
    		$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_530 = FALSE; break; }
    		$res_523 = $result;
    		$pos_523 = $this->pos;
    		$_522 = NULL;
    		do {
    			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_522 = FALSE; break; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    				$this->pos += 1;
    				$result["text"] .= ',';
    			}
    			else { $_522 = FALSE; break; }
    			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_522 = FALSE; break; }
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_522 = FALSE; break; }
    			$_522 = TRUE; break;
    		}
    		while(0);
    		if( $_522 === FALSE) {
    			$result = $res_523;
    			$this->pos = $pos_523;
    			unset( $res_523 );
    			unset( $pos_523 );
    		}
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_530 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    			$this->pos += 1;
    			$result["text"] .= ')';
    		}
    		else { $_530 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_530 = FALSE; break; }
    		$res_529 = $result;
    		$pos_529 = $this->pos;
    		$_528 = NULL;
    		do {
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ';') {
    				$this->pos += 1;
    				$result["text"] .= ';';
    			}
    			else { $_528 = FALSE; break; }
    			$_528 = TRUE; break;
    		}
    		while(0);
    		if( $_528 === FALSE) {
    			$result = $res_529;
    			$this->pos = $pos_529;
    			unset( $res_529 );
    			unset( $pos_529 );
    		}
    		$_530 = TRUE; break;
    	}
    	while(0);
    	if( $_530 === TRUE ) { return $this->finalise($result); }
    	if( $_530 === FALSE) { return FALSE; }
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



    function OldTPart__construct(&$res)
    {
        $res['php'] = "_t(";
    }

    function OldTPart_QuotedString(&$res, $sub)
    {
        $entity = $sub['String']['text'];
        if (strpos($entity ?? '', '.') === false) {
            $res['php'] .= "\$scope->XML_val('I18NNamespace').'.$entity'";
        } else {
            $res['php'] .= "'$entity'";
        }
    }

    function OldTPart_CallArguments(&$res, $sub)
    {
        $res['php'] .= ',' . $sub['php'];
    }

    function OldTPart__finalise(&$res)
    {
        $res['php'] .= ')';
    }

    /* OldTTag: "<%" < OldTPart > "%>" */
    protected $match_OldTTag_typestack = array('OldTTag');
    function match_OldTTag ($stack = array()) {
    	$matchrule = "OldTTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_538 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_538 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_538 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_538 = FALSE; break; }
    		$_538 = TRUE; break;
    	}
    	while(0);
    	if( $_538 === TRUE ) { return $this->finalise($result); }
    	if( $_538 === FALSE) { return FALSE; }
    }



    function OldTTag_OldTPart(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>" */
    protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
    function match_OldSprintfTag ($stack = array()) {
    	$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_555 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    			$this->pos += 1;
    			$result["text"] .= '(';
    		}
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    			$this->pos += 1;
    			$result["text"] .= ',';
    		}
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    			$this->pos += 1;
    			$result["text"] .= ')';
    		}
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		$_555 = TRUE; break;
    	}
    	while(0);
    	if( $_555 === TRUE ) { return $this->finalise($result); }
    	if( $_555 === FALSE) { return FALSE; }
    }



    function OldSprintfTag__construct(&$res)
    {
        $res['php'] = "sprintf(";
    }

    function OldSprintfTag_OldTPart(&$res, $sub)
    {
        $res['php'] .= $sub['php'];
    }

    function OldSprintfTag_CallArguments(&$res, $sub)
    {
        $res['php'] .= ',' . $sub['php'] . ')';
    }

    /* OldI18NTag: OldSprintfTag | OldTTag */
    protected $match_OldI18NTag_typestack = array('OldI18NTag');
    function match_OldI18NTag ($stack = array()) {
    	$matchrule = "OldI18NTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_560 = NULL;
    	do {
    		$res_557 = $result;
    		$pos_557 = $this->pos;
    		$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_560 = TRUE; break;
    		}
    		$result = $res_557;
    		$this->pos = $pos_557;
    		$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_560 = TRUE; break;
    		}
    		$result = $res_557;
    		$this->pos = $pos_557;
    		$_560 = FALSE; break;
    	}
    	while(0);
    	if( $_560 === TRUE ) { return $this->finalise($result); }
    	if( $_560 === FALSE) { return FALSE; }
    }



    function OldI18NTag_STR(&$res, $sub)
    {
        $res['php'] = '$val .= ' . $sub['php'] . ';';
    }

    /* NamedArgument: Name:Word "=" Value:Argument */
    protected $match_NamedArgument_typestack = array('NamedArgument');
    function match_NamedArgument ($stack = array()) {
    	$matchrule = "NamedArgument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_565 = NULL;
    	do {
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Name" );
    		}
    		else { $_565 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '=') {
    			$this->pos += 1;
    			$result["text"] .= '=';
    		}
    		else { $_565 = FALSE; break; }
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Value" );
    		}
    		else { $_565 = FALSE; break; }
    		$_565 = TRUE; break;
    	}
    	while(0);
    	if( $_565 === TRUE ) { return $this->finalise($result); }
    	if( $_565 === FALSE) { return FALSE; }
    }



    function NamedArgument_Name(&$res, $sub)
    {
        $res['php'] = "'" . $sub['text'] . "' => ";
    }

    function NamedArgument_Value(&$res, $sub)
    {
        switch ($sub['ArgumentMode']) {
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

    /* Include: "<%" < "include" < Template:NamespacedWord < (NamedArgument ( < "," < NamedArgument )*)? > "%>" */
    protected $match_Include_typestack = array('Include');
    function match_Include ($stack = array()) {
    	$matchrule = "Include"; $result = $this->construct($matchrule, $matchrule, null);
    	$_584 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_584 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'include' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_584 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'NamespacedWord'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else { $_584 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_581 = $result;
    		$pos_581 = $this->pos;
    		$_580 = NULL;
    		do {
    			$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_580 = FALSE; break; }
    			while (true) {
    				$res_579 = $result;
    				$pos_579 = $this->pos;
    				$_578 = NULL;
    				do {
    					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    					if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    						$this->pos += 1;
    						$result["text"] .= ',';
    					}
    					else { $_578 = FALSE; break; }
    					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    					$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    					}
    					else { $_578 = FALSE; break; }
    					$_578 = TRUE; break;
    				}
    				while(0);
    				if( $_578 === FALSE) {
    					$result = $res_579;
    					$this->pos = $pos_579;
    					unset( $res_579 );
    					unset( $pos_579 );
    					break;
    				}
    			}
    			$_580 = TRUE; break;
    		}
    		while(0);
    		if( $_580 === FALSE) {
    			$result = $res_581;
    			$this->pos = $pos_581;
    			unset( $res_581 );
    			unset( $pos_581 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_584 = FALSE; break; }
    		$_584 = TRUE; break;
    	}
    	while(0);
    	if( $_584 === TRUE ) { return $this->finalise($result); }
    	if( $_584 === FALSE) { return FALSE; }
    }



    function Include__construct(&$res)
    {
        $res['arguments'] = [];
    }

    function Include_Template(&$res, $sub)
    {
        $res['template'] = "'" . $sub['text'] . "'";
    }

    function Include_NamedArgument(&$res, $sub)
    {
        $res['arguments'][] = $sub['php'];
    }

    function Include__finalise(&$res)
    {
        $template = $res['template'];
        $arguments = $res['arguments'];

        // Note: 'type' here is important to disable subTemplates in SSViewer::getSubtemplateFor()
        $res['php'] = '$val .= \\SilverStripe\\View\\SSViewer::execute_template([["type" => "Includes", '.$template.'], '.$template.'], $scope->getItem(), [' .
            implode(',', $arguments)."], \$scope, true);\n";

        if ($this->includeDebuggingComments) { // Add include filename comments on dev sites
            $res['php'] =
                '$val .= \'<!-- include '.addslashes($template ?? '').' -->\';'. "\n".
                $res['php'].
                '$val .= \'<!-- end include '.addslashes($template ?? '').' -->\';'. "\n";
        }
    }

    /* BlockArguments: :Argument ( < "," < :Argument)* */
    protected $match_BlockArguments_typestack = array('BlockArguments');
    function match_BlockArguments ($stack = array()) {
    	$matchrule = "BlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
    	$_593 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Argument" );
    		}
    		else { $_593 = FALSE; break; }
    		while (true) {
    			$res_592 = $result;
    			$pos_592 = $this->pos;
    			$_591 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_591 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "Argument" );
    				}
    				else { $_591 = FALSE; break; }
    				$_591 = TRUE; break;
    			}
    			while(0);
    			if( $_591 === FALSE) {
    				$result = $res_592;
    				$this->pos = $pos_592;
    				unset( $res_592 );
    				unset( $pos_592 );
    				break;
    			}
    		}
    		$_593 = TRUE; break;
    	}
    	while(0);
    	if( $_593 === TRUE ) { return $this->finalise($result); }
    	if( $_593 === FALSE) { return FALSE; }
    }


    /* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")]) */
    protected $match_NotBlockTag_typestack = array('NotBlockTag');
    function match_NotBlockTag ($stack = array()) {
    	$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_631 = NULL;
    	do {
    		$res_595 = $result;
    		$pos_595 = $this->pos;
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_631 = TRUE; break;
    		}
    		$result = $res_595;
    		$this->pos = $pos_595;
    		$_629 = NULL;
    		do {
    			$_626 = NULL;
    			do {
    				$_624 = NULL;
    				do {
    					$res_597 = $result;
    					$pos_597 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_624 = TRUE; break;
    					}
    					$result = $res_597;
    					$this->pos = $pos_597;
    					$_622 = NULL;
    					do {
    						$res_599 = $result;
    						$pos_599 = $this->pos;
    						if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
    							$result["text"] .= $subres;
    							$_622 = TRUE; break;
    						}
    						$result = $res_599;
    						$this->pos = $pos_599;
    						$_620 = NULL;
    						do {
    							$res_601 = $result;
    							$pos_601 = $this->pos;
    							if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
    								$result["text"] .= $subres;
    								$_620 = TRUE; break;
    							}
    							$result = $res_601;
    							$this->pos = $pos_601;
    							$_618 = NULL;
    							do {
    								$res_603 = $result;
    								$pos_603 = $this->pos;
    								if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
    									$result["text"] .= $subres;
    									$_618 = TRUE; break;
    								}
    								$result = $res_603;
    								$this->pos = $pos_603;
    								$_616 = NULL;
    								do {
    									$res_605 = $result;
    									$pos_605 = $this->pos;
    									if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    										$_616 = TRUE; break;
    									}
    									$result = $res_605;
    									$this->pos = $pos_605;
    									$_614 = NULL;
    									do {
    										$res_607 = $result;
    										$pos_607 = $this->pos;
    										if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    											$result["text"] .= $subres;
    											$_614 = TRUE; break;
    										}
    										$result = $res_607;
    										$this->pos = $pos_607;
    										$_612 = NULL;
    										do {
    											$res_609 = $result;
    											$pos_609 = $this->pos;
    											if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    												$result["text"] .= $subres;
    												$_612 = TRUE; break;
    											}
    											$result = $res_609;
    											$this->pos = $pos_609;
    											if (( $subres = $this->literal( 'include' ) ) !== FALSE) {
    												$result["text"] .= $subres;
    												$_612 = TRUE; break;
    											}
    											$result = $res_609;
    											$this->pos = $pos_609;
    											$_612 = FALSE; break;
    										}
    										while(0);
    										if( $_612 === TRUE ) { $_614 = TRUE; break; }
    										$result = $res_607;
    										$this->pos = $pos_607;
    										$_614 = FALSE; break;
    									}
    									while(0);
    									if( $_614 === TRUE ) { $_616 = TRUE; break; }
    									$result = $res_605;
    									$this->pos = $pos_605;
    									$_616 = FALSE; break;
    								}
    								while(0);
    								if( $_616 === TRUE ) { $_618 = TRUE; break; }
    								$result = $res_603;
    								$this->pos = $pos_603;
    								$_618 = FALSE; break;
    							}
    							while(0);
    							if( $_618 === TRUE ) { $_620 = TRUE; break; }
    							$result = $res_601;
    							$this->pos = $pos_601;
    							$_620 = FALSE; break;
    						}
    						while(0);
    						if( $_620 === TRUE ) { $_622 = TRUE; break; }
    						$result = $res_599;
    						$this->pos = $pos_599;
    						$_622 = FALSE; break;
    					}
    					while(0);
    					if( $_622 === TRUE ) { $_624 = TRUE; break; }
    					$result = $res_597;
    					$this->pos = $pos_597;
    					$_624 = FALSE; break;
    				}
    				while(0);
    				if( $_624 === FALSE) { $_626 = FALSE; break; }
    				$_626 = TRUE; break;
    			}
    			while(0);
    			if( $_626 === FALSE) { $_629 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_629 = FALSE; break; }
    			$_629 = TRUE; break;
    		}
    		while(0);
    		if( $_629 === TRUE ) { $_631 = TRUE; break; }
    		$result = $res_595;
    		$this->pos = $pos_595;
    		$_631 = FALSE; break;
    	}
    	while(0);
    	if( $_631 === TRUE ) { return $this->finalise($result); }
    	if( $_631 === FALSE) { return FALSE; }
    }


    /* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher?
    '<%' < 'end_' '$BlockName' > '%>' */
    protected $match_ClosedBlock_typestack = array('ClosedBlock');
    function match_ClosedBlock ($stack = array()) {
    	$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
    	$_651 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_651 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_635 = $result;
    		$pos_635 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_635;
    			$this->pos = $pos_635;
    			$_651 = FALSE; break;
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
    		else { $_651 = FALSE; break; }
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
    		$stack[] = $result; $result = $this->construct( $matchrule, "Zap" ); 
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Zap' );
    		}
    		else {
    			$result = array_pop($stack);
    			$_651 = FALSE; break;
    		}
    		$res_644 = $result;
    		$pos_644 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_644;
    			$this->pos = $pos_644;
    			unset( $res_644 );
    			unset( $pos_644 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_651 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_651 = FALSE; break; }
    		if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_651 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_651 = FALSE; break; }
    		$_651 = TRUE; break;
    	}
    	while(0);
    	if( $_651 === TRUE ) { return $this->finalise($result); }
    	if( $_651 === FALSE) { return FALSE; }
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

    function ClosedBlock__construct(&$res)
    {
        $res['ArgumentCount'] = 0;
    }

    function ClosedBlock_BlockArguments(&$res, $sub)
    {
        if (isset($sub['Argument']['ArgumentMode'])) {
            $res['Arguments'] = [$sub['Argument']];
            $res['ArgumentCount'] = 1;
        } else {
            $res['Arguments'] = $sub['Argument'];
            $res['ArgumentCount'] = count($res['Arguments'] ?? []);
        }
    }

    function ClosedBlock__finalise(&$res)
    {
        $blockname = $res['BlockName']['text'];

        $method = 'ClosedBlock_Handle_'.$blockname;
        if (method_exists($this, $method ?? '')) {
            $res['php'] = $this->$method($res);
        } elseif (isset($this->closedBlocks[$blockname])) {
            $res['php'] = call_user_func($this->closedBlocks[$blockname], $res);
        } else {
            throw new SSTemplateParseException('Unknown closed block "'.$blockname.'" encountered. Perhaps you are ' .
            'not supposed to close this block, or have mis-spelled it?', $this);
        }
    }

    /**
     * This is an example of a block handler function. This one handles the loop tag.
     */
    function ClosedBlock_Handle_Loop(&$res)
    {
        if ($res['ArgumentCount'] > 1) {
            throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one ' .
                'argument only.', $this);
        }

        //loop without arguments loops on the current scope
        if ($res['ArgumentCount'] == 0) {
            $on = '$scope->obj(\'Up\', null)->obj(\'Foo\', null)';
        } else {    //loop in the normal way
            $arg = $res['Arguments'][0];
            if ($arg['ArgumentMode'] == 'string') {
                throw new SSTemplateParseException('Control block cant take string as argument.', $this);
            }
            $on = str_replace(
                '$$FINAL',
                'obj',
                ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']
            );
        }

        return
            $on . '; $scope->pushScope(); while (($key = $scope->next()) !== false) {' . PHP_EOL .
                $res['Template']['php'] . PHP_EOL .
            '}; $scope->popScope(); ';
    }

    /**
     * The closed block handler for with blocks
     */
    function ClosedBlock_Handle_With(&$res)
    {
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
    	$_664 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_664 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_655 = $result;
    		$pos_655 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_655;
    			$this->pos = $pos_655;
    			$_664 = FALSE; break;
    		}
    		else {
    			$result = $res_655;
    			$this->pos = $pos_655;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "BlockName" );
    		}
    		else { $_664 = FALSE; break; }
    		$res_661 = $result;
    		$pos_661 = $this->pos;
    		$_660 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_660 = FALSE; break; }
    			$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "BlockArguments" );
    			}
    			else { $_660 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_660 = FALSE; break; }
    			$_660 = TRUE; break;
    		}
    		while(0);
    		if( $_660 === FALSE) {
    			$result = $res_661;
    			$this->pos = $pos_661;
    			unset( $res_661 );
    			unset( $pos_661 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_664 = FALSE; break; }
    		$_664 = TRUE; break;
    	}
    	while(0);
    	if( $_664 === TRUE ) { return $this->finalise($result); }
    	if( $_664 === FALSE) { return FALSE; }
    }



    function OpenBlock__construct(&$res)
    {
        $res['ArgumentCount'] = 0;
    }

    function OpenBlock_BlockArguments(&$res, $sub)
    {
        if (isset($sub['Argument']['ArgumentMode'])) {
            $res['Arguments'] = [$sub['Argument']];
            $res['ArgumentCount'] = 1;
        } else {
            $res['Arguments'] = $sub['Argument'];
            $res['ArgumentCount'] = count($res['Arguments'] ?? []);
        }
    }

    function OpenBlock__finalise(&$res)
    {
        $blockname = $res['BlockName']['text'];

        $method = 'OpenBlock_Handle_'.$blockname;
        if (method_exists($this, $method ?? '')) {
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
    function OpenBlock_Handle_Debug(&$res)
    {
        if ($res['ArgumentCount'] == 0) {
            return '$scope->debug();';
        } elseif ($res['ArgumentCount'] == 1) {
            $arg = $res['Arguments'][0];

            if ($arg['ArgumentMode'] == 'string') {
                return 'Debug::show('.$arg['php'].');';
            }

            $php = ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php'];
            return '$val .= Debug::show('.str_replace('FINALGET!', 'cachedCall', $php ?? '').');';
        } else {
            throw new SSTemplateParseException('Debug takes 0 or 1 argument only.', $this);
        }
    }

    /**
     * This is an open block handler, for the <% base_tag %> tag
     */
    function OpenBlock_Handle_Base_tag(&$res)
    {
        if ($res['ArgumentCount'] != 0) {
            throw new SSTemplateParseException('Base_tag takes no arguments', $this);
        }
        return '$val .= \\SilverStripe\\View\\SSViewer::get_base_tag($val);';
    }

    /**
     * This is an open block handler, for the <% current_page %> tag
     */
    function OpenBlock_Handle_Current_page(&$res)
    {
        if ($res['ArgumentCount'] != 0) {
            throw new SSTemplateParseException('Current_page takes no arguments', $this);
        }
        return '$val .= $_SERVER[SCRIPT_URL];';
    }

    /* MismatchedEndBlock: '<%' < 'end_' :Word > '%>' */
    protected $match_MismatchedEndBlock_typestack = array('MismatchedEndBlock');
    function match_MismatchedEndBlock ($stack = array()) {
    	$matchrule = "MismatchedEndBlock"; $result = $this->construct($matchrule, $matchrule, null);
    	$_672 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_672 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_672 = FALSE; break; }
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Word" );
    		}
    		else { $_672 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_672 = FALSE; break; }
    		$_672 = TRUE; break;
    	}
    	while(0);
    	if( $_672 === TRUE ) { return $this->finalise($result); }
    	if( $_672 === FALSE) { return FALSE; }
    }



    function MismatchedEndBlock__finalise(&$res)
    {
        $blockname = $res['Word']['text'];
        throw new SSTemplateParseException('Unexpected close tag end_' . $blockname .
            ' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
    }

    /* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
    protected $match_MalformedOpenTag_typestack = array('MalformedOpenTag');
    function match_MalformedOpenTag ($stack = array()) {
    	$matchrule = "MalformedOpenTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_687 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_687 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_676 = $result;
    		$pos_676 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_676;
    			$this->pos = $pos_676;
    			$_687 = FALSE; break;
    		}
    		else {
    			$result = $res_676;
    			$this->pos = $pos_676;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Tag" );
    		}
    		else { $_687 = FALSE; break; }
    		$res_686 = $result;
    		$pos_686 = $this->pos;
    		$_685 = NULL;
    		do {
    			$res_682 = $result;
    			$pos_682 = $this->pos;
    			$_681 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_681 = FALSE; break; }
    				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "BlockArguments" );
    				}
    				else { $_681 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_681 = FALSE; break; }
    				$_681 = TRUE; break;
    			}
    			while(0);
    			if( $_681 === FALSE) {
    				$result = $res_682;
    				$this->pos = $pos_682;
    				unset( $res_682 );
    				unset( $pos_682 );
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_685 = FALSE; break; }
    			$_685 = TRUE; break;
    		}
    		while(0);
    		if( $_685 === TRUE ) {
    			$result = $res_686;
    			$this->pos = $pos_686;
    			$_687 = FALSE; break;
    		}
    		if( $_685 === FALSE) {
    			$result = $res_686;
    			$this->pos = $pos_686;
    		}
    		$_687 = TRUE; break;
    	}
    	while(0);
    	if( $_687 === TRUE ) { return $this->finalise($result); }
    	if( $_687 === FALSE) { return FALSE; }
    }



    function MalformedOpenTag__finalise(&$res)
    {
        $tag = $res['Tag']['text'];
        throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
    }

    /* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
    protected $match_MalformedCloseTag_typestack = array('MalformedCloseTag');
    function match_MalformedCloseTag ($stack = array()) {
    	$matchrule = "MalformedCloseTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_699 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_699 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
    		$_693 = NULL;
    		do {
    			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_693 = FALSE; break; }
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Word" );
    			}
    			else { $_693 = FALSE; break; }
    			$_693 = TRUE; break;
    		}
    		while(0);
    		if( $_693 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Tag' );
    		}
    		if( $_693 === FALSE) {
    			$result = array_pop($stack);
    			$_699 = FALSE; break;
    		}
    		$res_698 = $result;
    		$pos_698 = $this->pos;
    		$_697 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_697 = FALSE; break; }
    			$_697 = TRUE; break;
    		}
    		while(0);
    		if( $_697 === TRUE ) {
    			$result = $res_698;
    			$this->pos = $pos_698;
    			$_699 = FALSE; break;
    		}
    		if( $_697 === FALSE) {
    			$result = $res_698;
    			$this->pos = $pos_698;
    		}
    		$_699 = TRUE; break;
    	}
    	while(0);
    	if( $_699 === TRUE ) { return $this->finalise($result); }
    	if( $_699 === FALSE) { return FALSE; }
    }



    function MalformedCloseTag__finalise(&$res)
    {
        $tag = $res['Tag']['text'];
        throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an " .
            "argument to one?", $this);
    }

    /* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
    protected $match_MalformedBlock_typestack = array('MalformedBlock');
    function match_MalformedBlock ($stack = array()) {
    	$matchrule = "MalformedBlock"; $result = $this->construct($matchrule, $matchrule, null);
    	$_704 = NULL;
    	do {
    		$res_701 = $result;
    		$pos_701 = $this->pos;
    		$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_704 = TRUE; break;
    		}
    		$result = $res_701;
    		$this->pos = $pos_701;
    		$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_704 = TRUE; break;
    		}
    		$result = $res_701;
    		$this->pos = $pos_701;
    		$_704 = FALSE; break;
    	}
    	while(0);
    	if( $_704 === TRUE ) { return $this->finalise($result); }
    	if( $_704 === FALSE) { return FALSE; }
    }




    /* CommentWithContent: '<%--' ( !"--%>" /(?s)./ )+ '--%>' */
    protected $match_CommentWithContent_typestack = array('CommentWithContent');
    function match_CommentWithContent ($stack = array()) {
    	$matchrule = "CommentWithContent"; $result = $this->construct($matchrule, $matchrule, null);
    	$_712 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_712 = FALSE; break; }
    		$count = 0;
    		while (true) {
    			$res_710 = $result;
    			$pos_710 = $this->pos;
    			$_709 = NULL;
    			do {
    				$res_707 = $result;
    				$pos_707 = $this->pos;
    				if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$result = $res_707;
    					$this->pos = $pos_707;
    					$_709 = FALSE; break;
    				}
    				else {
    					$result = $res_707;
    					$this->pos = $pos_707;
    				}
    				if (( $subres = $this->rx( '/(?s)./' ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_709 = FALSE; break; }
    				$_709 = TRUE; break;
    			}
    			while(0);
    			if( $_709 === FALSE) {
    				$result = $res_710;
    				$this->pos = $pos_710;
    				unset( $res_710 );
    				unset( $pos_710 );
    				break;
    			}
    			$count += 1;
    		}
    		if ($count > 0) {  }
    		else { $_712 = FALSE; break; }
    		if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_712 = FALSE; break; }
    		$_712 = TRUE; break;
    	}
    	while(0);
    	if( $_712 === TRUE ) { return $this->finalise($result); }
    	if( $_712 === FALSE) { return FALSE; }
    }


    /* EmptyComment: '<%----%>' */
    protected $match_EmptyComment_typestack = array('EmptyComment');
    function match_EmptyComment ($stack = array()) {
    	$matchrule = "EmptyComment"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->literal( '<%----%>' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Comment: :EmptyComment | :CommentWithContent */
    protected $match_Comment_typestack = array('Comment');
    function match_Comment ($stack = array()) {
    	$matchrule = "Comment"; $result = $this->construct($matchrule, $matchrule, null);
    	$_718 = NULL;
    	do {
    		$res_715 = $result;
    		$pos_715 = $this->pos;
    		$matcher = 'match_'.'EmptyComment'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "EmptyComment" );
    			$_718 = TRUE; break;
    		}
    		$result = $res_715;
    		$this->pos = $pos_715;
    		$matcher = 'match_'.'CommentWithContent'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "CommentWithContent" );
    			$_718 = TRUE; break;
    		}
    		$result = $res_715;
    		$this->pos = $pos_715;
    		$_718 = FALSE; break;
    	}
    	while(0);
    	if( $_718 === TRUE ) { return $this->finalise($result); }
    	if( $_718 === FALSE) { return FALSE; }
    }



    function Comment__construct(&$res)
    {
        $res['php'] = '';
    }

    /* TopTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
    OpenBlock |  MalformedBlock | MismatchedEndBlock  | MalformedBracketInjection | Injection | Text)+ */
    protected $match_TopTemplate_typestack = array('TopTemplate','Template');
    function match_TopTemplate ($stack = array()) {
    	$matchrule = "TopTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'Template'));
    	$count = 0;
    	while (true) {
    		$res_778 = $result;
    		$pos_778 = $this->pos;
    		$_777 = NULL;
    		do {
    			$_775 = NULL;
    			do {
    				$res_720 = $result;
    				$pos_720 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_775 = TRUE; break;
    				}
    				$result = $res_720;
    				$this->pos = $pos_720;
    				$_773 = NULL;
    				do {
    					$res_722 = $result;
    					$pos_722 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_773 = TRUE; break;
    					}
    					$result = $res_722;
    					$this->pos = $pos_722;
    					$_771 = NULL;
    					do {
    						$res_724 = $result;
    						$pos_724 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_771 = TRUE; break;
    						}
    						$result = $res_724;
    						$this->pos = $pos_724;
    						$_769 = NULL;
    						do {
    							$res_726 = $result;
    							$pos_726 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_769 = TRUE; break;
    							}
    							$result = $res_726;
    							$this->pos = $pos_726;
    							$_767 = NULL;
    							do {
    								$res_728 = $result;
    								$pos_728 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_767 = TRUE; break;
    								}
    								$result = $res_728;
    								$this->pos = $pos_728;
    								$_765 = NULL;
    								do {
    									$res_730 = $result;
    									$pos_730 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_765 = TRUE; break;
    									}
    									$result = $res_730;
    									$this->pos = $pos_730;
    									$_763 = NULL;
    									do {
    										$res_732 = $result;
    										$pos_732 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_763 = TRUE; break;
    										}
    										$result = $res_732;
    										$this->pos = $pos_732;
    										$_761 = NULL;
    										do {
    											$res_734 = $result;
    											$pos_734 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_761 = TRUE; break;
    											}
    											$result = $res_734;
    											$this->pos = $pos_734;
    											$_759 = NULL;
    											do {
    												$res_736 = $result;
    												$pos_736 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_759 = TRUE; break;
    												}
    												$result = $res_736;
    												$this->pos = $pos_736;
    												$_757 = NULL;
    												do {
    													$res_738 = $result;
    													$pos_738 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_757 = TRUE; break;
    													}
    													$result = $res_738;
    													$this->pos = $pos_738;
    													$_755 = NULL;
    													do {
    														$res_740 = $result;
    														$pos_740 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_755 = TRUE; break;
    														}
    														$result = $res_740;
    														$this->pos = $pos_740;
    														$_753 = NULL;
    														do {
    															$res_742 = $result;
    															$pos_742 = $this->pos;
    															$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_753 = TRUE; break;
    															}
    															$result = $res_742;
    															$this->pos = $pos_742;
    															$_751 = NULL;
    															do {
    																$res_744 = $result;
    																$pos_744 = $this->pos;
    																$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_751 = TRUE; break;
    																}
    																$result = $res_744;
    																$this->pos = $pos_744;
    																$_749 = NULL;
    																do {
    																	$res_746 = $result;
    																	$pos_746 = $this->pos;
    																	$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																	if ($subres !== FALSE) {
    																		$this->store( $result, $subres );
    																		$_749 = TRUE; break;
    																	}
    																	$result = $res_746;
    																	$this->pos = $pos_746;
    																	$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																	if ($subres !== FALSE) {
    																		$this->store( $result, $subres );
    																		$_749 = TRUE; break;
    																	}
    																	$result = $res_746;
    																	$this->pos = $pos_746;
    																	$_749 = FALSE; break;
    																}
    																while(0);
    																if( $_749 === TRUE ) {
    																	$_751 = TRUE; break;
    																}
    																$result = $res_744;
    																$this->pos = $pos_744;
    																$_751 = FALSE; break;
    															}
    															while(0);
    															if( $_751 === TRUE ) {
    																$_753 = TRUE; break;
    															}
    															$result = $res_742;
    															$this->pos = $pos_742;
    															$_753 = FALSE; break;
    														}
    														while(0);
    														if( $_753 === TRUE ) { $_755 = TRUE; break; }
    														$result = $res_740;
    														$this->pos = $pos_740;
    														$_755 = FALSE; break;
    													}
    													while(0);
    													if( $_755 === TRUE ) { $_757 = TRUE; break; }
    													$result = $res_738;
    													$this->pos = $pos_738;
    													$_757 = FALSE; break;
    												}
    												while(0);
    												if( $_757 === TRUE ) { $_759 = TRUE; break; }
    												$result = $res_736;
    												$this->pos = $pos_736;
    												$_759 = FALSE; break;
    											}
    											while(0);
    											if( $_759 === TRUE ) { $_761 = TRUE; break; }
    											$result = $res_734;
    											$this->pos = $pos_734;
    											$_761 = FALSE; break;
    										}
    										while(0);
    										if( $_761 === TRUE ) { $_763 = TRUE; break; }
    										$result = $res_732;
    										$this->pos = $pos_732;
    										$_763 = FALSE; break;
    									}
    									while(0);
    									if( $_763 === TRUE ) { $_765 = TRUE; break; }
    									$result = $res_730;
    									$this->pos = $pos_730;
    									$_765 = FALSE; break;
    								}
    								while(0);
    								if( $_765 === TRUE ) { $_767 = TRUE; break; }
    								$result = $res_728;
    								$this->pos = $pos_728;
    								$_767 = FALSE; break;
    							}
    							while(0);
    							if( $_767 === TRUE ) { $_769 = TRUE; break; }
    							$result = $res_726;
    							$this->pos = $pos_726;
    							$_769 = FALSE; break;
    						}
    						while(0);
    						if( $_769 === TRUE ) { $_771 = TRUE; break; }
    						$result = $res_724;
    						$this->pos = $pos_724;
    						$_771 = FALSE; break;
    					}
    					while(0);
    					if( $_771 === TRUE ) { $_773 = TRUE; break; }
    					$result = $res_722;
    					$this->pos = $pos_722;
    					$_773 = FALSE; break;
    				}
    				while(0);
    				if( $_773 === TRUE ) { $_775 = TRUE; break; }
    				$result = $res_720;
    				$this->pos = $pos_720;
    				$_775 = FALSE; break;
    			}
    			while(0);
    			if( $_775 === FALSE) { $_777 = FALSE; break; }
    			$_777 = TRUE; break;
    		}
    		while(0);
    		if( $_777 === FALSE) {
    			$result = $res_778;
    			$this->pos = $pos_778;
    			unset( $res_778 );
    			unset( $pos_778 );
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
    function TopTemplate__construct(&$res)
    {
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
    		$res_817 = $result;
    		$pos_817 = $this->pos;
    		$_816 = NULL;
    		do {
    			$_814 = NULL;
    			do {
    				$res_779 = $result;
    				$pos_779 = $this->pos;
    				if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_814 = TRUE; break;
    				}
    				$result = $res_779;
    				$this->pos = $pos_779;
    				$_812 = NULL;
    				do {
    					$res_781 = $result;
    					$pos_781 = $this->pos;
    					if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_812 = TRUE; break;
    					}
    					$result = $res_781;
    					$this->pos = $pos_781;
    					$_810 = NULL;
    					do {
    						$res_783 = $result;
    						$pos_783 = $this->pos;
    						$_786 = NULL;
    						do {
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '<') {
    								$this->pos += 1;
    								$result["text"] .= '<';
    							}
    							else { $_786 = FALSE; break; }
    							$res_785 = $result;
    							$pos_785 = $this->pos;
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '%') {
    								$this->pos += 1;
    								$result["text"] .= '%';
    								$result = $res_785;
    								$this->pos = $pos_785;
    								$_786 = FALSE; break;
    							}
    							else {
    								$result = $res_785;
    								$this->pos = $pos_785;
    							}
    							$_786 = TRUE; break;
    						}
    						while(0);
    						if( $_786 === TRUE ) { $_810 = TRUE; break; }
    						$result = $res_783;
    						$this->pos = $pos_783;
    						$_808 = NULL;
    						do {
    							$res_788 = $result;
    							$pos_788 = $this->pos;
    							$_793 = NULL;
    							do {
    								if (substr($this->string ?? '',$this->pos ?? 0,1) == '$') {
    									$this->pos += 1;
    									$result["text"] .= '$';
    								}
    								else { $_793 = FALSE; break; }
    								$res_792 = $result;
    								$pos_792 = $this->pos;
    								$_791 = NULL;
    								do {
    									if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									else { $_791 = FALSE; break; }
    									$_791 = TRUE; break;
    								}
    								while(0);
    								if( $_791 === TRUE ) {
    									$result = $res_792;
    									$this->pos = $pos_792;
    									$_793 = FALSE; break;
    								}
    								if( $_791 === FALSE) {
    									$result = $res_792;
    									$this->pos = $pos_792;
    								}
    								$_793 = TRUE; break;
    							}
    							while(0);
    							if( $_793 === TRUE ) { $_808 = TRUE; break; }
    							$result = $res_788;
    							$this->pos = $pos_788;
    							$_806 = NULL;
    							do {
    								$res_795 = $result;
    								$pos_795 = $this->pos;
    								$_798 = NULL;
    								do {
    									if (substr($this->string ?? '',$this->pos ?? 0,1) == '{') {
    										$this->pos += 1;
    										$result["text"] .= '{';
    									}
    									else { $_798 = FALSE; break; }
    									$res_797 = $result;
    									$pos_797 = $this->pos;
    									if (substr($this->string ?? '',$this->pos ?? 0,1) == '$') {
    										$this->pos += 1;
    										$result["text"] .= '$';
    										$result = $res_797;
    										$this->pos = $pos_797;
    										$_798 = FALSE; break;
    									}
    									else {
    										$result = $res_797;
    										$this->pos = $pos_797;
    									}
    									$_798 = TRUE; break;
    								}
    								while(0);
    								if( $_798 === TRUE ) { $_806 = TRUE; break; }
    								$result = $res_795;
    								$this->pos = $pos_795;
    								$_804 = NULL;
    								do {
    									if (( $subres = $this->literal( '{$' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									else { $_804 = FALSE; break; }
    									$res_803 = $result;
    									$pos_803 = $this->pos;
    									$_802 = NULL;
    									do {
    										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) {
    											$result["text"] .= $subres;
    										}
    										else { $_802 = FALSE; break; }
    										$_802 = TRUE; break;
    									}
    									while(0);
    									if( $_802 === TRUE ) {
    										$result = $res_803;
    										$this->pos = $pos_803;
    										$_804 = FALSE; break;
    									}
    									if( $_802 === FALSE) {
    										$result = $res_803;
    										$this->pos = $pos_803;
    									}
    									$_804 = TRUE; break;
    								}
    								while(0);
    								if( $_804 === TRUE ) { $_806 = TRUE; break; }
    								$result = $res_795;
    								$this->pos = $pos_795;
    								$_806 = FALSE; break;
    							}
    							while(0);
    							if( $_806 === TRUE ) { $_808 = TRUE; break; }
    							$result = $res_788;
    							$this->pos = $pos_788;
    							$_808 = FALSE; break;
    						}
    						while(0);
    						if( $_808 === TRUE ) { $_810 = TRUE; break; }
    						$result = $res_783;
    						$this->pos = $pos_783;
    						$_810 = FALSE; break;
    					}
    					while(0);
    					if( $_810 === TRUE ) { $_812 = TRUE; break; }
    					$result = $res_781;
    					$this->pos = $pos_781;
    					$_812 = FALSE; break;
    				}
    				while(0);
    				if( $_812 === TRUE ) { $_814 = TRUE; break; }
    				$result = $res_779;
    				$this->pos = $pos_779;
    				$_814 = FALSE; break;
    			}
    			while(0);
    			if( $_814 === FALSE) { $_816 = FALSE; break; }
    			$_816 = TRUE; break;
    		}
    		while(0);
    		if( $_816 === FALSE) {
    			$result = $res_817;
    			$this->pos = $pos_817;
    			unset( $res_817 );
    			unset( $pos_817 );
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
    function Text__finalise(&$res)
    {
        $text = $res['text'];

        // Unescape any escaped characters in the text, then put back escapes for any single quotes and backslashes
        $text = stripslashes($text ?? '');
        $text = addcslashes($text ?? '', '\'\\');

        // TODO: This is pretty ugly & gets applied on all files not just html. I wonder if we can make this
        // non-dynamically calculated
        $code = <<<'EOC'
(\SilverStripe\View\SSViewer::getRewriteHashLinksDefault()
    ? \SilverStripe\Core\Convert::raw2att( preg_replace("/^(\\/)+/", "/", $_SERVER['REQUEST_URI'] ) )
    : "")
EOC;
        // Because preg_replace replacement requires escaped slashes, addcslashes here
        $text = preg_replace(
            '/(<a[^>]+href *= *)"#/i',
            '\\1"\' . ' . addcslashes($code ?? '', '\\')  . ' . \'#',
            $text ?? ''
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
     * @param string $string The source of the template
     * @param string $templateName The name of the template, normally the filename the template source was loaded from
     * @param bool $includeDebuggingComments True is debugging comments should be included in the output
     * @param bool $topTemplate True if this is a top template, false if it's just a template
     * @return mixed|string The php that, when executed (via include or exec) will behave as per the template source
     */
    public function compileString($string, $templateName = "", $includeDebuggingComments = false, $topTemplate = true)
    {
        if (!trim($string ?? '')) {
            $code = '';
        } else {
            parent::__construct($string);

            $this->includeDebuggingComments = $includeDebuggingComments;

            // Ignore UTF8 BOM at beginning of string. TODO: Confirm this is needed, make sure SSViewer handles UTF
            // (and other encodings) properly
            if (substr($string ?? '', 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
                $this->pos = 3;
            }

            // Match the source against the parser
            if ($topTemplate) {
                $result = $this->match_TopTemplate();
            } else {
                $result = $this->match_Template();
            }
            if (!$result) {
                throw new SSTemplateParseException('Unexpected problem parsing template', $this);
            }

            // Get the result
            $code = $result['php'];
        }

        // Include top level debugging comments if desired
        if ($includeDebuggingComments && $templateName && stripos($code ?? '', "<?xml") === false) {
            $code = $this->includeDebuggingComments($code, $templateName);
        }

        return $code;
    }

    /**
     * @param string $code
     * @param string $templateName
     * @return string $code
     */
    protected function includeDebuggingComments($code, $templateName)
    {
        // If this template contains a doctype, put it right after it,
        // if not, put it after the <html> tag to avoid IE glitches
        if (stripos($code ?? '', "<!doctype") !== false) {
            $code = preg_replace('/(<!doctype[^>]*("[^"]")*[^>]*>)/im', "$1\r\n<!-- template $templateName -->", $code ?? '');
            $code .= "\r\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
        } elseif (stripos($code ?? '', "<html") !== false) {
            $code = preg_replace_callback('/(.*)(<html[^>]*>)(.*)/i', function ($matches) use ($templateName) {
                if (stripos($matches[3] ?? '', '<!--') === false && stripos($matches[3] ?? '', '-->') !== false) {
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
            }, $code ?? '');
            $code = preg_replace('/(<\/html[^>]*>)/i', "<!-- end template $templateName -->$1", $code ?? '');
        } else {
            $code = str_replace('<?php' . PHP_EOL, '<?php' . PHP_EOL . '$val .= \'<!-- template ' . $templateName .
                ' -->\';' . "\r\n", $code ?? '');
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
    public function compileFile($template)
    {
        return $this->compileString(file_get_contents($template ?? ''), $template);
    }
}
