<?php

namespace VPlugins\BlogPostConnector\Tests\Webhook;

use VPlugins\BlogPostConnector\Helper\Globals;
use VPlugins\BlogPostConnector\Webhook\Webhook;
use WP_Mock\Tools\TestCase;

class WebhookTest extends TestCase {

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test that no HTTP request is sent while webhook delivery is disabled.
     */
    public function test_trigger_webhook_is_skipped_when_disabled() {
        \WP_Mock::userFunction('get_option', [
            'args' => [Globals::WEBHOOK_ENABLED_OPTION, '1'],
            'return' => '0',
        ]);

        \WP_Mock::userFunction('wp_remote_post', [
            'times' => 0,
        ]);

        Webhook::trigger_webhook(['action' => 'created']);

        $this->assertConditionsMet();
    }

    /**
     * Test that the HTTP request is still sent while webhook delivery is enabled.
     */
    public function test_trigger_webhook_sends_request_when_enabled() {
        \WP_Mock::userFunction('get_option', [
            'args' => [Globals::WEBHOOK_ENABLED_OPTION, '1'],
            'return' => '1',
        ]);

        \WP_Mock::userFunction('wp_remote_post', [
            'times' => 1,
            'return' => ['response' => ['code' => 200]],
        ]);

        \WP_Mock::userFunction('is_wp_error', [
            'return' => false,
        ]);

        Webhook::trigger_webhook(['action' => 'created']);

        $this->assertConditionsMet();
    }

    /**
     * Test that forced payloads (plugin lifecycle events) are sent even while
     * delivery is disabled, without consulting the flag at all.
     */
    public function test_trigger_webhook_sends_forced_request_while_disabled() {
        \WP_Mock::userFunction('get_option', [
            'times' => 0,
        ]);

        \WP_Mock::userFunction('wp_remote_post', [
            'times' => 1,
            'return' => ['response' => ['code' => 200]],
        ]);

        \WP_Mock::userFunction('is_wp_error', [
            'return' => false,
        ]);

        Webhook::trigger_webhook(['action' => 'deactivated'], true);

        $this->assertConditionsMet();
    }
}
