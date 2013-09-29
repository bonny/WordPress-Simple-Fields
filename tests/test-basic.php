<?php

class BasicTest extends WP_UnitTestCase {

	/*
	setUpBeforeClass
	tearDownAfterClass
	setUp
	tearDown
	*/

	private static $sf = null;

	public static function setUpBeforeClass() {

			self::$sf = $GLOBALS['sf'];

	}

	function testSetupCorrectly() {

			$this->assertTrue( class_exists("simple_fields"), "Simple Fields class must exist" );
			$this->assertTrue( isset( $GLOBALS['sf']), "Global variable must be set" );

	}

	function testCache() {

			// cache namespace key must be numeric
			$this->assertTrue( is_numeric( self::$sf->ns_key ) );
			$old_ns = self::$sf->ns_key;

			// clear caches and check that key is increased
			self::$sf->clear_caches();
			$this->assertTrue( is_numeric( self::$sf->ns_key ) );
			$this->assertTrue( self::$sf->ns_key > $old_ns );

			// sf_d( self::$sf->ns_key );

			// check that cache stores and gets things correctly
			$key = 'simple_fields_unittest_' . self::$sf->ns_key . '_cache_test';
			$group = "simple_fields";
			$val = rand_str();
			$this->assertFalse( wp_cache_get( $key, $group), "Cached value must not exist" );
			wp_cache_set($key , $val, $group );
			$cached_val = wp_cache_get( $key, $group);
			$this->assertEquals( $cached_val, $val, "Value from cache must be same as orginal value" );

			// clear caches again and make sure values not in cache anymore
			self::$sf->clear_caches();
			$cached_val = wp_cache_get( $key, $group);
			$this->assertNotEquals( $cached_val, $val, "Value from cache must no be same as orginal value" );
			$this->assertFalse( $cached_val , "Cached value must not exist" );

	 }

	 function testDefaultValues() {

	 	// check that all configs are empty


	 }

	 /*
	 Check if we can get settings from old installation/unit testing
	 */

}

