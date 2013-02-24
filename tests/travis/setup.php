#!/usr/bin/env php
<?php
/**
 * Initialises a test project that can be built by travis.
 *
 * The local framework checkout's composer file is parsed and used to built a
 * custom local framework archive which is then installed into an installer
 * base project.
 */

if (php_sapi_name() != 'cli') {
	header('HTTP/1.0 404 Not Found');
	exit;
}

$target = $argv[1];
$branch = 'master';
$dir = __DIR__;
$path = dirname(dirname($dir));
$parent = dirname($path);

// Print out some environment information.
printf("Database versions:\n");
printf("  * MySQL:      %s\n", trim(`mysql --version`));
printf("  * PostgreSQL: %s\n", trim(`pg_config --version`));
printf("  * SQLite:     %s\n\n", trim(`sqlite3 -version`));

// Extract the package info from the framework composer file, and build a
// custom project composer file with the local package explicitly defined.
echo "Reading composer information...\n";

$package = __DIR__ . '/../../composer.json';
$package = json_decode(file_get_contents($package), true);

// Override the default framework requirement with the one being built.
$package += array(
	'version' => 'dev-travis',
	'dist'    => array(
		'type' => 'tar',
		'url'  => "file://$parent/framework.tar"
	)
);

// Generate a custom composer file.
$composer = json_encode(array(
	'repositories' => array(array('type' => 'package', 'package' => $package)),
	'require'      => array('silverstripe/framework'  => 'dev-travis')
));

echo "Generated composer file:\n";
echo "$composer\n";

echo "Archiving framework...\n";
`cd $path`;
`tar -cf $parent/framework.tar .`;

echo "Cloning installer...\n";
`git clone --depth=100 --quiet -b master git://github.com/silverstripe/silverstripe-installer.git $target`;

echo "Cloning postgresql module...\n";
`git clone --depth=100 --quiet -b master git://github.com/silverstripe/silverstripe-postgresql.git $target/postgresql`;

echo "Cloning sqlite3 module...\n";
`git clone --depth=100 --quiet -b master git://github.com/silverstripe-labs/silverstripe-sqlite3.git $target/sqlite3`;

echo "Setting up project...\n";
`cp $dir/_ss_environment.php $target`;
`cp $dir/_config.php $target/mysite`;

echo "Replacing composer file...\n";
unlink("$target/composer.json");
file_put_contents("$target/composer.json", $composer);

echo "Running composer...\n";
`composer install --dev -d $target`;
