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
 * @category   Zend
 * @package    Zend
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: TestHelper.php 23772 2011-02-28 21:35:29Z ralph $
 */

/**
 * Include PHPUnit dependencies
 */
require_once 'PHPUnit/Runner/Version.php';

$phpunitVersion = PHPUnit_Runner_Version::id();
if ($phpunitVersion == '@package_version@' || version_compare($phpunitVersion, '3.5.5', '>=')) {
    if (version_compare($phpunitVersion, '3.6.0', '>=')) {
        echo 'This verison of PHPUnit is not supported in Zend Framework 1.x unit tests.';
        exit(1);
    }
    require_once 'PHPUnit/Autoload.php'; // >= PHPUnit 3.5.5
} else {
    require_once 'PHPUnit/Framework.php'; // < PHPUnit 3.5.5
}

/*
 * Set error reporting to the level to which Zend Framework code must comply.
 */
error_reporting(E_ALL | E_STRICT);

/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$ds = DIRECTORY_SEPARATOR;
$zfRoot        = realpath(dirname(dirname(dirname(__FILE__)))) . $ds . 'framework' . $ds . 'thirdparty';
$zfCoreLibrary = "$zfRoot";
$zfCoreTests   = "$zfRoot/tests";
$zfCustomLibrary = realpath(dirname(dirname(__FILE__))) . $ds . 'library';

/*
 * Prepend the Zend Framework library/ and tests/ directories to the
 * include_path. This allows the tests to run out of the box and helps prevent
 * loading other copies of the framework code and tests that would supersede
 * this copy.
 */
$path = array(
    $zfCoreLibrary,
    $zfCustomLibrary,
    $zfCoreTests,
    get_include_path()
    );
set_include_path(implode(PATH_SEPARATOR, $path));

// /*
//  * Load the user-defined test configuration file, if it exists; otherwise, load
//  * the default configuration.
//  */
// if (is_readable($zfCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php')) {
//     require_once $zfCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php';
// } else {
//     require_once $zfCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php.dist';
// }

/**
 * Start output buffering, if enabled
 */
if (defined('TESTS_ZEND_OB_ENABLED') && constant('TESTS_ZEND_OB_ENABLED')) {
    ob_start();
}

/*
 * Unset global variables that are no longer needed.
 */
unset($zfRoot, $zfCoreLibrary, $zfCoreTests, $path);
