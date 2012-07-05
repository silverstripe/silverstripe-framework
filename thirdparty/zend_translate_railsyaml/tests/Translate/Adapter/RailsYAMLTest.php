<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Translate_Adapter_RailsYAMLTest::main');
}

/**
 * Translate_Adapter_RailsYAML
 */
require_once dirname(__FILE__) . '/../../../library/Translate/Adapter/RailsYAML.php';

/**
 * @category   Zend
 * @package    Zend_Translate
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Translate
 */
class Translate_Adapter_RailsYAMLTest extends PHPUnit_Framework_TestCase
{
    /**
     * Error flag
     *
     * @var boolean
     */
    protected $_errorOccurred = false;

    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Translate_Adapter_RailsYAMLTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function setUp()
    {
        if (Translate_Adapter_RailsYAML::hasCache()) {
            Translate_Adapter_RailsYAML::clearCache();
            Translate_Adapter_RailsYAML::removeCache();
        }
    }

    public function tearDown()
    {
        if (Translate_Adapter_RailsYAML::hasCache()) {
            Translate_Adapter_RailsYAML::clearCache();
            Translate_Adapter_RailsYAML::removeCache();
        }
    }

    public function testCreate()
    {
        try {
            $adapter = new Translate_Adapter_RailsYAML('hastofail', 'en');
            $this->fail('Exception expected');
        } catch (Zend_Translate_Exception $e) {
            $this->assertContains('Error opening translation file', $e->getMessage());
        }

    }

    public function testToString()
    {
        $adapter = $this->getDefaultAdapter();
        $this->assertEquals('RailsYaml', $adapter->toString());
    }

    public function testTranslate()
    {
        $adapter = $this->getDefaultAdapter();
        $this->assertEquals('Message1 (en)', $adapter->translate('Message1'),
            'Message without namespace through translate()'
        );
        $this->assertEquals('Message1 (en)', $adapter->_('Message1'),
            'Message without namespace through _()'
        );
        $this->assertEquals('Namespace1 Message1 (en)', $adapter->translate('Namespace1.Message1'),
            'Message with namespace'
        );
    }

    public function testIsTranslated()
    {
        $adapter = $this->getDefaultAdapter();
        $this->assertTrue($adapter->isTranslated('Message1'));
        $this->assertFalse($adapter->isTranslated('NonExistent'));
        $this->assertTrue($adapter->isTranslated('Message1', true));
        $this->assertFalse($adapter->isTranslated('Message1', true, 'en_US'));
        $this->assertTrue($adapter->isTranslated('Message1', false, 'en_US'));
        $this->assertFalse($adapter->isTranslated('Message1', false, 'es'));
        $this->assertFalse($adapter->isTranslated('Message1', 'es'));
        $this->assertFalse($adapter->isTranslated('Message1', 'xx_XX'));
        $this->assertTrue($adapter->isTranslated('Message1', 'en_XX'));
    }

    public function testLoadTranslationData()
    {
        $adapter = $this->getDefaultAdapter();
        $this->assertEquals('Message1 (en)', $adapter->translate('Message1'));
        $this->assertEquals('Message2', $adapter->translate('Message2', 'ru'));
        $this->assertEquals('Message1', $adapter->translate('Message1', 'xx'));
        $this->assertEquals('Message1 (en)', $adapter->translate('Message1', 'en_US'));

        try {
            $adapter->addTranslation(dirname(__FILE__) . '/_files/translation_en.yml', 'xx');
            $this->fail("exception expected");
        } catch (Zend_Translate_Exception $e) {
            $this->assertContains('does not exist', $e->getMessage());
        }

        $adapter->addTranslation(dirname(__FILE__) . '/_files/translation_de.yml', 'de', array('clear' => true));
        $this->assertEquals('Message1 (de)', $adapter->translate('Message1'));
    }

