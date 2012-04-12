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
// $Id: Extended.php,v 1.3 2007/07/02 16:54:25 cweiske Exp $
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
class SSHTMLBBCodeParser_Filter_Extended extends SSHTMLBBCodeParser_Filter
{

    /**
    * An array of tags parsed by the engine
    *
    * @access   private
    * @var      array
    */
    var $_definedTags = array(
                                'color' => array( 'htmlopen'  => 'span',
                                                'htmlclose' => 'span',
                                                'allowed'   => 'all',
                                                'attributes'=> array('color' =>'style=%2$scolor:%1$s%2$s')),
                                'size' => array( 'htmlopen'  => 'span',
                                                'htmlclose' => 'span',
                                                'allowed'   => 'all',
                                                'attributes'=> array('size' =>'style=%2$sfont-size:%1$spt%2$s')),
                                'font' => array( 'htmlopen'  => 'span',
                                                'htmlclose' => 'span',
                                                'allowed'   => 'all',
                                                'attributes'=> array('font' =>'style=%2$sfont-family:%1$s%2$s')),
                                'align' => array( 'htmlopen'  => 'div',
                                                'htmlclose' => 'div',
                                                'allowed'   => 'all',
                                                'attributes'=> array('align' =>'style=%2$stext-align:%1$s%2$s')),
                                'quote' => array('htmlopen'  => 'q',
                                                'htmlclose' => 'q',
                                                'allowed'   => 'all',
                                                'attributes'=> array('quote' =>'cite=%2$s%1$s%2$s')),
                                'code' => array('htmlopen'  => 'div class="codesnippet"><p',
                                                'htmlclose' => 'p></div',
                                                'allowed'   => 'all',
                                                'attributes' => array()),
                                'php' => array('htmlopen'  => 'div class="codesnippet"><p',
                                                'htmlclose' => 'p></div',
                                                'allowed'   => 'all',
                                                'attributes' => array()),
                                'h1' => array('htmlopen'  => 'h1',
                                                'htmlclose' => 'h1',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'h2' => array('htmlopen'  => 'h2',
                                                'htmlclose' => 'h2',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'h3' => array('htmlopen'  => 'h3',
                                                'htmlclose' => 'h3',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'h4' => array('htmlopen'  => 'h4',
                                                'htmlclose' => 'h4',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'h5' => array('htmlopen'  => 'h5',
                                                'htmlclose' => 'h5',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'h6' => array('htmlopen'  => 'h6',
                                                'htmlclose' => 'h6',
                                                'allowed'   => 'all',
                                                'attributes'=> array())

    );

	function _preparse() {
        $this->_preparsed = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $this->_text);
	$this->_preparsed = preg_replace("/(\[php\])\s*/", '$1', $this->_preparsed);
	$this->_preparsed = preg_replace("/\s*(\[\/php\])\s/", '$1', $this->_preparsed);
	}
}


