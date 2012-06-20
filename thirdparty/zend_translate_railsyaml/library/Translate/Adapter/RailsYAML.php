<?php

/** Zend_Locale */
require_once 'Zend/Locale.php';

/** Zend_Translate_Adapter */
require_once 'Zend/Translate/Adapter.php';

// ischommer CUSTOM Check required because SS core also includes the lib, from a different location
if(!class_exists('sfYaml')) require_once 'thirdparty/sfYaml/lib/sfYaml.php';
if(!class_exists('sfYamlParser')) require_once 'thirdparty/sfYaml/lib/sfYamlParser.php';
if(!class_exists('sfYamlDumper')) require_once 'thirdparty/sfYaml/lib/sfYamlDumper.php';
// ischommer END_CUSTOM

class Translate_Adapter_RailsYaml extends Zend_Translate_Adapter {

    /**
     * Generates the adapter
     *
     * @param  array|Zend_Config $options Translation content
     */
    public function __construct($options = array()) {
        $this->_options['keyDelimiter'] = ".";
        
        parent::__construct($options);
    }

    /**
     * Load translation data
     *
     * @param  string|array  $data  Filename and full path to the translation source
     * @param  string        $locale    Locale/Language to add data for, identical with locale identifier,
     *                                  see Zend_Locale for more information
     * @param  array         $option    OPTIONAL Options to use
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {   
        $options = array_merge($this->_options, $options);

        if ($options['clear']  ||  !isset($this->_translate[$locale])) {
            $this->_translate[$locale] = array();
        }

        if(is_array($data)) return array($locale => $data);

        $this->_filename = $data;
        if (!is_readable($this->_filename)) {
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception('Error opening translation file \'' . $this->_filename . '\'.');
        }

        $content = sfYaml::load(file_get_contents($this->_filename));
        if($locale != 'auto' && $content && !array_key_exists($locale, $content)) {
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception(sprintf('Locale "%s" not found in file %s', $locale, $this->_filename));
        }

        // Rails YML files supported arbitrarily nested keys, Zend_Translate doesn't - so we flatten them.
        // See http://stackoverflow.com/questions/7011451/transaprently-flatten-an-array/7011675
        $flattened = array();
        if($content && $content[$locale]) {
            $iterator = new Translate_Adapter_RailsYaml_Iterator(new RecursiveArrayIterator($content[$locale]));
            foreach($iterator as $k => $v) {
                $flattened[implode($options['keyDelimiter'], $iterator->getKeyStack())] = $v;
            }
        }

        return array($locale => $flattened);
    }
  
    /**
    * returns the adapters name
    *
    * @return string
    */
    public function toString()
    {
        return "RailsYaml";
    }

}

class Translate_Adapter_RailsYaml_Iterator extends RecursiveIteratorIterator
{
    protected $keyStack = array();

    public function callGetChildren() 
    {
      $this->keyStack[] = parent::key();
      return parent::callGetChildren();
    }

    public function endChildren() 
    {
      array_pop($this->keyStack);
      parent::endChildren();
    }

    public function key() 
    {
      return json_encode($this->getKeyStack());
    }

    public function getKeyStack() 
    {
      return array_merge($this->keyStack, array(parent::key()));
    }
}