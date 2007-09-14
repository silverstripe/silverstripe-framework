<?php
// Required so SilverStripe includes this module

define('MCE_ROOT', 'jsparty/tiny_mce2/');

$path = Director::baseFolder().'/sapphire/parsers/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

?>