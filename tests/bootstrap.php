<?php

require( dirname( __FILE__ ) . '/includes/define-constants.php' );

if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
	die( "The WordPress PHPUnit test suite could not be found.\n" );
}

require_once WP_TESTS_DIR . '/includes/functions.php';

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

