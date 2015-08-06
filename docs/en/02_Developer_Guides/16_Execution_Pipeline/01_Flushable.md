title: Flushable
summary: Allows a class to define its own flush functionality.
 
# Flushable

## Introduction

The Flushable interface enables one to define, for each implementing class, a flush functionality that gets triggered when `flush=1` is requested in the URL.
`[api:FlushRequestFilter]` is run before a request is made, calling the static method `flush()` on all
implementors of `[api:Flushable]`.

## Usage

To use this API, implement `[api:Flushable]` and define a static method `flush()` that specifies the actions to be executed upon a flush request.

### Usage with SS_Cache

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

In the code below, some temporary files are created in a sub-directory _tempfiles of the assets directory, and are deleted on flush. This would be useful, for example, when using `GD` or `Imagick` to generate resampled images; we may want to delete any cached images on flush so the reseampled images are re-created on demand.

	:::php
	<?php
	class MyClass extends DataObject implements Flushable {
	
		public static function flush() {
			foreach(glob(ASSETS_PATH . '/_tempfiles/*.jpg') as $file) {
				unlink($file);
			}
		}
	
	}

