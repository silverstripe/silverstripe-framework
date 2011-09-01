<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category  Zend
 * @package   Zend_Currency
 * @copyright Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 * @version   $Id: CurrencyInterface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Exception class for Zend_Currency
 *
 * @category  Zend
 * @package   Zend_Currency
 * @copyright Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Currency_CurrencyInterface
{
    /**
     * Returns the actual exchange rate
     *
     * @param string $from Short Name of the base currency
     * @param string $to   Short Name of the currency to exchange to
     */
    public function getRate($from, $to);
}
