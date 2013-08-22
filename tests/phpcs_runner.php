<?php

if(php_sapi_name() != 'cli') {
	die("This script must be called from the command line\n");
}

if(!empty($_SERVER['argv'][1])) {
	$path = $_SERVER['argv'][1];
} else {
	die("Usage: php {$_SERVER['argv'][0]} <file>\n");
}

$result = array('comments' => array());

$extension = pathinfo($path, PATHINFO_EXTENSION);

// Whitelist of extensions to check (default phpcs list)
if(in_array($extension, array('php', 'js', 'inc', 'css'))) {
	// Run each sniff

	// phpcs --encoding=utf-8 --standard=framework/tests/phpcs/tabs.xml
	run_sniff('tabs.xml', $path, $result);

	// phpcs --encoding=utf-8 --tab-width=4 --standard=framework/tests/phpcs/ruleset.xml
	run_sniff('ruleset.xml', $path, $result, '--tab-width=4');
}
echo json_encode($result);

function run_sniff($standard, $path, array &$result, $extraFlags = '') {
	$sniffPath = escapeshellarg(__DIR__ . '/phpcs/' . $standard);
	$checkPath = escapeshellarg($path);

	exec("phpcs --encoding=utf-8 $extraFlags --standard=$sniffPath --report=xml $checkPath", $output);

	// We can't check the return code as it's non-zero if the sniff finds an error
	if($output) {
		$xml = implode("\n", $output);
		$xml = simplexml_load_string($xml);
		$errors = $xml->xpath('/phpcs/file/error');
		if($errors) {
			$sanePath = str_replace('/', '_', $path);
			foreach($errors as $error) {
				$attributes = $error->attributes();
				$result['comments'][] = array(
					'line' => (int)strval($attributes->line),
					'id' => $standard . '-' . $sanePath . '-' . $attributes->line . '-' . $attributes->column,
					'message' => strval($error)
				);
			}
		}
	}
}
