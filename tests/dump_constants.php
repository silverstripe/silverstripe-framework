<?php

// Helper for dumping the value of standard constants prior to test
require __DIR__ . '/../src/includes/autoload.php';

echo "=== User-Defined Constants ===\n";
$constants = get_defined_constants(true);
foreach ($constants['user'] as $name => $value) {
    echo " - {$name}: '{$value}'\n";
}
echo "==============================\n";
