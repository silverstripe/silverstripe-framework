<?php

use SilverStripe\Core\Config\Config;

// Dynamically change environment
Config::inst()->update('SilverStripe\\Control\\Director', 'environment_type', 'dev');