    public function testOptions()
    {
        $adapter = $this->getDefaultAdapter();
        $adapter->setOptions(array('testoption' => 'testkey'));
        $expected = array(
            'testoption'      => 'testkey',
            'clear'           => false,
            'content'         => dirname(__FILE__) . '/_files/translation_en.yml',
            'scan'            => null,
            'locale'          => 'en',
            'ignore'          => '.',
            // 'disableNotices'  => false,
            'log'             => false,
            'logMessage'      => 'Untranslated message within \'%locale%\': %message%',
            'logUntranslated' => false,
            'reload'          => false,
        );

        $options = $adapter->getOptions();

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $options);
            $this->assertEquals($value, $options[$key], $key);
        }

        $this->assertEquals('testkey', $adapter->getOptions('testoption'));
        $this->assertTrue(is_null($adapter->getOptions('nooption')));
    }

    public function testClearing()
    {
        $adapter = $this->getDefaultAdapter();
        $this->assertEquals('Message1 (en)', $adapter->translate('Message1'));
        $adapter->addTranslation(dirname(__FILE__) . '/_files/translation_de.yml', 'de', array('clear' => true));
        $this->assertEquals('Message1 (de)', $adapter->translate('Message1'));
        $this->assertEquals('Message4', $adapter->translate('Message4'));
    }

    public function testCaching()
    {
        require_once 'Zend/Cache.php';
        $cache = Zend_Cache::factory('Core', 'File',
            array('lifetime' => 120, 'automatic_serialization' => true),
            array('cache_dir' => dirname(__FILE__) . '/_files/'));

        $this->assertFalse(Translate_Adapter_RailsYAML::hasCache());
        Translate_Adapter_RailsYAML::setCache($cache);
        $this->assertTrue(Translate_Adapter_RailsYAML::hasCache());

        $adapter = $this->getDefaultAdapter();
        $cache   = Translate_Adapter_RailsYAML::getCache();
        $this->assertTrue($cache instanceof Zend_Cache_Core);
        unset ($adapter);

        $adapter = $this->getDefaultAdapter();
        $cache   = Translate_Adapter_RailsYAML::getCache();
        $this->assertTrue($cache instanceof Zend_Cache_Core);

        Translate_Adapter_RailsYAML::removeCache();
        $this->assertFalse(Translate_Adapter_RailsYAML::hasCache());

        $cache->save('testdata', 'testid');
        Translate_Adapter_RailsYAML::setCache($cache);
        $adapter = $this->getDefaultAdapter();
        Translate_Adapter_RailsYAML::removeCache();
        $temp = $cache->load('testid');
        $this->assertEquals('testdata', $temp);
    }

    public function testLoadingFilesIntoCacheAfterwards()
    {
        require_once 'Zend/Cache.php';
        $cache = Zend_Cache::factory('Core', 'File',
            array('lifetime' => 120, 'automatic_serialization' => true),
            array('cache_dir' => dirname(__FILE__) . '/_files/'));

        $this->assertFalse(Translate_Adapter_RailsYAML::hasCache());
        Translate_Adapter_RailsYAML::setCache($cache);
        $this->assertTrue(Translate_Adapter_RailsYAML::hasCache());

        $adapter = $this->getDefaultAdapter();
        $cache   = Translate_Adapter_RailsYAML::getCache();
        $this->assertTrue($cache instanceof Zend_Cache_Core);

        $adapter->addTranslation(dirname(__FILE__) . '/_files/translation_de.yml', 'de', array('reload' => true));
        $test = $adapter->getMessages('all');
        $this->assertEquals(4, count($test['de']));
    }

    /**
     * Ignores a raised PHP error when in effect, but throws a flag to indicate an error occurred
     *
     * @param  integer $errno
     * @param  string  $errstr
     * @param  string  $errfile
     * @param  integer $errline
     * @param  array   $errcontext
     * @return void
     */
    public function errorHandlerIgnore($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        $this->_errorOccurred = true;
    }
    
    /**
     * @return Translate_Adapter_RailsYAML
     */
    protected function getDefaultAdapter() 
    {
        return new Translate_Adapter_RailsYAML(
            dirname(__FILE__) . '/_files/translation_en.yml', 
            'en',
            array('disableNotices' => true)
        );
    }
}

// Call Translate_Adapter_RailsYAMLTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Translate_Adapter_RailsYAMLTest::main") {
    Translate_Adapter_RailsYAMLTest::main();
}
