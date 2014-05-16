<?php

#echo "WP_TESTS_DIR is: " . getenv( 'WP_TESTS_DIR' );
#phpinfo();exit;

// change loading based on this:
// https://buddypress.trac.wordpress.org/changeset/7421

/*require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';
require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
*/

/** 
 * In the pre-develop.svn WP development environment, an environmental bash 
 * variable would be set to run PHP Unit tests. However, this has been done 
 * away with in a post-develop.svn world. We'll still check if this variable 
 * is set for backwards compat. 
 */ 
if ( getenv( 'WP_TESTS_DIR' ) ) { 
    define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) ); 
    define( 'WP_TESTS_CONFIG_PATH', WP_TESTS_DIR . '/wp-tests-config.php' ); 
} 
else { 
    define( 'WP_ROOT_DIR', dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) ); 
    define( 'WP_TESTS_DIR', WP_ROOT_DIR . '/tests/phpunit' ); 
    define( 'WP_TESTS_CONFIG_PATH', WP_ROOT_DIR . '/wp-tests-config.php' ); 
} 
 
if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) 
    die( 'The WordPress PHPUnit test suite could not be found.' ); 
 
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

	/*
	function plugin() {
		return CWS_PageLinksTo::$instance;
	}
	*/

}

