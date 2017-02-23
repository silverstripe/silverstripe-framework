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
class SSTemplateParser extends Parser implements TemplateParser
{

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
    public function __construct($closedBlocks = array(), $openBlocks = array())
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
    public function setOpenBlocks($openBlocks)
    {
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
	OpenBlock | MalformedBlock | Injection | Text)+ */
    protected $match_Template_typestack = array('Template');
    function match_Template($stack = array())
    {
        $matchrule = "Template";
        $result = $this->construct($matchrule, $matchrule, null);
        $count = 0;
        while (true) {
            $res_50 = $result;
            $pos_50 = $this->pos;
            $_49 = null;
            do {
                $_47 = null;
                do {
                    $res_0 = $result;
                    $pos_0 = $this->pos;
                    $matcher = 'match_'.'Comment';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres);
                        $_47 = true;
                        break;
                    }
                    $result = $res_0;
                    $this->pos = $pos_0;
                    $_45 = null;
                    do {
                        $res_2 = $result;
                        $pos_2 = $this->pos;
                        $matcher = 'match_'.'Translate';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                            $_45 = true;
                            break;
                        }
                        $result = $res_2;
                        $this->pos = $pos_2;
                        $_43 = null;
                        do {
                            $res_4 = $result;
                            $pos_4 = $this->pos;
                            $matcher = 'match_'.'If';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                                $_43 = true;
                                break;
                            }
                            $result = $res_4;
                            $this->pos = $pos_4;
                            $_41 = null;
                            do {
                                $res_6 = $result;
                                $pos_6 = $this->pos;
                                $matcher = 'match_'.'Require';
                                $key = $matcher;
                                $pos = $this->pos;
                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                if ($subres !== false) {
                                    $this->store($result, $subres);
                                    $_41 = true;
                                    break;
                                }
                                $result = $res_6;
                                $this->pos = $pos_6;
                                $_39 = null;
                                do {
                                    $res_8 = $result;
                                    $pos_8 = $this->pos;
                                    $matcher = 'match_'.'CacheBlock';
                                    $key = $matcher;
                                    $pos = $this->pos;
                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                    if ($subres !== false) {
                                        $this->store($result, $subres);
                                        $_39 = true;
                                        break;
                                    }
                                    $result = $res_8;
                                    $this->pos = $pos_8;
                                    $_37 = null;
                                    do {
                                        $res_10 = $result;
                                        $pos_10 = $this->pos;
                                        $matcher = 'match_'.'UncachedBlock';
                                        $key = $matcher;
                                        $pos = $this->pos;
                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                        if ($subres !== false) {
                                            $this->store($result, $subres);
                                            $_37 = true;
                                            break;
                                        }
                                        $result = $res_10;
                                        $this->pos = $pos_10;
                                        $_35 = null;
                                        do {
                                            $res_12 = $result;
                                            $pos_12 = $this->pos;
                                            $matcher = 'match_'.'OldI18NTag';
                                            $key = $matcher;
                                            $pos = $this->pos;
                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                            if ($subres !== false) {
                                                $this->store($result, $subres);
                                                $_35 = true;
                                                break;
                                            }
                                            $result = $res_12;
                                            $this->pos = $pos_12;
                                            $_33 = null;
                                            do {
                                                $res_14 = $result;
                                                $pos_14 = $this->pos;
                                                $matcher = 'match_'.'Include';
                                                $key = $matcher;
                                                $pos = $this->pos;
                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                if ($subres !== false) {
                                                    $this->store($result, $subres);
                                                    $_33 = true;
                                                    break;
                                                }
                                                $result = $res_14;
                                                $this->pos = $pos_14;
                                                $_31 = null;
                                                do {
                                                    $res_16 = $result;
                                                    $pos_16 = $this->pos;
                                                    $matcher = 'match_'.'ClosedBlock';
                                                    $key = $matcher;
                                                    $pos = $this->pos;
                                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                    if ($subres !== false) {
                                                        $this->store($result, $subres);
                                                        $_31 = true;
                                                        break;
                                                    }
                                                    $result = $res_16;
                                                    $this->pos = $pos_16;
                                                    $_29 = null;
                                                    do {
                                                        $res_18 = $result;
                                                        $pos_18 = $this->pos;
                                                        $matcher = 'match_'.'OpenBlock';
                                                        $key = $matcher;
                                                        $pos = $this->pos;
                                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                        if ($subres !== false) {
                                                            $this->store($result, $subres);
                                                            $_29 = true;
                                                            break;
                                                        }
                                                        $result = $res_18;
                                                        $this->pos = $pos_18;
                                                        $_27 = null;
                                                        do {
                                                            $res_20 = $result;
                                                            $pos_20 = $this->pos;
                                                            $matcher = 'match_'.'MalformedBlock';
                                                            $key = $matcher;
                                                            $pos = $this->pos;
                                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                            if ($subres !== false) {
                                                                $this->store($result, $subres);
                                                                $_27 = true;
                                                                break;
                                                            }
                                                            $result = $res_20;
                                                            $this->pos = $pos_20;
                                                            $_25 = null;
                                                            do {
                                                                $res_22 = $result;
                                                                $pos_22 = $this->pos;
                                                                $matcher = 'match_'.'Injection';
                                                                $key = $matcher;
                                                                $pos = $this->pos;
                                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                if ($subres !== false) {
                                                                    $this->store($result, $subres);
                                                                    $_25 = true;
                                                                    break;
                                                                }
                                                                $result = $res_22;
                                                                $this->pos = $pos_22;
                                                                $matcher = 'match_'.'Text';
                                                                $key = $matcher;
                                                                $pos = $this->pos;
                                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                if ($subres !== false) {
                                                                    $this->store($result, $subres);
                                                                    $_25 = true;
                                                                    break;
                                                                }
                                                                $result = $res_22;
                                                                $this->pos = $pos_22;
                                                                $_25 = false;
                                                                break;
                                                            } while (0);
                                                            if ($_25 === true) {
                                                                $_27 = true;
                                                                break;
                                                            }
                                                            $result = $res_20;
                                                            $this->pos = $pos_20;
                                                            $_27 = false;
                                                            break;
                                                        } while (0);
                                                        if ($_27 === true) {
                                                            $_29 = true;
                                                            break;
                                                        }
                                                        $result = $res_18;
                                                        $this->pos = $pos_18;
                                                        $_29 = false;
                                                        break;
                                                    } while (0);
                                                    if ($_29 === true) {
                                                        $_31 = true;
                                                        break;
                                                    }
                                                    $result = $res_16;
                                                    $this->pos = $pos_16;
                                                    $_31 = false;
                                                    break;
                                                } while (0);
                                                if ($_31 === true) {
                                                    $_33 = true;
                                                    break;
                                                }
                                                $result = $res_14;
                                                $this->pos = $pos_14;
                                                $_33 = false;
                                                break;
                                            } while (0);
                                            if ($_33 === true) {
                                                $_35 = true;
                                                break;
                                            }
                                            $result = $res_12;
                                            $this->pos = $pos_12;
                                            $_35 = false;
                                            break;
                                        } while (0);
                                        if ($_35 === true) {
                                            $_37 = true;
                                            break;
                                        }
                                        $result = $res_10;
                                        $this->pos = $pos_10;
                                        $_37 = false;
                                        break;
                                    } while (0);
                                    if ($_37 === true) {
                                        $_39 = true;
                                        break;
                                    }
                                    $result = $res_8;
                                    $this->pos = $pos_8;
                                    $_39 = false;
                                    break;
                                } while (0);
                                if ($_39 === true) {
                                    $_41 = true;
                                    break;
                                }
                                $result = $res_6;
                                $this->pos = $pos_6;
                                $_41 = false;
                                break;
                            } while (0);
                            if ($_41 === true) {
                                $_43 = true;
                                break;
                            }
                            $result = $res_4;
                            $this->pos = $pos_4;
                            $_43 = false;
                            break;
                        } while (0);
                        if ($_43 === true) {
                            $_45 = true;
                            break;
                        }
                        $result = $res_2;
                        $this->pos = $pos_2;
                        $_45 = false;
                        break;
                    } while (0);
                    if ($_45 === true) {
                        $_47 = true;
                        break;
                    }
                    $result = $res_0;
                    $this->pos = $pos_0;
                    $_47 = false;
                    break;
                } while (0);
                if ($_47 === false) {
                    $_49 = false;
                    break;
                }
                $_49 = true;
                break;
            } while (0);
            if ($_49 === false) {
                $result = $res_50;
                $this->pos = $pos_50;
                unset($res_50);
                unset($pos_50);
                break;
            }
            $count += 1;
        }
        if ($count > 0) {
            return $this->finalise($result);
        } else {
            return false;
        }
    }



    function Template_STR(&$res, $sub)
    {
        $res['php'] .= $sub['php'] . PHP_EOL ;
    }

    /* Word: / [A-Za-z_] [A-Za-z0-9_]* / */
    protected $match_Word_typestack = array('Word');
    function match_Word($stack = array())
    {
        $matchrule = "Word";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/ [A-Za-z_] [A-Za-z0-9_]* /') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* NamespacedWord: / [A-Za-z_\/\\] [A-Za-z0-9_\/\\]* / */
    protected $match_NamespacedWord_typestack = array('NamespacedWord');
    function match_NamespacedWord($stack = array())
    {
        $matchrule = "NamespacedWord";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/ [A-Za-z_\/\\\\] [A-Za-z0-9_\/\\\\]* /') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* Number: / [0-9]+ / */
    protected $match_Number_typestack = array('Number');
    function match_Number($stack = array())
    {
        $matchrule = "Number";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/ [0-9]+ /') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* Value: / [A-Za-z0-9_]+ / */
    protected $match_Value_typestack = array('Value');
    function match_Value($stack = array())
    {
        $matchrule = "Value";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/ [A-Za-z0-9_]+ /') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* CallArguments: :Argument ( < "," < :Argument )* */
    protected $match_CallArguments_typestack = array('CallArguments');
    function match_CallArguments($stack = array())
    {
        $matchrule = "CallArguments";
        $result = $this->construct($matchrule, $matchrule, null);
        $_62 = null;
        do {
            $matcher = 'match_'.'Argument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Argument");
            } else {
                $_62 = false;
                break;
            }
            while (true) {
                $res_61 = $result;
                $pos_61 = $this->pos;
                $_60 = null;
                do {
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    if (substr($this->string, $this->pos, 1) == ',') {
                        $this->pos += 1;
                        $result["text"] .= ',';
                    } else {
                        $_60 = false;
                        break;
                    }
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    $matcher = 'match_'.'Argument';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "Argument");
                    } else {
                        $_60 = false;
                        break;
                    }
                    $_60 = true;
                    break;
                } while (0);
                if ($_60 === false) {
                    $result = $res_61;
                    $this->pos = $pos_61;
                    unset($res_61);
                    unset($pos_61);
                    break;
                }
            }
            $_62 = true;
            break;
        } while (0);
        if ($_62 === true) {
            return $this->finalise($result);
        }
        if ($_62 === false) {
            return false;
        }
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
    function match_Call($stack = array())
    {
        $matchrule = "Call";
        $result = $this->construct($matchrule, $matchrule, null);
        $_72 = null;
        do {
            $matcher = 'match_'.'Word';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Method");
            } else {
                $_72 = false;
                break;
            }
            $res_71 = $result;
            $pos_71 = $this->pos;
            $_70 = null;
            do {
                if (substr($this->string, $this->pos, 1) == '(') {
                    $this->pos += 1;
                    $result["text"] .= '(';
                } else {
                    $_70 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $res_67 = $result;
                $pos_67 = $this->pos;
                $matcher = 'match_'.'CallArguments';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "CallArguments");
                } else {
                    $result = $res_67;
                    $this->pos = $pos_67;
                    unset($res_67);
                    unset($pos_67);
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                if (substr($this->string, $this->pos, 1) == ')') {
                    $this->pos += 1;
                    $result["text"] .= ')';
                } else {
                    $_70 = false;
                    break;
                }
                $_70 = true;
                break;
            } while (0);
            if ($_70 === false) {
                $result = $res_71;
                $this->pos = $pos_71;
                unset($res_71);
                unset($pos_71);
            }
            $_72 = true;
            break;
        } while (0);
        if ($_72 === true) {
            return $this->finalise($result);
        }
        if ($_72 === false) {
            return false;
        }
    }


    /* LookupStep: :Call &"." */
    protected $match_LookupStep_typestack = array('LookupStep');
    function match_LookupStep($stack = array())
    {
        $matchrule = "LookupStep";
        $result = $this->construct($matchrule, $matchrule, null);
        $_76 = null;
        do {
            $matcher = 'match_'.'Call';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Call");
            } else {
                $_76 = false;
                break;
            }
            $res_75 = $result;
            $pos_75 = $this->pos;
            if (substr($this->string, $this->pos, 1) == '.') {
                $this->pos += 1;
                $result["text"] .= '.';
                $result = $res_75;
                $this->pos = $pos_75;
            } else {
                $result = $res_75;
                $this->pos = $pos_75;
                $_76 = false;
                break;
            }
            $_76 = true;
            break;
        } while (0);
        if ($_76 === true) {
            return $this->finalise($result);
        }
        if ($_76 === false) {
            return false;
        }
    }


    /* LastLookupStep: :Call */
    protected $match_LastLookupStep_typestack = array('LastLookupStep');
    function match_LastLookupStep($stack = array())
    {
        $matchrule = "LastLookupStep";
        $result = $this->construct($matchrule, $matchrule, null);
        $matcher = 'match_'.'Call';
        $key = $matcher;
        $pos = $this->pos;
        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
        if ($subres !== false) {
            $this->store($result, $subres, "Call");
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* Lookup: LookupStep ("." LookupStep)* "." LastLookupStep | LastLookupStep */
    protected $match_Lookup_typestack = array('Lookup');
    function match_Lookup($stack = array())
    {
        $matchrule = "Lookup";
        $result = $this->construct($matchrule, $matchrule, null);
        $_90 = null;
        do {
            $res_79 = $result;
            $pos_79 = $this->pos;
            $_87 = null;
            do {
                $matcher = 'match_'.'LookupStep';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_87 = false;
                    break;
                }
                while (true) {
                    $res_84 = $result;
                    $pos_84 = $this->pos;
                    $_83 = null;
                    do {
                        if (substr($this->string, $this->pos, 1) == '.') {
                            $this->pos += 1;
                            $result["text"] .= '.';
                        } else {
                            $_83 = false;
                            break;
                        }
                        $matcher = 'match_'.'LookupStep';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                        } else {
                            $_83 = false;
                            break;
                        }
                        $_83 = true;
                        break;
                    } while (0);
                    if ($_83 === false) {
                        $result = $res_84;
                        $this->pos = $pos_84;
                        unset($res_84);
                        unset($pos_84);
                        break;
                    }
                }
                if (substr($this->string, $this->pos, 1) == '.') {
                    $this->pos += 1;
                    $result["text"] .= '.';
                } else {
                    $_87 = false;
                    break;
                }
                $matcher = 'match_'.'LastLookupStep';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_87 = false;
                    break;
                }
                $_87 = true;
                break;
            } while (0);
            if ($_87 === true) {
                $_90 = true;
                break;
            }
            $result = $res_79;
            $this->pos = $pos_79;
            $matcher = 'match_'.'LastLookupStep';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_90 = true;
                break;
            }
            $result = $res_79;
            $this->pos = $pos_79;
            $_90 = false;
            break;
        } while (0);
        if ($_90 === true) {
            return $this->finalise($result);
        }
        if ($_90 === false) {
            return false;
        }
    }




    function Lookup__construct(&$res)
    {
        $res['php'] = '$scope->locally()';
        $res['LookupSteps'] = array();
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
            $res['php'] .= "->$method('$property', array($arguments), true)";
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
    function match_Translate($stack = array())
    {
        $matchrule = "Translate";
        $result = $this->construct($matchrule, $matchrule, null);
        $_116 = null;
        do {
            if (( $subres = $this->literal('<%t') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_116 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'Entity';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_116 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_98 = $result;
            $pos_98 = $this->pos;
            $_97 = null;
            do {
                $matcher = 'match_'.'QuotedString';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "Default");
                } else {
                    $_97 = false;
                    break;
                }
                $_97 = true;
                break;
            } while (0);
            if ($_97 === false) {
                $result = $res_98;
                $this->pos = $pos_98;
                unset($res_98);
                unset($pos_98);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_109 = $result;
            $pos_109 = $this->pos;
            $_108 = null;
            do {
                $res_103 = $result;
                $pos_103 = $this->pos;
                $_102 = null;
                do {
                    if (( $subres = $this->literal('is') ) !== false) {
                        $result["text"] .= $subres;
                    } else {
                        $_102 = false;
                        break;
                    }
                    if (substr($this->string, $this->pos, 1) == '=') {
                        $this->pos += 1;
                        $result["text"] .= '=';
                    } else {
                        $_102 = false;
                        break;
                    }
                    $_102 = true;
                    break;
                } while (0);
                if ($_102 === true) {
                    $result = $res_103;
                    $this->pos = $pos_103;
                    $_108 = false;
                    break;
                }
                if ($_102 === false) {
                    $result = $res_103;
                    $this->pos = $pos_103;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                if (( $subres = $this->literal('is') ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_108 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $matcher = 'match_'.'QuotedString';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "Context");
                } else {
                    $_108 = false;
                    break;
                }
                $_108 = true;
                break;
            } while (0);
            if ($_108 === false) {
                $result = $res_109;
                $this->pos = $pos_109;
                unset($res_109);
                unset($pos_109);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_113 = $result;
            $pos_113 = $this->pos;
            $_112 = null;
            do {
                $matcher = 'match_'.'InjectionVariables';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_112 = false;
                    break;
                }
                $_112 = true;
                break;
            } while (0);
            if ($_112 === false) {
                $result = $res_113;
                $this->pos = $pos_113;
                unset($res_113);
                unset($pos_113);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_116 = false;
                break;
            }
            $_116 = true;
            break;
        } while (0);
        if ($_116 === true) {
            return $this->finalise($result);
        }
        if ($_116 === false) {
            return false;
        }
    }


    /* InjectionVariables: (< InjectionName:Word "=" Argument)+ */
    protected $match_InjectionVariables_typestack = array('InjectionVariables');
    function match_InjectionVariables($stack = array())
    {
        $matchrule = "InjectionVariables";
        $result = $this->construct($matchrule, $matchrule, null);
        $count = 0;
        while (true) {
            $res_123 = $result;
            $pos_123 = $this->pos;
            $_122 = null;
            do {
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $matcher = 'match_'.'Word';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "InjectionName");
                } else {
                    $_122 = false;
                    break;
                }
                if (substr($this->string, $this->pos, 1) == '=') {
                    $this->pos += 1;
                    $result["text"] .= '=';
                } else {
                    $_122 = false;
                    break;
                }
                $matcher = 'match_'.'Argument';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_122 = false;
                    break;
                }
                $_122 = true;
                break;
            } while (0);
            if ($_122 === false) {
                $result = $res_123;
                $this->pos = $pos_123;
                unset($res_123);
                unset($pos_123);
                break;
            }
            $count += 1;
        }
        if ($count > 0) {
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* Entity: / [A-Za-z_] [\w\.]* / */
    protected $match_Entity_typestack = array('Entity');
    function match_Entity($stack = array())
    {
        $matchrule = "Entity";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/ [A-Za-z_] [\w\.]* /') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
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
        $res['php'] = "array(";
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
        $res['php'] .= ')';
    }


    /* SimpleInjection: '$' :Lookup */
    protected $match_SimpleInjection_typestack = array('SimpleInjection');
    function match_SimpleInjection($stack = array())
    {
        $matchrule = "SimpleInjection";
        $result = $this->construct($matchrule, $matchrule, null);
        $_127 = null;
        do {
            if (substr($this->string, $this->pos, 1) == '$') {
                $this->pos += 1;
                $result["text"] .= '$';
            } else {
                $_127 = false;
                break;
            }
            $matcher = 'match_'.'Lookup';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Lookup");
            } else {
                $_127 = false;
                break;
            }
            $_127 = true;
            break;
        } while (0);
        if ($_127 === true) {
            return $this->finalise($result);
        }
        if ($_127 === false) {
            return false;
        }
    }


    /* BracketInjection: '{$' :Lookup "}" */
    protected $match_BracketInjection_typestack = array('BracketInjection');
    function match_BracketInjection($stack = array())
    {
        $matchrule = "BracketInjection";
        $result = $this->construct($matchrule, $matchrule, null);
        $_132 = null;
        do {
            if (( $subres = $this->literal('{$') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_132 = false;
                break;
            }
            $matcher = 'match_'.'Lookup';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Lookup");
            } else {
                $_132 = false;
                break;
            }
            if (substr($this->string, $this->pos, 1) == '}') {
                $this->pos += 1;
                $result["text"] .= '}';
            } else {
                $_132 = false;
                break;
            }
            $_132 = true;
            break;
        } while (0);
        if ($_132 === true) {
            return $this->finalise($result);
        }
        if ($_132 === false) {
            return false;
        }
    }


    /* Injection: BracketInjection | SimpleInjection */
    protected $match_Injection_typestack = array('Injection');
    function match_Injection($stack = array())
    {
        $matchrule = "Injection";
        $result = $this->construct($matchrule, $matchrule, null);
        $_137 = null;
        do {
            $res_134 = $result;
            $pos_134 = $this->pos;
            $matcher = 'match_'.'BracketInjection';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_137 = true;
                break;
            }
            $result = $res_134;
            $this->pos = $pos_134;
            $matcher = 'match_'.'SimpleInjection';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_137 = true;
                break;
            }
            $result = $res_134;
            $this->pos = $pos_134;
            $_137 = false;
            break;
        } while (0);
        if ($_137 === true) {
            return $this->finalise($result);
        }
        if ($_137 === false) {
            return false;
        }
    }



    function Injection_STR(&$res, $sub)
    {
        $res['php'] = '$val .= '. str_replace('$$FINAL', 'XML_val', $sub['Lookup']['php']) . ';';
    }

    /* DollarMarkedLookup: SimpleInjection */
    protected $match_DollarMarkedLookup_typestack = array('DollarMarkedLookup');
    function match_DollarMarkedLookup($stack = array())
    {
        $matchrule = "DollarMarkedLookup";
        $result = $this->construct($matchrule, $matchrule, null);
        $matcher = 'match_'.'SimpleInjection';
        $key = $matcher;
        $pos = $this->pos;
        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
        if ($subres !== false) {
            $this->store($result, $subres);
            return $this->finalise($result);
        } else {
            return false;
        }
    }



    function DollarMarkedLookup_STR(&$res, $sub)
    {
        $res['Lookup'] = $sub['Lookup'];
    }

    /* QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q' */
    protected $match_QuotedString_typestack = array('QuotedString');
    function match_QuotedString($stack = array())
    {
        $matchrule = "QuotedString";
        $result = $this->construct($matchrule, $matchrule, null);
        $_143 = null;
        do {
            $stack[] = $result;
            $result = $this->construct($matchrule, "q");
            if (( $subres = $this->rx('/[\'"]/') ) !== false) {
                $result["text"] .= $subres;
                $subres = $result;
                $result = array_pop($stack);
                $this->store($result, $subres, 'q');
            } else {
                $result = array_pop($stack);
                $_143 = false;
                break;
            }
            $stack[] = $result;
            $result = $this->construct($matchrule, "String");
            if (( $subres = $this->rx('/ (\\\\\\\\ | \\\\. | [^'.$this->expression($result, $stack, 'q').'\\\\])* /') ) !== false) {
                $result["text"] .= $subres;
                $subres = $result;
                $result = array_pop($stack);
                $this->store($result, $subres, 'String');
            } else {
                $result = array_pop($stack);
                $_143 = false;
                break;
            }
            if (( $subres = $this->literal(''.$this->expression($result, $stack, 'q').'') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_143 = false;
                break;
            }
            $_143 = true;
            break;
        } while (0);
        if ($_143 === true) {
            return $this->finalise($result);
        }
        if ($_143 === false) {
            return false;
        }
    }


    /* FreeString: /[^,)%!=><|&]+/ */
    protected $match_FreeString_typestack = array('FreeString');
    function match_FreeString($stack = array())
    {
        $matchrule = "FreeString";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/[^,)%!=><|&]+/') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
    }


    /* Argument:
	:DollarMarkedLookup |
	:QuotedString |
	:Lookup !(< FreeString)|
	:FreeString */
    protected $match_Argument_typestack = array('Argument');
    function match_Argument($stack = array())
    {
        $matchrule = "Argument";
        $result = $this->construct($matchrule, $matchrule, null);
        $_163 = null;
        do {
            $res_146 = $result;
            $pos_146 = $this->pos;
            $matcher = 'match_'.'DollarMarkedLookup';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "DollarMarkedLookup");
                $_163 = true;
                break;
            }
            $result = $res_146;
            $this->pos = $pos_146;
            $_161 = null;
            do {
                $res_148 = $result;
                $pos_148 = $this->pos;
                $matcher = 'match_'.'QuotedString';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "QuotedString");
                    $_161 = true;
                    break;
                }
                $result = $res_148;
                $this->pos = $pos_148;
                $_159 = null;
                do {
                    $res_150 = $result;
                    $pos_150 = $this->pos;
                    $_156 = null;
                    do {
                        $matcher = 'match_'.'Lookup';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres, "Lookup");
                        } else {
                            $_156 = false;
                            break;
                        }
                        $res_155 = $result;
                        $pos_155 = $this->pos;
                        $_154 = null;
                        do {
                            if (( $subres = $this->whitespace() ) !== false) {
                                $result["text"] .= $subres;
                            }
                            $matcher = 'match_'.'FreeString';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                            } else {
                                $_154 = false;
                                break;
                            }
                            $_154 = true;
                            break;
                        } while (0);
                        if ($_154 === true) {
                            $result = $res_155;
                            $this->pos = $pos_155;
                            $_156 = false;
                            break;
                        }
                        if ($_154 === false) {
                            $result = $res_155;
                            $this->pos = $pos_155;
                        }
                        $_156 = true;
                        break;
                    } while (0);
                    if ($_156 === true) {
                        $_159 = true;
                        break;
                    }
                    $result = $res_150;
                    $this->pos = $pos_150;
                    $matcher = 'match_'.'FreeString';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "FreeString");
                        $_159 = true;
                        break;
                    }
                    $result = $res_150;
                    $this->pos = $pos_150;
                    $_159 = false;
                    break;
                } while (0);
                if ($_159 === true) {
                    $_161 = true;
                    break;
                }
                $result = $res_148;
                $this->pos = $pos_148;
                $_161 = false;
                break;
            } while (0);
            if ($_161 === true) {
                $_163 = true;
                break;
            }
            $result = $res_146;
            $this->pos = $pos_146;
            $_163 = false;
            break;
        } while (0);
        if ($_163 === true) {
            return $this->finalise($result);
        }
        if ($_163 === false) {
            return false;
        }
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
    function match_ComparisonOperator($stack = array())
    {
        $matchrule = "ComparisonOperator";
        $result = $this->construct($matchrule, $matchrule, null);
        $_188 = null;
        do {
            $res_165 = $result;
            $pos_165 = $this->pos;
            if (( $subres = $this->literal('!=') ) !== false) {
                $result["text"] .= $subres;
                $_188 = true;
                break;
            }
            $result = $res_165;
            $this->pos = $pos_165;
            $_186 = null;
            do {
                $res_167 = $result;
                $pos_167 = $this->pos;
                if (( $subres = $this->literal('==') ) !== false) {
                    $result["text"] .= $subres;
                    $_186 = true;
                    break;
                }
                $result = $res_167;
                $this->pos = $pos_167;
                $_184 = null;
                do {
                    $res_169 = $result;
                    $pos_169 = $this->pos;
                    if (( $subres = $this->literal('>=') ) !== false) {
                        $result["text"] .= $subres;
                        $_184 = true;
                        break;
                    }
                    $result = $res_169;
                    $this->pos = $pos_169;
                    $_182 = null;
                    do {
                        $res_171 = $result;
                        $pos_171 = $this->pos;
                        if (substr($this->string, $this->pos, 1) == '>') {
                            $this->pos += 1;
                            $result["text"] .= '>';
                            $_182 = true;
                            break;
                        }
                        $result = $res_171;
                        $this->pos = $pos_171;
                        $_180 = null;
                        do {
                            $res_173 = $result;
                            $pos_173 = $this->pos;
                            if (( $subres = $this->literal('<=') ) !== false) {
                                $result["text"] .= $subres;
                                $_180 = true;
                                break;
                            }
                            $result = $res_173;
                            $this->pos = $pos_173;
                            $_178 = null;
                            do {
                                $res_175 = $result;
                                $pos_175 = $this->pos;
                                if (substr($this->string, $this->pos, 1) == '<') {
                                    $this->pos += 1;
                                    $result["text"] .= '<';
                                    $_178 = true;
                                    break;
                                }
                                $result = $res_175;
                                $this->pos = $pos_175;
                                if (substr($this->string, $this->pos, 1) == '=') {
                                    $this->pos += 1;
                                    $result["text"] .= '=';
                                    $_178 = true;
                                    break;
                                }
                                $result = $res_175;
                                $this->pos = $pos_175;
                                $_178 = false;
                                break;
                            } while (0);
                            if ($_178 === true) {
                                $_180 = true;
                                break;
                            }
                            $result = $res_173;
                            $this->pos = $pos_173;
                            $_180 = false;
                            break;
                        } while (0);
                        if ($_180 === true) {
                            $_182 = true;
                            break;
                        }
                        $result = $res_171;
                        $this->pos = $pos_171;
                        $_182 = false;
                        break;
                    } while (0);
                    if ($_182 === true) {
                        $_184 = true;
                        break;
                    }
                    $result = $res_169;
                    $this->pos = $pos_169;
                    $_184 = false;
                    break;
                } while (0);
                if ($_184 === true) {
                    $_186 = true;
                    break;
                }
                $result = $res_167;
                $this->pos = $pos_167;
                $_186 = false;
                break;
            } while (0);
            if ($_186 === true) {
                $_188 = true;
                break;
            }
            $result = $res_165;
            $this->pos = $pos_165;
            $_188 = false;
            break;
        } while (0);
        if ($_188 === true) {
            return $this->finalise($result);
        }
        if ($_188 === false) {
            return false;
        }
    }


    /* Comparison: Argument < ComparisonOperator > Argument */
    protected $match_Comparison_typestack = array('Comparison');
    function match_Comparison($stack = array())
    {
        $matchrule = "Comparison";
        $result = $this->construct($matchrule, $matchrule, null);
        $_195 = null;
        do {
            $matcher = 'match_'.'Argument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_195 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'ComparisonOperator';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_195 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'Argument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_195 = false;
                break;
            }
            $_195 = true;
            break;
        } while (0);
        if ($_195 === true) {
            return $this->finalise($result);
        }
        if ($_195 === false) {
            return false;
        }
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
    function match_PresenceCheck($stack = array())
    {
        $matchrule = "PresenceCheck";
        $result = $this->construct($matchrule, $matchrule, null);
        $_202 = null;
        do {
            $res_200 = $result;
            $pos_200 = $this->pos;
            $_199 = null;
            do {
                $stack[] = $result;
                $result = $this->construct($matchrule, "Not");
                if (( $subres = $this->literal('not') ) !== false) {
                    $result["text"] .= $subres;
                    $subres = $result;
                    $result = array_pop($stack);
                    $this->store($result, $subres, 'Not');
                } else {
                    $result = array_pop($stack);
                    $_199 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $_199 = true;
                break;
            } while (0);
            if ($_199 === false) {
                $result = $res_200;
                $this->pos = $pos_200;
                unset($res_200);
                unset($pos_200);
            }
            $matcher = 'match_'.'Argument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_202 = false;
                break;
            }
            $_202 = true;
            break;
        } while (0);
        if ($_202 === true) {
            return $this->finalise($result);
        }
        if ($_202 === false) {
            return false;
        }
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
    function match_IfArgumentPortion($stack = array())
    {
        $matchrule = "IfArgumentPortion";
        $result = $this->construct($matchrule, $matchrule, null);
        $_207 = null;
        do {
            $res_204 = $result;
            $pos_204 = $this->pos;
            $matcher = 'match_'.'Comparison';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_207 = true;
                break;
            }
            $result = $res_204;
            $this->pos = $pos_204;
            $matcher = 'match_'.'PresenceCheck';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_207 = true;
                break;
            }
            $result = $res_204;
            $this->pos = $pos_204;
            $_207 = false;
            break;
        } while (0);
        if ($_207 === true) {
            return $this->finalise($result);
        }
        if ($_207 === false) {
            return false;
        }
    }



    function IfArgumentPortion_STR(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* BooleanOperator: "||" | "&&" */
    protected $match_BooleanOperator_typestack = array('BooleanOperator');
    function match_BooleanOperator($stack = array())
    {
        $matchrule = "BooleanOperator";
        $result = $this->construct($matchrule, $matchrule, null);
        $_212 = null;
        do {
            $res_209 = $result;
            $pos_209 = $this->pos;
            if (( $subres = $this->literal('||') ) !== false) {
                $result["text"] .= $subres;
                $_212 = true;
                break;
            }
            $result = $res_209;
            $this->pos = $pos_209;
            if (( $subres = $this->literal('&&') ) !== false) {
                $result["text"] .= $subres;
                $_212 = true;
                break;
            }
            $result = $res_209;
            $this->pos = $pos_209;
            $_212 = false;
            break;
        } while (0);
        if ($_212 === true) {
            return $this->finalise($result);
        }
        if ($_212 === false) {
            return false;
        }
    }


    /* IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )* */
    protected $match_IfArgument_typestack = array('IfArgument');
    function match_IfArgument($stack = array())
    {
        $matchrule = "IfArgument";
        $result = $this->construct($matchrule, $matchrule, null);
        $_221 = null;
        do {
            $matcher = 'match_'.'IfArgumentPortion';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "IfArgumentPortion");
            } else {
                $_221 = false;
                break;
            }
            while (true) {
                $res_220 = $result;
                $pos_220 = $this->pos;
                $_219 = null;
                do {
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    $matcher = 'match_'.'BooleanOperator';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "BooleanOperator");
                    } else {
                        $_219 = false;
                        break;
                    }
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    $matcher = 'match_'.'IfArgumentPortion';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "IfArgumentPortion");
                    } else {
                        $_219 = false;
                        break;
                    }
                    $_219 = true;
                    break;
                } while (0);
                if ($_219 === false) {
                    $result = $res_220;
                    $this->pos = $pos_220;
                    unset($res_220);
                    unset($pos_220);
                    break;
                }
            }
            $_221 = true;
            break;
        } while (0);
        if ($_221 === true) {
            return $this->finalise($result);
        }
        if ($_221 === false) {
            return false;
        }
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
    function match_IfPart($stack = array())
    {
        $matchrule = "IfPart";
        $result = $this->construct($matchrule, $matchrule, null);
        $_231 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_231 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('if') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_231 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_231 = false;
                break;
            }
            $matcher = 'match_'.'IfArgument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "IfArgument");
            } else {
                $_231 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_231 = false;
                break;
            }
            $res_230 = $result;
            $pos_230 = $this->pos;
            $matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher');
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Template");
            } else {
                $result = $res_230;
                $this->pos = $pos_230;
                unset($res_230);
                unset($pos_230);
            }
            $_231 = true;
            break;
        } while (0);
        if ($_231 === true) {
            return $this->finalise($result);
        }
        if ($_231 === false) {
            return false;
        }
    }


    /* ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher? */
    protected $match_ElseIfPart_typestack = array('ElseIfPart');
    function match_ElseIfPart($stack = array())
    {
        $matchrule = "ElseIfPart";
        $result = $this->construct($matchrule, $matchrule, null);
        $_241 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_241 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('else_if') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_241 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_241 = false;
                break;
            }
            $matcher = 'match_'.'IfArgument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "IfArgument");
            } else {
                $_241 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_241 = false;
                break;
            }
            $res_240 = $result;
            $pos_240 = $this->pos;
            $matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher');
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Template");
            } else {
                $result = $res_240;
                $this->pos = $pos_240;
                unset($res_240);
                unset($pos_240);
            }
            $_241 = true;
            break;
        } while (0);
        if ($_241 === true) {
            return $this->finalise($result);
        }
        if ($_241 === false) {
            return false;
        }
    }


    /* ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher? */
    protected $match_ElsePart_typestack = array('ElsePart');
    function match_ElsePart($stack = array())
    {
        $matchrule = "ElsePart";
        $result = $this->construct($matchrule, $matchrule, null);
        $_249 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_249 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('else') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_249 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_249 = false;
                break;
            }
            $res_248 = $result;
            $pos_248 = $this->pos;
            $matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher');
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Template");
            } else {
                $result = $res_248;
                $this->pos = $pos_248;
                unset($res_248);
                unset($pos_248);
            }
            $_249 = true;
            break;
        } while (0);
        if ($_249 === true) {
            return $this->finalise($result);
        }
        if ($_249 === false) {
            return false;
        }
    }


    /* If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>' */
    protected $match_If_typestack = array('If');
    function match_If($stack = array())
    {
        $matchrule = "If";
        $result = $this->construct($matchrule, $matchrule, null);
        $_259 = null;
        do {
            $matcher = 'match_'.'IfPart';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_259 = false;
                break;
            }
            while (true) {
                $res_252 = $result;
                $pos_252 = $this->pos;
                $matcher = 'match_'.'ElseIfPart';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $result = $res_252;
                    $this->pos = $pos_252;
                    unset($res_252);
                    unset($pos_252);
                    break;
                }
            }
            $res_253 = $result;
            $pos_253 = $this->pos;
            $matcher = 'match_'.'ElsePart';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $result = $res_253;
                $this->pos = $pos_253;
                unset($res_253);
                unset($pos_253);
            }
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_259 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('end_if') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_259 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_259 = false;
                break;
            }
            $_259 = true;
            break;
        } while (0);
        if ($_259 === true) {
            return $this->finalise($result);
        }
        if ($_259 === false) {
            return false;
        }
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
    function match_Require($stack = array())
    {
        $matchrule = "Require";
        $result = $this->construct($matchrule, $matchrule, null);
        $_275 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_275 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('require') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_275 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_275 = false;
                break;
            }
            $stack[] = $result;
            $result = $this->construct($matchrule, "Call");
            $_271 = null;
            do {
                $matcher = 'match_'.'Word';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "Method");
                } else {
                    $_271 = false;
                    break;
                }
                if (substr($this->string, $this->pos, 1) == '(') {
                    $this->pos += 1;
                    $result["text"] .= '(';
                } else {
                    $_271 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $matcher = 'match_'.'CallArguments';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "CallArguments");
                } else {
                    $_271 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                if (substr($this->string, $this->pos, 1) == ')') {
                    $this->pos += 1;
                    $result["text"] .= ')';
                } else {
                    $_271 = false;
                    break;
                }
                $_271 = true;
                break;
            } while (0);
            if ($_271 === true) {
                $subres = $result;
                $result = array_pop($stack);
                $this->store($result, $subres, 'Call');
            }
            if ($_271 === false) {
                $result = array_pop($stack);
                $_275 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_275 = false;
                break;
            }
            $_275 = true;
            break;
        } while (0);
        if ($_275 === true) {
            return $this->finalise($result);
        }
        if ($_275 === false) {
            return false;
        }
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
    function match_CacheBlockArgument($stack = array())
    {
        $matchrule = "CacheBlockArgument";
        $result = $this->construct($matchrule, $matchrule, null);
        $_295 = null;
        do {
            $res_283 = $result;
            $pos_283 = $this->pos;
            $_282 = null;
            do {
                $_280 = null;
                do {
                    $res_277 = $result;
                    $pos_277 = $this->pos;
                    if (( $subres = $this->literal('if ') ) !== false) {
                        $result["text"] .= $subres;
                        $_280 = true;
                        break;
                    }
                    $result = $res_277;
                    $this->pos = $pos_277;
                    if (( $subres = $this->literal('unless ') ) !== false) {
                        $result["text"] .= $subres;
                        $_280 = true;
                        break;
                    }
                    $result = $res_277;
                    $this->pos = $pos_277;
                    $_280 = false;
                    break;
                } while (0);
                if ($_280 === false) {
                    $_282 = false;
                    break;
                }
                $_282 = true;
                break;
            } while (0);
            if ($_282 === true) {
                $result = $res_283;
                $this->pos = $pos_283;
                $_295 = false;
                break;
            }
            if ($_282 === false) {
                $result = $res_283;
                $this->pos = $pos_283;
            }
            $_293 = null;
            do {
                $_291 = null;
                do {
                    $res_284 = $result;
                    $pos_284 = $this->pos;
                    $matcher = 'match_'.'DollarMarkedLookup';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "DollarMarkedLookup");
                        $_291 = true;
                        break;
                    }
                    $result = $res_284;
                    $this->pos = $pos_284;
                    $_289 = null;
                    do {
                        $res_286 = $result;
                        $pos_286 = $this->pos;
                        $matcher = 'match_'.'QuotedString';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres, "QuotedString");
                            $_289 = true;
                            break;
                        }
                        $result = $res_286;
                        $this->pos = $pos_286;
                        $matcher = 'match_'.'Lookup';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres, "Lookup");
                            $_289 = true;
                            break;
                        }
                        $result = $res_286;
                        $this->pos = $pos_286;
                        $_289 = false;
                        break;
                    } while (0);
                    if ($_289 === true) {
                        $_291 = true;
                        break;
                    }
                    $result = $res_284;
                    $this->pos = $pos_284;
                    $_291 = false;
                    break;
                } while (0);
                if ($_291 === false) {
                    $_293 = false;
                    break;
                }
                $_293 = true;
                break;
            } while (0);
            if ($_293 === false) {
                $_295 = false;
                break;
            }
            $_295 = true;
            break;
        } while (0);
        if ($_295 === true) {
            return $this->finalise($result);
        }
        if ($_295 === false) {
            return false;
        }
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
    function match_CacheBlockArguments($stack = array())
    {
        $matchrule = "CacheBlockArguments";
        $result = $this->construct($matchrule, $matchrule, null);
        $_304 = null;
        do {
            $matcher = 'match_'.'CacheBlockArgument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_304 = false;
                break;
            }
            while (true) {
                $res_303 = $result;
                $pos_303 = $this->pos;
                $_302 = null;
                do {
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    if (substr($this->string, $this->pos, 1) == ',') {
                        $this->pos += 1;
                        $result["text"] .= ',';
                    } else {
                        $_302 = false;
                        break;
                    }
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    $matcher = 'match_'.'CacheBlockArgument';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres);
                    } else {
                        $_302 = false;
                        break;
                    }
                    $_302 = true;
                    break;
                } while (0);
                if ($_302 === false) {
                    $result = $res_303;
                    $this->pos = $pos_303;
                    unset($res_303);
                    unset($pos_303);
                    break;
                }
            }
            $_304 = true;
            break;
        } while (0);
        if ($_304 === true) {
            return $this->finalise($result);
        }
        if ($_304 === false) {
            return false;
        }
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
	OpenBlock | MalformedBlock | Injection | Text)+ */
    protected $match_CacheBlockTemplate_typestack = array('CacheBlockTemplate','Template');
    function match_CacheBlockTemplate($stack = array())
    {
        $matchrule = "CacheBlockTemplate";
        $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'CacheRestrictedTemplate'));
        $count = 0;
        while (true) {
            $res_348 = $result;
            $pos_348 = $this->pos;
            $_347 = null;
            do {
                $_345 = null;
                do {
                    $res_306 = $result;
                    $pos_306 = $this->pos;
                    $matcher = 'match_'.'Comment';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres);
                        $_345 = true;
                        break;
                    }
                    $result = $res_306;
                    $this->pos = $pos_306;
                    $_343 = null;
                    do {
                        $res_308 = $result;
                        $pos_308 = $this->pos;
                        $matcher = 'match_'.'Translate';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                            $_343 = true;
                            break;
                        }
                        $result = $res_308;
                        $this->pos = $pos_308;
                        $_341 = null;
                        do {
                            $res_310 = $result;
                            $pos_310 = $this->pos;
                            $matcher = 'match_'.'If';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                                $_341 = true;
                                break;
                            }
                            $result = $res_310;
                            $this->pos = $pos_310;
                            $_339 = null;
                            do {
                                $res_312 = $result;
                                $pos_312 = $this->pos;
                                $matcher = 'match_'.'Require';
                                $key = $matcher;
                                $pos = $this->pos;
                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                if ($subres !== false) {
                                    $this->store($result, $subres);
                                    $_339 = true;
                                    break;
                                }
                                $result = $res_312;
                                $this->pos = $pos_312;
                                $_337 = null;
                                do {
                                    $res_314 = $result;
                                    $pos_314 = $this->pos;
                                    $matcher = 'match_'.'OldI18NTag';
                                    $key = $matcher;
                                    $pos = $this->pos;
                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                    if ($subres !== false) {
                                        $this->store($result, $subres);
                                        $_337 = true;
                                        break;
                                    }
                                    $result = $res_314;
                                    $this->pos = $pos_314;
                                    $_335 = null;
                                    do {
                                        $res_316 = $result;
                                        $pos_316 = $this->pos;
                                        $matcher = 'match_'.'Include';
                                        $key = $matcher;
                                        $pos = $this->pos;
                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                        if ($subres !== false) {
                                            $this->store($result, $subres);
                                            $_335 = true;
                                            break;
                                        }
                                        $result = $res_316;
                                        $this->pos = $pos_316;
                                        $_333 = null;
                                        do {
                                            $res_318 = $result;
                                            $pos_318 = $this->pos;
                                            $matcher = 'match_'.'ClosedBlock';
                                            $key = $matcher;
                                            $pos = $this->pos;
                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                            if ($subres !== false) {
                                                $this->store($result, $subres);
                                                $_333 = true;
                                                break;
                                            }
                                            $result = $res_318;
                                            $this->pos = $pos_318;
                                            $_331 = null;
                                            do {
                                                $res_320 = $result;
                                                $pos_320 = $this->pos;
                                                $matcher = 'match_'.'OpenBlock';
                                                $key = $matcher;
                                                $pos = $this->pos;
                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                if ($subres !== false) {
                                                    $this->store($result, $subres);
                                                    $_331 = true;
                                                    break;
                                                }
                                                $result = $res_320;
                                                $this->pos = $pos_320;
                                                $_329 = null;
                                                do {
                                                    $res_322 = $result;
                                                    $pos_322 = $this->pos;
                                                    $matcher = 'match_'.'MalformedBlock';
                                                    $key = $matcher;
                                                    $pos = $this->pos;
                                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                    if ($subres !== false) {
                                                        $this->store($result, $subres);
                                                        $_329 = true;
                                                        break;
                                                    }
                                                    $result = $res_322;
                                                    $this->pos = $pos_322;
                                                    $_327 = null;
                                                    do {
                                                        $res_324 = $result;
                                                        $pos_324 = $this->pos;
                                                        $matcher = 'match_'.'Injection';
                                                        $key = $matcher;
                                                        $pos = $this->pos;
                                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                        if ($subres !== false) {
                                                            $this->store($result, $subres);
                                                            $_327 = true;
                                                            break;
                                                        }
                                                        $result = $res_324;
                                                        $this->pos = $pos_324;
                                                        $matcher = 'match_'.'Text';
                                                        $key = $matcher;
                                                        $pos = $this->pos;
                                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                        if ($subres !== false) {
                                                            $this->store($result, $subres);
                                                            $_327 = true;
                                                            break;
                                                        }
                                                        $result = $res_324;
                                                        $this->pos = $pos_324;
                                                        $_327 = false;
                                                        break;
                                                    } while (0);
                                                    if ($_327 === true) {
                                                        $_329 = true;
                                                        break;
                                                    }
                                                    $result = $res_322;
                                                    $this->pos = $pos_322;
                                                    $_329 = false;
                                                    break;
                                                } while (0);
                                                if ($_329 === true) {
                                                    $_331 = true;
                                                    break;
                                                }
                                                $result = $res_320;
                                                $this->pos = $pos_320;
                                                $_331 = false;
                                                break;
                                            } while (0);
                                            if ($_331 === true) {
                                                $_333 = true;
                                                break;
                                            }
                                            $result = $res_318;
                                            $this->pos = $pos_318;
                                            $_333 = false;
                                            break;
                                        } while (0);
                                        if ($_333 === true) {
                                            $_335 = true;
                                            break;
                                        }
                                        $result = $res_316;
                                        $this->pos = $pos_316;
                                        $_335 = false;
                                        break;
                                    } while (0);
                                    if ($_335 === true) {
                                        $_337 = true;
                                        break;
                                    }
                                    $result = $res_314;
                                    $this->pos = $pos_314;
                                    $_337 = false;
                                    break;
                                } while (0);
                                if ($_337 === true) {
                                    $_339 = true;
                                    break;
                                }
                                $result = $res_312;
                                $this->pos = $pos_312;
                                $_339 = false;
                                break;
                            } while (0);
                            if ($_339 === true) {
                                $_341 = true;
                                break;
                            }
                            $result = $res_310;
                            $this->pos = $pos_310;
                            $_341 = false;
                            break;
                        } while (0);
                        if ($_341 === true) {
                            $_343 = true;
                            break;
                        }
                        $result = $res_308;
                        $this->pos = $pos_308;
                        $_343 = false;
                        break;
                    } while (0);
                    if ($_343 === true) {
                        $_345 = true;
                        break;
                    }
                    $result = $res_306;
                    $this->pos = $pos_306;
                    $_345 = false;
                    break;
                } while (0);
                if ($_345 === false) {
                    $_347 = false;
                    break;
                }
                $_347 = true;
                break;
            } while (0);
            if ($_347 === false) {
                $result = $res_348;
                $this->pos = $pos_348;
                unset($res_348);
                unset($pos_348);
                break;
            }
            $count += 1;
        }
        if ($count > 0) {
            return $this->finalise($result);
        } else {
            return false;
        }
    }




    /* UncachedBlock:
	'<%' < "uncached" < CacheBlockArguments? ( < Conditional:("if"|"unless") > Condition:IfArgument )? > '%>'
		Template:$TemplateMatcher?
		'<%' < 'end_' ("uncached"|"cached"|"cacheblock") > '%>' */
    protected $match_UncachedBlock_typestack = array('UncachedBlock');
    function match_UncachedBlock($stack = array())
    {
        $matchrule = "UncachedBlock";
        $result = $this->construct($matchrule, $matchrule, null);
        $_385 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_385 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('uncached') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_385 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_353 = $result;
            $pos_353 = $this->pos;
            $matcher = 'match_'.'CacheBlockArguments';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $result = $res_353;
                $this->pos = $pos_353;
                unset($res_353);
                unset($pos_353);
            }
            $res_365 = $result;
            $pos_365 = $this->pos;
            $_364 = null;
            do {
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $stack[] = $result;
                $result = $this->construct($matchrule, "Conditional");
                $_360 = null;
                do {
                    $_358 = null;
                    do {
                        $res_355 = $result;
                        $pos_355 = $this->pos;
                        if (( $subres = $this->literal('if') ) !== false) {
                            $result["text"] .= $subres;
                            $_358 = true;
                            break;
                        }
                        $result = $res_355;
                        $this->pos = $pos_355;
                        if (( $subres = $this->literal('unless') ) !== false) {
                            $result["text"] .= $subres;
                            $_358 = true;
                            break;
                        }
                        $result = $res_355;
                        $this->pos = $pos_355;
                        $_358 = false;
                        break;
                    } while (0);
                    if ($_358 === false) {
                        $_360 = false;
                        break;
                    }
                    $_360 = true;
                    break;
                } while (0);
                if ($_360 === true) {
                    $subres = $result;
                    $result = array_pop($stack);
                    $this->store($result, $subres, 'Conditional');
                }
                if ($_360 === false) {
                    $result = array_pop($stack);
                    $_364 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $matcher = 'match_'.'IfArgument';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "Condition");
                } else {
                    $_364 = false;
                    break;
                }
                $_364 = true;
                break;
            } while (0);
            if ($_364 === false) {
                $result = $res_365;
                $this->pos = $pos_365;
                unset($res_365);
                unset($pos_365);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_385 = false;
                break;
            }
            $res_368 = $result;
            $pos_368 = $this->pos;
            $matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher');
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Template");
            } else {
                $result = $res_368;
                $this->pos = $pos_368;
                unset($res_368);
                unset($pos_368);
            }
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_385 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('end_') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_385 = false;
                break;
            }
            $_381 = null;
            do {
                $_379 = null;
                do {
                    $res_372 = $result;
                    $pos_372 = $this->pos;
                    if (( $subres = $this->literal('uncached') ) !== false) {
                        $result["text"] .= $subres;
                        $_379 = true;
                        break;
                    }
                    $result = $res_372;
                    $this->pos = $pos_372;
                    $_377 = null;
                    do {
                        $res_374 = $result;
                        $pos_374 = $this->pos;
                        if (( $subres = $this->literal('cached') ) !== false) {
                            $result["text"] .= $subres;
                            $_377 = true;
                            break;
                        }
                        $result = $res_374;
                        $this->pos = $pos_374;
                        if (( $subres = $this->literal('cacheblock') ) !== false) {
                            $result["text"] .= $subres;
                            $_377 = true;
                            break;
                        }
                        $result = $res_374;
                        $this->pos = $pos_374;
                        $_377 = false;
                        break;
                    } while (0);
                    if ($_377 === true) {
                        $_379 = true;
                        break;
                    }
                    $result = $res_372;
                    $this->pos = $pos_372;
                    $_379 = false;
                    break;
                } while (0);
                if ($_379 === false) {
                    $_381 = false;
                    break;
                }
                $_381 = true;
                break;
            } while (0);
            if ($_381 === false) {
                $_385 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_385 = false;
                break;
            }
            $_385 = true;
            break;
        } while (0);
        if ($_385 === true) {
            return $this->finalise($result);
        }
        if ($_385 === false) {
            return false;
        }
    }



    function UncachedBlock_Template(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* CacheRestrictedTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
	OpenBlock | MalformedBlock | Injection | Text)+ */
    protected $match_CacheRestrictedTemplate_typestack = array('CacheRestrictedTemplate','Template');
    function match_CacheRestrictedTemplate($stack = array())
    {
        $matchrule = "CacheRestrictedTemplate";
        $result = $this->construct($matchrule, $matchrule, null);
        $count = 0;
        while (true) {
            $res_437 = $result;
            $pos_437 = $this->pos;
            $_436 = null;
            do {
                $_434 = null;
                do {
                    $res_387 = $result;
                    $pos_387 = $this->pos;
                    $matcher = 'match_'.'Comment';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres);
                        $_434 = true;
                        break;
                    }
                    $result = $res_387;
                    $this->pos = $pos_387;
                    $_432 = null;
                    do {
                        $res_389 = $result;
                        $pos_389 = $this->pos;
                        $matcher = 'match_'.'Translate';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                            $_432 = true;
                            break;
                        }
                        $result = $res_389;
                        $this->pos = $pos_389;
                        $_430 = null;
                        do {
                            $res_391 = $result;
                            $pos_391 = $this->pos;
                            $matcher = 'match_'.'If';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                                $_430 = true;
                                break;
                            }
                            $result = $res_391;
                            $this->pos = $pos_391;
                            $_428 = null;
                            do {
                                $res_393 = $result;
                                $pos_393 = $this->pos;
                                $matcher = 'match_'.'Require';
                                $key = $matcher;
                                $pos = $this->pos;
                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                if ($subres !== false) {
                                    $this->store($result, $subres);
                                    $_428 = true;
                                    break;
                                }
                                $result = $res_393;
                                $this->pos = $pos_393;
                                $_426 = null;
                                do {
                                    $res_395 = $result;
                                    $pos_395 = $this->pos;
                                    $matcher = 'match_'.'CacheBlock';
                                    $key = $matcher;
                                    $pos = $this->pos;
                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                    if ($subres !== false) {
                                        $this->store($result, $subres);
                                        $_426 = true;
                                        break;
                                    }
                                    $result = $res_395;
                                    $this->pos = $pos_395;
                                    $_424 = null;
                                    do {
                                        $res_397 = $result;
                                        $pos_397 = $this->pos;
                                        $matcher = 'match_'.'UncachedBlock';
                                        $key = $matcher;
                                        $pos = $this->pos;
                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                        if ($subres !== false) {
                                            $this->store($result, $subres);
                                            $_424 = true;
                                            break;
                                        }
                                        $result = $res_397;
                                        $this->pos = $pos_397;
                                        $_422 = null;
                                        do {
                                            $res_399 = $result;
                                            $pos_399 = $this->pos;
                                            $matcher = 'match_'.'OldI18NTag';
                                            $key = $matcher;
                                            $pos = $this->pos;
                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                            if ($subres !== false) {
                                                $this->store($result, $subres);
                                                $_422 = true;
                                                break;
                                            }
                                            $result = $res_399;
                                            $this->pos = $pos_399;
                                            $_420 = null;
                                            do {
                                                $res_401 = $result;
                                                $pos_401 = $this->pos;
                                                $matcher = 'match_'.'Include';
                                                $key = $matcher;
                                                $pos = $this->pos;
                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                if ($subres !== false) {
                                                    $this->store($result, $subres);
                                                    $_420 = true;
                                                    break;
                                                }
                                                $result = $res_401;
                                                $this->pos = $pos_401;
                                                $_418 = null;
                                                do {
                                                    $res_403 = $result;
                                                    $pos_403 = $this->pos;
                                                    $matcher = 'match_'.'ClosedBlock';
                                                    $key = $matcher;
                                                    $pos = $this->pos;
                                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                    if ($subres !== false) {
                                                        $this->store($result, $subres);
                                                        $_418 = true;
                                                        break;
                                                    }
                                                    $result = $res_403;
                                                    $this->pos = $pos_403;
                                                    $_416 = null;
                                                    do {
                                                        $res_405 = $result;
                                                        $pos_405 = $this->pos;
                                                        $matcher = 'match_'.'OpenBlock';
                                                        $key = $matcher;
                                                        $pos = $this->pos;
                                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                        if ($subres !== false) {
                                                            $this->store($result, $subres);
                                                            $_416 = true;
                                                            break;
                                                        }
                                                        $result = $res_405;
                                                        $this->pos = $pos_405;
                                                        $_414 = null;
                                                        do {
                                                            $res_407 = $result;
                                                            $pos_407 = $this->pos;
                                                            $matcher = 'match_'.'MalformedBlock';
                                                            $key = $matcher;
                                                            $pos = $this->pos;
                                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                            if ($subres !== false) {
                                                                $this->store($result, $subres);
                                                                $_414 = true;
                                                                break;
                                                            }
                                                            $result = $res_407;
                                                            $this->pos = $pos_407;
                                                            $_412 = null;
                                                            do {
                                                                $res_409 = $result;
                                                                $pos_409 = $this->pos;
                                                                $matcher = 'match_'.'Injection';
                                                                $key = $matcher;
                                                                $pos = $this->pos;
                                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                if ($subres !== false) {
                                                                    $this->store($result, $subres);
                                                                    $_412 = true;
                                                                    break;
                                                                }
                                                                $result = $res_409;
                                                                $this->pos = $pos_409;
                                                                $matcher = 'match_'.'Text';
                                                                $key = $matcher;
                                                                $pos = $this->pos;
                                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                if ($subres !== false) {
                                                                    $this->store($result, $subres);
                                                                    $_412 = true;
                                                                    break;
                                                                }
                                                                $result = $res_409;
                                                                $this->pos = $pos_409;
                                                                $_412 = false;
                                                                break;
                                                            } while (0);
                                                            if ($_412 === true) {
                                                                $_414 = true;
                                                                break;
                                                            }
                                                            $result = $res_407;
                                                            $this->pos = $pos_407;
                                                            $_414 = false;
                                                            break;
                                                        } while (0);
                                                        if ($_414 === true) {
                                                            $_416 = true;
                                                            break;
                                                        }
                                                        $result = $res_405;
                                                        $this->pos = $pos_405;
                                                        $_416 = false;
                                                        break;
                                                    } while (0);
                                                    if ($_416 === true) {
                                                        $_418 = true;
                                                        break;
                                                    }
                                                    $result = $res_403;
                                                    $this->pos = $pos_403;
                                                    $_418 = false;
                                                    break;
                                                } while (0);
                                                if ($_418 === true) {
                                                    $_420 = true;
                                                    break;
                                                }
                                                $result = $res_401;
                                                $this->pos = $pos_401;
                                                $_420 = false;
                                                break;
                                            } while (0);
                                            if ($_420 === true) {
                                                $_422 = true;
                                                break;
                                            }
                                            $result = $res_399;
                                            $this->pos = $pos_399;
                                            $_422 = false;
                                            break;
                                        } while (0);
                                        if ($_422 === true) {
                                            $_424 = true;
                                            break;
                                        }
                                        $result = $res_397;
                                        $this->pos = $pos_397;
                                        $_424 = false;
                                        break;
                                    } while (0);
                                    if ($_424 === true) {
                                        $_426 = true;
                                        break;
                                    }
                                    $result = $res_395;
                                    $this->pos = $pos_395;
                                    $_426 = false;
                                    break;
                                } while (0);
                                if ($_426 === true) {
                                    $_428 = true;
                                    break;
                                }
                                $result = $res_393;
                                $this->pos = $pos_393;
                                $_428 = false;
                                break;
                            } while (0);
                            if ($_428 === true) {
                                $_430 = true;
                                break;
                            }
                            $result = $res_391;
                            $this->pos = $pos_391;
                            $_430 = false;
                            break;
                        } while (0);
                        if ($_430 === true) {
                            $_432 = true;
                            break;
                        }
                        $result = $res_389;
                        $this->pos = $pos_389;
                        $_432 = false;
                        break;
                    } while (0);
                    if ($_432 === true) {
                        $_434 = true;
                        break;
                    }
                    $result = $res_387;
                    $this->pos = $pos_387;
                    $_434 = false;
                    break;
                } while (0);
                if ($_434 === false) {
                    $_436 = false;
                    break;
                }
                $_436 = true;
                break;
            } while (0);
            if ($_436 === false) {
                $result = $res_437;
                $this->pos = $pos_437;
                unset($res_437);
                unset($pos_437);
                break;
            }
            $count += 1;
        }
        if ($count > 0) {
            return $this->finalise($result);
        } else {
            return false;
        }
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
    function match_CacheBlock($stack = array())
    {
        $matchrule = "CacheBlock";
        $result = $this->construct($matchrule, $matchrule, null);
        $_492 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_492 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $stack[] = $result;
            $result = $this->construct($matchrule, "CacheTag");
            $_445 = null;
            do {
                $_443 = null;
                do {
                    $res_440 = $result;
                    $pos_440 = $this->pos;
                    if (( $subres = $this->literal('cached') ) !== false) {
                        $result["text"] .= $subres;
                        $_443 = true;
                        break;
                    }
                    $result = $res_440;
                    $this->pos = $pos_440;
                    if (( $subres = $this->literal('cacheblock') ) !== false) {
                        $result["text"] .= $subres;
                        $_443 = true;
                        break;
                    }
                    $result = $res_440;
                    $this->pos = $pos_440;
                    $_443 = false;
                    break;
                } while (0);
                if ($_443 === false) {
                    $_445 = false;
                    break;
                }
                $_445 = true;
                break;
            } while (0);
            if ($_445 === true) {
                $subres = $result;
                $result = array_pop($stack);
                $this->store($result, $subres, 'CacheTag');
            }
            if ($_445 === false) {
                $result = array_pop($stack);
                $_492 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_450 = $result;
            $pos_450 = $this->pos;
            $_449 = null;
            do {
                $matcher = 'match_'.'CacheBlockArguments';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_449 = false;
                    break;
                }
                $_449 = true;
                break;
            } while (0);
            if ($_449 === false) {
                $result = $res_450;
                $this->pos = $pos_450;
                unset($res_450);
                unset($pos_450);
            }
            $res_462 = $result;
            $pos_462 = $this->pos;
            $_461 = null;
            do {
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $stack[] = $result;
                $result = $this->construct($matchrule, "Conditional");
                $_457 = null;
                do {
                    $_455 = null;
                    do {
                        $res_452 = $result;
                        $pos_452 = $this->pos;
                        if (( $subres = $this->literal('if') ) !== false) {
                            $result["text"] .= $subres;
                            $_455 = true;
                            break;
                        }
                        $result = $res_452;
                        $this->pos = $pos_452;
                        if (( $subres = $this->literal('unless') ) !== false) {
                            $result["text"] .= $subres;
                            $_455 = true;
                            break;
                        }
                        $result = $res_452;
                        $this->pos = $pos_452;
                        $_455 = false;
                        break;
                    } while (0);
                    if ($_455 === false) {
                        $_457 = false;
                        break;
                    }
                    $_457 = true;
                    break;
                } while (0);
                if ($_457 === true) {
                    $subres = $result;
                    $result = array_pop($stack);
                    $this->store($result, $subres, 'Conditional');
                }
                if ($_457 === false) {
                    $result = array_pop($stack);
                    $_461 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                $matcher = 'match_'.'IfArgument';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "Condition");
                } else {
                    $_461 = false;
                    break;
                }
                $_461 = true;
                break;
            } while (0);
            if ($_461 === false) {
                $result = $res_462;
                $this->pos = $pos_462;
                unset($res_462);
                unset($pos_462);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_492 = false;
                break;
            }
            while (true) {
                $res_475 = $result;
                $pos_475 = $this->pos;
                $_474 = null;
                do {
                    $_472 = null;
                    do {
                        $res_465 = $result;
                        $pos_465 = $this->pos;
                        $matcher = 'match_'.'CacheBlock';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                            $_472 = true;
                            break;
                        }
                        $result = $res_465;
                        $this->pos = $pos_465;
                        $_470 = null;
                        do {
                            $res_467 = $result;
                            $pos_467 = $this->pos;
                            $matcher = 'match_'.'UncachedBlock';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                                $_470 = true;
                                break;
                            }
                            $result = $res_467;
                            $this->pos = $pos_467;
                            $matcher = 'match_'.'CacheBlockTemplate';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                                $_470 = true;
                                break;
                            }
                            $result = $res_467;
                            $this->pos = $pos_467;
                            $_470 = false;
                            break;
                        } while (0);
                        if ($_470 === true) {
                            $_472 = true;
                            break;
                        }
                        $result = $res_465;
                        $this->pos = $pos_465;
                        $_472 = false;
                        break;
                    } while (0);
                    if ($_472 === false) {
                        $_474 = false;
                        break;
                    }
                    $_474 = true;
                    break;
                } while (0);
                if ($_474 === false) {
                    $result = $res_475;
                    $this->pos = $pos_475;
                    unset($res_475);
                    unset($pos_475);
                    break;
                }
            }
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_492 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('end_') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_492 = false;
                break;
            }
            $_488 = null;
            do {
                $_486 = null;
                do {
                    $res_479 = $result;
                    $pos_479 = $this->pos;
                    if (( $subres = $this->literal('cached') ) !== false) {
                        $result["text"] .= $subres;
                        $_486 = true;
                        break;
                    }
                    $result = $res_479;
                    $this->pos = $pos_479;
                    $_484 = null;
                    do {
                        $res_481 = $result;
                        $pos_481 = $this->pos;
                        if (( $subres = $this->literal('uncached') ) !== false) {
                            $result["text"] .= $subres;
                            $_484 = true;
                            break;
                        }
                        $result = $res_481;
                        $this->pos = $pos_481;
                        if (( $subres = $this->literal('cacheblock') ) !== false) {
                            $result["text"] .= $subres;
                            $_484 = true;
                            break;
                        }
                        $result = $res_481;
                        $this->pos = $pos_481;
                        $_484 = false;
                        break;
                    } while (0);
                    if ($_484 === true) {
                        $_486 = true;
                        break;
                    }
                    $result = $res_479;
                    $this->pos = $pos_479;
                    $_486 = false;
                    break;
                } while (0);
                if ($_486 === false) {
                    $_488 = false;
                    break;
                }
                $_488 = true;
                break;
            } while (0);
            if ($_488 === false) {
                $_492 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_492 = false;
                break;
            }
            $_492 = true;
            break;
        } while (0);
        if ($_492 === true) {
            return $this->finalise($result);
        }
        if ($_492 === false) {
            return false;
        }
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
    function match_OldTPart($stack = array())
    {
        $matchrule = "OldTPart";
        $result = $this->construct($matchrule, $matchrule, null);
        $_511 = null;
        do {
            if (( $subres = $this->literal('_t') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_511 = false;
                break;
            }
            $matcher = 'match_'.'N';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_511 = false;
                break;
            }
            if (substr($this->string, $this->pos, 1) == '(') {
                $this->pos += 1;
                $result["text"] .= '(';
            } else {
                $_511 = false;
                break;
            }
            $matcher = 'match_'.'N';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_511 = false;
                break;
            }
            $matcher = 'match_'.'QuotedString';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_511 = false;
                break;
            }
            $res_504 = $result;
            $pos_504 = $this->pos;
            $_503 = null;
            do {
                $matcher = 'match_'.'N';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_503 = false;
                    break;
                }
                if (substr($this->string, $this->pos, 1) == ',') {
                    $this->pos += 1;
                    $result["text"] .= ',';
                } else {
                    $_503 = false;
                    break;
                }
                $matcher = 'match_'.'N';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_503 = false;
                    break;
                }
                $matcher = 'match_'.'CallArguments';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_503 = false;
                    break;
                }
                $_503 = true;
                break;
            } while (0);
            if ($_503 === false) {
                $result = $res_504;
                $this->pos = $pos_504;
                unset($res_504);
                unset($pos_504);
            }
            $matcher = 'match_'.'N';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_511 = false;
                break;
            }
            if (substr($this->string, $this->pos, 1) == ')') {
                $this->pos += 1;
                $result["text"] .= ')';
            } else {
                $_511 = false;
                break;
            }
            $matcher = 'match_'.'N';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_511 = false;
                break;
            }
            $res_510 = $result;
            $pos_510 = $this->pos;
            $_509 = null;
            do {
                if (substr($this->string, $this->pos, 1) == ';') {
                    $this->pos += 1;
                    $result["text"] .= ';';
                } else {
                    $_509 = false;
                    break;
                }
                $_509 = true;
                break;
            } while (0);
            if ($_509 === false) {
                $result = $res_510;
                $this->pos = $pos_510;
                unset($res_510);
                unset($pos_510);
            }
            $_511 = true;
            break;
        } while (0);
        if ($_511 === true) {
            return $this->finalise($result);
        }
        if ($_511 === false) {
            return false;
        }
    }


    /* N: / [\s\n]* / */
    protected $match_N_typestack = array('N');
    function match_N($stack = array())
    {
        $matchrule = "N";
        $result = $this->construct($matchrule, $matchrule, null);
        if (( $subres = $this->rx('/ [\s\n]* /') ) !== false) {
            $result["text"] .= $subres;
            return $this->finalise($result);
        } else {
            return false;
        }
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
    function match_OldTTag($stack = array())
    {
        $matchrule = "OldTTag";
        $result = $this->construct($matchrule, $matchrule, null);
        $_519 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_519 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'OldTPart';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_519 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_519 = false;
                break;
            }
            $_519 = true;
            break;
        } while (0);
        if ($_519 === true) {
            return $this->finalise($result);
        }
        if ($_519 === false) {
            return false;
        }
    }



    function OldTTag_OldTPart(&$res, $sub)
    {
        $res['php'] = $sub['php'];
    }

    /* OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>" */
    protected $match_OldSprintfTag_typestack = array('OldSprintfTag');
    function match_OldSprintfTag($stack = array())
    {
        $matchrule = "OldSprintfTag";
        $result = $this->construct($matchrule, $matchrule, null);
        $_536 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('sprintf') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (substr($this->string, $this->pos, 1) == '(') {
                $this->pos += 1;
                $result["text"] .= '(';
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'OldTPart';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (substr($this->string, $this->pos, 1) == ',') {
                $this->pos += 1;
                $result["text"] .= ',';
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'CallArguments';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (substr($this->string, $this->pos, 1) == ')') {
                $this->pos += 1;
                $result["text"] .= ')';
            } else {
                $_536 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_536 = false;
                break;
            }
            $_536 = true;
            break;
        } while (0);
        if ($_536 === true) {
            return $this->finalise($result);
        }
        if ($_536 === false) {
            return false;
        }
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
    function match_OldI18NTag($stack = array())
    {
        $matchrule = "OldI18NTag";
        $result = $this->construct($matchrule, $matchrule, null);
        $_541 = null;
        do {
            $res_538 = $result;
            $pos_538 = $this->pos;
            $matcher = 'match_'.'OldSprintfTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_541 = true;
                break;
            }
            $result = $res_538;
            $this->pos = $pos_538;
            $matcher = 'match_'.'OldTTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_541 = true;
                break;
            }
            $result = $res_538;
            $this->pos = $pos_538;
            $_541 = false;
            break;
        } while (0);
        if ($_541 === true) {
            return $this->finalise($result);
        }
        if ($_541 === false) {
            return false;
        }
    }



    function OldI18NTag_STR(&$res, $sub)
    {
        $res['php'] = '$val .= ' . $sub['php'] . ';';
    }

    /* NamedArgument: Name:Word "=" Value:Argument */
    protected $match_NamedArgument_typestack = array('NamedArgument');
    function match_NamedArgument($stack = array())
    {
        $matchrule = "NamedArgument";
        $result = $this->construct($matchrule, $matchrule, null);
        $_546 = null;
        do {
            $matcher = 'match_'.'Word';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Name");
            } else {
                $_546 = false;
                break;
            }
            if (substr($this->string, $this->pos, 1) == '=') {
                $this->pos += 1;
                $result["text"] .= '=';
            } else {
                $_546 = false;
                break;
            }
            $matcher = 'match_'.'Argument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Value");
            } else {
                $_546 = false;
                break;
            }
            $_546 = true;
            break;
        } while (0);
        if ($_546 === true) {
            return $this->finalise($result);
        }
        if ($_546 === false) {
            return false;
        }
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
    function match_Include($stack = array())
    {
        $matchrule = "Include";
        $result = $this->construct($matchrule, $matchrule, null);
        $_565 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_565 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('include') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_565 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $matcher = 'match_'.'NamespacedWord';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Template");
            } else {
                $_565 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_562 = $result;
            $pos_562 = $this->pos;
            $_561 = null;
            do {
                $matcher = 'match_'.'NamedArgument';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres);
                } else {
                    $_561 = false;
                    break;
                }
                while (true) {
                    $res_560 = $result;
                    $pos_560 = $this->pos;
                    $_559 = null;
                    do {
                        if (( $subres = $this->whitespace() ) !== false) {
                            $result["text"] .= $subres;
                        }
                        if (substr($this->string, $this->pos, 1) == ',') {
                            $this->pos += 1;
                            $result["text"] .= ',';
                        } else {
                            $_559 = false;
                            break;
                        }
                        if (( $subres = $this->whitespace() ) !== false) {
                            $result["text"] .= $subres;
                        }
                        $matcher = 'match_'.'NamedArgument';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                        } else {
                            $_559 = false;
                            break;
                        }
                        $_559 = true;
                        break;
                    } while (0);
                    if ($_559 === false) {
                        $result = $res_560;
                        $this->pos = $pos_560;
                        unset($res_560);
                        unset($pos_560);
                        break;
                    }
                }
                $_561 = true;
                break;
            } while (0);
            if ($_561 === false) {
                $result = $res_562;
                $this->pos = $pos_562;
                unset($res_562);
                unset($pos_562);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_565 = false;
                break;
            }
            $_565 = true;
            break;
        } while (0);
        if ($_565 === true) {
            return $this->finalise($result);
        }
        if ($_565 === false) {
            return false;
        }
    }



    function Include__construct(&$res)
    {
        $res['arguments'] = array();
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

        $res['php'] = '$val .= \\SilverStripe\\View\\SSViewer::execute_template(["type" => "Includes", '.$template.'], $scope->getItem(), array(' .
            implode(',', $arguments)."), \$scope);\n";

        if ($this->includeDebuggingComments) { // Add include filename comments on dev sites
            $res['php'] =
                '$val .= \'<!-- include '.addslashes($template).' -->\';'. "\n".
                $res['php'].
                '$val .= \'<!-- end include '.addslashes($template).' -->\';'. "\n";
        }
    }

    /* BlockArguments: :Argument ( < "," < :Argument)* */
    protected $match_BlockArguments_typestack = array('BlockArguments');
    function match_BlockArguments($stack = array())
    {
        $matchrule = "BlockArguments";
        $result = $this->construct($matchrule, $matchrule, null);
        $_574 = null;
        do {
            $matcher = 'match_'.'Argument';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Argument");
            } else {
                $_574 = false;
                break;
            }
            while (true) {
                $res_573 = $result;
                $pos_573 = $this->pos;
                $_572 = null;
                do {
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    if (substr($this->string, $this->pos, 1) == ',') {
                        $this->pos += 1;
                        $result["text"] .= ',';
                    } else {
                        $_572 = false;
                        break;
                    }
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    }
                    $matcher = 'match_'.'Argument';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "Argument");
                    } else {
                        $_572 = false;
                        break;
                    }
                    $_572 = true;
                    break;
                } while (0);
                if ($_572 === false) {
                    $result = $res_573;
                    $this->pos = $pos_573;
                    unset($res_573);
                    unset($pos_573);
                    break;
                }
            }
            $_574 = true;
            break;
        } while (0);
        if ($_574 === true) {
            return $this->finalise($result);
        }
        if ($_574 === false) {
            return false;
        }
    }


    /* NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")]) */
    protected $match_NotBlockTag_typestack = array('NotBlockTag');
    function match_NotBlockTag($stack = array())
    {
        $matchrule = "NotBlockTag";
        $result = $this->construct($matchrule, $matchrule, null);
        $_612 = null;
        do {
            $res_576 = $result;
            $pos_576 = $this->pos;
            if (( $subres = $this->literal('end_') ) !== false) {
                $result["text"] .= $subres;
                $_612 = true;
                break;
            }
            $result = $res_576;
            $this->pos = $pos_576;
            $_610 = null;
            do {
                $_607 = null;
                do {
                    $_605 = null;
                    do {
                        $res_578 = $result;
                        $pos_578 = $this->pos;
                        if (( $subres = $this->literal('if') ) !== false) {
                            $result["text"] .= $subres;
                            $_605 = true;
                            break;
                        }
                        $result = $res_578;
                        $this->pos = $pos_578;
                        $_603 = null;
                        do {
                            $res_580 = $result;
                            $pos_580 = $this->pos;
                            if (( $subres = $this->literal('else_if') ) !== false) {
                                $result["text"] .= $subres;
                                $_603 = true;
                                break;
                            }
                            $result = $res_580;
                            $this->pos = $pos_580;
                            $_601 = null;
                            do {
                                $res_582 = $result;
                                $pos_582 = $this->pos;
                                if (( $subres = $this->literal('else') ) !== false) {
                                    $result["text"] .= $subres;
                                    $_601 = true;
                                    break;
                                }
                                $result = $res_582;
                                $this->pos = $pos_582;
                                $_599 = null;
                                do {
                                    $res_584 = $result;
                                    $pos_584 = $this->pos;
                                    if (( $subres = $this->literal('require') ) !== false) {
                                        $result["text"] .= $subres;
                                        $_599 = true;
                                        break;
                                    }
                                    $result = $res_584;
                                    $this->pos = $pos_584;
                                    $_597 = null;
                                    do {
                                        $res_586 = $result;
                                        $pos_586 = $this->pos;
                                        if (( $subres = $this->literal('cached') ) !== false) {
                                            $result["text"] .= $subres;
                                            $_597 = true;
                                            break;
                                        }
                                        $result = $res_586;
                                        $this->pos = $pos_586;
                                        $_595 = null;
                                        do {
                                            $res_588 = $result;
                                            $pos_588 = $this->pos;
                                            if (( $subres = $this->literal('uncached') ) !== false) {
                                                $result["text"] .= $subres;
                                                $_595 = true;
                                                break;
                                            }
                                            $result = $res_588;
                                            $this->pos = $pos_588;
                                            $_593 = null;
                                            do {
                                                $res_590 = $result;
                                                $pos_590 = $this->pos;
                                                if (( $subres = $this->literal('cacheblock') ) !== false) {
                                                    $result["text"] .= $subres;
                                                    $_593 = true;
                                                    break;
                                                }
                                                $result = $res_590;
                                                $this->pos = $pos_590;
                                                if (( $subres = $this->literal('include') ) !== false) {
                                                    $result["text"] .= $subres;
                                                    $_593 = true;
                                                    break;
                                                }
                                                $result = $res_590;
                                                $this->pos = $pos_590;
                                                $_593 = false;
                                                break;
                                            } while (0);
                                            if ($_593 === true) {
                                                $_595 = true;
                                                break;
                                            }
                                            $result = $res_588;
                                            $this->pos = $pos_588;
                                            $_595 = false;
                                            break;
                                        } while (0);
                                        if ($_595 === true) {
                                            $_597 = true;
                                            break;
                                        }
                                        $result = $res_586;
                                        $this->pos = $pos_586;
                                        $_597 = false;
                                        break;
                                    } while (0);
                                    if ($_597 === true) {
                                        $_599 = true;
                                        break;
                                    }
                                    $result = $res_584;
                                    $this->pos = $pos_584;
                                    $_599 = false;
                                    break;
                                } while (0);
                                if ($_599 === true) {
                                    $_601 = true;
                                    break;
                                }
                                $result = $res_582;
                                $this->pos = $pos_582;
                                $_601 = false;
                                break;
                            } while (0);
                            if ($_601 === true) {
                                $_603 = true;
                                break;
                            }
                            $result = $res_580;
                            $this->pos = $pos_580;
                            $_603 = false;
                            break;
                        } while (0);
                        if ($_603 === true) {
                            $_605 = true;
                            break;
                        }
                        $result = $res_578;
                        $this->pos = $pos_578;
                        $_605 = false;
                        break;
                    } while (0);
                    if ($_605 === false) {
                        $_607 = false;
                        break;
                    }
                    $_607 = true;
                    break;
                } while (0);
                if ($_607 === false) {
                    $_610 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_610 = false;
                    break;
                }
                $_610 = true;
                break;
            } while (0);
            if ($_610 === true) {
                $_612 = true;
                break;
            }
            $result = $res_576;
            $this->pos = $pos_576;
            $_612 = false;
            break;
        } while (0);
        if ($_612 === true) {
            return $this->finalise($result);
        }
        if ($_612 === false) {
            return false;
        }
    }


    /* ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher?
	'<%' < 'end_' '$BlockName' > '%>' */
    protected $match_ClosedBlock_typestack = array('ClosedBlock');
    function match_ClosedBlock($stack = array())
    {
        $matchrule = "ClosedBlock";
        $result = $this->construct($matchrule, $matchrule, null);
        $_632 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_632 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_616 = $result;
            $pos_616 = $this->pos;
            $matcher = 'match_'.'NotBlockTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $result = $res_616;
                $this->pos = $pos_616;
                $_632 = false;
                break;
            } else {
                $result = $res_616;
                $this->pos = $pos_616;
            }
            $matcher = 'match_'.'Word';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "BlockName");
            } else {
                $_632 = false;
                break;
            }
            $res_622 = $result;
            $pos_622 = $this->pos;
            $_621 = null;
            do {
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_621 = false;
                    break;
                }
                $matcher = 'match_'.'BlockArguments';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "BlockArguments");
                } else {
                    $_621 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_621 = false;
                    break;
                }
                $_621 = true;
                break;
            } while (0);
            if ($_621 === false) {
                $result = $res_622;
                $this->pos = $pos_622;
                unset($res_622);
                unset($pos_622);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $stack[] = $result;
            $result = $this->construct($matchrule, "Zap");
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
                $subres = $result;
                $result = array_pop($stack);
                $this->store($result, $subres, 'Zap');
            } else {
                $result = array_pop($stack);
                $_632 = false;
                break;
            }
            $res_625 = $result;
            $pos_625 = $this->pos;
            $matcher = 'match_'.$this->expression($result, $stack, 'TemplateMatcher');
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Template");
            } else {
                $result = $res_625;
                $this->pos = $pos_625;
                unset($res_625);
                unset($pos_625);
            }
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_632 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('end_') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_632 = false;
                break;
            }
            if (( $subres = $this->literal(''.$this->expression($result, $stack, 'BlockName').'') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_632 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_632 = false;
                break;
            }
            $_632 = true;
            break;
        } while (0);
        if ($_632 === true) {
            return $this->finalise($result);
        }
        if ($_632 === false) {
            return false;
        }
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
            $res['Arguments'] = array($sub['Argument']);
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
    function match_OpenBlock($stack = array())
    {
        $matchrule = "OpenBlock";
        $result = $this->construct($matchrule, $matchrule, null);
        $_645 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_645 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_636 = $result;
            $pos_636 = $this->pos;
            $matcher = 'match_'.'NotBlockTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $result = $res_636;
                $this->pos = $pos_636;
                $_645 = false;
                break;
            } else {
                $result = $res_636;
                $this->pos = $pos_636;
            }
            $matcher = 'match_'.'Word';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "BlockName");
            } else {
                $_645 = false;
                break;
            }
            $res_642 = $result;
            $pos_642 = $this->pos;
            $_641 = null;
            do {
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_641 = false;
                    break;
                }
                $matcher = 'match_'.'BlockArguments';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "BlockArguments");
                } else {
                    $_641 = false;
                    break;
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_641 = false;
                    break;
                }
                $_641 = true;
                break;
            } while (0);
            if ($_641 === false) {
                $result = $res_642;
                $this->pos = $pos_642;
                unset($res_642);
                unset($pos_642);
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_645 = false;
                break;
            }
            $_645 = true;
            break;
        } while (0);
        if ($_645 === true) {
            return $this->finalise($result);
        }
        if ($_645 === false) {
            return false;
        }
    }



    function OpenBlock__construct(&$res)
    {
        $res['ArgumentCount'] = 0;
    }

    function OpenBlock_BlockArguments(&$res, $sub)
    {
        if (isset($sub['Argument']['ArgumentMode'])) {
            $res['Arguments'] = array($sub['Argument']);
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
    function match_MismatchedEndBlock($stack = array())
    {
        $matchrule = "MismatchedEndBlock";
        $result = $this->construct($matchrule, $matchrule, null);
        $_653 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_653 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('end_') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_653 = false;
                break;
            }
            $matcher = 'match_'.'Word';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Word");
            } else {
                $_653 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            if (( $subres = $this->literal('%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_653 = false;
                break;
            }
            $_653 = true;
            break;
        } while (0);
        if ($_653 === true) {
            return $this->finalise($result);
        }
        if ($_653 === false) {
            return false;
        }
    }



    function MismatchedEndBlock__finalise(&$res)
    {
        $blockname = $res['Word']['text'];
        throw new SSTemplateParseException('Unexpected close tag end_' . $blockname .
            ' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
    }

    /* MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' ) */
    protected $match_MalformedOpenTag_typestack = array('MalformedOpenTag');
    function match_MalformedOpenTag($stack = array())
    {
        $matchrule = "MalformedOpenTag";
        $result = $this->construct($matchrule, $matchrule, null);
        $_668 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_668 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $res_657 = $result;
            $pos_657 = $this->pos;
            $matcher = 'match_'.'NotBlockTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $result = $res_657;
                $this->pos = $pos_657;
                $_668 = false;
                break;
            } else {
                $result = $res_657;
                $this->pos = $pos_657;
            }
            $matcher = 'match_'.'Word';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres, "Tag");
            } else {
                $_668 = false;
                break;
            }
            $res_667 = $result;
            $pos_667 = $this->pos;
            $_666 = null;
            do {
                $res_663 = $result;
                $pos_663 = $this->pos;
                $_662 = null;
                do {
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    } else {
                        $_662 = false;
                        break;
                    }
                    $matcher = 'match_'.'BlockArguments';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres, "BlockArguments");
                    } else {
                        $_662 = false;
                        break;
                    }
                    if (( $subres = $this->whitespace() ) !== false) {
                        $result["text"] .= $subres;
                    } else {
                        $_662 = false;
                        break;
                    }
                    $_662 = true;
                    break;
                } while (0);
                if ($_662 === false) {
                    $result = $res_663;
                    $this->pos = $pos_663;
                    unset($res_663);
                    unset($pos_663);
                }
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                if (( $subres = $this->literal('%>') ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_666 = false;
                    break;
                }
                $_666 = true;
                break;
            } while (0);
            if ($_666 === true) {
                $result = $res_667;
                $this->pos = $pos_667;
                $_668 = false;
                break;
            }
            if ($_666 === false) {
                $result = $res_667;
                $this->pos = $pos_667;
            }
            $_668 = true;
            break;
        } while (0);
        if ($_668 === true) {
            return $this->finalise($result);
        }
        if ($_668 === false) {
            return false;
        }
    }



    function MalformedOpenTag__finalise(&$res)
    {
        $tag = $res['Tag']['text'];
        throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
    }

    /* MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' ) */
    protected $match_MalformedCloseTag_typestack = array('MalformedCloseTag');
    function match_MalformedCloseTag($stack = array())
    {
        $matchrule = "MalformedCloseTag";
        $result = $this->construct($matchrule, $matchrule, null);
        $_680 = null;
        do {
            if (( $subres = $this->literal('<%') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_680 = false;
                break;
            }
            if (( $subres = $this->whitespace() ) !== false) {
                $result["text"] .= $subres;
            }
            $stack[] = $result;
            $result = $this->construct($matchrule, "Tag");
            $_674 = null;
            do {
                if (( $subres = $this->literal('end_') ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_674 = false;
                    break;
                }
                $matcher = 'match_'.'Word';
                $key = $matcher;
                $pos = $this->pos;
                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                if ($subres !== false) {
                    $this->store($result, $subres, "Word");
                } else {
                    $_674 = false;
                    break;
                }
                $_674 = true;
                break;
            } while (0);
            if ($_674 === true) {
                $subres = $result;
                $result = array_pop($stack);
                $this->store($result, $subres, 'Tag');
            }
            if ($_674 === false) {
                $result = array_pop($stack);
                $_680 = false;
                break;
            }
            $res_679 = $result;
            $pos_679 = $this->pos;
            $_678 = null;
            do {
                if (( $subres = $this->whitespace() ) !== false) {
                    $result["text"] .= $subres;
                }
                if (( $subres = $this->literal('%>') ) !== false) {
                    $result["text"] .= $subres;
                } else {
                    $_678 = false;
                    break;
                }
                $_678 = true;
                break;
            } while (0);
            if ($_678 === true) {
                $result = $res_679;
                $this->pos = $pos_679;
                $_680 = false;
                break;
            }
            if ($_678 === false) {
                $result = $res_679;
                $this->pos = $pos_679;
            }
            $_680 = true;
            break;
        } while (0);
        if ($_680 === true) {
            return $this->finalise($result);
        }
        if ($_680 === false) {
            return false;
        }
    }



    function MalformedCloseTag__finalise(&$res)
    {
        $tag = $res['Tag']['text'];
        throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an " .
            "argument to one?", $this);
    }

    /* MalformedBlock: MalformedOpenTag | MalformedCloseTag */
    protected $match_MalformedBlock_typestack = array('MalformedBlock');
    function match_MalformedBlock($stack = array())
    {
        $matchrule = "MalformedBlock";
        $result = $this->construct($matchrule, $matchrule, null);
        $_685 = null;
        do {
            $res_682 = $result;
            $pos_682 = $this->pos;
            $matcher = 'match_'.'MalformedOpenTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_685 = true;
                break;
            }
            $result = $res_682;
            $this->pos = $pos_682;
            $matcher = 'match_'.'MalformedCloseTag';
            $key = $matcher;
            $pos = $this->pos;
            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
            if ($subres !== false) {
                $this->store($result, $subres);
                $_685 = true;
                break;
            }
            $result = $res_682;
            $this->pos = $pos_682;
            $_685 = false;
            break;
        } while (0);
        if ($_685 === true) {
            return $this->finalise($result);
        }
        if ($_685 === false) {
            return false;
        }
    }




    /* Comment: "<%--" (!"--%>" /(?s)./)+ "--%>" */
    protected $match_Comment_typestack = array('Comment');
    function match_Comment($stack = array())
    {
        $matchrule = "Comment";
        $result = $this->construct($matchrule, $matchrule, null);
        $_693 = null;
        do {
            if (( $subres = $this->literal('<%--') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_693 = false;
                break;
            }
            $count = 0;
            while (true) {
                $res_691 = $result;
                $pos_691 = $this->pos;
                $_690 = null;
                do {
                    $res_688 = $result;
                    $pos_688 = $this->pos;
                    if (( $subres = $this->literal('--%>') ) !== false) {
                        $result["text"] .= $subres;
                        $result = $res_688;
                        $this->pos = $pos_688;
                        $_690 = false;
                        break;
                    } else {
                        $result = $res_688;
                        $this->pos = $pos_688;
                    }
                    if (( $subres = $this->rx('/(?s)./') ) !== false) {
                        $result["text"] .= $subres;
                    } else {
                        $_690 = false;
                        break;
                    }
                    $_690 = true;
                    break;
                } while (0);
                if ($_690 === false) {
                    $result = $res_691;
                    $this->pos = $pos_691;
                    unset($res_691);
                    unset($pos_691);
                    break;
                }
                $count += 1;
            }
            if ($count > 0) {
            } else {
                $_693 = false;
                break;
            }
            if (( $subres = $this->literal('--%>') ) !== false) {
                $result["text"] .= $subres;
            } else {
                $_693 = false;
                break;
            }
            $_693 = true;
            break;
        } while (0);
        if ($_693 === true) {
            return $this->finalise($result);
        }
        if ($_693 === false) {
            return false;
        }
    }



    function Comment__construct(&$res)
    {
        $res['php'] = '';
    }

    /* TopTemplate: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
	OpenBlock |  MalformedBlock | MismatchedEndBlock  | Injection | Text)+ */
    protected $match_TopTemplate_typestack = array('TopTemplate','Template');
    function match_TopTemplate($stack = array())
    {
        $matchrule = "TopTemplate";
        $result = $this->construct($matchrule, $matchrule, array('TemplateMatcher' => 'Template'));
        $count = 0;
        while (true) {
            $res_749 = $result;
            $pos_749 = $this->pos;
            $_748 = null;
            do {
                $_746 = null;
                do {
                    $res_695 = $result;
                    $pos_695 = $this->pos;
                    $matcher = 'match_'.'Comment';
                    $key = $matcher;
                    $pos = $this->pos;
                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                    if ($subres !== false) {
                        $this->store($result, $subres);
                        $_746 = true;
                        break;
                    }
                    $result = $res_695;
                    $this->pos = $pos_695;
                    $_744 = null;
                    do {
                        $res_697 = $result;
                        $pos_697 = $this->pos;
                        $matcher = 'match_'.'Translate';
                        $key = $matcher;
                        $pos = $this->pos;
                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                        if ($subres !== false) {
                            $this->store($result, $subres);
                            $_744 = true;
                            break;
                        }
                        $result = $res_697;
                        $this->pos = $pos_697;
                        $_742 = null;
                        do {
                            $res_699 = $result;
                            $pos_699 = $this->pos;
                            $matcher = 'match_'.'If';
                            $key = $matcher;
                            $pos = $this->pos;
                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                            if ($subres !== false) {
                                $this->store($result, $subres);
                                $_742 = true;
                                break;
                            }
                            $result = $res_699;
                            $this->pos = $pos_699;
                            $_740 = null;
                            do {
                                $res_701 = $result;
                                $pos_701 = $this->pos;
                                $matcher = 'match_'.'Require';
                                $key = $matcher;
                                $pos = $this->pos;
                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                if ($subres !== false) {
                                    $this->store($result, $subres);
                                    $_740 = true;
                                    break;
                                }
                                $result = $res_701;
                                $this->pos = $pos_701;
                                $_738 = null;
                                do {
                                    $res_703 = $result;
                                    $pos_703 = $this->pos;
                                    $matcher = 'match_'.'CacheBlock';
                                    $key = $matcher;
                                    $pos = $this->pos;
                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                    if ($subres !== false) {
                                        $this->store($result, $subres);
                                        $_738 = true;
                                        break;
                                    }
                                    $result = $res_703;
                                    $this->pos = $pos_703;
                                    $_736 = null;
                                    do {
                                        $res_705 = $result;
                                        $pos_705 = $this->pos;
                                        $matcher = 'match_'.'UncachedBlock';
                                        $key = $matcher;
                                        $pos = $this->pos;
                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                        if ($subres !== false) {
                                            $this->store($result, $subres);
                                            $_736 = true;
                                            break;
                                        }
                                        $result = $res_705;
                                        $this->pos = $pos_705;
                                        $_734 = null;
                                        do {
                                            $res_707 = $result;
                                            $pos_707 = $this->pos;
                                            $matcher = 'match_'.'OldI18NTag';
                                            $key = $matcher;
                                            $pos = $this->pos;
                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                            if ($subres !== false) {
                                                $this->store($result, $subres);
                                                $_734 = true;
                                                break;
                                            }
                                            $result = $res_707;
                                            $this->pos = $pos_707;
                                            $_732 = null;
                                            do {
                                                $res_709 = $result;
                                                $pos_709 = $this->pos;
                                                $matcher = 'match_'.'Include';
                                                $key = $matcher;
                                                $pos = $this->pos;
                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                if ($subres !== false) {
                                                    $this->store($result, $subres);
                                                    $_732 = true;
                                                    break;
                                                }
                                                $result = $res_709;
                                                $this->pos = $pos_709;
                                                $_730 = null;
                                                do {
                                                    $res_711 = $result;
                                                    $pos_711 = $this->pos;
                                                    $matcher = 'match_'.'ClosedBlock';
                                                    $key = $matcher;
                                                    $pos = $this->pos;
                                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                    if ($subres !== false) {
                                                        $this->store($result, $subres);
                                                        $_730 = true;
                                                        break;
                                                    }
                                                    $result = $res_711;
                                                    $this->pos = $pos_711;
                                                    $_728 = null;
                                                    do {
                                                        $res_713 = $result;
                                                        $pos_713 = $this->pos;
                                                        $matcher = 'match_'.'OpenBlock';
                                                        $key = $matcher;
                                                        $pos = $this->pos;
                                                        $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                        if ($subres !== false) {
                                                            $this->store($result, $subres);
                                                            $_728 = true;
                                                            break;
                                                        }
                                                        $result = $res_713;
                                                        $this->pos = $pos_713;
                                                        $_726 = null;
                                                        do {
                                                            $res_715 = $result;
                                                            $pos_715 = $this->pos;
                                                            $matcher = 'match_'.'MalformedBlock';
                                                            $key = $matcher;
                                                            $pos = $this->pos;
                                                            $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                            if ($subres !== false) {
                                                                $this->store($result, $subres);
                                                                $_726 = true;
                                                                break;
                                                            }
                                                            $result = $res_715;
                                                            $this->pos = $pos_715;
                                                            $_724 = null;
                                                            do {
                                                                $res_717 = $result;
                                                                $pos_717 = $this->pos;
                                                                $matcher = 'match_'.'MismatchedEndBlock';
                                                                $key = $matcher;
                                                                $pos = $this->pos;
                                                                $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                if ($subres !== false) {
                                                                    $this->store($result, $subres);
                                                                    $_724 = true;
                                                                    break;
                                                                }
                                                                $result = $res_717;
                                                                $this->pos = $pos_717;
                                                                $_722 = null;
                                                                do {
                                                                    $res_719 = $result;
                                                                    $pos_719 = $this->pos;
                                                                    $matcher = 'match_'.'Injection';
                                                                    $key = $matcher;
                                                                    $pos = $this->pos;
                                                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                    if ($subres !== false) {
                                                                        $this->store($result, $subres);
                                                                        $_722 = true;
                                                                        break;
                                                                    }
                                                                    $result = $res_719;
                                                                    $this->pos = $pos_719;
                                                                    $matcher = 'match_'.'Text';
                                                                    $key = $matcher;
                                                                    $pos = $this->pos;
                                                                    $subres = ( $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher(array_merge($stack, array($result)))) );
                                                                    if ($subres !== false) {
                                                                        $this->store($result, $subres);
                                                                        $_722 = true;
                                                                        break;
                                                                    }
                                                                    $result = $res_719;
                                                                    $this->pos = $pos_719;
                                                                    $_722 = false;
                                                                    break;
                                                                } while (0);
                                                                if ($_722 === true) {
                                                                    $_724 = true;
                                                                    break;
                                                                }
                                                                $result = $res_717;
                                                                $this->pos = $pos_717;
                                                                $_724 = false;
                                                                break;
                                                            } while (0);
                                                            if ($_724 === true) {
                                                                $_726 = true;
                                                                break;
                                                            }
                                                            $result = $res_715;
                                                            $this->pos = $pos_715;
                                                            $_726 = false;
                                                            break;
                                                        } while (0);
                                                        if ($_726 === true) {
                                                            $_728 = true;
                                                            break;
                                                        }
                                                        $result = $res_713;
                                                        $this->pos = $pos_713;
                                                        $_728 = false;
                                                        break;
                                                    } while (0);
                                                    if ($_728 === true) {
                                                        $_730 = true;
                                                        break;
                                                    }
                                                    $result = $res_711;
                                                    $this->pos = $pos_711;
                                                    $_730 = false;
                                                    break;
                                                } while (0);
                                                if ($_730 === true) {
                                                    $_732 = true;
                                                    break;
                                                }
                                                $result = $res_709;
                                                $this->pos = $pos_709;
                                                $_732 = false;
                                                break;
                                            } while (0);
                                            if ($_732 === true) {
                                                $_734 = true;
                                                break;
                                            }
                                            $result = $res_707;
                                            $this->pos = $pos_707;
                                            $_734 = false;
                                            break;
                                        } while (0);
                                        if ($_734 === true) {
                                            $_736 = true;
                                            break;
                                        }
                                        $result = $res_705;
                                        $this->pos = $pos_705;
                                        $_736 = false;
                                        break;
                                    } while (0);
                                    if ($_736 === true) {
                                        $_738 = true;
                                        break;
                                    }
                                    $result = $res_703;
                                    $this->pos = $pos_703;
                                    $_738 = false;
                                    break;
                                } while (0);
                                if ($_738 === true) {
                                    $_740 = true;
                                    break;
                                }
                                $result = $res_701;
                                $this->pos = $pos_701;
                                $_740 = false;
                                break;
                            } while (0);
                            if ($_740 === true) {
                                $_742 = true;
                                break;
                            }
                            $result = $res_699;
                            $this->pos = $pos_699;
                            $_742 = false;
                            break;
                        } while (0);
                        if ($_742 === true) {
                            $_744 = true;
                            break;
                        }
                        $result = $res_697;
                        $this->pos = $pos_697;
                        $_744 = false;
                        break;
                    } while (0);
                    if ($_744 === true) {
                        $_746 = true;
                        break;
                    }
                    $result = $res_695;
                    $this->pos = $pos_695;
                    $_746 = false;
                    break;
                } while (0);
                if ($_746 === false) {
                    $_748 = false;
                    break;
                }
                $_748 = true;
                break;
            } while (0);
            if ($_748 === false) {
                $result = $res_749;
                $this->pos = $pos_749;
                unset($res_749);
                unset($pos_749);
                break;
            }
            $count += 1;
        }
        if ($count > 0) {
            return $this->finalise($result);
        } else {
            return false;
        }
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
    function match_Text($stack = array())
    {
        $matchrule = "Text";
        $result = $this->construct($matchrule, $matchrule, null);
        $count = 0;
        while (true) {
            $res_788 = $result;
            $pos_788 = $this->pos;
            $_787 = null;
            do {
                $_785 = null;
                do {
                    $res_750 = $result;
                    $pos_750 = $this->pos;
                    if (( $subres = $this->rx('/ [^<${\\\\]+ /') ) !== false) {
                        $result["text"] .= $subres;
                        $_785 = true;
                        break;
                    }
                    $result = $res_750;
                    $this->pos = $pos_750;
                    $_783 = null;
                    do {
                        $res_752 = $result;
                        $pos_752 = $this->pos;
                        if (( $subres = $this->rx('/ (\\\\.) /') ) !== false) {
                            $result["text"] .= $subres;
                            $_783 = true;
                            break;
                        }
                        $result = $res_752;
                        $this->pos = $pos_752;
                        $_781 = null;
                        do {
                            $res_754 = $result;
                            $pos_754 = $this->pos;
                            $_757 = null;
                            do {
                                if (substr($this->string, $this->pos, 1) == '<') {
                                    $this->pos += 1;
                                    $result["text"] .= '<';
                                } else {
                                    $_757 = false;
                                    break;
                                }
                                $res_756 = $result;
                                $pos_756 = $this->pos;
                                if (substr($this->string, $this->pos, 1) == '%') {
                                    $this->pos += 1;
                                    $result["text"] .= '%';
                                    $result = $res_756;
                                    $this->pos = $pos_756;
                                    $_757 = false;
                                    break;
                                } else {
                                    $result = $res_756;
                                    $this->pos = $pos_756;
                                }
                                $_757 = true;
                                break;
                            } while (0);
                            if ($_757 === true) {
                                $_781 = true;
                                break;
                            }
                            $result = $res_754;
                            $this->pos = $pos_754;
                            $_779 = null;
                            do {
                                $res_759 = $result;
                                $pos_759 = $this->pos;
                                $_764 = null;
                                do {
                                    if (substr($this->string, $this->pos, 1) == '$') {
                                        $this->pos += 1;
                                        $result["text"] .= '$';
                                    } else {
                                        $_764 = false;
                                        break;
                                    }
                                    $res_763 = $result;
                                    $pos_763 = $this->pos;
                                    $_762 = null;
                                    do {
                                        if (( $subres = $this->rx('/[A-Za-z_]/') ) !== false) {
                                            $result["text"] .= $subres;
                                        } else {
                                            $_762 = false;
                                            break;
                                        }
                                        $_762 = true;
                                        break;
                                    } while (0);
                                    if ($_762 === true) {
                                        $result = $res_763;
                                        $this->pos = $pos_763;
                                        $_764 = false;
                                        break;
                                    }
                                    if ($_762 === false) {
                                        $result = $res_763;
                                        $this->pos = $pos_763;
                                    }
                                    $_764 = true;
                                    break;
                                } while (0);
                                if ($_764 === true) {
                                    $_779 = true;
                                    break;
                                }
                                $result = $res_759;
                                $this->pos = $pos_759;
                                $_777 = null;
                                do {
                                    $res_766 = $result;
                                    $pos_766 = $this->pos;
                                    $_769 = null;
                                    do {
                                        if (substr($this->string, $this->pos, 1) == '{') {
                                            $this->pos += 1;
                                            $result["text"] .= '{';
                                        } else {
                                            $_769 = false;
                                            break;
                                        }
                                        $res_768 = $result;
                                        $pos_768 = $this->pos;
                                        if (substr($this->string, $this->pos, 1) == '$') {
                                            $this->pos += 1;
                                            $result["text"] .= '$';
                                            $result = $res_768;
                                            $this->pos = $pos_768;
                                            $_769 = false;
                                            break;
                                        } else {
                                            $result = $res_768;
                                            $this->pos = $pos_768;
                                        }
                                        $_769 = true;
                                        break;
                                    } while (0);
                                    if ($_769 === true) {
                                        $_777 = true;
                                        break;
                                    }
                                    $result = $res_766;
                                    $this->pos = $pos_766;
                                    $_775 = null;
                                    do {
                                        if (( $subres = $this->literal('{$') ) !== false) {
                                            $result["text"] .= $subres;
                                        } else {
                                            $_775 = false;
                                            break;
                                        }
                                        $res_774 = $result;
                                        $pos_774 = $this->pos;
                                        $_773 = null;
                                        do {
                                            if (( $subres = $this->rx('/[A-Za-z_]/') ) !== false) {
                                                $result["text"] .= $subres;
                                            } else {
                                                $_773 = false;
                                                break;
                                            }
                                            $_773 = true;
                                            break;
                                        } while (0);
                                        if ($_773 === true) {
                                            $result = $res_774;
                                            $this->pos = $pos_774;
                                            $_775 = false;
                                            break;
                                        }
                                        if ($_773 === false) {
                                            $result = $res_774;
                                            $this->pos = $pos_774;
                                        }
                                        $_775 = true;
                                        break;
                                    } while (0);
                                    if ($_775 === true) {
                                        $_777 = true;
                                        break;
                                    }
                                    $result = $res_766;
                                    $this->pos = $pos_766;
                                    $_777 = false;
                                    break;
                                } while (0);
                                if ($_777 === true) {
                                    $_779 = true;
                                    break;
                                }
                                $result = $res_759;
                                $this->pos = $pos_759;
                                $_779 = false;
                                break;
                            } while (0);
                            if ($_779 === true) {
                                $_781 = true;
                                break;
                            }
                            $result = $res_754;
                            $this->pos = $pos_754;
                            $_781 = false;
                            break;
                        } while (0);
                        if ($_781 === true) {
                            $_783 = true;
                            break;
                        }
                        $result = $res_752;
                        $this->pos = $pos_752;
                        $_783 = false;
                        break;
                    } while (0);
                    if ($_783 === true) {
                        $_785 = true;
                        break;
                    }
                    $result = $res_750;
                    $this->pos = $pos_750;
                    $_785 = false;
                    break;
                } while (0);
                if ($_785 === false) {
                    $_787 = false;
                    break;
                }
                $_787 = true;
                break;
            } while (0);
            if ($_787 === false) {
                $result = $res_788;
                $this->pos = $pos_788;
                unset($res_788);
                unset($pos_788);
                break;
            }
            $count += 1;
        }
        if ($count > 0) {
            return $this->finalise($result);
        } else {
            return false;
        }
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
(\SilverStripe\View\SSViewer::config()->get('rewrite_hash_links')
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

            // Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF
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
