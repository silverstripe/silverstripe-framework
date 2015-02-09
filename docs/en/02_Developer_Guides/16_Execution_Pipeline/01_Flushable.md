title: Flushable
summary: Allows a class to define it's own flush functionality.
 
# Flushable

## Introduction

Allows a class to define it's own flush functionality, which is triggered when `flush=1` is requested in the URL.
`[api:FlushRequestFilter]` is run before a request is made, calling `flush()` statically on all
implementors of `[api:Flushable]`.

## Usage

To use this API, you need to make your class implement `[api:Flushable]`, and define a `flush()` static function,
this defines the actions that need to be executed on a flush request.

### Using with SS_Cache

This example uses `[api:SS_Cache]` in some custom code, and the same cache is cleaned on flush:

	:::php
	<?php
	class MyClass extends DataObject implements Flushable {
	
		public static function flush() {
			SS_Cache::factory('mycache')->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
	
		public function MyCachedContent() {
			$cache = SS_Cache::factory('mycache')
			$something = $cache->load('mykey');
			if(!$something) {
				$something = 'value to be cached';
				$cache->save($something, 'mykey');
			}
			return $something;
		}
	
	}

### Using with filesystem

Another example, some temporary files are created in a directory in assets, and are deleted on flush. This would be
useful in an example like `GD` or `Imagick` generating resampled images, but we want to delete any cached images on
flush so they are re-created on demand.

	:::php
	<?php
	class MyClass extends DataObject implements Flushable {
	
		public static function flush() {
			foreach(glob(ASSETS_PATH . '/_tempfiles/*.jpg') as $file) {
				unlink($file);
			}
		}
	
	}

