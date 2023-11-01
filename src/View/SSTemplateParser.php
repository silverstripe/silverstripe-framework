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
        if ($res['php'] !== '') {
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

        if (isset($sub['Call']['CallArguments']) && isset($sub['Call']['CallArguments']['php'])) {
            $arguments = $sub['Call']['CallArguments']['php'];
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


    /* Null: / (null)\b /i */
    protected $match_Null_typestack = array('Null');
    function match_Null ($stack = array()) {
    	$matchrule = "Null"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ (null)\b /i' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Boolean: / (true|false)\b  /i */
    protected $match_Boolean_typestack = array('Boolean');
    function match_Boolean ($stack = array()) {
    	$matchrule = "Boolean"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ (true|false)\b  /i' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Sign: / [+-] / */
    protected $match_Sign_typestack = array('Sign');
    function match_Sign ($stack = array()) {
    	$matchrule = "Sign"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ [+-] /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Float: / [0-9]*\.?[0-9]+([eE][-+]?[0-9]+)? / */
    protected $match_Float_typestack = array('Float');
    function match_Float ($stack = array()) {
    	$matchrule = "Float"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ [0-9]*\.?[0-9]+([eE][-+]?[0-9]+)? /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Hexadecimal: / 0[xX][0-9a-fA-F]+ / */
    protected $match_Hexadecimal_typestack = array('Hexadecimal');
    function match_Hexadecimal ($stack = array()) {
    	$matchrule = "Hexadecimal"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ 0[xX][0-9a-fA-F]+ /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Octal: / 0[0-7]+ / */
    protected $match_Octal_typestack = array('Octal');
    function match_Octal ($stack = array()) {
    	$matchrule = "Octal"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ 0[0-7]+ /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Binary: / 0[bB][01]+ / */
    protected $match_Binary_typestack = array('Binary');
    function match_Binary ($stack = array()) {
    	$matchrule = "Binary"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ 0[bB][01]+ /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Decimal: / 0 | [1-9][0-9]* / */
    protected $match_Decimal_typestack = array('Decimal');
    function match_Decimal ($stack = array()) {
    	$matchrule = "Decimal"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ 0 | [1-9][0-9]* /' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* IntegerOrFloat: ( Sign )? ( Hexadecimal | Binary | Float | Octal | Decimal ) */
    protected $match_IntegerOrFloat_typestack = array('IntegerOrFloat');
    function match_IntegerOrFloat ($stack = array()) {
    	$matchrule = "IntegerOrFloat"; $result = $this->construct($matchrule, $matchrule, null);
    	$_186 = NULL;
    	do {
    		$res_166 = $result;
    		$pos_166 = $this->pos;
    		$_165 = NULL;
    		do {
    			$matcher = 'match_'.'Sign'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_165 = FALSE; break; }
    			$_165 = TRUE; break;
    		}
    		while(0);
    		if( $_165 === FALSE) {
    			$result = $res_166;
    			$this->pos = $pos_166;
    			unset( $res_166 );
    			unset( $pos_166 );
    		}
    		$_184 = NULL;
    		do {
    			$_182 = NULL;
    			do {
    				$res_167 = $result;
    				$pos_167 = $this->pos;
    				$matcher = 'match_'.'Hexadecimal'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_182 = TRUE; break;
    				}
    				$result = $res_167;
    				$this->pos = $pos_167;
    				$_180 = NULL;
    				do {
    					$res_169 = $result;
    					$pos_169 = $this->pos;
    					$matcher = 'match_'.'Binary'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_180 = TRUE; break;
    					}
    					$result = $res_169;
    					$this->pos = $pos_169;
    					$_178 = NULL;
    					do {
    						$res_171 = $result;
    						$pos_171 = $this->pos;
    						$matcher = 'match_'.'Float'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_178 = TRUE; break;
    						}
    						$result = $res_171;
    						$this->pos = $pos_171;
    						$_176 = NULL;
    						do {
    							$res_173 = $result;
    							$pos_173 = $this->pos;
    							$matcher = 'match_'.'Octal'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_176 = TRUE; break;
    							}
    							$result = $res_173;
    							$this->pos = $pos_173;
    							$matcher = 'match_'.'Decimal'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_176 = TRUE; break;
    							}
    							$result = $res_173;
    							$this->pos = $pos_173;
    							$_176 = FALSE; break;
    						}
    						while(0);
    						if( $_176 === TRUE ) { $_178 = TRUE; break; }
    						$result = $res_171;
    						$this->pos = $pos_171;
    						$_178 = FALSE; break;
    					}
    					while(0);
    					if( $_178 === TRUE ) { $_180 = TRUE; break; }
    					$result = $res_169;
    					$this->pos = $pos_169;
    					$_180 = FALSE; break;
    				}
    				while(0);
    				if( $_180 === TRUE ) { $_182 = TRUE; break; }
    				$result = $res_167;
    				$this->pos = $pos_167;
    				$_182 = FALSE; break;
    			}
    			while(0);
    			if( $_182 === FALSE) { $_184 = FALSE; break; }
    			$_184 = TRUE; break;
    		}
    		while(0);
    		if( $_184 === FALSE) { $_186 = FALSE; break; }
    		$_186 = TRUE; break;
    	}
    	while(0);
    	if( $_186 === TRUE ) { return $this->finalise($result); }
    	if( $_186 === FALSE) { return FALSE; }
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
    :Null |
    :Boolean |
    :IntegerOrFloat |
    :Lookup !(< FreeString)|
    :FreeString */
    protected $match_Argument_typestack = array('Argument');
    function match_Argument ($stack = array()) {
    	$matchrule = "Argument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_218 = NULL;
    	do {
    		$res_189 = $result;
    		$pos_189 = $this->pos;
    		$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "DollarMarkedLookup" );
    			$_218 = TRUE; break;
    		}
    		$result = $res_189;
    		$this->pos = $pos_189;
    		$_216 = NULL;
    		do {
    			$res_191 = $result;
    			$pos_191 = $this->pos;
    			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "QuotedString" );
    				$_216 = TRUE; break;
    			}
    			$result = $res_191;
    			$this->pos = $pos_191;
    			$_214 = NULL;
    			do {
    				$res_193 = $result;
    				$pos_193 = $this->pos;
    				$matcher = 'match_'.'Null'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "Null" );
    					$_214 = TRUE; break;
    				}
    				$result = $res_193;
    				$this->pos = $pos_193;
    				$_212 = NULL;
    				do {
    					$res_195 = $result;
    					$pos_195 = $this->pos;
    					$matcher = 'match_'.'Boolean'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "Boolean" );
    						$_212 = TRUE; break;
    					}
    					$result = $res_195;
    					$this->pos = $pos_195;
    					$_210 = NULL;
    					do {
    						$res_197 = $result;
    						$pos_197 = $this->pos;
    						$matcher = 'match_'.'IntegerOrFloat'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres, "IntegerOrFloat" );
    							$_210 = TRUE; break;
    						}
    						$result = $res_197;
    						$this->pos = $pos_197;
    						$_208 = NULL;
    						do {
    							$res_199 = $result;
    							$pos_199 = $this->pos;
    							$_205 = NULL;
    							do {
    								$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres, "Lookup" );
    								}
    								else { $_205 = FALSE; break; }
    								$res_204 = $result;
    								$pos_204 = $this->pos;
    								$_203 = NULL;
    								do {
    									if (( $subres = $this->whitespace(  ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    									}
    									else { $_203 = FALSE; break; }
    									$_203 = TRUE; break;
    								}
    								while(0);
    								if( $_203 === TRUE ) {
    									$result = $res_204;
    									$this->pos = $pos_204;
    									$_205 = FALSE; break;
    								}
    								if( $_203 === FALSE) {
    									$result = $res_204;
    									$this->pos = $pos_204;
    								}
    								$_205 = TRUE; break;
    							}
    							while(0);
    							if( $_205 === TRUE ) { $_208 = TRUE; break; }
    							$result = $res_199;
    							$this->pos = $pos_199;
    							$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres, "FreeString" );
    								$_208 = TRUE; break;
    							}
    							$result = $res_199;
    							$this->pos = $pos_199;
    							$_208 = FALSE; break;
    						}
    						while(0);
    						if( $_208 === TRUE ) { $_210 = TRUE; break; }
    						$result = $res_197;
    						$this->pos = $pos_197;
    						$_210 = FALSE; break;
    					}
    					while(0);
    					if( $_210 === TRUE ) { $_212 = TRUE; break; }
    					$result = $res_195;
    					$this->pos = $pos_195;
    					$_212 = FALSE; break;
    				}
    				while(0);
    				if( $_212 === TRUE ) { $_214 = TRUE; break; }
    				$result = $res_193;
    				$this->pos = $pos_193;
    				$_214 = FALSE; break;
    			}
    			while(0);
    			if( $_214 === TRUE ) { $_216 = TRUE; break; }
    			$result = $res_191;
    			$this->pos = $pos_191;
    			$_216 = FALSE; break;
    		}
    		while(0);
    		if( $_216 === TRUE ) { $_218 = TRUE; break; }
    		$result = $res_189;
    		$this->pos = $pos_189;
    		$_218 = FALSE; break;
    	}
    	while(0);
    	if( $_218 === TRUE ) { return $this->finalise($result); }
    	if( $_218 === FALSE) { return FALSE; }
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

    function Argument_Null(&$res, $sub)
    {
        $res['ArgumentMode'] = 'string';
        $res['php'] = $sub['text'];
    }

    function Argument_Boolean(&$res, $sub)
    {
        $res['ArgumentMode'] = 'string';
        $res['php'] = $sub['text'];
    }

    function Argument_IntegerOrFloat(&$res, $sub)
    {
        $res['ArgumentMode'] = 'string';
        $res['php'] = $sub['text'];
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
    	$_243 = NULL;
    	do {
    		$res_220 = $result;
    		$pos_220 = $this->pos;
    		if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_243 = TRUE; break;
    		}
    		$result = $res_220;
    		$this->pos = $pos_220;
    		$_241 = NULL;
    		do {
    			$res_222 = $result;
    			$pos_222 = $this->pos;
    			if (( $subres = $this->literal( '==' ) ) !== FALSE) {
    				$result["text"] .= $subres;
    				$_241 = TRUE; break;
    			}
    			$result = $res_222;
    			$this->pos = $pos_222;
    			$_239 = NULL;
    			do {
    				$res_224 = $result;
    				$pos_224 = $this->pos;
    				if (( $subres = $this->literal( '>=' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_239 = TRUE; break;
    				}
    				$result = $res_224;
    				$this->pos = $pos_224;
    				$_237 = NULL;
    				do {
    					$res_226 = $result;
    					$pos_226 = $this->pos;
    					if (substr($this->string ?? '',$this->pos ?? 0,1) == '>') {
    						$this->pos += 1;
    						$result["text"] .= '>';
    						$_237 = TRUE; break;
    					}
    					$result = $res_226;
    					$this->pos = $pos_226;
    					$_235 = NULL;
    					do {
    						$res_228 = $result;
    						$pos_228 = $this->pos;
    						if (( $subres = $this->literal( '<=' ) ) !== FALSE) {
    							$result["text"] .= $subres;
    							$_235 = TRUE; break;
    						}
    						$result = $res_228;
    						$this->pos = $pos_228;
    						$_233 = NULL;
    						do {
    							$res_230 = $result;
    							$pos_230 = $this->pos;
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '<') {
    								$this->pos += 1;
    								$result["text"] .= '<';
    								$_233 = TRUE; break;
    							}
    							$result = $res_230;
    							$this->pos = $pos_230;
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '=') {
    								$this->pos += 1;
    								$result["text"] .= '=';
    								$_233 = TRUE; break;
    							}
    							$result = $res_230;
    							$this->pos = $pos_230;
    							$_233 = FALSE; break;
    						}
    						while(0);
    						if( $_233 === TRUE ) { $_235 = TRUE; break; }
    						$result = $res_228;
    						$this->pos = $pos_228;
    						$_235 = FALSE; break;
    					}
    					while(0);
    					if( $_235 === TRUE ) { $_237 = TRUE; break; }
    					$result = $res_226;
    					$this->pos = $pos_226;
    					$_237 = FALSE; break;
    				}
    				while(0);
    				if( $_237 === TRUE ) { $_239 = TRUE; break; }
    				$result = $res_224;
    				$this->pos = $pos_224;
    				$_239 = FALSE; break;
    			}
    			while(0);
    			if( $_239 === TRUE ) { $_241 = TRUE; break; }
    			$result = $res_222;
    			$this->pos = $pos_222;
    			$_241 = FALSE; break;
    		}
    		while(0);
    		if( $_241 === TRUE ) { $_243 = TRUE; break; }
    		$result = $res_220;
    		$this->pos = $pos_220;
    		$_243 = FALSE; break;
    	}
    	while(0);
    	if( $_243 === TRUE ) { return $this->finalise($result); }
    	if( $_243 === FALSE) { return FALSE; }
    }


    /* Comparison: Argument < ComparisonOperator > Argument */
    protected $match_Comparison_typestack = array('Comparison');
    function match_Comparison ($stack = array()) {
    	$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
    	$_250 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_250 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_250 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_250 = FALSE; break; }
    		$_250 = TRUE; break;
    	}
    	while(0);
    	if( $_250 === TRUE ) { return $this->finalise($result); }
    	if( $_250 === FALSE) { return FALSE; }
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
    	$_257 = NULL;
    	do {
    		$res_255 = $result;
    		$pos_255 = $this->pos;
    		$_254 = NULL;
    		do {
    			$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
    			if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
    				$result["text"] .= $subres;
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Not' );
    			}
    			else {
    				$result = array_pop($stack);
    				$_254 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$_254 = TRUE; break;
    		}
    		while(0);
    		if( $_254 === FALSE) {
    			$result = $res_255;
    			$this->pos = $pos_255;
    			unset( $res_255 );
    			unset( $pos_255 );
    		}
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_257 = FALSE; break; }
    		$_257 = TRUE; break;
    	}
    	while(0);
    	if( $_257 === TRUE ) { return $this->finalise($result); }
    	if( $_257 === FALSE) { return FALSE; }
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
            $res['php'] .= str_replace('$$FINAL', 'hasValue', $php ?? '');
        }
    }

    /* IfArgumentPortion: Comparison | PresenceCheck */
    protected $match_IfArgumentPortion_typestack = array('IfArgumentPortion');
    function match_IfArgumentPortion ($stack = array()) {
    	$matchrule = "IfArgumentPortion"; $result = $this->construct($matchrule, $matchrule, null);
    	$_262 = NULL;
    	do {
    		$res_259 = $result;
    		$pos_259 = $this->pos;
    		$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_262 = TRUE; break;
    		}
    		$result = $res_259;
    		$this->pos = $pos_259;
    		$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_262 = TRUE; break;
    		}
    		$result = $res_259;
    		$this->pos = $pos_259;
    		$_262 = FALSE; break;
    	}
    	while(0);
    	if( $_262 === TRUE ) { return $this->finalise($result); }
    	if( $_262 === FALSE) { return FALSE; }
    }



    function IfArgumentPortion_STR(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* BooleanOperator: "||" | "&&" */
    protected $match_BooleanOperator_typestack = array('BooleanOperator');
    function match_BooleanOperator ($stack = array()) {
    	$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
    	$_267 = NULL;
    	do {
    		$res_264 = $result;
    		$pos_264 = $this->pos;
    		if (( $subres = $this->literal( '||' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_267 = TRUE; break;
    		}
    		$result = $res_264;
    		$this->pos = $pos_264;
    		if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_267 = TRUE; break;
    		}
    		$result = $res_264;
    		$this->pos = $pos_264;
    		$_267 = FALSE; break;
    	}
    	while(0);
    	if( $_267 === TRUE ) { return $this->finalise($result); }
    	if( $_267 === FALSE) { return FALSE; }
    }


    /* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
    protected $match_IfArgument_typestack = array('IfArgument');
    function match_IfArgument ($stack = array()) {
    	$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_276 = NULL;
    	do {
    		$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgumentPortion" );
    		}
    		else { $_276 = FALSE; break; }
    		while (true) {
    			$res_275 = $result;
    			$pos_275 = $this->pos;
    			$_274 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "BooleanOperator" );
    				}
    				else { $_274 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "IfArgumentPortion" );
    				}
    				else { $_274 = FALSE; break; }
    				$_274 = TRUE; break;
    			}
    			while(0);
    			if( $_274 === FALSE) {
    				$result = $res_275;
    				$this->pos = $pos_275;
    				unset( $res_275 );
    				unset( $pos_275 );
    				break;
    			}
    		}
    		$_276 = TRUE; break;
    	}
    	while(0);
    	if( $_276 === TRUE ) { return $this->finalise($result); }
    	if( $_276 === FALSE) { return FALSE; }
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
    	$_286 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgument" );
    		}
    		else { $_286 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_286 = FALSE; break; }
    		$res_285 = $result;
    		$pos_285 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_285;
    			$this->pos = $pos_285;
    			unset( $res_285 );
    			unset( $pos_285 );
    		}
    		$_286 = TRUE; break;
    	}
    	while(0);
    	if( $_286 === TRUE ) { return $this->finalise($result); }
    	if( $_286 === FALSE) { return FALSE; }
    }


    /* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
    protected $match_ElseIfPart_typestack = array('ElseIfPart');
    function match_ElseIfPart ($stack = array()) {
    	$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_296 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_296 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_296 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_296 = FALSE; break; }
    		$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgument" );
    		}
    		else { $_296 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_296 = FALSE; break; }
    		$res_295 = $result;
    		$pos_295 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_295;
    			$this->pos = $pos_295;
    			unset( $res_295 );
    			unset( $pos_295 );
    		}
    		$_296 = TRUE; break;
    	}
    	while(0);
    	if( $_296 === TRUE ) { return $this->finalise($result); }
    	if( $_296 === FALSE) { return FALSE; }
    }


    /* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher? */
    protected $match_ElsePart_typestack = array('ElsePart');
    function match_ElsePart ($stack = array()) {
    	$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_304 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_304 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_304 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_304 = FALSE; break; }
    		$res_303 = $result;
    		$pos_303 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_303;
    			$this->pos = $pos_303;
    			unset( $res_303 );
    			unset( $pos_303 );
    		}
    		$_304 = TRUE; break;
    	}
    	while(0);
    	if( $_304 === TRUE ) { return $this->finalise($result); }
    	if( $_304 === FALSE) { return FALSE; }
    }


    /* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
    protected $match_If_typestack = array('If');
    function match_If ($stack = array()) {
    	$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
    	$_314 = NULL;
    	do {
    		$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_314 = FALSE; break; }
    		while (true) {
    			$res_307 = $result;
    			$pos_307 = $this->pos;
    			$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else {
    				$result = $res_307;
    				$this->pos = $pos_307;
    				unset( $res_307 );
    				unset( $pos_307 );
    				break;
    			}
    		}
    		$res_308 = $result;
    		$pos_308 = $this->pos;
    		$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else {
    			$result = $res_308;
    			$this->pos = $pos_308;
    			unset( $res_308 );
    			unset( $pos_308 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_314 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_314 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_314 = FALSE; break; }
    		$_314 = TRUE; break;
    	}
    	while(0);
    	if( $_314 === TRUE ) { return $this->finalise($result); }
    	if( $_314 === FALSE) { return FALSE; }
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
    	$_330 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_330 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_330 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_330 = FALSE; break; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
    		$_326 = NULL;
    		do {
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Method" );
    			}
    			else { $_326 = FALSE; break; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    				$this->pos += 1;
    				$result["text"] .= '(';
    			}
    			else { $_326 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "CallArguments" );
    			}
    			else { $_326 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    				$this->pos += 1;
    				$result["text"] .= ')';
    			}
    			else { $_326 = FALSE; break; }
    			$_326 = TRUE; break;
    		}
    		while(0);
    		if( $_326 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Call' );
    		}
    		if( $_326 === FALSE) {
    			$result = array_pop($stack);
    			$_330 = FALSE; break;
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_330 = FALSE; break; }
    		$_330 = TRUE; break;
    	}
    	while(0);
    	if( $_330 === TRUE ) { return $this->finalise($result); }
    	if( $_330 === FALSE) { return FALSE; }
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
    	$_350 = NULL;
    	do {
    		$res_338 = $result;
    		$pos_338 = $this->pos;
    		$_337 = NULL;
    		do {
    			$_335 = NULL;
    			do {
    				$res_332 = $result;
    				$pos_332 = $this->pos;
    				if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_335 = TRUE; break;
    				}
    				$result = $res_332;
    				$this->pos = $pos_332;
    				if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_335 = TRUE; break;
    				}
    				$result = $res_332;
    				$this->pos = $pos_332;
    				$_335 = FALSE; break;
    			}
    			while(0);
    			if( $_335 === FALSE) { $_337 = FALSE; break; }
    			$_337 = TRUE; break;
    		}
    		while(0);
    		if( $_337 === TRUE ) {
    			$result = $res_338;
    			$this->pos = $pos_338;
    			$_350 = FALSE; break;
    		}
    		if( $_337 === FALSE) {
    			$result = $res_338;
    			$this->pos = $pos_338;
    		}
    		$_348 = NULL;
    		do {
    			$_346 = NULL;
    			do {
    				$res_339 = $result;
    				$pos_339 = $this->pos;
    				$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "DollarMarkedLookup" );
    					$_346 = TRUE; break;
    				}
    				$result = $res_339;
    				$this->pos = $pos_339;
    				$_344 = NULL;
    				do {
    					$res_341 = $result;
    					$pos_341 = $this->pos;
    					$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "QuotedString" );
    						$_344 = TRUE; break;
    					}
    					$result = $res_341;
    					$this->pos = $pos_341;
    					$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "Lookup" );
    						$_344 = TRUE; break;
    					}
    					$result = $res_341;
    					$this->pos = $pos_341;
    					$_344 = FALSE; break;
    				}
    				while(0);
    				if( $_344 === TRUE ) { $_346 = TRUE; break; }
    				$result = $res_339;
    				$this->pos = $pos_339;
    				$_346 = FALSE; break;
    			}
    			while(0);
    			if( $_346 === FALSE) { $_348 = FALSE; break; }
    			$_348 = TRUE; break;
    		}
    		while(0);
    		if( $_348 === FALSE) { $_350 = FALSE; break; }
    		$_350 = TRUE; break;
    	}
    	while(0);
    	if( $_350 === TRUE ) { return $this->finalise($result); }
    	if( $_350 === FALSE) { return FALSE; }
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
    	$_359 = NULL;
    	do {
    		$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_359 = FALSE; break; }
    		while (true) {
    			$res_358 = $result;
    			$pos_358 = $this->pos;
    			$_357 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_357 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    				}
    				else { $_357 = FALSE; break; }
    				$_357 = TRUE; break;
    			}
    			while(0);
    			if( $_357 === FALSE) {
    				$result = $res_358;
    				$this->pos = $pos_358;
    				unset( $res_358 );
    				unset( $pos_358 );
    				break;
    			}
    		}
    		$_359 = TRUE; break;
    	}
    	while(0);
    	if( $_359 === TRUE ) { return $this->finalise($result); }
    	if( $_359 === FALSE) { return FALSE; }
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
    		$res_407 = $result;
    		$pos_407 = $this->pos;
    		$_406 = NULL;
    		do {
    			$_404 = NULL;
    			do {
    				$res_361 = $result;
    				$pos_361 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_404 = TRUE; break;
    				}
    				$result = $res_361;
    				$this->pos = $pos_361;
    				$_402 = NULL;
    				do {
    					$res_363 = $result;
    					$pos_363 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_402 = TRUE; break;
    					}
    					$result = $res_363;
    					$this->pos = $pos_363;
    					$_400 = NULL;
    					do {
    						$res_365 = $result;
    						$pos_365 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_400 = TRUE; break;
    						}
    						$result = $res_365;
    						$this->pos = $pos_365;
    						$_398 = NULL;
    						do {
    							$res_367 = $result;
    							$pos_367 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_398 = TRUE; break;
    							}
    							$result = $res_367;
    							$this->pos = $pos_367;
    							$_396 = NULL;
    							do {
    								$res_369 = $result;
    								$pos_369 = $this->pos;
    								$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_396 = TRUE; break;
    								}
    								$result = $res_369;
    								$this->pos = $pos_369;
    								$_394 = NULL;
    								do {
    									$res_371 = $result;
    									$pos_371 = $this->pos;
    									$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_394 = TRUE; break;
    									}
    									$result = $res_371;
    									$this->pos = $pos_371;
    									$_392 = NULL;
    									do {
    										$res_373 = $result;
    										$pos_373 = $this->pos;
    										$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_392 = TRUE; break;
    										}
    										$result = $res_373;
    										$this->pos = $pos_373;
    										$_390 = NULL;
    										do {
    											$res_375 = $result;
    											$pos_375 = $this->pos;
    											$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_390 = TRUE; break;
    											}
    											$result = $res_375;
    											$this->pos = $pos_375;
    											$_388 = NULL;
    											do {
    												$res_377 = $result;
    												$pos_377 = $this->pos;
    												$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_388 = TRUE; break;
    												}
    												$result = $res_377;
    												$this->pos = $pos_377;
    												$_386 = NULL;
    												do {
    													$res_379 = $result;
    													$pos_379 = $this->pos;
    													$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_386 = TRUE; break;
    													}
    													$result = $res_379;
    													$this->pos = $pos_379;
    													$_384 = NULL;
    													do {
    														$res_381 = $result;
    														$pos_381 = $this->pos;
    														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_384 = TRUE; break;
    														}
    														$result = $res_381;
    														$this->pos = $pos_381;
    														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_384 = TRUE; break;
    														}
    														$result = $res_381;
    														$this->pos = $pos_381;
    														$_384 = FALSE; break;
    													}
    													while(0);
    													if( $_384 === TRUE ) { $_386 = TRUE; break; }
    													$result = $res_379;
    													$this->pos = $pos_379;
    													$_386 = FALSE; break;
    												}
    												while(0);
    												if( $_386 === TRUE ) { $_388 = TRUE; break; }
    												$result = $res_377;
    												$this->pos = $pos_377;
    												$_388 = FALSE; break;
    											}
    											while(0);
    											if( $_388 === TRUE ) { $_390 = TRUE; break; }
    											$result = $res_375;
    											$this->pos = $pos_375;
    											$_390 = FALSE; break;
    										}
    										while(0);
    										if( $_390 === TRUE ) { $_392 = TRUE; break; }
    										$result = $res_373;
    										$this->pos = $pos_373;
    										$_392 = FALSE; break;
    									}
    									while(0);
    									if( $_392 === TRUE ) { $_394 = TRUE; break; }
    									$result = $res_371;
    									$this->pos = $pos_371;
    									$_394 = FALSE; break;
    								}
    								while(0);
    								if( $_394 === TRUE ) { $_396 = TRUE; break; }
    								$result = $res_369;
    								$this->pos = $pos_369;
    								$_396 = FALSE; break;
    							}
    							while(0);
    							if( $_396 === TRUE ) { $_398 = TRUE; break; }
    							$result = $res_367;
    							$this->pos = $pos_367;
    							$_398 = FALSE; break;
    						}
    						while(0);
    						if( $_398 === TRUE ) { $_400 = TRUE; break; }
    						$result = $res_365;
    						$this->pos = $pos_365;
    						$_400 = FALSE; break;
    					}
    					while(0);
    					if( $_400 === TRUE ) { $_402 = TRUE; break; }
    					$result = $res_363;
    					$this->pos = $pos_363;
    					$_402 = FALSE; break;
    				}
    				while(0);
    				if( $_402 === TRUE ) { $_404 = TRUE; break; }
    				$result = $res_361;
    				$this->pos = $pos_361;
    				$_404 = FALSE; break;
    			}
    			while(0);
    			if( $_404 === FALSE) { $_406 = FALSE; break; }
    			$_406 = TRUE; break;
    		}
    		while(0);
    		if( $_406 === FALSE) {
    			$result = $res_407;
    			$this->pos = $pos_407;
    			unset( $res_407 );
    			unset( $pos_407 );
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
    	$_444 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_444 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_444 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_412 = $result;
    		$pos_412 = $this->pos;
    		$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else {
    			$result = $res_412;
    			$this->pos = $pos_412;
    			unset( $res_412 );
    			unset( $pos_412 );
    		}
    		$res_424 = $result;
    		$pos_424 = $this->pos;
    		$_423 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
    			$_419 = NULL;
    			do {
    				$_417 = NULL;
    				do {
    					$res_414 = $result;
    					$pos_414 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_417 = TRUE; break;
    					}
    					$result = $res_414;
    					$this->pos = $pos_414;
    					if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_417 = TRUE; break;
    					}
    					$result = $res_414;
    					$this->pos = $pos_414;
    					$_417 = FALSE; break;
    				}
    				while(0);
    				if( $_417 === FALSE) { $_419 = FALSE; break; }
    				$_419 = TRUE; break;
    			}
    			while(0);
    			if( $_419 === TRUE ) {
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Conditional' );
    			}
    			if( $_419 === FALSE) {
    				$result = array_pop($stack);
    				$_423 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Condition" );
    			}
    			else { $_423 = FALSE; break; }
    			$_423 = TRUE; break;
    		}
    		while(0);
    		if( $_423 === FALSE) {
    			$result = $res_424;
    			$this->pos = $pos_424;
    			unset( $res_424 );
    			unset( $pos_424 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_444 = FALSE; break; }
    		$res_427 = $result;
    		$pos_427 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_427;
    			$this->pos = $pos_427;
    			unset( $res_427 );
    			unset( $pos_427 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_444 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_444 = FALSE; break; }
    		$_440 = NULL;
    		do {
    			$_438 = NULL;
    			do {
    				$res_431 = $result;
    				$pos_431 = $this->pos;
    				if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_438 = TRUE; break;
    				}
    				$result = $res_431;
    				$this->pos = $pos_431;
    				$_436 = NULL;
    				do {
    					$res_433 = $result;
    					$pos_433 = $this->pos;
    					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_436 = TRUE; break;
    					}
    					$result = $res_433;
    					$this->pos = $pos_433;
    					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_436 = TRUE; break;
    					}
    					$result = $res_433;
    					$this->pos = $pos_433;
    					$_436 = FALSE; break;
    				}
    				while(0);
    				if( $_436 === TRUE ) { $_438 = TRUE; break; }
    				$result = $res_431;
    				$this->pos = $pos_431;
    				$_438 = FALSE; break;
    			}
    			while(0);
    			if( $_438 === FALSE) { $_440 = FALSE; break; }
    			$_440 = TRUE; break;
    		}
    		while(0);
    		if( $_440 === FALSE) { $_444 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_444 = FALSE; break; }
    		$_444 = TRUE; break;
    	}
    	while(0);
    	if( $_444 === TRUE ) { return $this->finalise($result); }
    	if( $_444 === FALSE) { return FALSE; }
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
    		$res_500 = $result;
    		$pos_500 = $this->pos;
    		$_499 = NULL;
    		do {
    			$_497 = NULL;
    			do {
    				$res_446 = $result;
    				$pos_446 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_497 = TRUE; break;
    				}
    				$result = $res_446;
    				$this->pos = $pos_446;
    				$_495 = NULL;
    				do {
    					$res_448 = $result;
    					$pos_448 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_495 = TRUE; break;
    					}
    					$result = $res_448;
    					$this->pos = $pos_448;
    					$_493 = NULL;
    					do {
    						$res_450 = $result;
    						$pos_450 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_493 = TRUE; break;
    						}
    						$result = $res_450;
    						$this->pos = $pos_450;
    						$_491 = NULL;
    						do {
    							$res_452 = $result;
    							$pos_452 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_491 = TRUE; break;
    							}
    							$result = $res_452;
    							$this->pos = $pos_452;
    							$_489 = NULL;
    							do {
    								$res_454 = $result;
    								$pos_454 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_489 = TRUE; break;
    								}
    								$result = $res_454;
    								$this->pos = $pos_454;
    								$_487 = NULL;
    								do {
    									$res_456 = $result;
    									$pos_456 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_487 = TRUE; break;
    									}
    									$result = $res_456;
    									$this->pos = $pos_456;
    									$_485 = NULL;
    									do {
    										$res_458 = $result;
    										$pos_458 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_485 = TRUE; break;
    										}
    										$result = $res_458;
    										$this->pos = $pos_458;
    										$_483 = NULL;
    										do {
    											$res_460 = $result;
    											$pos_460 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_483 = TRUE; break;
    											}
    											$result = $res_460;
    											$this->pos = $pos_460;
    											$_481 = NULL;
    											do {
    												$res_462 = $result;
    												$pos_462 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_481 = TRUE; break;
    												}
    												$result = $res_462;
    												$this->pos = $pos_462;
    												$_479 = NULL;
    												do {
    													$res_464 = $result;
    													$pos_464 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_479 = TRUE; break;
    													}
    													$result = $res_464;
    													$this->pos = $pos_464;
    													$_477 = NULL;
    													do {
    														$res_466 = $result;
    														$pos_466 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_477 = TRUE; break;
    														}
    														$result = $res_466;
    														$this->pos = $pos_466;
    														$_475 = NULL;
    														do {
    															$res_468 = $result;
    															$pos_468 = $this->pos;
    															$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_475 = TRUE; break;
    															}
    															$result = $res_468;
    															$this->pos = $pos_468;
    															$_473 = NULL;
    															do {
    																$res_470 = $result;
    																$pos_470 = $this->pos;
    																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_473 = TRUE; break;
    																}
    																$result = $res_470;
    																$this->pos = $pos_470;
    																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_473 = TRUE; break;
    																}
    																$result = $res_470;
    																$this->pos = $pos_470;
    																$_473 = FALSE; break;
    															}
    															while(0);
    															if( $_473 === TRUE ) {
    																$_475 = TRUE; break;
    															}
    															$result = $res_468;
    															$this->pos = $pos_468;
    															$_475 = FALSE; break;
    														}
    														while(0);
    														if( $_475 === TRUE ) { $_477 = TRUE; break; }
    														$result = $res_466;
    														$this->pos = $pos_466;
    														$_477 = FALSE; break;
    													}
    													while(0);
    													if( $_477 === TRUE ) { $_479 = TRUE; break; }
    													$result = $res_464;
    													$this->pos = $pos_464;
    													$_479 = FALSE; break;
    												}
    												while(0);
    												if( $_479 === TRUE ) { $_481 = TRUE; break; }
    												$result = $res_462;
    												$this->pos = $pos_462;
    												$_481 = FALSE; break;
    											}
    											while(0);
    											if( $_481 === TRUE ) { $_483 = TRUE; break; }
    											$result = $res_460;
    											$this->pos = $pos_460;
    											$_483 = FALSE; break;
    										}
    										while(0);
    										if( $_483 === TRUE ) { $_485 = TRUE; break; }
    										$result = $res_458;
    										$this->pos = $pos_458;
    										$_485 = FALSE; break;
    									}
    									while(0);
    									if( $_485 === TRUE ) { $_487 = TRUE; break; }
    									$result = $res_456;
    									$this->pos = $pos_456;
    									$_487 = FALSE; break;
    								}
    								while(0);
    								if( $_487 === TRUE ) { $_489 = TRUE; break; }
    								$result = $res_454;
    								$this->pos = $pos_454;
    								$_489 = FALSE; break;
    							}
    							while(0);
    							if( $_489 === TRUE ) { $_491 = TRUE; break; }
    							$result = $res_452;
    							$this->pos = $pos_452;
    							$_491 = FALSE; break;
    						}
    						while(0);
    						if( $_491 === TRUE ) { $_493 = TRUE; break; }
    						$result = $res_450;
    						$this->pos = $pos_450;
    						$_493 = FALSE; break;
    					}
    					while(0);
    					if( $_493 === TRUE ) { $_495 = TRUE; break; }
    					$result = $res_448;
    					$this->pos = $pos_448;
    					$_495 = FALSE; break;
    				}
    				while(0);
    				if( $_495 === TRUE ) { $_497 = TRUE; break; }
    				$result = $res_446;
    				$this->pos = $pos_446;
    				$_497 = FALSE; break;
    			}
    			while(0);
    			if( $_497 === FALSE) { $_499 = FALSE; break; }
    			$_499 = TRUE; break;
    		}
    		while(0);
    		if( $_499 === FALSE) {
    			$result = $res_500;
    			$this->pos = $pos_500;
    			unset( $res_500 );
    			unset( $pos_500 );
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
    	$_555 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
    		$_508 = NULL;
    		do {
    			$_506 = NULL;
    			do {
    				$res_503 = $result;
    				$pos_503 = $this->pos;
    				if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_506 = TRUE; break;
    				}
    				$result = $res_503;
    				$this->pos = $pos_503;
    				if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_506 = TRUE; break;
    				}
    				$result = $res_503;
    				$this->pos = $pos_503;
    				$_506 = FALSE; break;
    			}
    			while(0);
    			if( $_506 === FALSE) { $_508 = FALSE; break; }
    			$_508 = TRUE; break;
    		}
    		while(0);
    		if( $_508 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'CacheTag' );
    		}
    		if( $_508 === FALSE) {
    			$result = array_pop($stack);
    			$_555 = FALSE; break;
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_513 = $result;
    		$pos_513 = $this->pos;
    		$_512 = NULL;
    		do {
    			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_512 = FALSE; break; }
    			$_512 = TRUE; break;
    		}
    		while(0);
    		if( $_512 === FALSE) {
    			$result = $res_513;
    			$this->pos = $pos_513;
    			unset( $res_513 );
    			unset( $pos_513 );
    		}
    		$res_525 = $result;
    		$pos_525 = $this->pos;
    		$_524 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
    			$_520 = NULL;
    			do {
    				$_518 = NULL;
    				do {
    					$res_515 = $result;
    					$pos_515 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_518 = TRUE; break;
    					}
    					$result = $res_515;
    					$this->pos = $pos_515;
    					if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_518 = TRUE; break;
    					}
    					$result = $res_515;
    					$this->pos = $pos_515;
    					$_518 = FALSE; break;
    				}
    				while(0);
    				if( $_518 === FALSE) { $_520 = FALSE; break; }
    				$_520 = TRUE; break;
    			}
    			while(0);
    			if( $_520 === TRUE ) {
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Conditional' );
    			}
    			if( $_520 === FALSE) {
    				$result = array_pop($stack);
    				$_524 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Condition" );
    			}
    			else { $_524 = FALSE; break; }
    			$_524 = TRUE; break;
    		}
    		while(0);
    		if( $_524 === FALSE) {
    			$result = $res_525;
    			$this->pos = $pos_525;
    			unset( $res_525 );
    			unset( $pos_525 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		while (true) {
    			$res_538 = $result;
    			$pos_538 = $this->pos;
    			$_537 = NULL;
    			do {
    				$_535 = NULL;
    				do {
    					$res_528 = $result;
    					$pos_528 = $this->pos;
    					$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_535 = TRUE; break;
    					}
    					$result = $res_528;
    					$this->pos = $pos_528;
    					$_533 = NULL;
    					do {
    						$res_530 = $result;
    						$pos_530 = $this->pos;
    						$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_533 = TRUE; break;
    						}
    						$result = $res_530;
    						$this->pos = $pos_530;
    						$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
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
    					if( $_533 === TRUE ) { $_535 = TRUE; break; }
    					$result = $res_528;
    					$this->pos = $pos_528;
    					$_535 = FALSE; break;
    				}
    				while(0);
    				if( $_535 === FALSE) { $_537 = FALSE; break; }
    				$_537 = TRUE; break;
    			}
    			while(0);
    			if( $_537 === FALSE) {
    				$result = $res_538;
    				$this->pos = $pos_538;
    				unset( $res_538 );
    				unset( $pos_538 );
    				break;
    			}
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		$_551 = NULL;
    		do {
    			$_549 = NULL;
    			do {
    				$res_542 = $result;
    				$pos_542 = $this->pos;
    				if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_549 = TRUE; break;
    				}
    				$result = $res_542;
    				$this->pos = $pos_542;
    				$_547 = NULL;
    				do {
    					$res_544 = $result;
    					$pos_544 = $this->pos;
    					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_547 = TRUE; break;
    					}
    					$result = $res_544;
    					$this->pos = $pos_544;
    					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_547 = TRUE; break;
    					}
    					$result = $res_544;
    					$this->pos = $pos_544;
    					$_547 = FALSE; break;
    				}
    				while(0);
    				if( $_547 === TRUE ) { $_549 = TRUE; break; }
    				$result = $res_542;
    				$this->pos = $pos_542;
    				$_549 = FALSE; break;
    			}
    			while(0);
    			if( $_549 === FALSE) { $_551 = FALSE; break; }
    			$_551 = TRUE; break;
    		}
    		while(0);
    		if( $_551 === FALSE) { $_555 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_555 = FALSE; break; }
    		$_555 = TRUE; break;
    	}
    	while(0);
    	if( $_555 === TRUE ) { return $this->finalise($result); }
    	if( $_555 === FALSE) { return FALSE; }
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
    	$_574 = NULL;
    	do {
    		if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_574 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_574 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    			$this->pos += 1;
    			$result["text"] .= '(';
    		}
    		else { $_574 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_574 = FALSE; break; }
    		$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_574 = FALSE; break; }
    		$res_567 = $result;
    		$pos_567 = $this->pos;
    		$_566 = NULL;
    		do {
    			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_566 = FALSE; break; }
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    				$this->pos += 1;
    				$result["text"] .= ',';
    			}
    			else { $_566 = FALSE; break; }
    			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_566 = FALSE; break; }
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_566 = FALSE; break; }
    			$_566 = TRUE; break;
    		}
    		while(0);
    		if( $_566 === FALSE) {
    			$result = $res_567;
    			$this->pos = $pos_567;
    			unset( $res_567 );
    			unset( $pos_567 );
    		}
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_574 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    			$this->pos += 1;
    			$result["text"] .= ')';
    		}
    		else { $_574 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_574 = FALSE; break; }
    		$res_573 = $result;
    		$pos_573 = $this->pos;
    		$_572 = NULL;
    		do {
    			if (substr($this->string ?? '',$this->pos ?? 0,1) == ';') {
    				$this->pos += 1;
    				$result["text"] .= ';';
    			}
    			else { $_572 = FALSE; break; }
    			$_572 = TRUE; break;
    		}
    		while(0);
    		if( $_572 === FALSE) {
    			$result = $res_573;
    			$this->pos = $pos_573;
    			unset( $res_573 );
    			unset( $pos_573 );
    		}
    		$_574 = TRUE; break;
    	}
    	while(0);
    	if( $_574 === TRUE ) { return $this->finalise($result); }
    	if( $_574 === FALSE) { return FALSE; }
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
    	$_582 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_582 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_582 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_582 = FALSE; break; }
    		$_582 = TRUE; break;
    	}
    	while(0);
    	if( $_582 === TRUE ) { return $this->finalise($result); }
    	if( $_582 === FALSE) { return FALSE; }
    }



    function OldTTag_OldTPart(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>" */
    protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
    function match_OldSprintfTag ($stack = array()) {
    	$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_599 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '(') {
    			$this->pos += 1;
    			$result["text"] .= '(';
    		}
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    			$this->pos += 1;
    			$result["text"] .= ',';
    		}
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == ')') {
    			$this->pos += 1;
    			$result["text"] .= ')';
    		}
    		else { $_599 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_599 = FALSE; break; }
    		$_599 = TRUE; break;
    	}
    	while(0);
    	if( $_599 === TRUE ) { return $this->finalise($result); }
    	if( $_599 === FALSE) { return FALSE; }
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
    	$_604 = NULL;
    	do {
    		$res_601 = $result;
    		$pos_601 = $this->pos;
    		$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_604 = TRUE; break;
    		}
    		$result = $res_601;
    		$this->pos = $pos_601;
    		$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_604 = TRUE; break;
    		}
    		$result = $res_601;
    		$this->pos = $pos_601;
    		$_604 = FALSE; break;
    	}
    	while(0);
    	if( $_604 === TRUE ) { return $this->finalise($result); }
    	if( $_604 === FALSE) { return FALSE; }
    }



    function OldI18NTag_STR(&$res, $sub)
    {
        $res['php'] = '$val .= ' . $sub['php'] . ';';
    }

    /* NamedArgument: Name:Word "=" Value:Argument */
    protected $match_NamedArgument_typestack = array('NamedArgument');
    function match_NamedArgument ($stack = array()) {
    	$matchrule = "NamedArgument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_609 = NULL;
    	do {
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Name" );
    		}
    		else { $_609 = FALSE; break; }
    		if (substr($this->string ?? '',$this->pos ?? 0,1) == '=') {
    			$this->pos += 1;
    			$result["text"] .= '=';
    		}
    		else { $_609 = FALSE; break; }
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Value" );
    		}
    		else { $_609 = FALSE; break; }
    		$_609 = TRUE; break;
    	}
    	while(0);
    	if( $_609 === TRUE ) { return $this->finalise($result); }
    	if( $_609 === FALSE) { return FALSE; }
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
                $res['php'] .= str_replace('$$FINAL', 'obj', $sub['php'] ?? '') . '->self()';
                break;
        }
    }

    /* Include: "<%" < "include" < Template:NamespacedWord < (NamedArgument ( < "," < NamedArgument )*)? > "%>" */
    protected $match_Include_typestack = array('Include');
    function match_Include ($stack = array()) {
    	$matchrule = "Include"; $result = $this->construct($matchrule, $matchrule, null);
    	$_628 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_628 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'include' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_628 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'NamespacedWord'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else { $_628 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_625 = $result;
    		$pos_625 = $this->pos;
    		$_624 = NULL;
    		do {
    			$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_624 = FALSE; break; }
    			while (true) {
    				$res_623 = $result;
    				$pos_623 = $this->pos;
    				$_622 = NULL;
    				do {
    					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    					if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    						$this->pos += 1;
    						$result["text"] .= ',';
    					}
    					else { $_622 = FALSE; break; }
    					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    					$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    					}
    					else { $_622 = FALSE; break; }
    					$_622 = TRUE; break;
    				}
    				while(0);
    				if( $_622 === FALSE) {
    					$result = $res_623;
    					$this->pos = $pos_623;
    					unset( $res_623 );
    					unset( $pos_623 );
    					break;
    				}
    			}
    			$_624 = TRUE; break;
    		}
    		while(0);
    		if( $_624 === FALSE) {
    			$result = $res_625;
    			$this->pos = $pos_625;
    			unset( $res_625 );
    			unset( $pos_625 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_628 = FALSE; break; }
    		$_628 = TRUE; break;
    	}
    	while(0);
    	if( $_628 === TRUE ) { return $this->finalise($result); }
    	if( $_628 === FALSE) { return FALSE; }
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
    	$_637 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Argument" );
    		}
    		else { $_637 = FALSE; break; }
    		while (true) {
    			$res_636 = $result;
    			$pos_636 = $this->pos;
    			$_635 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string ?? '',$this->pos ?? 0,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_635 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "Argument" );
    				}
    				else { $_635 = FALSE; break; }
    				$_635 = TRUE; break;
    			}
    			while(0);
    			if( $_635 === FALSE) {
    				$result = $res_636;
    				$this->pos = $pos_636;
    				unset( $res_636 );
    				unset( $pos_636 );
    				break;
    			}
    		}
    		$_637 = TRUE; break;
    	}
    	while(0);
    	if( $_637 === TRUE ) { return $this->finalise($result); }
    	if( $_637 === FALSE) { return FALSE; }
    }


    /* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")]) */
    protected $match_NotBlockTag_typestack = array('NotBlockTag');
    function match_NotBlockTag ($stack = array()) {
    	$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_675 = NULL;
    	do {
    		$res_639 = $result;
    		$pos_639 = $this->pos;
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_675 = TRUE; break;
    		}
    		$result = $res_639;
    		$this->pos = $pos_639;
    		$_673 = NULL;
    		do {
    			$_670 = NULL;
    			do {
    				$_668 = NULL;
    				do {
    					$res_641 = $result;
    					$pos_641 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_668 = TRUE; break;
    					}
    					$result = $res_641;
    					$this->pos = $pos_641;
    					$_666 = NULL;
    					do {
    						$res_643 = $result;
    						$pos_643 = $this->pos;
    						if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
    							$result["text"] .= $subres;
    							$_666 = TRUE; break;
    						}
    						$result = $res_643;
    						$this->pos = $pos_643;
    						$_664 = NULL;
    						do {
    							$res_645 = $result;
    							$pos_645 = $this->pos;
    							if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
    								$result["text"] .= $subres;
    								$_664 = TRUE; break;
    							}
    							$result = $res_645;
    							$this->pos = $pos_645;
    							$_662 = NULL;
    							do {
    								$res_647 = $result;
    								$pos_647 = $this->pos;
    								if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
    									$result["text"] .= $subres;
    									$_662 = TRUE; break;
    								}
    								$result = $res_647;
    								$this->pos = $pos_647;
    								$_660 = NULL;
    								do {
    									$res_649 = $result;
    									$pos_649 = $this->pos;
    									if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    										$_660 = TRUE; break;
    									}
    									$result = $res_649;
    									$this->pos = $pos_649;
    									$_658 = NULL;
    									do {
    										$res_651 = $result;
    										$pos_651 = $this->pos;
    										if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    											$result["text"] .= $subres;
    											$_658 = TRUE; break;
    										}
    										$result = $res_651;
    										$this->pos = $pos_651;
    										$_656 = NULL;
    										do {
    											$res_653 = $result;
    											$pos_653 = $this->pos;
    											if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    												$result["text"] .= $subres;
    												$_656 = TRUE; break;
    											}
    											$result = $res_653;
    											$this->pos = $pos_653;
    											if (( $subres = $this->literal( 'include' ) ) !== FALSE) {
    												$result["text"] .= $subres;
    												$_656 = TRUE; break;
    											}
    											$result = $res_653;
    											$this->pos = $pos_653;
    											$_656 = FALSE; break;
    										}
    										while(0);
    										if( $_656 === TRUE ) { $_658 = TRUE; break; }
    										$result = $res_651;
    										$this->pos = $pos_651;
    										$_658 = FALSE; break;
    									}
    									while(0);
    									if( $_658 === TRUE ) { $_660 = TRUE; break; }
    									$result = $res_649;
    									$this->pos = $pos_649;
    									$_660 = FALSE; break;
    								}
    								while(0);
    								if( $_660 === TRUE ) { $_662 = TRUE; break; }
    								$result = $res_647;
    								$this->pos = $pos_647;
    								$_662 = FALSE; break;
    							}
    							while(0);
    							if( $_662 === TRUE ) { $_664 = TRUE; break; }
    							$result = $res_645;
    							$this->pos = $pos_645;
    							$_664 = FALSE; break;
    						}
    						while(0);
    						if( $_664 === TRUE ) { $_666 = TRUE; break; }
    						$result = $res_643;
    						$this->pos = $pos_643;
    						$_666 = FALSE; break;
    					}
    					while(0);
    					if( $_666 === TRUE ) { $_668 = TRUE; break; }
    					$result = $res_641;
    					$this->pos = $pos_641;
    					$_668 = FALSE; break;
    				}
    				while(0);
    				if( $_668 === FALSE) { $_670 = FALSE; break; }
    				$_670 = TRUE; break;
    			}
    			while(0);
    			if( $_670 === FALSE) { $_673 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_673 = FALSE; break; }
    			$_673 = TRUE; break;
    		}
    		while(0);
    		if( $_673 === TRUE ) { $_675 = TRUE; break; }
    		$result = $res_639;
    		$this->pos = $pos_639;
    		$_675 = FALSE; break;
    	}
    	while(0);
    	if( $_675 === TRUE ) { return $this->finalise($result); }
    	if( $_675 === FALSE) { return FALSE; }
    }


    /* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher?
    '<%' < 'end_' '$BlockName' > '%>' */
    protected $match_ClosedBlock_typestack = array('ClosedBlock');
    function match_ClosedBlock ($stack = array()) {
    	$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
    	$_695 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_695 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_679 = $result;
    		$pos_679 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_679;
    			$this->pos = $pos_679;
    			$_695 = FALSE; break;
    		}
    		else {
    			$result = $res_679;
    			$this->pos = $pos_679;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "BlockName" );
    		}
    		else { $_695 = FALSE; break; }
    		$res_685 = $result;
    		$pos_685 = $this->pos;
    		$_684 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_684 = FALSE; break; }
    			$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "BlockArguments" );
    			}
    			else { $_684 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_684 = FALSE; break; }
    			$_684 = TRUE; break;
    		}
    		while(0);
    		if( $_684 === FALSE) {
    			$result = $res_685;
    			$this->pos = $pos_685;
    			unset( $res_685 );
    			unset( $pos_685 );
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
    			$_695 = FALSE; break;
    		}
    		$res_688 = $result;
    		$pos_688 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_688;
    			$this->pos = $pos_688;
    			unset( $res_688 );
    			unset( $pos_688 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_695 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_695 = FALSE; break; }
    		if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_695 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_695 = FALSE; break; }
    		$_695 = TRUE; break;
    	}
    	while(0);
    	if( $_695 === TRUE ) { return $this->finalise($result); }
    	if( $_695 === FALSE) { return FALSE; }
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
    	$_708 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_708 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_699 = $result;
    		$pos_699 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_699;
    			$this->pos = $pos_699;
    			$_708 = FALSE; break;
    		}
    		else {
    			$result = $res_699;
    			$this->pos = $pos_699;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "BlockName" );
    		}
    		else { $_708 = FALSE; break; }
    		$res_705 = $result;
    		$pos_705 = $this->pos;
    		$_704 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_704 = FALSE; break; }
    			$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "BlockArguments" );
    			}
    			else { $_704 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_704 = FALSE; break; }
    			$_704 = TRUE; break;
    		}
    		while(0);
    		if( $_704 === FALSE) {
    			$result = $res_705;
    			$this->pos = $pos_705;
    			unset( $res_705 );
    			unset( $pos_705 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_708 = FALSE; break; }
    		$_708 = TRUE; break;
    	}
    	while(0);
    	if( $_708 === TRUE ) { return $this->finalise($result); }
    	if( $_708 === FALSE) { return FALSE; }
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
    	$_716 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_716 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_716 = FALSE; break; }
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Word" );
    		}
    		else { $_716 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_716 = FALSE; break; }
    		$_716 = TRUE; break;
    	}
    	while(0);
    	if( $_716 === TRUE ) { return $this->finalise($result); }
    	if( $_716 === FALSE) { return FALSE; }
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
    	$_731 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_731 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_720 = $result;
    		$pos_720 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_720;
    			$this->pos = $pos_720;
    			$_731 = FALSE; break;
    		}
    		else {
    			$result = $res_720;
    			$this->pos = $pos_720;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Tag" );
    		}
    		else { $_731 = FALSE; break; }
    		$res_730 = $result;
    		$pos_730 = $this->pos;
    		$_729 = NULL;
    		do {
    			$res_726 = $result;
    			$pos_726 = $this->pos;
    			$_725 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_725 = FALSE; break; }
    				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "BlockArguments" );
    				}
    				else { $_725 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_725 = FALSE; break; }
    				$_725 = TRUE; break;
    			}
    			while(0);
    			if( $_725 === FALSE) {
    				$result = $res_726;
    				$this->pos = $pos_726;
    				unset( $res_726 );
    				unset( $pos_726 );
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_729 = FALSE; break; }
    			$_729 = TRUE; break;
    		}
    		while(0);
    		if( $_729 === TRUE ) {
    			$result = $res_730;
    			$this->pos = $pos_730;
    			$_731 = FALSE; break;
    		}
    		if( $_729 === FALSE) {
    			$result = $res_730;
    			$this->pos = $pos_730;
    		}
    		$_731 = TRUE; break;
    	}
    	while(0);
    	if( $_731 === TRUE ) { return $this->finalise($result); }
    	if( $_731 === FALSE) { return FALSE; }
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
    	$_743 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_743 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
    		$_737 = NULL;
    		do {
    			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_737 = FALSE; break; }
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Word" );
    			}
    			else { $_737 = FALSE; break; }
    			$_737 = TRUE; break;
    		}
    		while(0);
    		if( $_737 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Tag' );
    		}
    		if( $_737 === FALSE) {
    			$result = array_pop($stack);
    			$_743 = FALSE; break;
    		}
    		$res_742 = $result;
    		$pos_742 = $this->pos;
    		$_741 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_741 = FALSE; break; }
    			$_741 = TRUE; break;
    		}
    		while(0);
    		if( $_741 === TRUE ) {
    			$result = $res_742;
    			$this->pos = $pos_742;
    			$_743 = FALSE; break;
    		}
    		if( $_741 === FALSE) {
    			$result = $res_742;
    			$this->pos = $pos_742;
    		}
    		$_743 = TRUE; break;
    	}
    	while(0);
    	if( $_743 === TRUE ) { return $this->finalise($result); }
    	if( $_743 === FALSE) { return FALSE; }
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
    	$_748 = NULL;
    	do {
    		$res_745 = $result;
    		$pos_745 = $this->pos;
    		$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_748 = TRUE; break;
    		}
    		$result = $res_745;
    		$this->pos = $pos_745;
    		$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_748 = TRUE; break;
    		}
    		$result = $res_745;
    		$this->pos = $pos_745;
    		$_748 = FALSE; break;
    	}
    	while(0);
    	if( $_748 === TRUE ) { return $this->finalise($result); }
    	if( $_748 === FALSE) { return FALSE; }
    }




    /* CommentWithContent: '<%--' ( !"--%>" /(?s)./ )+ '--%>' */
    protected $match_CommentWithContent_typestack = array('CommentWithContent');
    function match_CommentWithContent ($stack = array()) {
    	$matchrule = "CommentWithContent"; $result = $this->construct($matchrule, $matchrule, null);
    	$_756 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_756 = FALSE; break; }
    		$count = 0;
    		while (true) {
    			$res_754 = $result;
    			$pos_754 = $this->pos;
    			$_753 = NULL;
    			do {
    				$res_751 = $result;
    				$pos_751 = $this->pos;
    				if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$result = $res_751;
    					$this->pos = $pos_751;
    					$_753 = FALSE; break;
    				}
    				else {
    					$result = $res_751;
    					$this->pos = $pos_751;
    				}
    				if (( $subres = $this->rx( '/(?s)./' ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_753 = FALSE; break; }
    				$_753 = TRUE; break;
    			}
    			while(0);
    			if( $_753 === FALSE) {
    				$result = $res_754;
    				$this->pos = $pos_754;
    				unset( $res_754 );
    				unset( $pos_754 );
    				break;
    			}
    			$count += 1;
    		}
    		if ($count > 0) {  }
    		else { $_756 = FALSE; break; }
    		if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_756 = FALSE; break; }
    		$_756 = TRUE; break;
    	}
    	while(0);
    	if( $_756 === TRUE ) { return $this->finalise($result); }
    	if( $_756 === FALSE) { return FALSE; }
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
    	$_762 = NULL;
    	do {
    		$res_759 = $result;
    		$pos_759 = $this->pos;
    		$matcher = 'match_'.'EmptyComment'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "EmptyComment" );
    			$_762 = TRUE; break;
    		}
    		$result = $res_759;
    		$this->pos = $pos_759;
    		$matcher = 'match_'.'CommentWithContent'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "CommentWithContent" );
    			$_762 = TRUE; break;
    		}
    		$result = $res_759;
    		$this->pos = $pos_759;
    		$_762 = FALSE; break;
    	}
    	while(0);
    	if( $_762 === TRUE ) { return $this->finalise($result); }
    	if( $_762 === FALSE) { return FALSE; }
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
    		$res_822 = $result;
    		$pos_822 = $this->pos;
    		$_821 = NULL;
    		do {
    			$_819 = NULL;
    			do {
    				$res_764 = $result;
    				$pos_764 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_819 = TRUE; break;
    				}
    				$result = $res_764;
    				$this->pos = $pos_764;
    				$_817 = NULL;
    				do {
    					$res_766 = $result;
    					$pos_766 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_817 = TRUE; break;
    					}
    					$result = $res_766;
    					$this->pos = $pos_766;
    					$_815 = NULL;
    					do {
    						$res_768 = $result;
    						$pos_768 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_815 = TRUE; break;
    						}
    						$result = $res_768;
    						$this->pos = $pos_768;
    						$_813 = NULL;
    						do {
    							$res_770 = $result;
    							$pos_770 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_813 = TRUE; break;
    							}
    							$result = $res_770;
    							$this->pos = $pos_770;
    							$_811 = NULL;
    							do {
    								$res_772 = $result;
    								$pos_772 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_811 = TRUE; break;
    								}
    								$result = $res_772;
    								$this->pos = $pos_772;
    								$_809 = NULL;
    								do {
    									$res_774 = $result;
    									$pos_774 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_809 = TRUE; break;
    									}
    									$result = $res_774;
    									$this->pos = $pos_774;
    									$_807 = NULL;
    									do {
    										$res_776 = $result;
    										$pos_776 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_807 = TRUE; break;
    										}
    										$result = $res_776;
    										$this->pos = $pos_776;
    										$_805 = NULL;
    										do {
    											$res_778 = $result;
    											$pos_778 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_805 = TRUE; break;
    											}
    											$result = $res_778;
    											$this->pos = $pos_778;
    											$_803 = NULL;
    											do {
    												$res_780 = $result;
    												$pos_780 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_803 = TRUE; break;
    												}
    												$result = $res_780;
    												$this->pos = $pos_780;
    												$_801 = NULL;
    												do {
    													$res_782 = $result;
    													$pos_782 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_801 = TRUE; break;
    													}
    													$result = $res_782;
    													$this->pos = $pos_782;
    													$_799 = NULL;
    													do {
    														$res_784 = $result;
    														$pos_784 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_799 = TRUE; break;
    														}
    														$result = $res_784;
    														$this->pos = $pos_784;
    														$_797 = NULL;
    														do {
    															$res_786 = $result;
    															$pos_786 = $this->pos;
    															$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_797 = TRUE; break;
    															}
    															$result = $res_786;
    															$this->pos = $pos_786;
    															$_795 = NULL;
    															do {
    																$res_788 = $result;
    																$pos_788 = $this->pos;
    																$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_795 = TRUE; break;
    																}
    																$result = $res_788;
    																$this->pos = $pos_788;
    																$_793 = NULL;
    																do {
    																	$res_790 = $result;
    																	$pos_790 = $this->pos;
    																	$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																	if ($subres !== FALSE) {
    																		$this->store( $result, $subres );
    																		$_793 = TRUE; break;
    																	}
    																	$result = $res_790;
    																	$this->pos = $pos_790;
    																	$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																	if ($subres !== FALSE) {
    																		$this->store( $result, $subres );
    																		$_793 = TRUE; break;
    																	}
    																	$result = $res_790;
    																	$this->pos = $pos_790;
    																	$_793 = FALSE; break;
    																}
    																while(0);
    																if( $_793 === TRUE ) {
    																	$_795 = TRUE; break;
    																}
    																$result = $res_788;
    																$this->pos = $pos_788;
    																$_795 = FALSE; break;
    															}
    															while(0);
    															if( $_795 === TRUE ) {
    																$_797 = TRUE; break;
    															}
    															$result = $res_786;
    															$this->pos = $pos_786;
    															$_797 = FALSE; break;
    														}
    														while(0);
    														if( $_797 === TRUE ) { $_799 = TRUE; break; }
    														$result = $res_784;
    														$this->pos = $pos_784;
    														$_799 = FALSE; break;
    													}
    													while(0);
    													if( $_799 === TRUE ) { $_801 = TRUE; break; }
    													$result = $res_782;
    													$this->pos = $pos_782;
    													$_801 = FALSE; break;
    												}
    												while(0);
    												if( $_801 === TRUE ) { $_803 = TRUE; break; }
    												$result = $res_780;
    												$this->pos = $pos_780;
    												$_803 = FALSE; break;
    											}
    											while(0);
    											if( $_803 === TRUE ) { $_805 = TRUE; break; }
    											$result = $res_778;
    											$this->pos = $pos_778;
    											$_805 = FALSE; break;
    										}
    										while(0);
    										if( $_805 === TRUE ) { $_807 = TRUE; break; }
    										$result = $res_776;
    										$this->pos = $pos_776;
    										$_807 = FALSE; break;
    									}
    									while(0);
    									if( $_807 === TRUE ) { $_809 = TRUE; break; }
    									$result = $res_774;
    									$this->pos = $pos_774;
    									$_809 = FALSE; break;
    								}
    								while(0);
    								if( $_809 === TRUE ) { $_811 = TRUE; break; }
    								$result = $res_772;
    								$this->pos = $pos_772;
    								$_811 = FALSE; break;
    							}
    							while(0);
    							if( $_811 === TRUE ) { $_813 = TRUE; break; }
    							$result = $res_770;
    							$this->pos = $pos_770;
    							$_813 = FALSE; break;
    						}
    						while(0);
    						if( $_813 === TRUE ) { $_815 = TRUE; break; }
    						$result = $res_768;
    						$this->pos = $pos_768;
    						$_815 = FALSE; break;
    					}
    					while(0);
    					if( $_815 === TRUE ) { $_817 = TRUE; break; }
    					$result = $res_766;
    					$this->pos = $pos_766;
    					$_817 = FALSE; break;
    				}
    				while(0);
    				if( $_817 === TRUE ) { $_819 = TRUE; break; }
    				$result = $res_764;
    				$this->pos = $pos_764;
    				$_819 = FALSE; break;
    			}
    			while(0);
    			if( $_819 === FALSE) { $_821 = FALSE; break; }
    			$_821 = TRUE; break;
    		}
    		while(0);
    		if( $_821 === FALSE) {
    			$result = $res_822;
    			$this->pos = $pos_822;
    			unset( $res_822 );
    			unset( $pos_822 );
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
    		$res_861 = $result;
    		$pos_861 = $this->pos;
    		$_860 = NULL;
    		do {
    			$_858 = NULL;
    			do {
    				$res_823 = $result;
    				$pos_823 = $this->pos;
    				if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_858 = TRUE; break;
    				}
    				$result = $res_823;
    				$this->pos = $pos_823;
    				$_856 = NULL;
    				do {
    					$res_825 = $result;
    					$pos_825 = $this->pos;
    					if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_856 = TRUE; break;
    					}
    					$result = $res_825;
    					$this->pos = $pos_825;
    					$_854 = NULL;
    					do {
    						$res_827 = $result;
    						$pos_827 = $this->pos;
    						$_830 = NULL;
    						do {
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '<') {
    								$this->pos += 1;
    								$result["text"] .= '<';
    							}
    							else { $_830 = FALSE; break; }
    							$res_829 = $result;
    							$pos_829 = $this->pos;
    							if (substr($this->string ?? '',$this->pos ?? 0,1) == '%') {
    								$this->pos += 1;
    								$result["text"] .= '%';
    								$result = $res_829;
    								$this->pos = $pos_829;
    								$_830 = FALSE; break;
    							}
    							else {
    								$result = $res_829;
    								$this->pos = $pos_829;
    							}
    							$_830 = TRUE; break;
    						}
    						while(0);
    						if( $_830 === TRUE ) { $_854 = TRUE; break; }
    						$result = $res_827;
    						$this->pos = $pos_827;
    						$_852 = NULL;
    						do {
    							$res_832 = $result;
    							$pos_832 = $this->pos;
    							$_837 = NULL;
    							do {
    								if (substr($this->string ?? '',$this->pos ?? 0,1) == '$') {
    									$this->pos += 1;
    									$result["text"] .= '$';
    								}
    								else { $_837 = FALSE; break; }
    								$res_836 = $result;
    								$pos_836 = $this->pos;
    								$_835 = NULL;
    								do {
    									if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									else { $_835 = FALSE; break; }
    									$_835 = TRUE; break;
    								}
    								while(0);
    								if( $_835 === TRUE ) {
    									$result = $res_836;
    									$this->pos = $pos_836;
    									$_837 = FALSE; break;
    								}
    								if( $_835 === FALSE) {
    									$result = $res_836;
    									$this->pos = $pos_836;
    								}
    								$_837 = TRUE; break;
    							}
    							while(0);
    							if( $_837 === TRUE ) { $_852 = TRUE; break; }
    							$result = $res_832;
    							$this->pos = $pos_832;
    							$_850 = NULL;
    							do {
    								$res_839 = $result;
    								$pos_839 = $this->pos;
    								$_842 = NULL;
    								do {
    									if (substr($this->string ?? '',$this->pos ?? 0,1) == '{') {
    										$this->pos += 1;
    										$result["text"] .= '{';
    									}
    									else { $_842 = FALSE; break; }
    									$res_841 = $result;
    									$pos_841 = $this->pos;
    									if (substr($this->string ?? '',$this->pos ?? 0,1) == '$') {
    										$this->pos += 1;
    										$result["text"] .= '$';
    										$result = $res_841;
    										$this->pos = $pos_841;
    										$_842 = FALSE; break;
    									}
    									else {
    										$result = $res_841;
    										$this->pos = $pos_841;
    									}
    									$_842 = TRUE; break;
    								}
    								while(0);
    								if( $_842 === TRUE ) { $_850 = TRUE; break; }
    								$result = $res_839;
    								$this->pos = $pos_839;
    								$_848 = NULL;
    								do {
    									if (( $subres = $this->literal( '{$' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									else { $_848 = FALSE; break; }
    									$res_847 = $result;
    									$pos_847 = $this->pos;
    									$_846 = NULL;
    									do {
    										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) {
    											$result["text"] .= $subres;
    										}
    										else { $_846 = FALSE; break; }
    										$_846 = TRUE; break;
    									}
    									while(0);
    									if( $_846 === TRUE ) {
    										$result = $res_847;
    										$this->pos = $pos_847;
    										$_848 = FALSE; break;
    									}
    									if( $_846 === FALSE) {
    										$result = $res_847;
    										$this->pos = $pos_847;
    									}
    									$_848 = TRUE; break;
    								}
    								while(0);
    								if( $_848 === TRUE ) { $_850 = TRUE; break; }
    								$result = $res_839;
    								$this->pos = $pos_839;
    								$_850 = FALSE; break;
    							}
    							while(0);
    							if( $_850 === TRUE ) { $_852 = TRUE; break; }
    							$result = $res_832;
    							$this->pos = $pos_832;
    							$_852 = FALSE; break;
    						}
    						while(0);
    						if( $_852 === TRUE ) { $_854 = TRUE; break; }
    						$result = $res_827;
    						$this->pos = $pos_827;
    						$_854 = FALSE; break;
    					}
    					while(0);
    					if( $_854 === TRUE ) { $_856 = TRUE; break; }
    					$result = $res_825;
    					$this->pos = $pos_825;
    					$_856 = FALSE; break;
    				}
    				while(0);
    				if( $_856 === TRUE ) { $_858 = TRUE; break; }
    				$result = $res_823;
    				$this->pos = $pos_823;
    				$_858 = FALSE; break;
    			}
    			while(0);
    			if( $_858 === FALSE) { $_860 = FALSE; break; }
    			$_860 = TRUE; break;
    		}
    		while(0);
    		if( $_860 === FALSE) {
    			$result = $res_861;
    			$this->pos = $pos_861;
    			unset( $res_861 );
    			unset( $pos_861 );
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

            // Ignore UTF8 BOM at beginning of string.
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
