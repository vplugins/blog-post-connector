<?php

namespace VPlugins\BlogPostConnector\Tests\Helper;

use VPlugins\BlogPostConnector\Helper\Globals;
use WP_Mock\Tools\TestCase;

class GlobalsMultisiteTest extends TestCase {

    /**
     * Whether the test runs on a multisite network, and on its main site.
     */
    private $multisite = false;
    private $main_site = true;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();

        \WP_Mock::userFunction('is_multisite', [
            'return' => function () {
                return $this->multisite;
            },
        ]);
        \WP_Mock::userFunction('is_main_site', [
            'return' => function () {
                return $this->main_site;
            },
        ]);
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * A single site runs the plugin and activates it normally.
     */
    public function test_single_site_is_supported_and_can_activate() {
        $this->assertTrue(Globals::is_supported_site());
        $this->assertNull(Globals::get_activation_error(false));
    }

    /**
     * The main site of a network runs the plugin and activates it normally.
     */
    public function test_main_site_is_supported_and_can_activate() {
        $this->multisite = true;

        $this->assertTrue(Globals::is_supported_site());
        $this->assertNull(Globals::get_activation_error(false));
    }

    /**
     * A subsite does not run the plugin and refuses activation.
     */
    public function test_subsite_is_not_supported_and_refuses_activation() {
        $this->multisite = true;
        $this->main_site = false;

        $this->assertFalse(Globals::is_supported_site());
        $this->assertStringContainsString('main site', Globals::get_activation_error(false));
    }

    /**
     * Network activation is refused, since it would put the plugin on every subsite.
     */
    public function test_network_activation_is_refused() {
        $this->multisite = true;

        $this->assertStringContainsString('network activated', Globals::get_activation_error(true));
    }
}
