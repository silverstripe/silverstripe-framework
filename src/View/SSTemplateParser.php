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
    				if (substr($this->string,$this->pos,1) == ',') {
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
            str_replace('$$FINAL', 'XML_val', $sub['php']);
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
    			if (substr($this->string,$this->pos,1) == '(') {
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
    			if (substr($this->string,$this->pos,1) == ')') {
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
    		if (substr($this->string,$this->pos,1) == '.') {
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
    					if (substr($this->string,$this->pos,1) == '.') {
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
    			if (substr($this->string,$this->pos,1) == '.') {
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
    				if (substr($this->string,$this->pos,1) == '=') {
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
    			if (substr($this->string,$this->pos,1) == '=') {
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
        $res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']) . ',';
    }

    function InjectionVariables__finalise(&$res)
    {
        if (substr($res['php'], -1) == ',') {
            $res['php'] = substr($res['php'], 0, -1); //remove last comma in the array
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
    			if (substr($this->string,$this->pos,1) == '}') {
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
    		if (substr($this->string,$this->pos,1) == '$') {
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
    		if (substr($this->string,$this->pos,1) == '}') {
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


    /* Boolean: / (true|false) /i */
    protected $match_Boolean_typestack = array('Boolean');
    function match_Boolean ($stack = array()) {
    	$matchrule = "Boolean"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/ (true|false) /i' ) ) !== FALSE) {
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
    	$_185 = NULL;
    	do {
    		$res_165 = $result;
    		$pos_165 = $this->pos;
    		$_164 = NULL;
    		do {
    			$matcher = 'match_'.'Sign'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_164 = FALSE; break; }
    			$_164 = TRUE; break;
    		}
    		while(0);
    		if( $_164 === FALSE) {
    			$result = $res_165;
    			$this->pos = $pos_165;
    			unset( $res_165 );
    			unset( $pos_165 );
    		}
    		$_183 = NULL;
    		do {
    			$_181 = NULL;
    			do {
    				$res_166 = $result;
    				$pos_166 = $this->pos;
    				$matcher = 'match_'.'Hexadecimal'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_181 = TRUE; break;
    				}
    				$result = $res_166;
    				$this->pos = $pos_166;
    				$_179 = NULL;
    				do {
    					$res_168 = $result;
    					$pos_168 = $this->pos;
    					$matcher = 'match_'.'Binary'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_179 = TRUE; break;
    					}
    					$result = $res_168;
    					$this->pos = $pos_168;
    					$_177 = NULL;
    					do {
    						$res_170 = $result;
    						$pos_170 = $this->pos;
    						$matcher = 'match_'.'Float'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_177 = TRUE; break;
    						}
    						$result = $res_170;
    						$this->pos = $pos_170;
    						$_175 = NULL;
    						do {
    							$res_172 = $result;
    							$pos_172 = $this->pos;
    							$matcher = 'match_'.'Octal'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_175 = TRUE; break;
    							}
    							$result = $res_172;
    							$this->pos = $pos_172;
    							$matcher = 'match_'.'Decimal'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_175 = TRUE; break;
    							}
    							$result = $res_172;
    							$this->pos = $pos_172;
    							$_175 = FALSE; break;
    						}
    						while(0);
    						if( $_175 === TRUE ) { $_177 = TRUE; break; }
    						$result = $res_170;
    						$this->pos = $pos_170;
    						$_177 = FALSE; break;
    					}
    					while(0);
    					if( $_177 === TRUE ) { $_179 = TRUE; break; }
    					$result = $res_168;
    					$this->pos = $pos_168;
    					$_179 = FALSE; break;
    				}
    				while(0);
    				if( $_179 === TRUE ) { $_181 = TRUE; break; }
    				$result = $res_166;
    				$this->pos = $pos_166;
    				$_181 = FALSE; break;
    			}
    			while(0);
    			if( $_181 === FALSE) { $_183 = FALSE; break; }
    			$_183 = TRUE; break;
    		}
    		while(0);
    		if( $_183 === FALSE) { $_185 = FALSE; break; }
    		$_185 = TRUE; break;
    	}
    	while(0);
    	if( $_185 === TRUE ) { return $this->finalise($result); }
    	if( $_185 === FALSE) { return FALSE; }
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
    :Boolean |
    :IntegerOrFloat |
    :Lookup !(< FreeString)|
    :FreeString */
    protected $match_Argument_typestack = array('Argument');
    function match_Argument ($stack = array()) {
    	$matchrule = "Argument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_213 = NULL;
    	do {
    		$res_188 = $result;
    		$pos_188 = $this->pos;
    		$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "DollarMarkedLookup" );
    			$_213 = TRUE; break;
    		}
    		$result = $res_188;
    		$this->pos = $pos_188;
    		$_211 = NULL;
    		do {
    			$res_190 = $result;
    			$pos_190 = $this->pos;
    			$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "QuotedString" );
    				$_211 = TRUE; break;
    			}
    			$result = $res_190;
    			$this->pos = $pos_190;
    			$_209 = NULL;
    			do {
    				$res_192 = $result;
    				$pos_192 = $this->pos;
    				$matcher = 'match_'.'Boolean'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "Boolean" );
    					$_209 = TRUE; break;
    				}
    				$result = $res_192;
    				$this->pos = $pos_192;
    				$_207 = NULL;
    				do {
    					$res_194 = $result;
    					$pos_194 = $this->pos;
    					$matcher = 'match_'.'IntegerOrFloat'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "IntegerOrFloat" );
    						$_207 = TRUE; break;
    					}
    					$result = $res_194;
    					$this->pos = $pos_194;
    					$_205 = NULL;
    					do {
    						$res_196 = $result;
    						$pos_196 = $this->pos;
    						$_202 = NULL;
    						do {
    							$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres, "Lookup" );
    							}
    							else { $_202 = FALSE; break; }
    							$res_201 = $result;
    							$pos_201 = $this->pos;
    							$_200 = NULL;
    							do {
    								if (( $subres = $this->whitespace(  ) ) !== FALSE) {
    									$result["text"] .= $subres;
    								}
    								$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    								}
    								else { $_200 = FALSE; break; }
    								$_200 = TRUE; break;
    							}
    							while(0);
    							if( $_200 === TRUE ) {
    								$result = $res_201;
    								$this->pos = $pos_201;
    								$_202 = FALSE; break;
    							}
    							if( $_200 === FALSE) {
    								$result = $res_201;
    								$this->pos = $pos_201;
    							}
    							$_202 = TRUE; break;
    						}
    						while(0);
    						if( $_202 === TRUE ) { $_205 = TRUE; break; }
    						$result = $res_196;
    						$this->pos = $pos_196;
    						$matcher = 'match_'.'FreeString'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres, "FreeString" );
    							$_205 = TRUE; break;
    						}
    						$result = $res_196;
    						$this->pos = $pos_196;
    						$_205 = FALSE; break;
    					}
    					while(0);
    					if( $_205 === TRUE ) { $_207 = TRUE; break; }
    					$result = $res_194;
    					$this->pos = $pos_194;
    					$_207 = FALSE; break;
    				}
    				while(0);
    				if( $_207 === TRUE ) { $_209 = TRUE; break; }
    				$result = $res_192;
    				$this->pos = $pos_192;
    				$_209 = FALSE; break;
    			}
    			while(0);
    			if( $_209 === TRUE ) { $_211 = TRUE; break; }
    			$result = $res_190;
    			$this->pos = $pos_190;
    			$_211 = FALSE; break;
    		}
    		while(0);
    		if( $_211 === TRUE ) { $_213 = TRUE; break; }
    		$result = $res_188;
    		$this->pos = $pos_188;
    		$_213 = FALSE; break;
    	}
    	while(0);
    	if( $_213 === TRUE ) { return $this->finalise($result); }
    	if( $_213 === FALSE) { return FALSE; }
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
        $res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text']) . "'";
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
        if (count($sub['LookupSteps']) == 1 && !isset($sub['LookupSteps'][0]['Call']['Arguments'])) {
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
        $res['php'] = "'" . str_replace("'", "\\'", trim($sub['text'])) . "'";
    }

    /* ComparisonOperator: "!=" | "==" | ">=" | ">" | "<=" | "<" | "=" */
    protected $match_ComparisonOperator_typestack = array('ComparisonOperator');
    function match_ComparisonOperator ($stack = array()) {
    	$matchrule = "ComparisonOperator"; $result = $this->construct($matchrule, $matchrule, null);
    	$_238 = NULL;
    	do {
    		$res_215 = $result;
    		$pos_215 = $this->pos;
    		if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_238 = TRUE; break;
    		}
    		$result = $res_215;
    		$this->pos = $pos_215;
    		$_236 = NULL;
    		do {
    			$res_217 = $result;
    			$pos_217 = $this->pos;
    			if (( $subres = $this->literal( '==' ) ) !== FALSE) {
    				$result["text"] .= $subres;
    				$_236 = TRUE; break;
    			}
    			$result = $res_217;
    			$this->pos = $pos_217;
    			$_234 = NULL;
    			do {
    				$res_219 = $result;
    				$pos_219 = $this->pos;
    				if (( $subres = $this->literal( '>=' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_234 = TRUE; break;
    				}
    				$result = $res_219;
    				$this->pos = $pos_219;
    				$_232 = NULL;
    				do {
    					$res_221 = $result;
    					$pos_221 = $this->pos;
    					if (substr($this->string,$this->pos,1) == '>') {
    						$this->pos += 1;
    						$result["text"] .= '>';
    						$_232 = TRUE; break;
    					}
    					$result = $res_221;
    					$this->pos = $pos_221;
    					$_230 = NULL;
    					do {
    						$res_223 = $result;
    						$pos_223 = $this->pos;
    						if (( $subres = $this->literal( '<=' ) ) !== FALSE) {
    							$result["text"] .= $subres;
    							$_230 = TRUE; break;
    						}
    						$result = $res_223;
    						$this->pos = $pos_223;
    						$_228 = NULL;
    						do {
    							$res_225 = $result;
    							$pos_225 = $this->pos;
    							if (substr($this->string,$this->pos,1) == '<') {
    								$this->pos += 1;
    								$result["text"] .= '<';
    								$_228 = TRUE; break;
    							}
    							$result = $res_225;
    							$this->pos = $pos_225;
    							if (substr($this->string,$this->pos,1) == '=') {
    								$this->pos += 1;
    								$result["text"] .= '=';
    								$_228 = TRUE; break;
    							}
    							$result = $res_225;
    							$this->pos = $pos_225;
    							$_228 = FALSE; break;
    						}
    						while(0);
    						if( $_228 === TRUE ) { $_230 = TRUE; break; }
    						$result = $res_223;
    						$this->pos = $pos_223;
    						$_230 = FALSE; break;
    					}
    					while(0);
    					if( $_230 === TRUE ) { $_232 = TRUE; break; }
    					$result = $res_221;
    					$this->pos = $pos_221;
    					$_232 = FALSE; break;
    				}
    				while(0);
    				if( $_232 === TRUE ) { $_234 = TRUE; break; }
    				$result = $res_219;
    				$this->pos = $pos_219;
    				$_234 = FALSE; break;
    			}
    			while(0);
    			if( $_234 === TRUE ) { $_236 = TRUE; break; }
    			$result = $res_217;
    			$this->pos = $pos_217;
    			$_236 = FALSE; break;
    		}
    		while(0);
    		if( $_236 === TRUE ) { $_238 = TRUE; break; }
    		$result = $res_215;
    		$this->pos = $pos_215;
    		$_238 = FALSE; break;
    	}
    	while(0);
    	if( $_238 === TRUE ) { return $this->finalise($result); }
    	if( $_238 === FALSE) { return FALSE; }
    }


    /* Comparison: Argument < ComparisonOperator > Argument */
    protected $match_Comparison_typestack = array('Comparison');
    function match_Comparison ($stack = array()) {
    	$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
    	$_245 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_245 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'ComparisonOperator'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_245 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_245 = FALSE; break; }
    		$_245 = TRUE; break;
    	}
    	while(0);
    	if( $_245 === TRUE ) { return $this->finalise($result); }
    	if( $_245 === FALSE) { return FALSE; }
    }



    function Comparison_Argument(&$res, $sub)
    {
        if ($sub['ArgumentMode'] == 'default') {
            if (!empty($res['php'])) {
                $res['php'] .= $sub['string_php'];
            } else {
                $res['php'] = str_replace('$$FINAL', 'XML_val', $sub['lookup_php']);
            }
        } else {
            $res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
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
    	$_252 = NULL;
    	do {
    		$res_250 = $result;
    		$pos_250 = $this->pos;
    		$_249 = NULL;
    		do {
    			$stack[] = $result; $result = $this->construct( $matchrule, "Not" ); 
    			if (( $subres = $this->literal( 'not' ) ) !== FALSE) {
    				$result["text"] .= $subres;
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Not' );
    			}
    			else {
    				$result = array_pop($stack);
    				$_249 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$_249 = TRUE; break;
    		}
    		while(0);
    		if( $_249 === FALSE) {
    			$result = $res_250;
    			$this->pos = $pos_250;
    			unset( $res_250 );
    			unset( $pos_250 );
    		}
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_252 = FALSE; break; }
    		$_252 = TRUE; break;
    	}
    	while(0);
    	if( $_252 === TRUE ) { return $this->finalise($result); }
    	if( $_252 === FALSE) { return FALSE; }
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
            $res['php'] .= str_replace('$$FINAL', 'hasValue', $php);
        }
    }

    /* IfArgumentPortion: Comparison | PresenceCheck */
    protected $match_IfArgumentPortion_typestack = array('IfArgumentPortion');
    function match_IfArgumentPortion ($stack = array()) {
    	$matchrule = "IfArgumentPortion"; $result = $this->construct($matchrule, $matchrule, null);
    	$_257 = NULL;
    	do {
    		$res_254 = $result;
    		$pos_254 = $this->pos;
    		$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_257 = TRUE; break;
    		}
    		$result = $res_254;
    		$this->pos = $pos_254;
    		$matcher = 'match_'.'PresenceCheck'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_257 = TRUE; break;
    		}
    		$result = $res_254;
    		$this->pos = $pos_254;
    		$_257 = FALSE; break;
    	}
    	while(0);
    	if( $_257 === TRUE ) { return $this->finalise($result); }
    	if( $_257 === FALSE) { return FALSE; }
    }



    function IfArgumentPortion_STR(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* BooleanOperator: "||" | "&&" */
    protected $match_BooleanOperator_typestack = array('BooleanOperator');
    function match_BooleanOperator ($stack = array()) {
    	$matchrule = "BooleanOperator"; $result = $this->construct($matchrule, $matchrule, null);
    	$_262 = NULL;
    	do {
    		$res_259 = $result;
    		$pos_259 = $this->pos;
    		if (( $subres = $this->literal( '||' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_262 = TRUE; break;
    		}
    		$result = $res_259;
    		$this->pos = $pos_259;
    		if (( $subres = $this->literal( '&&' ) ) !== FALSE) {
    			$result["text"] .= $subres;
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


    /* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
    protected $match_IfArgument_typestack = array('IfArgument');
    function match_IfArgument ($stack = array()) {
    	$matchrule = "IfArgument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_271 = NULL;
    	do {
    		$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgumentPortion" );
    		}
    		else { $_271 = FALSE; break; }
    		while (true) {
    			$res_270 = $result;
    			$pos_270 = $this->pos;
    			$_269 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'BooleanOperator'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "BooleanOperator" );
    				}
    				else { $_269 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'IfArgumentPortion'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "IfArgumentPortion" );
    				}
    				else { $_269 = FALSE; break; }
    				$_269 = TRUE; break;
    			}
    			while(0);
    			if( $_269 === FALSE) {
    				$result = $res_270;
    				$this->pos = $pos_270;
    				unset( $res_270 );
    				unset( $pos_270 );
    				break;
    			}
    		}
    		$_271 = TRUE; break;
    	}
    	while(0);
    	if( $_271 === TRUE ) { return $this->finalise($result); }
    	if( $_271 === FALSE) { return FALSE; }
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
    	$_281 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_281 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_281 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_281 = FALSE; break; }
    		$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgument" );
    		}
    		else { $_281 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_281 = FALSE; break; }
    		$res_280 = $result;
    		$pos_280 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_280;
    			$this->pos = $pos_280;
    			unset( $res_280 );
    			unset( $pos_280 );
    		}
    		$_281 = TRUE; break;
    	}
    	while(0);
    	if( $_281 === TRUE ) { return $this->finalise($result); }
    	if( $_281 === FALSE) { return FALSE; }
    }


    /* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
    protected $match_ElseIfPart_typestack = array('ElseIfPart');
    function match_ElseIfPart ($stack = array()) {
    	$matchrule = "ElseIfPart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_291 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_291 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_291 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_291 = FALSE; break; }
    		$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "IfArgument" );
    		}
    		else { $_291 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_291 = FALSE; break; }
    		$res_290 = $result;
    		$pos_290 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_290;
    			$this->pos = $pos_290;
    			unset( $res_290 );
    			unset( $pos_290 );
    		}
    		$_291 = TRUE; break;
    	}
    	while(0);
    	if( $_291 === TRUE ) { return $this->finalise($result); }
    	if( $_291 === FALSE) { return FALSE; }
    }


    /* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher? */
    protected $match_ElsePart_typestack = array('ElsePart');
    function match_ElsePart ($stack = array()) {
    	$matchrule = "ElsePart"; $result = $this->construct($matchrule, $matchrule, null);
    	$_299 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_299 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'else' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_299 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_299 = FALSE; break; }
    		$res_298 = $result;
    		$pos_298 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_298;
    			$this->pos = $pos_298;
    			unset( $res_298 );
    			unset( $pos_298 );
    		}
    		$_299 = TRUE; break;
    	}
    	while(0);
    	if( $_299 === TRUE ) { return $this->finalise($result); }
    	if( $_299 === FALSE) { return FALSE; }
    }


    /* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
    protected $match_If_typestack = array('If');
    function match_If ($stack = array()) {
    	$matchrule = "If"; $result = $this->construct($matchrule, $matchrule, null);
    	$_309 = NULL;
    	do {
    		$matcher = 'match_'.'IfPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_309 = FALSE; break; }
    		while (true) {
    			$res_302 = $result;
    			$pos_302 = $this->pos;
    			$matcher = 'match_'.'ElseIfPart'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else {
    				$result = $res_302;
    				$this->pos = $pos_302;
    				unset( $res_302 );
    				unset( $pos_302 );
    				break;
    			}
    		}
    		$res_303 = $result;
    		$pos_303 = $this->pos;
    		$matcher = 'match_'.'ElsePart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else {
    			$result = $res_303;
    			$this->pos = $pos_303;
    			unset( $res_303 );
    			unset( $pos_303 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_309 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_if' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_309 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_309 = FALSE; break; }
    		$_309 = TRUE; break;
    	}
    	while(0);
    	if( $_309 === TRUE ) { return $this->finalise($result); }
    	if( $_309 === FALSE) { return FALSE; }
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
    	$_325 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_325 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'require' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_325 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_325 = FALSE; break; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "Call" ); 
    		$_321 = NULL;
    		do {
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Method" );
    			}
    			else { $_321 = FALSE; break; }
    			if (substr($this->string,$this->pos,1) == '(') {
    				$this->pos += 1;
    				$result["text"] .= '(';
    			}
    			else { $_321 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "CallArguments" );
    			}
    			else { $_321 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (substr($this->string,$this->pos,1) == ')') {
    				$this->pos += 1;
    				$result["text"] .= ')';
    			}
    			else { $_321 = FALSE; break; }
    			$_321 = TRUE; break;
    		}
    		while(0);
    		if( $_321 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Call' );
    		}
    		if( $_321 === FALSE) {
    			$result = array_pop($stack);
    			$_325 = FALSE; break;
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_325 = FALSE; break; }
    		$_325 = TRUE; break;
    	}
    	while(0);
    	if( $_325 === TRUE ) { return $this->finalise($result); }
    	if( $_325 === FALSE) { return FALSE; }
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
    	$_345 = NULL;
    	do {
    		$res_333 = $result;
    		$pos_333 = $this->pos;
    		$_332 = NULL;
    		do {
    			$_330 = NULL;
    			do {
    				$res_327 = $result;
    				$pos_327 = $this->pos;
    				if (( $subres = $this->literal( 'if ' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_330 = TRUE; break;
    				}
    				$result = $res_327;
    				$this->pos = $pos_327;
    				if (( $subres = $this->literal( 'unless ' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_330 = TRUE; break;
    				}
    				$result = $res_327;
    				$this->pos = $pos_327;
    				$_330 = FALSE; break;
    			}
    			while(0);
    			if( $_330 === FALSE) { $_332 = FALSE; break; }
    			$_332 = TRUE; break;
    		}
    		while(0);
    		if( $_332 === TRUE ) {
    			$result = $res_333;
    			$this->pos = $pos_333;
    			$_345 = FALSE; break;
    		}
    		if( $_332 === FALSE) {
    			$result = $res_333;
    			$this->pos = $pos_333;
    		}
    		$_343 = NULL;
    		do {
    			$_341 = NULL;
    			do {
    				$res_334 = $result;
    				$pos_334 = $this->pos;
    				$matcher = 'match_'.'DollarMarkedLookup'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "DollarMarkedLookup" );
    					$_341 = TRUE; break;
    				}
    				$result = $res_334;
    				$this->pos = $pos_334;
    				$_339 = NULL;
    				do {
    					$res_336 = $result;
    					$pos_336 = $this->pos;
    					$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "QuotedString" );
    						$_339 = TRUE; break;
    					}
    					$result = $res_336;
    					$this->pos = $pos_336;
    					$matcher = 'match_'.'Lookup'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres, "Lookup" );
    						$_339 = TRUE; break;
    					}
    					$result = $res_336;
    					$this->pos = $pos_336;
    					$_339 = FALSE; break;
    				}
    				while(0);
    				if( $_339 === TRUE ) { $_341 = TRUE; break; }
    				$result = $res_334;
    				$this->pos = $pos_334;
    				$_341 = FALSE; break;
    			}
    			while(0);
    			if( $_341 === FALSE) { $_343 = FALSE; break; }
    			$_343 = TRUE; break;
    		}
    		while(0);
    		if( $_343 === FALSE) { $_345 = FALSE; break; }
    		$_345 = TRUE; break;
    	}
    	while(0);
    	if( $_345 === TRUE ) { return $this->finalise($result); }
    	if( $_345 === FALSE) { return FALSE; }
    }



    function CacheBlockArgument_DollarMarkedLookup(&$res, $sub)
    {
        $res['php'] = $sub['Lookup']['php'];
    }

    function CacheBlockArgument_QuotedString(&$res, $sub)
    {
        $res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text']) . "'";
    }

    function CacheBlockArgument_Lookup(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* CacheBlockArguments: CacheBlockArgument ( < "," < CacheBlockArgument )* */
    protected $match_CacheBlockArguments_typestack = array('CacheBlockArguments');
    function match_CacheBlockArguments ($stack = array()) {
    	$matchrule = "CacheBlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
    	$_354 = NULL;
    	do {
    		$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_354 = FALSE; break; }
    		while (true) {
    			$res_353 = $result;
    			$pos_353 = $this->pos;
    			$_352 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string,$this->pos,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_352 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'CacheBlockArgument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    				}
    				else { $_352 = FALSE; break; }
    				$_352 = TRUE; break;
    			}
    			while(0);
    			if( $_352 === FALSE) {
    				$result = $res_353;
    				$this->pos = $pos_353;
    				unset( $res_353 );
    				unset( $pos_353 );
    				break;
    			}
    		}
    		$_354 = TRUE; break;
    	}
    	while(0);
    	if( $_354 === TRUE ) { return $this->finalise($result); }
    	if( $_354 === FALSE) { return FALSE; }
    }



    function CacheBlockArguments_CacheBlockArgument(&$res, $sub)
    {
        if (!empty($res['php'])) {
            $res['php'] .= ".'_'.";
        } else {
            $res['php'] = '';
        }

        $res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
    }

    /* CacheBlockTemplate: (Comment | Translate | If | Require |    OldI18NTag | Include | ClosedBlock |
    OpenBlock | MalformedBlock | MalformedBracketInjection | Injection | Text)+ */
    protected $match_CacheBlockTemplate_typestack = array('CacheBlockTemplate','Template');
    function match_CacheBlockTemplate ($stack = array()) {
    	$matchrule = "CacheBlockTemplate"; $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'CacheRestrictedTemplate'));
    	$count = 0;
    	while (true) {
    		$res_402 = $result;
    		$pos_402 = $this->pos;
    		$_401 = NULL;
    		do {
    			$_399 = NULL;
    			do {
    				$res_356 = $result;
    				$pos_356 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_399 = TRUE; break;
    				}
    				$result = $res_356;
    				$this->pos = $pos_356;
    				$_397 = NULL;
    				do {
    					$res_358 = $result;
    					$pos_358 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_397 = TRUE; break;
    					}
    					$result = $res_358;
    					$this->pos = $pos_358;
    					$_395 = NULL;
    					do {
    						$res_360 = $result;
    						$pos_360 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_395 = TRUE; break;
    						}
    						$result = $res_360;
    						$this->pos = $pos_360;
    						$_393 = NULL;
    						do {
    							$res_362 = $result;
    							$pos_362 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_393 = TRUE; break;
    							}
    							$result = $res_362;
    							$this->pos = $pos_362;
    							$_391 = NULL;
    							do {
    								$res_364 = $result;
    								$pos_364 = $this->pos;
    								$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_391 = TRUE; break;
    								}
    								$result = $res_364;
    								$this->pos = $pos_364;
    								$_389 = NULL;
    								do {
    									$res_366 = $result;
    									$pos_366 = $this->pos;
    									$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_389 = TRUE; break;
    									}
    									$result = $res_366;
    									$this->pos = $pos_366;
    									$_387 = NULL;
    									do {
    										$res_368 = $result;
    										$pos_368 = $this->pos;
    										$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_387 = TRUE; break;
    										}
    										$result = $res_368;
    										$this->pos = $pos_368;
    										$_385 = NULL;
    										do {
    											$res_370 = $result;
    											$pos_370 = $this->pos;
    											$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_385 = TRUE; break;
    											}
    											$result = $res_370;
    											$this->pos = $pos_370;
    											$_383 = NULL;
    											do {
    												$res_372 = $result;
    												$pos_372 = $this->pos;
    												$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_383 = TRUE; break;
    												}
    												$result = $res_372;
    												$this->pos = $pos_372;
    												$_381 = NULL;
    												do {
    													$res_374 = $result;
    													$pos_374 = $this->pos;
    													$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_381 = TRUE; break;
    													}
    													$result = $res_374;
    													$this->pos = $pos_374;
    													$_379 = NULL;
    													do {
    														$res_376 = $result;
    														$pos_376 = $this->pos;
    														$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_379 = TRUE; break;
    														}
    														$result = $res_376;
    														$this->pos = $pos_376;
    														$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_379 = TRUE; break;
    														}
    														$result = $res_376;
    														$this->pos = $pos_376;
    														$_379 = FALSE; break;
    													}
    													while(0);
    													if( $_379 === TRUE ) { $_381 = TRUE; break; }
    													$result = $res_374;
    													$this->pos = $pos_374;
    													$_381 = FALSE; break;
    												}
    												while(0);
    												if( $_381 === TRUE ) { $_383 = TRUE; break; }
    												$result = $res_372;
    												$this->pos = $pos_372;
    												$_383 = FALSE; break;
    											}
    											while(0);
    											if( $_383 === TRUE ) { $_385 = TRUE; break; }
    											$result = $res_370;
    											$this->pos = $pos_370;
    											$_385 = FALSE; break;
    										}
    										while(0);
    										if( $_385 === TRUE ) { $_387 = TRUE; break; }
    										$result = $res_368;
    										$this->pos = $pos_368;
    										$_387 = FALSE; break;
    									}
    									while(0);
    									if( $_387 === TRUE ) { $_389 = TRUE; break; }
    									$result = $res_366;
    									$this->pos = $pos_366;
    									$_389 = FALSE; break;
    								}
    								while(0);
    								if( $_389 === TRUE ) { $_391 = TRUE; break; }
    								$result = $res_364;
    								$this->pos = $pos_364;
    								$_391 = FALSE; break;
    							}
    							while(0);
    							if( $_391 === TRUE ) { $_393 = TRUE; break; }
    							$result = $res_362;
    							$this->pos = $pos_362;
    							$_393 = FALSE; break;
    						}
    						while(0);
    						if( $_393 === TRUE ) { $_395 = TRUE; break; }
    						$result = $res_360;
    						$this->pos = $pos_360;
    						$_395 = FALSE; break;
    					}
    					while(0);
    					if( $_395 === TRUE ) { $_397 = TRUE; break; }
    					$result = $res_358;
    					$this->pos = $pos_358;
    					$_397 = FALSE; break;
    				}
    				while(0);
    				if( $_397 === TRUE ) { $_399 = TRUE; break; }
    				$result = $res_356;
    				$this->pos = $pos_356;
    				$_399 = FALSE; break;
    			}
    			while(0);
    			if( $_399 === FALSE) { $_401 = FALSE; break; }
    			$_401 = TRUE; break;
    		}
    		while(0);
    		if( $_401 === FALSE) {
    			$result = $res_402;
    			$this->pos = $pos_402;
    			unset( $res_402 );
    			unset( $pos_402 );
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
    	$_439 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_439 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_439 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_407 = $result;
    		$pos_407 = $this->pos;
    		$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else {
    			$result = $res_407;
    			$this->pos = $pos_407;
    			unset( $res_407 );
    			unset( $pos_407 );
    		}
    		$res_419 = $result;
    		$pos_419 = $this->pos;
    		$_418 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
    			$_414 = NULL;
    			do {
    				$_412 = NULL;
    				do {
    					$res_409 = $result;
    					$pos_409 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_412 = TRUE; break;
    					}
    					$result = $res_409;
    					$this->pos = $pos_409;
    					if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_412 = TRUE; break;
    					}
    					$result = $res_409;
    					$this->pos = $pos_409;
    					$_412 = FALSE; break;
    				}
    				while(0);
    				if( $_412 === FALSE) { $_414 = FALSE; break; }
    				$_414 = TRUE; break;
    			}
    			while(0);
    			if( $_414 === TRUE ) {
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Conditional' );
    			}
    			if( $_414 === FALSE) {
    				$result = array_pop($stack);
    				$_418 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Condition" );
    			}
    			else { $_418 = FALSE; break; }
    			$_418 = TRUE; break;
    		}
    		while(0);
    		if( $_418 === FALSE) {
    			$result = $res_419;
    			$this->pos = $pos_419;
    			unset( $res_419 );
    			unset( $pos_419 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_439 = FALSE; break; }
    		$res_422 = $result;
    		$pos_422 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_422;
    			$this->pos = $pos_422;
    			unset( $res_422 );
    			unset( $pos_422 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_439 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_439 = FALSE; break; }
    		$_435 = NULL;
    		do {
    			$_433 = NULL;
    			do {
    				$res_426 = $result;
    				$pos_426 = $this->pos;
    				if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_433 = TRUE; break;
    				}
    				$result = $res_426;
    				$this->pos = $pos_426;
    				$_431 = NULL;
    				do {
    					$res_428 = $result;
    					$pos_428 = $this->pos;
    					if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_431 = TRUE; break;
    					}
    					$result = $res_428;
    					$this->pos = $pos_428;
    					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_431 = TRUE; break;
    					}
    					$result = $res_428;
    					$this->pos = $pos_428;
    					$_431 = FALSE; break;
    				}
    				while(0);
    				if( $_431 === TRUE ) { $_433 = TRUE; break; }
    				$result = $res_426;
    				$this->pos = $pos_426;
    				$_433 = FALSE; break;
    			}
    			while(0);
    			if( $_433 === FALSE) { $_435 = FALSE; break; }
    			$_435 = TRUE; break;
    		}
    		while(0);
    		if( $_435 === FALSE) { $_439 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_439 = FALSE; break; }
    		$_439 = TRUE; break;
    	}
    	while(0);
    	if( $_439 === TRUE ) { return $this->finalise($result); }
    	if( $_439 === FALSE) { return FALSE; }
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
    		$res_495 = $result;
    		$pos_495 = $this->pos;
    		$_494 = NULL;
    		do {
    			$_492 = NULL;
    			do {
    				$res_441 = $result;
    				$pos_441 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_492 = TRUE; break;
    				}
    				$result = $res_441;
    				$this->pos = $pos_441;
    				$_490 = NULL;
    				do {
    					$res_443 = $result;
    					$pos_443 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_490 = TRUE; break;
    					}
    					$result = $res_443;
    					$this->pos = $pos_443;
    					$_488 = NULL;
    					do {
    						$res_445 = $result;
    						$pos_445 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_488 = TRUE; break;
    						}
    						$result = $res_445;
    						$this->pos = $pos_445;
    						$_486 = NULL;
    						do {
    							$res_447 = $result;
    							$pos_447 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_486 = TRUE; break;
    							}
    							$result = $res_447;
    							$this->pos = $pos_447;
    							$_484 = NULL;
    							do {
    								$res_449 = $result;
    								$pos_449 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_484 = TRUE; break;
    								}
    								$result = $res_449;
    								$this->pos = $pos_449;
    								$_482 = NULL;
    								do {
    									$res_451 = $result;
    									$pos_451 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_482 = TRUE; break;
    									}
    									$result = $res_451;
    									$this->pos = $pos_451;
    									$_480 = NULL;
    									do {
    										$res_453 = $result;
    										$pos_453 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_480 = TRUE; break;
    										}
    										$result = $res_453;
    										$this->pos = $pos_453;
    										$_478 = NULL;
    										do {
    											$res_455 = $result;
    											$pos_455 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_478 = TRUE; break;
    											}
    											$result = $res_455;
    											$this->pos = $pos_455;
    											$_476 = NULL;
    											do {
    												$res_457 = $result;
    												$pos_457 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_476 = TRUE; break;
    												}
    												$result = $res_457;
    												$this->pos = $pos_457;
    												$_474 = NULL;
    												do {
    													$res_459 = $result;
    													$pos_459 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_474 = TRUE; break;
    													}
    													$result = $res_459;
    													$this->pos = $pos_459;
    													$_472 = NULL;
    													do {
    														$res_461 = $result;
    														$pos_461 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_472 = TRUE; break;
    														}
    														$result = $res_461;
    														$this->pos = $pos_461;
    														$_470 = NULL;
    														do {
    															$res_463 = $result;
    															$pos_463 = $this->pos;
    															$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_470 = TRUE; break;
    															}
    															$result = $res_463;
    															$this->pos = $pos_463;
    															$_468 = NULL;
    															do {
    																$res_465 = $result;
    																$pos_465 = $this->pos;
    																$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_468 = TRUE; break;
    																}
    																$result = $res_465;
    																$this->pos = $pos_465;
    																$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_468 = TRUE; break;
    																}
    																$result = $res_465;
    																$this->pos = $pos_465;
    																$_468 = FALSE; break;
    															}
    															while(0);
    															if( $_468 === TRUE ) {
    																$_470 = TRUE; break;
    															}
    															$result = $res_463;
    															$this->pos = $pos_463;
    															$_470 = FALSE; break;
    														}
    														while(0);
    														if( $_470 === TRUE ) { $_472 = TRUE; break; }
    														$result = $res_461;
    														$this->pos = $pos_461;
    														$_472 = FALSE; break;
    													}
    													while(0);
    													if( $_472 === TRUE ) { $_474 = TRUE; break; }
    													$result = $res_459;
    													$this->pos = $pos_459;
    													$_474 = FALSE; break;
    												}
    												while(0);
    												if( $_474 === TRUE ) { $_476 = TRUE; break; }
    												$result = $res_457;
    												$this->pos = $pos_457;
    												$_476 = FALSE; break;
    											}
    											while(0);
    											if( $_476 === TRUE ) { $_478 = TRUE; break; }
    											$result = $res_455;
    											$this->pos = $pos_455;
    											$_478 = FALSE; break;
    										}
    										while(0);
    										if( $_478 === TRUE ) { $_480 = TRUE; break; }
    										$result = $res_453;
    										$this->pos = $pos_453;
    										$_480 = FALSE; break;
    									}
    									while(0);
    									if( $_480 === TRUE ) { $_482 = TRUE; break; }
    									$result = $res_451;
    									$this->pos = $pos_451;
    									$_482 = FALSE; break;
    								}
    								while(0);
    								if( $_482 === TRUE ) { $_484 = TRUE; break; }
    								$result = $res_449;
    								$this->pos = $pos_449;
    								$_484 = FALSE; break;
    							}
    							while(0);
    							if( $_484 === TRUE ) { $_486 = TRUE; break; }
    							$result = $res_447;
    							$this->pos = $pos_447;
    							$_486 = FALSE; break;
    						}
    						while(0);
    						if( $_486 === TRUE ) { $_488 = TRUE; break; }
    						$result = $res_445;
    						$this->pos = $pos_445;
    						$_488 = FALSE; break;
    					}
    					while(0);
    					if( $_488 === TRUE ) { $_490 = TRUE; break; }
    					$result = $res_443;
    					$this->pos = $pos_443;
    					$_490 = FALSE; break;
    				}
    				while(0);
    				if( $_490 === TRUE ) { $_492 = TRUE; break; }
    				$result = $res_441;
    				$this->pos = $pos_441;
    				$_492 = FALSE; break;
    			}
    			while(0);
    			if( $_492 === FALSE) { $_494 = FALSE; break; }
    			$_494 = TRUE; break;
    		}
    		while(0);
    		if( $_494 === FALSE) {
    			$result = $res_495;
    			$this->pos = $pos_495;
    			unset( $res_495 );
    			unset( $pos_495 );
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
    	$_550 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_550 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "CacheTag" ); 
    		$_503 = NULL;
    		do {
    			$_501 = NULL;
    			do {
    				$res_498 = $result;
    				$pos_498 = $this->pos;
    				if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_501 = TRUE; break;
    				}
    				$result = $res_498;
    				$this->pos = $pos_498;
    				if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_501 = TRUE; break;
    				}
    				$result = $res_498;
    				$this->pos = $pos_498;
    				$_501 = FALSE; break;
    			}
    			while(0);
    			if( $_501 === FALSE) { $_503 = FALSE; break; }
    			$_503 = TRUE; break;
    		}
    		while(0);
    		if( $_503 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'CacheTag' );
    		}
    		if( $_503 === FALSE) {
    			$result = array_pop($stack);
    			$_550 = FALSE; break;
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_508 = $result;
    		$pos_508 = $this->pos;
    		$_507 = NULL;
    		do {
    			$matcher = 'match_'.'CacheBlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_507 = FALSE; break; }
    			$_507 = TRUE; break;
    		}
    		while(0);
    		if( $_507 === FALSE) {
    			$result = $res_508;
    			$this->pos = $pos_508;
    			unset( $res_508 );
    			unset( $pos_508 );
    		}
    		$res_520 = $result;
    		$pos_520 = $this->pos;
    		$_519 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$stack[] = $result; $result = $this->construct( $matchrule, "Conditional" ); 
    			$_515 = NULL;
    			do {
    				$_513 = NULL;
    				do {
    					$res_510 = $result;
    					$pos_510 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_513 = TRUE; break;
    					}
    					$result = $res_510;
    					$this->pos = $pos_510;
    					if (( $subres = $this->literal( 'unless' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_513 = TRUE; break;
    					}
    					$result = $res_510;
    					$this->pos = $pos_510;
    					$_513 = FALSE; break;
    				}
    				while(0);
    				if( $_513 === FALSE) { $_515 = FALSE; break; }
    				$_515 = TRUE; break;
    			}
    			while(0);
    			if( $_515 === TRUE ) {
    				$subres = $result; $result = array_pop($stack);
    				$this->store( $result, $subres, 'Conditional' );
    			}
    			if( $_515 === FALSE) {
    				$result = array_pop($stack);
    				$_519 = FALSE; break;
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$matcher = 'match_'.'IfArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Condition" );
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
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_550 = FALSE; break; }
    		while (true) {
    			$res_533 = $result;
    			$pos_533 = $this->pos;
    			$_532 = NULL;
    			do {
    				$_530 = NULL;
    				do {
    					$res_523 = $result;
    					$pos_523 = $this->pos;
    					$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_530 = TRUE; break;
    					}
    					$result = $res_523;
    					$this->pos = $pos_523;
    					$_528 = NULL;
    					do {
    						$res_525 = $result;
    						$pos_525 = $this->pos;
    						$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_528 = TRUE; break;
    						}
    						$result = $res_525;
    						$this->pos = $pos_525;
    						$matcher = 'match_'.'CacheBlockTemplate'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_528 = TRUE; break;
    						}
    						$result = $res_525;
    						$this->pos = $pos_525;
    						$_528 = FALSE; break;
    					}
    					while(0);
    					if( $_528 === TRUE ) { $_530 = TRUE; break; }
    					$result = $res_523;
    					$this->pos = $pos_523;
    					$_530 = FALSE; break;
    				}
    				while(0);
    				if( $_530 === FALSE) { $_532 = FALSE; break; }
    				$_532 = TRUE; break;
    			}
    			while(0);
    			if( $_532 === FALSE) {
    				$result = $res_533;
    				$this->pos = $pos_533;
    				unset( $res_533 );
    				unset( $pos_533 );
    				break;
    			}
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_550 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_550 = FALSE; break; }
    		$_546 = NULL;
    		do {
    			$_544 = NULL;
    			do {
    				$res_537 = $result;
    				$pos_537 = $this->pos;
    				if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_544 = TRUE; break;
    				}
    				$result = $res_537;
    				$this->pos = $pos_537;
    				$_542 = NULL;
    				do {
    					$res_539 = $result;
    					$pos_539 = $this->pos;
    					if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_542 = TRUE; break;
    					}
    					$result = $res_539;
    					$this->pos = $pos_539;
    					if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_542 = TRUE; break;
    					}
    					$result = $res_539;
    					$this->pos = $pos_539;
    					$_542 = FALSE; break;
    				}
    				while(0);
    				if( $_542 === TRUE ) { $_544 = TRUE; break; }
    				$result = $res_537;
    				$this->pos = $pos_537;
    				$_544 = FALSE; break;
    			}
    			while(0);
    			if( $_544 === FALSE) { $_546 = FALSE; break; }
    			$_546 = TRUE; break;
    		}
    		while(0);
    		if( $_546 === FALSE) { $_550 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_550 = FALSE; break; }
    		$_550 = TRUE; break;
    	}
    	while(0);
    	if( $_550 === TRUE ) { return $this->finalise($result); }
    	if( $_550 === FALSE) { return FALSE; }
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
            . '.\'_' . sha1($sub['php']) // sha of template
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
    	$_569 = NULL;
    	do {
    		if (( $subres = $this->literal( '_t' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_569 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_569 = FALSE; break; }
    		if (substr($this->string,$this->pos,1) == '(') {
    			$this->pos += 1;
    			$result["text"] .= '(';
    		}
    		else { $_569 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_569 = FALSE; break; }
    		$matcher = 'match_'.'QuotedString'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_569 = FALSE; break; }
    		$res_562 = $result;
    		$pos_562 = $this->pos;
    		$_561 = NULL;
    		do {
    			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_561 = FALSE; break; }
    			if (substr($this->string,$this->pos,1) == ',') {
    				$this->pos += 1;
    				$result["text"] .= ',';
    			}
    			else { $_561 = FALSE; break; }
    			$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_561 = FALSE; break; }
    			$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_561 = FALSE; break; }
    			$_561 = TRUE; break;
    		}
    		while(0);
    		if( $_561 === FALSE) {
    			$result = $res_562;
    			$this->pos = $pos_562;
    			unset( $res_562 );
    			unset( $pos_562 );
    		}
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_569 = FALSE; break; }
    		if (substr($this->string,$this->pos,1) == ')') {
    			$this->pos += 1;
    			$result["text"] .= ')';
    		}
    		else { $_569 = FALSE; break; }
    		$matcher = 'match_'.'N'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_569 = FALSE; break; }
    		$res_568 = $result;
    		$pos_568 = $this->pos;
    		$_567 = NULL;
    		do {
    			if (substr($this->string,$this->pos,1) == ';') {
    				$this->pos += 1;
    				$result["text"] .= ';';
    			}
    			else { $_567 = FALSE; break; }
    			$_567 = TRUE; break;
    		}
    		while(0);
    		if( $_567 === FALSE) {
    			$result = $res_568;
    			$this->pos = $pos_568;
    			unset( $res_568 );
    			unset( $pos_568 );
    		}
    		$_569 = TRUE; break;
    	}
    	while(0);
    	if( $_569 === TRUE ) { return $this->finalise($result); }
    	if( $_569 === FALSE) { return FALSE; }
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
        if (strpos($entity, '.') === false) {
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
    	$_577 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_577 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_577 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_577 = FALSE; break; }
    		$_577 = TRUE; break;
    	}
    	while(0);
    	if( $_577 === TRUE ) { return $this->finalise($result); }
    	if( $_577 === FALSE) { return FALSE; }
    }



    function OldTTag_OldTPart(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>" */
    protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
    function match_OldSprintfTag ($stack = array()) {
    	$matchrule = "OldSprintfTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_594 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'sprintf' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string,$this->pos,1) == '(') {
    			$this->pos += 1;
    			$result["text"] .= '(';
    		}
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'OldTPart'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string,$this->pos,1) == ',') {
    			$this->pos += 1;
    			$result["text"] .= ',';
    		}
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'CallArguments'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    		}
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (substr($this->string,$this->pos,1) == ')') {
    			$this->pos += 1;
    			$result["text"] .= ')';
    		}
    		else { $_594 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_594 = FALSE; break; }
    		$_594 = TRUE; break;
    	}
    	while(0);
    	if( $_594 === TRUE ) { return $this->finalise($result); }
    	if( $_594 === FALSE) { return FALSE; }
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
    	$_599 = NULL;
    	do {
    		$res_596 = $result;
    		$pos_596 = $this->pos;
    		$matcher = 'match_'.'OldSprintfTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_599 = TRUE; break;
    		}
    		$result = $res_596;
    		$this->pos = $pos_596;
    		$matcher = 'match_'.'OldTTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_599 = TRUE; break;
    		}
    		$result = $res_596;
    		$this->pos = $pos_596;
    		$_599 = FALSE; break;
    	}
    	while(0);
    	if( $_599 === TRUE ) { return $this->finalise($result); }
    	if( $_599 === FALSE) { return FALSE; }
    }



    function OldI18NTag_STR(&$res, $sub)
    {
        $res['php'] = '$val .= ' . $sub['php'] . ';';
    }

    /* NamedArgument: Name:Word "=" Value:Argument */
    protected $match_NamedArgument_typestack = array('NamedArgument');
    function match_NamedArgument ($stack = array()) {
    	$matchrule = "NamedArgument"; $result = $this->construct($matchrule, $matchrule, null);
    	$_604 = NULL;
    	do {
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Name" );
    		}
    		else { $_604 = FALSE; break; }
    		if (substr($this->string,$this->pos,1) == '=') {
    			$this->pos += 1;
    			$result["text"] .= '=';
    		}
    		else { $_604 = FALSE; break; }
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Value" );
    		}
    		else { $_604 = FALSE; break; }
    		$_604 = TRUE; break;
    	}
    	while(0);
    	if( $_604 === TRUE ) { return $this->finalise($result); }
    	if( $_604 === FALSE) { return FALSE; }
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
    	$_623 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_623 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'include' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_623 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$matcher = 'match_'.'NamespacedWord'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else { $_623 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_620 = $result;
    		$pos_620 = $this->pos;
    		$_619 = NULL;
    		do {
    			$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_619 = FALSE; break; }
    			while (true) {
    				$res_618 = $result;
    				$pos_618 = $this->pos;
    				$_617 = NULL;
    				do {
    					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    					if (substr($this->string,$this->pos,1) == ',') {
    						$this->pos += 1;
    						$result["text"] .= ',';
    					}
    					else { $_617 = FALSE; break; }
    					if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    					$matcher = 'match_'.'NamedArgument'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    					}
    					else { $_617 = FALSE; break; }
    					$_617 = TRUE; break;
    				}
    				while(0);
    				if( $_617 === FALSE) {
    					$result = $res_618;
    					$this->pos = $pos_618;
    					unset( $res_618 );
    					unset( $pos_618 );
    					break;
    				}
    			}
    			$_619 = TRUE; break;
    		}
    		while(0);
    		if( $_619 === FALSE) {
    			$result = $res_620;
    			$this->pos = $pos_620;
    			unset( $res_620 );
    			unset( $pos_620 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_623 = FALSE; break; }
    		$_623 = TRUE; break;
    	}
    	while(0);
    	if( $_623 === TRUE ) { return $this->finalise($result); }
    	if( $_623 === FALSE) { return FALSE; }
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
                '$val .= \'<!-- include '.addslashes($template).' -->\';'. "\n".
                $res['php'].
                '$val .= \'<!-- end include '.addslashes($template).' -->\';'. "\n";
        }
    }

    /* BlockArguments: :Argument ( < "," < :Argument)* */
    protected $match_BlockArguments_typestack = array('BlockArguments');
    function match_BlockArguments ($stack = array()) {
    	$matchrule = "BlockArguments"; $result = $this->construct($matchrule, $matchrule, null);
    	$_632 = NULL;
    	do {
    		$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Argument" );
    		}
    		else { $_632 = FALSE; break; }
    		while (true) {
    			$res_631 = $result;
    			$pos_631 = $this->pos;
    			$_630 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				if (substr($this->string,$this->pos,1) == ',') {
    					$this->pos += 1;
    					$result["text"] .= ',';
    				}
    				else { $_630 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				$matcher = 'match_'.'Argument'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "Argument" );
    				}
    				else { $_630 = FALSE; break; }
    				$_630 = TRUE; break;
    			}
    			while(0);
    			if( $_630 === FALSE) {
    				$result = $res_631;
    				$this->pos = $pos_631;
    				unset( $res_631 );
    				unset( $pos_631 );
    				break;
    			}
    		}
    		$_632 = TRUE; break;
    	}
    	while(0);
    	if( $_632 === TRUE ) { return $this->finalise($result); }
    	if( $_632 === FALSE) { return FALSE; }
    }


    /* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")]) */
    protected $match_NotBlockTag_typestack = array('NotBlockTag');
    function match_NotBlockTag ($stack = array()) {
    	$matchrule = "NotBlockTag"; $result = $this->construct($matchrule, $matchrule, null);
    	$_670 = NULL;
    	do {
    		$res_634 = $result;
    		$pos_634 = $this->pos;
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) {
    			$result["text"] .= $subres;
    			$_670 = TRUE; break;
    		}
    		$result = $res_634;
    		$this->pos = $pos_634;
    		$_668 = NULL;
    		do {
    			$_665 = NULL;
    			do {
    				$_663 = NULL;
    				do {
    					$res_636 = $result;
    					$pos_636 = $this->pos;
    					if (( $subres = $this->literal( 'if' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_663 = TRUE; break;
    					}
    					$result = $res_636;
    					$this->pos = $pos_636;
    					$_661 = NULL;
    					do {
    						$res_638 = $result;
    						$pos_638 = $this->pos;
    						if (( $subres = $this->literal( 'else_if' ) ) !== FALSE) {
    							$result["text"] .= $subres;
    							$_661 = TRUE; break;
    						}
    						$result = $res_638;
    						$this->pos = $pos_638;
    						$_659 = NULL;
    						do {
    							$res_640 = $result;
    							$pos_640 = $this->pos;
    							if (( $subres = $this->literal( 'else' ) ) !== FALSE) {
    								$result["text"] .= $subres;
    								$_659 = TRUE; break;
    							}
    							$result = $res_640;
    							$this->pos = $pos_640;
    							$_657 = NULL;
    							do {
    								$res_642 = $result;
    								$pos_642 = $this->pos;
    								if (( $subres = $this->literal( 'require' ) ) !== FALSE) {
    									$result["text"] .= $subres;
    									$_657 = TRUE; break;
    								}
    								$result = $res_642;
    								$this->pos = $pos_642;
    								$_655 = NULL;
    								do {
    									$res_644 = $result;
    									$pos_644 = $this->pos;
    									if (( $subres = $this->literal( 'cached' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    										$_655 = TRUE; break;
    									}
    									$result = $res_644;
    									$this->pos = $pos_644;
    									$_653 = NULL;
    									do {
    										$res_646 = $result;
    										$pos_646 = $this->pos;
    										if (( $subres = $this->literal( 'uncached' ) ) !== FALSE) {
    											$result["text"] .= $subres;
    											$_653 = TRUE; break;
    										}
    										$result = $res_646;
    										$this->pos = $pos_646;
    										$_651 = NULL;
    										do {
    											$res_648 = $result;
    											$pos_648 = $this->pos;
    											if (( $subres = $this->literal( 'cacheblock' ) ) !== FALSE) {
    												$result["text"] .= $subres;
    												$_651 = TRUE; break;
    											}
    											$result = $res_648;
    											$this->pos = $pos_648;
    											if (( $subres = $this->literal( 'include' ) ) !== FALSE) {
    												$result["text"] .= $subres;
    												$_651 = TRUE; break;
    											}
    											$result = $res_648;
    											$this->pos = $pos_648;
    											$_651 = FALSE; break;
    										}
    										while(0);
    										if( $_651 === TRUE ) { $_653 = TRUE; break; }
    										$result = $res_646;
    										$this->pos = $pos_646;
    										$_653 = FALSE; break;
    									}
    									while(0);
    									if( $_653 === TRUE ) { $_655 = TRUE; break; }
    									$result = $res_644;
    									$this->pos = $pos_644;
    									$_655 = FALSE; break;
    								}
    								while(0);
    								if( $_655 === TRUE ) { $_657 = TRUE; break; }
    								$result = $res_642;
    								$this->pos = $pos_642;
    								$_657 = FALSE; break;
    							}
    							while(0);
    							if( $_657 === TRUE ) { $_659 = TRUE; break; }
    							$result = $res_640;
    							$this->pos = $pos_640;
    							$_659 = FALSE; break;
    						}
    						while(0);
    						if( $_659 === TRUE ) { $_661 = TRUE; break; }
    						$result = $res_638;
    						$this->pos = $pos_638;
    						$_661 = FALSE; break;
    					}
    					while(0);
    					if( $_661 === TRUE ) { $_663 = TRUE; break; }
    					$result = $res_636;
    					$this->pos = $pos_636;
    					$_663 = FALSE; break;
    				}
    				while(0);
    				if( $_663 === FALSE) { $_665 = FALSE; break; }
    				$_665 = TRUE; break;
    			}
    			while(0);
    			if( $_665 === FALSE) { $_668 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_668 = FALSE; break; }
    			$_668 = TRUE; break;
    		}
    		while(0);
    		if( $_668 === TRUE ) { $_670 = TRUE; break; }
    		$result = $res_634;
    		$this->pos = $pos_634;
    		$_670 = FALSE; break;
    	}
    	while(0);
    	if( $_670 === TRUE ) { return $this->finalise($result); }
    	if( $_670 === FALSE) { return FALSE; }
    }


    /* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher?
    '<%' < 'end_' '$BlockName' > '%>' */
    protected $match_ClosedBlock_typestack = array('ClosedBlock');
    function match_ClosedBlock ($stack = array()) {
    	$matchrule = "ClosedBlock"; $result = $this->construct($matchrule, $matchrule, null);
    	$_690 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_690 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_674 = $result;
    		$pos_674 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_674;
    			$this->pos = $pos_674;
    			$_690 = FALSE; break;
    		}
    		else {
    			$result = $res_674;
    			$this->pos = $pos_674;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "BlockName" );
    		}
    		else { $_690 = FALSE; break; }
    		$res_680 = $result;
    		$pos_680 = $this->pos;
    		$_679 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_679 = FALSE; break; }
    			$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "BlockArguments" );
    			}
    			else { $_679 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_679 = FALSE; break; }
    			$_679 = TRUE; break;
    		}
    		while(0);
    		if( $_679 === FALSE) {
    			$result = $res_680;
    			$this->pos = $pos_680;
    			unset( $res_680 );
    			unset( $pos_680 );
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
    			$_690 = FALSE; break;
    		}
    		$res_683 = $result;
    		$pos_683 = $this->pos;
    		$matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher'); $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Template" );
    		}
    		else {
    			$result = $res_683;
    			$this->pos = $pos_683;
    			unset( $res_683 );
    			unset( $pos_683 );
    		}
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_690 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_690 = FALSE; break; }
    		if (( $subres = $this->literal( ''.$this->expression($result, $stack, 'BlockName').'' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_690 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_690 = FALSE; break; }
    		$_690 = TRUE; break;
    	}
    	while(0);
    	if( $_690 === TRUE ) { return $this->finalise($result); }
    	if( $_690 === FALSE) { return FALSE; }
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
            $res['ArgumentCount'] = count($res['Arguments']);
        }
    }

    function ClosedBlock__finalise(&$res)
    {
        $blockname = $res['BlockName']['text'];

        $method = 'ClosedBlock_Handle_'.$blockname;
        if (method_exists($this, $method)) {
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
    	$_703 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_703 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_694 = $result;
    		$pos_694 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_694;
    			$this->pos = $pos_694;
    			$_703 = FALSE; break;
    		}
    		else {
    			$result = $res_694;
    			$this->pos = $pos_694;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "BlockName" );
    		}
    		else { $_703 = FALSE; break; }
    		$res_700 = $result;
    		$pos_700 = $this->pos;
    		$_699 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_699 = FALSE; break; }
    			$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "BlockArguments" );
    			}
    			else { $_699 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_699 = FALSE; break; }
    			$_699 = TRUE; break;
    		}
    		while(0);
    		if( $_699 === FALSE) {
    			$result = $res_700;
    			$this->pos = $pos_700;
    			unset( $res_700 );
    			unset( $pos_700 );
    		}
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_703 = FALSE; break; }
    		$_703 = TRUE; break;
    	}
    	while(0);
    	if( $_703 === TRUE ) { return $this->finalise($result); }
    	if( $_703 === FALSE) { return FALSE; }
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
            $res['ArgumentCount'] = count($res['Arguments']);
        }
    }

    function OpenBlock__finalise(&$res)
    {
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
            return '$val .= Debug::show('.str_replace('FINALGET!', 'cachedCall', $php).');';
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
    	$_711 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_711 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_711 = FALSE; break; }
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Word" );
    		}
    		else { $_711 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_711 = FALSE; break; }
    		$_711 = TRUE; break;
    	}
    	while(0);
    	if( $_711 === TRUE ) { return $this->finalise($result); }
    	if( $_711 === FALSE) { return FALSE; }
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
    	$_726 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_726 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$res_715 = $result;
    		$pos_715 = $this->pos;
    		$matcher = 'match_'.'NotBlockTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$result = $res_715;
    			$this->pos = $pos_715;
    			$_726 = FALSE; break;
    		}
    		else {
    			$result = $res_715;
    			$this->pos = $pos_715;
    		}
    		$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "Tag" );
    		}
    		else { $_726 = FALSE; break; }
    		$res_725 = $result;
    		$pos_725 = $this->pos;
    		$_724 = NULL;
    		do {
    			$res_721 = $result;
    			$pos_721 = $this->pos;
    			$_720 = NULL;
    			do {
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_720 = FALSE; break; }
    				$matcher = 'match_'.'BlockArguments'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres, "BlockArguments" );
    				}
    				else { $_720 = FALSE; break; }
    				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_720 = FALSE; break; }
    				$_720 = TRUE; break;
    			}
    			while(0);
    			if( $_720 === FALSE) {
    				$result = $res_721;
    				$this->pos = $pos_721;
    				unset( $res_721 );
    				unset( $pos_721 );
    			}
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_724 = FALSE; break; }
    			$_724 = TRUE; break;
    		}
    		while(0);
    		if( $_724 === TRUE ) {
    			$result = $res_725;
    			$this->pos = $pos_725;
    			$_726 = FALSE; break;
    		}
    		if( $_724 === FALSE) {
    			$result = $res_725;
    			$this->pos = $pos_725;
    		}
    		$_726 = TRUE; break;
    	}
    	while(0);
    	if( $_726 === TRUE ) { return $this->finalise($result); }
    	if( $_726 === FALSE) { return FALSE; }
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
    	$_738 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_738 = FALSE; break; }
    		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    		$stack[] = $result; $result = $this->construct( $matchrule, "Tag" ); 
    		$_732 = NULL;
    		do {
    			if (( $subres = $this->literal( 'end_' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_732 = FALSE; break; }
    			$matcher = 'match_'.'Word'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres, "Word" );
    			}
    			else { $_732 = FALSE; break; }
    			$_732 = TRUE; break;
    		}
    		while(0);
    		if( $_732 === TRUE ) {
    			$subres = $result; $result = array_pop($stack);
    			$this->store( $result, $subres, 'Tag' );
    		}
    		if( $_732 === FALSE) {
    			$result = array_pop($stack);
    			$_738 = FALSE; break;
    		}
    		$res_737 = $result;
    		$pos_737 = $this->pos;
    		$_736 = NULL;
    		do {
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			if (( $subres = $this->literal( '%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    			else { $_736 = FALSE; break; }
    			$_736 = TRUE; break;
    		}
    		while(0);
    		if( $_736 === TRUE ) {
    			$result = $res_737;
    			$this->pos = $pos_737;
    			$_738 = FALSE; break;
    		}
    		if( $_736 === FALSE) {
    			$result = $res_737;
    			$this->pos = $pos_737;
    		}
    		$_738 = TRUE; break;
    	}
    	while(0);
    	if( $_738 === TRUE ) { return $this->finalise($result); }
    	if( $_738 === FALSE) { return FALSE; }
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
    	$_743 = NULL;
    	do {
    		$res_740 = $result;
    		$pos_740 = $this->pos;
    		$matcher = 'match_'.'MalformedOpenTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_743 = TRUE; break;
    		}
    		$result = $res_740;
    		$this->pos = $pos_740;
    		$matcher = 'match_'.'MalformedCloseTag'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_743 = TRUE; break;
    		}
    		$result = $res_740;
    		$this->pos = $pos_740;
    		$_743 = FALSE; break;
    	}
    	while(0);
    	if( $_743 === TRUE ) { return $this->finalise($result); }
    	if( $_743 === FALSE) { return FALSE; }
    }




    /* CommentWithContent: '<%--' ( !"--%>" /(?s)./ )+ '--%>' */
    protected $match_CommentWithContent_typestack = array('CommentWithContent');
    function match_CommentWithContent ($stack = array()) {
    	$matchrule = "CommentWithContent"; $result = $this->construct($matchrule, $matchrule, null);
    	$_751 = NULL;
    	do {
    		if (( $subres = $this->literal( '<%--' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_751 = FALSE; break; }
    		$count = 0;
    		while (true) {
    			$res_749 = $result;
    			$pos_749 = $this->pos;
    			$_748 = NULL;
    			do {
    				$res_746 = $result;
    				$pos_746 = $this->pos;
    				if (( $subres = $this->literal( '--%>' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$result = $res_746;
    					$this->pos = $pos_746;
    					$_748 = FALSE; break;
    				}
    				else {
    					$result = $res_746;
    					$this->pos = $pos_746;
    				}
    				if (( $subres = $this->rx( '/(?s)./' ) ) !== FALSE) { $result["text"] .= $subres; }
    				else { $_748 = FALSE; break; }
    				$_748 = TRUE; break;
    			}
    			while(0);
    			if( $_748 === FALSE) {
    				$result = $res_749;
    				$this->pos = $pos_749;
    				unset( $res_749 );
    				unset( $pos_749 );
    				break;
    			}
    			$count += 1;
    		}
    		if ($count > 0) {  }
    		else { $_751 = FALSE; break; }
    		if (( $subres = $this->literal( '--%>' ) ) !== FALSE) { $result["text"] .= $subres; }
    		else { $_751 = FALSE; break; }
    		$_751 = TRUE; break;
    	}
    	while(0);
    	if( $_751 === TRUE ) { return $this->finalise($result); }
    	if( $_751 === FALSE) { return FALSE; }
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
    	$_757 = NULL;
    	do {
    		$res_754 = $result;
    		$pos_754 = $this->pos;
    		$matcher = 'match_'.'EmptyComment'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "EmptyComment" );
    			$_757 = TRUE; break;
    		}
    		$result = $res_754;
    		$this->pos = $pos_754;
    		$matcher = 'match_'.'CommentWithContent'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "CommentWithContent" );
    			$_757 = TRUE; break;
    		}
    		$result = $res_754;
    		$this->pos = $pos_754;
    		$_757 = FALSE; break;
    	}
    	while(0);
    	if( $_757 === TRUE ) { return $this->finalise($result); }
    	if( $_757 === FALSE) { return FALSE; }
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
    		$res_817 = $result;
    		$pos_817 = $this->pos;
    		$_816 = NULL;
    		do {
    			$_814 = NULL;
    			do {
    				$res_759 = $result;
    				$pos_759 = $this->pos;
    				$matcher = 'match_'.'Comment'; $key = $matcher; $pos = $this->pos;
    				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    				if ($subres !== FALSE) {
    					$this->store( $result, $subres );
    					$_814 = TRUE; break;
    				}
    				$result = $res_759;
    				$this->pos = $pos_759;
    				$_812 = NULL;
    				do {
    					$res_761 = $result;
    					$pos_761 = $this->pos;
    					$matcher = 'match_'.'Translate'; $key = $matcher; $pos = $this->pos;
    					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    					if ($subres !== FALSE) {
    						$this->store( $result, $subres );
    						$_812 = TRUE; break;
    					}
    					$result = $res_761;
    					$this->pos = $pos_761;
    					$_810 = NULL;
    					do {
    						$res_763 = $result;
    						$pos_763 = $this->pos;
    						$matcher = 'match_'.'If'; $key = $matcher; $pos = $this->pos;
    						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    						if ($subres !== FALSE) {
    							$this->store( $result, $subres );
    							$_810 = TRUE; break;
    						}
    						$result = $res_763;
    						$this->pos = $pos_763;
    						$_808 = NULL;
    						do {
    							$res_765 = $result;
    							$pos_765 = $this->pos;
    							$matcher = 'match_'.'Require'; $key = $matcher; $pos = $this->pos;
    							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    							if ($subres !== FALSE) {
    								$this->store( $result, $subres );
    								$_808 = TRUE; break;
    							}
    							$result = $res_765;
    							$this->pos = $pos_765;
    							$_806 = NULL;
    							do {
    								$res_767 = $result;
    								$pos_767 = $this->pos;
    								$matcher = 'match_'.'CacheBlock'; $key = $matcher; $pos = $this->pos;
    								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    								if ($subres !== FALSE) {
    									$this->store( $result, $subres );
    									$_806 = TRUE; break;
    								}
    								$result = $res_767;
    								$this->pos = $pos_767;
    								$_804 = NULL;
    								do {
    									$res_769 = $result;
    									$pos_769 = $this->pos;
    									$matcher = 'match_'.'UncachedBlock'; $key = $matcher; $pos = $this->pos;
    									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    									if ($subres !== FALSE) {
    										$this->store( $result, $subres );
    										$_804 = TRUE; break;
    									}
    									$result = $res_769;
    									$this->pos = $pos_769;
    									$_802 = NULL;
    									do {
    										$res_771 = $result;
    										$pos_771 = $this->pos;
    										$matcher = 'match_'.'OldI18NTag'; $key = $matcher; $pos = $this->pos;
    										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    										if ($subres !== FALSE) {
    											$this->store( $result, $subres );
    											$_802 = TRUE; break;
    										}
    										$result = $res_771;
    										$this->pos = $pos_771;
    										$_800 = NULL;
    										do {
    											$res_773 = $result;
    											$pos_773 = $this->pos;
    											$matcher = 'match_'.'Include'; $key = $matcher; $pos = $this->pos;
    											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    											if ($subres !== FALSE) {
    												$this->store( $result, $subres );
    												$_800 = TRUE; break;
    											}
    											$result = $res_773;
    											$this->pos = $pos_773;
    											$_798 = NULL;
    											do {
    												$res_775 = $result;
    												$pos_775 = $this->pos;
    												$matcher = 'match_'.'ClosedBlock'; $key = $matcher; $pos = $this->pos;
    												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    												if ($subres !== FALSE) {
    													$this->store( $result, $subres );
    													$_798 = TRUE; break;
    												}
    												$result = $res_775;
    												$this->pos = $pos_775;
    												$_796 = NULL;
    												do {
    													$res_777 = $result;
    													$pos_777 = $this->pos;
    													$matcher = 'match_'.'OpenBlock'; $key = $matcher; $pos = $this->pos;
    													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    													if ($subres !== FALSE) {
    														$this->store( $result, $subres );
    														$_796 = TRUE; break;
    													}
    													$result = $res_777;
    													$this->pos = $pos_777;
    													$_794 = NULL;
    													do {
    														$res_779 = $result;
    														$pos_779 = $this->pos;
    														$matcher = 'match_'.'MalformedBlock'; $key = $matcher; $pos = $this->pos;
    														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    														if ($subres !== FALSE) {
    															$this->store( $result, $subres );
    															$_794 = TRUE; break;
    														}
    														$result = $res_779;
    														$this->pos = $pos_779;
    														$_792 = NULL;
    														do {
    															$res_781 = $result;
    															$pos_781 = $this->pos;
    															$matcher = 'match_'.'MismatchedEndBlock'; $key = $matcher; $pos = $this->pos;
    															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    															if ($subres !== FALSE) {
    																$this->store( $result, $subres );
    																$_792 = TRUE; break;
    															}
    															$result = $res_781;
    															$this->pos = $pos_781;
    															$_790 = NULL;
    															do {
    																$res_783 = $result;
    																$pos_783 = $this->pos;
    																$matcher = 'match_'.'MalformedBracketInjection'; $key = $matcher; $pos = $this->pos;
    																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																if ($subres !== FALSE) {
    																	$this->store( $result, $subres );
    																	$_790 = TRUE; break;
    																}
    																$result = $res_783;
    																$this->pos = $pos_783;
    																$_788 = NULL;
    																do {
    																	$res_785 = $result;
    																	$pos_785 = $this->pos;
    																	$matcher = 'match_'.'Injection'; $key = $matcher; $pos = $this->pos;
    																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																	if ($subres !== FALSE) {
    																		$this->store( $result, $subres );
    																		$_788 = TRUE; break;
    																	}
    																	$result = $res_785;
    																	$this->pos = $pos_785;
    																	$matcher = 'match_'.'Text'; $key = $matcher; $pos = $this->pos;
    																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    																	if ($subres !== FALSE) {
    																		$this->store( $result, $subres );
    																		$_788 = TRUE; break;
    																	}
    																	$result = $res_785;
    																	$this->pos = $pos_785;
    																	$_788 = FALSE; break;
    																}
    																while(0);
    																if( $_788 === TRUE ) {
    																	$_790 = TRUE; break;
    																}
    																$result = $res_783;
    																$this->pos = $pos_783;
    																$_790 = FALSE; break;
    															}
    															while(0);
    															if( $_790 === TRUE ) {
    																$_792 = TRUE; break;
    															}
    															$result = $res_781;
    															$this->pos = $pos_781;
    															$_792 = FALSE; break;
    														}
    														while(0);
    														if( $_792 === TRUE ) { $_794 = TRUE; break; }
    														$result = $res_779;
    														$this->pos = $pos_779;
    														$_794 = FALSE; break;
    													}
    													while(0);
    													if( $_794 === TRUE ) { $_796 = TRUE; break; }
    													$result = $res_777;
    													$this->pos = $pos_777;
    													$_796 = FALSE; break;
    												}
    												while(0);
    												if( $_796 === TRUE ) { $_798 = TRUE; break; }
    												$result = $res_775;
    												$this->pos = $pos_775;
    												$_798 = FALSE; break;
    											}
    											while(0);
    											if( $_798 === TRUE ) { $_800 = TRUE; break; }
    											$result = $res_773;
    											$this->pos = $pos_773;
    											$_800 = FALSE; break;
    										}
    										while(0);
    										if( $_800 === TRUE ) { $_802 = TRUE; break; }
    										$result = $res_771;
    										$this->pos = $pos_771;
    										$_802 = FALSE; break;
    									}
    									while(0);
    									if( $_802 === TRUE ) { $_804 = TRUE; break; }
    									$result = $res_769;
    									$this->pos = $pos_769;
    									$_804 = FALSE; break;
    								}
    								while(0);
    								if( $_804 === TRUE ) { $_806 = TRUE; break; }
    								$result = $res_767;
    								$this->pos = $pos_767;
    								$_806 = FALSE; break;
    							}
    							while(0);
    							if( $_806 === TRUE ) { $_808 = TRUE; break; }
    							$result = $res_765;
    							$this->pos = $pos_765;
    							$_808 = FALSE; break;
    						}
    						while(0);
    						if( $_808 === TRUE ) { $_810 = TRUE; break; }
    						$result = $res_763;
    						$this->pos = $pos_763;
    						$_810 = FALSE; break;
    					}
    					while(0);
    					if( $_810 === TRUE ) { $_812 = TRUE; break; }
    					$result = $res_761;
    					$this->pos = $pos_761;
    					$_812 = FALSE; break;
    				}
    				while(0);
    				if( $_812 === TRUE ) { $_814 = TRUE; break; }
    				$result = $res_759;
    				$this->pos = $pos_759;
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
    		$res_856 = $result;
    		$pos_856 = $this->pos;
    		$_855 = NULL;
    		do {
    			$_853 = NULL;
    			do {
    				$res_818 = $result;
    				$pos_818 = $this->pos;
    				if (( $subres = $this->rx( '/ [^<${\\\\]+ /' ) ) !== FALSE) {
    					$result["text"] .= $subres;
    					$_853 = TRUE; break;
    				}
    				$result = $res_818;
    				$this->pos = $pos_818;
    				$_851 = NULL;
    				do {
    					$res_820 = $result;
    					$pos_820 = $this->pos;
    					if (( $subres = $this->rx( '/ (\\\\.) /' ) ) !== FALSE) {
    						$result["text"] .= $subres;
    						$_851 = TRUE; break;
    					}
    					$result = $res_820;
    					$this->pos = $pos_820;
    					$_849 = NULL;
    					do {
    						$res_822 = $result;
    						$pos_822 = $this->pos;
    						$_825 = NULL;
    						do {
    							if (substr($this->string,$this->pos,1) == '<') {
    								$this->pos += 1;
    								$result["text"] .= '<';
    							}
    							else { $_825 = FALSE; break; }
    							$res_824 = $result;
    							$pos_824 = $this->pos;
    							if (substr($this->string,$this->pos,1) == '%') {
    								$this->pos += 1;
    								$result["text"] .= '%';
    								$result = $res_824;
    								$this->pos = $pos_824;
    								$_825 = FALSE; break;
    							}
    							else {
    								$result = $res_824;
    								$this->pos = $pos_824;
    							}
    							$_825 = TRUE; break;
    						}
    						while(0);
    						if( $_825 === TRUE ) { $_849 = TRUE; break; }
    						$result = $res_822;
    						$this->pos = $pos_822;
    						$_847 = NULL;
    						do {
    							$res_827 = $result;
    							$pos_827 = $this->pos;
    							$_832 = NULL;
    							do {
    								if (substr($this->string,$this->pos,1) == '$') {
    									$this->pos += 1;
    									$result["text"] .= '$';
    								}
    								else { $_832 = FALSE; break; }
    								$res_831 = $result;
    								$pos_831 = $this->pos;
    								$_830 = NULL;
    								do {
    									if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									else { $_830 = FALSE; break; }
    									$_830 = TRUE; break;
    								}
    								while(0);
    								if( $_830 === TRUE ) {
    									$result = $res_831;
    									$this->pos = $pos_831;
    									$_832 = FALSE; break;
    								}
    								if( $_830 === FALSE) {
    									$result = $res_831;
    									$this->pos = $pos_831;
    								}
    								$_832 = TRUE; break;
    							}
    							while(0);
    							if( $_832 === TRUE ) { $_847 = TRUE; break; }
    							$result = $res_827;
    							$this->pos = $pos_827;
    							$_845 = NULL;
    							do {
    								$res_834 = $result;
    								$pos_834 = $this->pos;
    								$_837 = NULL;
    								do {
    									if (substr($this->string,$this->pos,1) == '{') {
    										$this->pos += 1;
    										$result["text"] .= '{';
    									}
    									else { $_837 = FALSE; break; }
    									$res_836 = $result;
    									$pos_836 = $this->pos;
    									if (substr($this->string,$this->pos,1) == '$') {
    										$this->pos += 1;
    										$result["text"] .= '$';
    										$result = $res_836;
    										$this->pos = $pos_836;
    										$_837 = FALSE; break;
    									}
    									else {
    										$result = $res_836;
    										$this->pos = $pos_836;
    									}
    									$_837 = TRUE; break;
    								}
    								while(0);
    								if( $_837 === TRUE ) { $_845 = TRUE; break; }
    								$result = $res_834;
    								$this->pos = $pos_834;
    								$_843 = NULL;
    								do {
    									if (( $subres = $this->literal( '{$' ) ) !== FALSE) {
    										$result["text"] .= $subres;
    									}
    									else { $_843 = FALSE; break; }
    									$res_842 = $result;
    									$pos_842 = $this->pos;
    									$_841 = NULL;
    									do {
    										if (( $subres = $this->rx( '/[A-Za-z_]/' ) ) !== FALSE) {
    											$result["text"] .= $subres;
    										}
    										else { $_841 = FALSE; break; }
    										$_841 = TRUE; break;
    									}
    									while(0);
    									if( $_841 === TRUE ) {
    										$result = $res_842;
    										$this->pos = $pos_842;
    										$_843 = FALSE; break;
    									}
    									if( $_841 === FALSE) {
    										$result = $res_842;
    										$this->pos = $pos_842;
    									}
    									$_843 = TRUE; break;
    								}
    								while(0);
    								if( $_843 === TRUE ) { $_845 = TRUE; break; }
    								$result = $res_834;
    								$this->pos = $pos_834;
    								$_845 = FALSE; break;
    							}
    							while(0);
    							if( $_845 === TRUE ) { $_847 = TRUE; break; }
    							$result = $res_827;
    							$this->pos = $pos_827;
    							$_847 = FALSE; break;
    						}
    						while(0);
    						if( $_847 === TRUE ) { $_849 = TRUE; break; }
    						$result = $res_822;
    						$this->pos = $pos_822;
    						$_849 = FALSE; break;
    					}
    					while(0);
    					if( $_849 === TRUE ) { $_851 = TRUE; break; }
    					$result = $res_820;
    					$this->pos = $pos_820;
    					$_851 = FALSE; break;
    				}
    				while(0);
    				if( $_851 === TRUE ) { $_853 = TRUE; break; }
    				$result = $res_818;
    				$this->pos = $pos_818;
    				$_853 = FALSE; break;
    			}
    			while(0);
    			if( $_853 === FALSE) { $_855 = FALSE; break; }
    			$_855 = TRUE; break;
    		}
    		while(0);
    		if( $_855 === FALSE) {
    			$result = $res_856;
    			$this->pos = $pos_856;
    			unset( $res_856 );
    			unset( $pos_856 );
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
        $text = stripslashes($text);
        $text = addcslashes($text, '\'\\');

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
            '\\1"\' . ' . addcslashes($code, '\\')  . ' . \'#',
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
     * @param string $string The source of the template
     * @param string $templateName The name of the template, normally the filename the template source was loaded from
     * @param bool $includeDebuggingComments True is debugging comments should be included in the output
     * @param bool $topTemplate True if this is a top template, false if it's just a template
     * @return mixed|string The php that, when executed (via include or exec) will behave as per the template source
     */
    public function compileString($string, $templateName = "", $includeDebuggingComments = false, $topTemplate = true)
    {
        if (!trim($string)) {
            $code = '';
        } else {
            parent::__construct($string);

            $this->includeDebuggingComments = $includeDebuggingComments;

            // Ignore UTF8 BOM at beginning of string. TODO: Confirm this is needed, make sure SSViewer handles UTF
            // (and other encodings) properly
            if (substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
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
        if ($includeDebuggingComments && $templateName && stripos($code, "<?xml") === false) {
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
        if (stripos($code, "<!doctype") !== false) {
            $code = preg_replace('/(<!doctype[^>]*("[^"]")*[^>]*>)/im', "$1\r\n<!-- template $templateName -->", $code);
            $code .= "\r\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
        } elseif (stripos($code, "<html") !== false) {
            $code = preg_replace_callback('/(.*)(<html[^>]*>)(.*)/i', function ($matches) use ($templateName) {
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
    public function compileFile($template)
    {
        return $this->compileString(file_get_contents($template), $template);
    }
}
