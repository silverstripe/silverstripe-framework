<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Stijn de Reede <sjr@gmx.co.uk>                               |
// +----------------------------------------------------------------------+
//
// $Id: Email.php,v 1.5 2007/07/02 16:54:25 cweiske Exp $
//

/**
 * @package framework
 * @subpackage misc
 * @author   Stijn de Reede  <sjr@gmx.co.uk>
 */


/**
 */
require_once 'HTML/BBCodeParser/Filter.php';




/**
 * @package framework
 * @subpackage misc
 */
class SSHTMLBBCodeParser_Filter_EmailLinks extends SSHTMLBBCodeParser_Filter
{

    /**
    * An array of tags parsed by the engine
    *
    * @access   private
    * @var      array
    */
    var $_definedTags = array(  'email' => array(   'htmlopen'  => 'a',
                                                    'htmlclose' => 'a',
                                                    'allowed'   => 'none^img',
                                                    'attributes'=> array('email' =>'href=%2$smailto:%1$s%2$s')

                                               )
                              );


    /**
    * Executes statements before the actual array building starts
    *
    * This method should be overwritten in a filter if you want to do
    * something before the parsing process starts. This can be useful to
    * allow certain short alternative tags which then can be converted into
    * proper tags with preg_replace() calls.
    * The main class walks through all the filters and and calls this
    * method if it exists. The filters should modify their private $_text
    * variable.
    *
    * @return   none
    * @access   private
    * @see      $_text
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function _preparse()
    {
        $options = SSHTMLBBCodeParser::getStaticProperty('SSHTMLBBCodeParser','_options');
        $o = $options['open'];
        $c = $options['close'];
        $oe = $options['open_esc'];
        $ce = $options['close_esc'];
        $pattern = array(   "!(^|\s)([-a-z0-9_.]+@[-a-z0-9.]+\.[a-z]{2,4})!i",
                            "!".$oe."email(".$ce."|\s.*".$ce.")(.*)".$oe."/email".$ce."!Ui");
        $replace = array(   "\\1".$o."email=\\2".$c."\\2".$o."/email".$c,
                            $o."email=\\2\\1\\2".$o."/email".$c);
        $this->_preparsed = preg_replace($pattern, $replace, $this->_text);
    }


}



