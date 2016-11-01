<?php

namespace SilverStripe\View;

use Exception;
use JSMin;

class JSMinifier implements Requirements_Minifier {

	public function minify($content, $type, $filename) {
		// Non-js files aren't minified
		if($type !== 'js') {
			return $content . "\n";
		}

		// Combine JS
		try {
			require_once('thirdparty/jsmin/jsmin.php');
			increase_time_limit_to();
			$content = JSMin::minify($content);
		} catch(Exception $e) {
			$message = $e->getMessage();
			user_error("Failed to minify {$filename}, exception: {$message}", E_USER_WARNING);
		} finally {
			return $content . ";\n";
		}
	}
}
