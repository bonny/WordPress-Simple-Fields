<?php

#echo "WP_TESTS_DIR is: " . getenv( 'WP_TESTS_DIR' );
#phpinfo();exit;

require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../simple_fields.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';


class SimpleFields_TestCase extends WP_UnitTestCase {
	
	public static $sf = null;
	
	public static function setUpBeforeClass() {

		self::$sf = $GLOBALS['sf'];

	}

	/*
	function plugin() {
		return CWS_PageLinksTo::$instance;
	}
	*/

}

