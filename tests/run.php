<?php
/**
 * php tests/run.php
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/framework.php';

foreach ( glob( __DIR__ . '/test-*.php' ) as $file ) {
	require_once $file;
}

echo 'Auto Cleanup WP, Tests auf PHP ' . PHP_VERSION . PHP_EOL . PHP_EOL;

exit( ASU_Tests::run() );
