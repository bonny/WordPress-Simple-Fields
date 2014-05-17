<?php

ini_set('display_errors','on');
error_reporting(E_ALL);


echo "\n" . __FILE__ . " " . __LINE__ . "\n";
echo "\ngetcwd: " . getcwd() . "\n";

#require( dirname( __FILE__ ) . '/includes/define-constants.php' );

echo "\n" . __FILE__ . " " . __LINE__ . "\n";

#if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
#	die( "The WordPress PHPUnit test suite could not be found.\n" );
#}
require_once dirname( __FILE__ ) . '/../tmp/wordpress-tests/includes/functions.php';


echo "\n" . __FILE__ . " " . __LINE__ . "\n";

#require_once WP_TESTS_DIR . '/includes/functions.php';

echo "\n" . __FILE__ . " " . __LINE__ . "\n";

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../simple_fields.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

echo "\n" . __FILE__ . " " . __LINE__ . "\n";

require WP_TESTS_DIR . '/includes/bootstrap.php';

class SimpleFields_TestCase extends WP_UnitTestCase {
	
	public static $sf = null;
	
	public static function setUpBeforeClass() {

		self::$sf = $GLOBALS['sf'];

	}

}

/*
#EDD bootstrap version

ini_set('display_errors','on');
error_reporting(E_ALL);
define( 'EDD_PLUGIN_DIR', dirname( dirname( __FILE__ ) ) . '/'  );

require_once dirname( __FILE__ ) . '/../tmp/wordpress-tests/includes/functions.php';

function _install_and_load_edd() {
	require dirname( __FILE__ ) . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_install_and_load_edd' );

require dirname( __FILE__ ) . '/../tmp/wordpress-tests/includes/bootstrap.php';

require dirname( __FILE__ ) . '/framework/testcase.php';
*/