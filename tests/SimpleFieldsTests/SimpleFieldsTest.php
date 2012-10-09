<?php
/**
 * MyPlugin Tests
 */
class MyPluginTest extends WP_UnitTestCase {

    // public $plugin_slug = 'my-plugin';

    public function setUp() {
        parent::setUp();
        //$this->my_plugin = $GLOBALS['my_plugin'];
        global $sf;
        $this->sf = $sf;
    }

    public function testAppendContent() {
        #$this->assertEquals( "<p>Hello WordPress Unit Tests</p>", $this->my_plugin->append_content(''), '->append_content() appends text' );
    }

    function test_debug() {

        $this->expectOutputString("<pre class='sf_box_debug'>this is simple fields debug function</pre>");
        sf_d("this is simple fields debug function");

    }

    /**
     * A contrived example using some WordPress functionality
     */
    public function testPostTitle() {
        
        // This will simulate running WordPress' main query.
        // See wordpress-tests/lib/testcase.php
        $this->go_to('http://unit-test.simple-fields.com/wordpress/?p=1');

        // Now that the main query has run, we can do tests that are more functional in nature
        #global $wp_query;
        #sf_d($wp_query);
        #$post = $wp_query->get_queried_object();
        #var_dump($post);
        #$this->assertEquals('Hello world!', $post->post_title );
        #$this->assertEquals('Hello world!', $post->post_title );
    }
}

