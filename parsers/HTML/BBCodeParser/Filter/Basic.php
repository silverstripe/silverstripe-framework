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
// $Id: Basic.php,v 1.6 2007/07/02 16:54:25 cweiske Exp $
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
class SSHTMLBBCodeParser_Filter_Basic extends SSHTMLBBCodeParser_Filter
{

    /**
    * An array of tags parsed by the engine
    *
    * @access   private
    * @var      array
    */
    var $_definedTags = array(  'b' => array(   'htmlopen'  => 'strong',
                                                'htmlclose' => 'strong',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'i' => array(   'htmlopen'  => 'em',
                                                'htmlclose' => 'em',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'u' => array(   'htmlopen'  => 'span style="text-decoration:underline;"',
                                                'htmlclose' => 'span',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                's' => array(   'htmlopen'  => 'del',
                                                'htmlclose' => 'del',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'sub' => array( 'htmlopen'  => 'sub',
                                                'htmlclose' => 'sub',
                                                'allowed'   => 'all',
                                                'attributes'=> array()),
                                'sup' => array( 'htmlopen'  => 'sup',
                                                'htmlclose' => 'sup',
                                                'allowed'   => 'all',
                                                'attributes'=> array())
                            );

}


