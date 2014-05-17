<?php

ini_set('display_errors','on');
error_reporting(E_ALL);

define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) );

echo "\nWP_TESTS_DIR is " . WP_TESTS_DIR . "\n";
echo "\ncurrent dir is " . getcwd() . "\n";

require_once dirname( __FILE__ ) . '/../tmp/wordpress-tests/includes/functions.php';


function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../simple_fields.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require WP_TESTS_DIR . '/includes/bootstrap.php';

class SimpleFields_TestCase extends WP_UnitTestCase {
	
	public static $sf = null;
	
	public static function setUpBeforeClass() {

		self::$sf = $GLOBALS['sf'];

	}

}
