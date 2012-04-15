# Zend_Translate Adapter for Rails-style YAML files #

## Overview ##

Adds support for translations in YAML to [Zend_Translate](http://framework.zend.com/manual/en/zend.translate.html).
As Yaml is a very flexible format, the translation files need some conventions.
These conventions are adopted from Ruby on Rails (see [Rails' i18n docs](http://guides.rubyonrails.org/i18n.html)).
Note: You don't need Ruby or Rails to run this code, its just PHP with the same YAML conventions.

## Requirements ##

 * Zend Framework (tested with 1.11.6)
 * PHP 5.2

## Installation and Usage ##

Assumes a working `include_path` setup for Zend (see [tutorial](http://framework.zend.com/manual/en/learning.quickstart.create-project.html)).

Copy the files into your Zend directory (replace `<zend_path>` below):

	cp -r library/Translate/Adapter/* <zend_path>/Zend/Translate/Adapter
	cp -r tests/Translate/Adapter/* <zend_path>/Zend/tests/Translate/Adapter
	
Usage:

	require_once 'Zend/Translate/Adapater/RailsYaml.php';
	$adapter = new Zend_Translate_Adapter_RailsYaml('en.yml', 'en');
	$adapter->addTranslation('de.yml', 'de');

Does not support namespace "fallbacks", as `Zend_Translate`
doesn't have built-in support for them - it just flattens nested keys.
Does not support multiple locales per translation file.

## Sample translation files

en.yml

	en:
	  Message1: Message 1 (en)
	  Message2: Message 2 (en)
	  Namespace1:
	    Message1: Namespace 1 Message 2 (en)
	    Namespace1Message1: Namespace 1 Message 2 (en)

de.yml

	de:
	  Message1: Message 1 (de)
	  Namespace1:
	    Message1: Namespace 1 Message 2 (de)
	    Namespace1Message1: Namespace 1 Message 2 (de)

## Running the unit tests ##

The tests assume the Zend Framework in a very specific location. See `tests/TestHelper.php` for details.
Its recommended that you copy the relevant files directly into the Zend directory structure.

## Links ##

 * [`Zend_Translate_Yaml` Proposal on zend.com](http://framework.zend.com/wiki/display/ZFPROP/Zend_Translate_Yaml+-+Thomas+Weidner) - not actively pursued any longer
 * [`Zend_Translate_Yaml` sample code](http://framework.zend.com/issues/browse/ZF-2152)