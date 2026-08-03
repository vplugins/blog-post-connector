<?php

namespace VPlugins\BlogPostConnector\Tests\Webhook;

use VPlugins\BlogPostConnector\Endpoints\WebhookControl;
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
            'args' => [WebhookControl::OPTION_NAME, '1'],
            'return' => '0',
        ]);

        \WP_Mock::userFunction('wp_remote_post', [
            'times' => 0,
        ]);

        Webhook::trigger_webhook(['action' => 'created']);
    }

    /**
     * Test that the HTTP request is still sent while webhook delivery is enabled.
     */
    public function test_trigger_webhook_sends_request_when_enabled() {
        \WP_Mock::userFunction('get_option', [
            'args' => [WebhookControl::OPTION_NAME, '1'],
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
    }
}
