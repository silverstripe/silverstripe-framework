<?php
error_reporting(E_STRICT);

fwrite(STDERR, memory_get_peak_usage(true)."\n");

require '../jsmin.php';
echo JSMin::minify(file_get_contents('ext-all-debug.js'));

fwrite(STDERR, memory_get_peak_usage(true)."\n");
?>