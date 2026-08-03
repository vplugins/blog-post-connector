<?php

namespace VPlugins\BlogPostConnector\Tests\Endpoints;

use VPlugins\BlogPostConnector\Endpoints\WebhookControl;
use WP_Mock\Tools\TestCase;
use WP_REST_Request;

class WebhookControlTest extends TestCase {

    /**
     * @var WebhookControl
     */
    private $webhookControl;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->webhookControl = new WebhookControl();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test that both the enable and disable routes are registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/webhook/enable',
                [
                    'methods' => 'POST',
                    'callback' => [$this->webhookControl, 'enable_webhook'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/webhook/disable',
                [
                    'methods' => 'POST',
                    'callback' => [$this->webhookControl, 'disable_webhook'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->webhookControl->register_routes();

        \WP_Mock::assertHooksAdded();
    }

    /**
     * Test that enabling the webhook persists the enabled state and reports it back.
     */
    public function test_enable_webhook_sets_option_and_returns_enabled_status() {
        \WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => [WebhookControl::OPTION_NAME, '1'],
            'return' => true,
        ]);
        \WP_Mock::userFunction('get_option', [
            'args' => ['sm_post_connector_enable_logs'],
            'return' => false,
        ]);
        \WP_Mock::userFunction('__', [
            'return' => function ($text) {
                return $text;
            },
        ]);

        $request = \Mockery::mock(WP_REST_Request::class);

        $response = $this->webhookControl->enable_webhook($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('enabled', $response->get_data()['data']['webhook_status']);
    }

    /**
     * Test that disabling the webhook persists the disabled state and reports it back.
     */
    public function test_disable_webhook_sets_option_and_returns_disabled_status() {
        \WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => [WebhookControl::OPTION_NAME, '0'],
            'return' => true,
        ]);
        \WP_Mock::userFunction('get_option', [
            'args' => ['sm_post_connector_enable_logs'],
            'return' => false,
        ]);
        \WP_Mock::userFunction('__', [
            'return' => function ($text) {
                return $text;
            },
        ]);

        $request = \Mockery::mock(WP_REST_Request::class);

        $response = $this->webhookControl->disable_webhook($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('disabled', $response->get_data()['data']['webhook_status']);
    }

    /**
     * Test that repeated calls to disable an already-disabled webhook remain a success no-op.
     */
    public function test_disable_webhook_is_idempotent() {
        \WP_Mock::userFunction('update_option', [
            'times' => 2,
            'args' => [WebhookControl::OPTION_NAME, '0'],
            'return' => true,
        ]);
        \WP_Mock::userFunction('get_option', [
            'args' => ['sm_post_connector_enable_logs'],
            'return' => false,
        ]);
        \WP_Mock::userFunction('__', [
            'return' => function ($text) {
                return $text;
            },
        ]);

        $request = \Mockery::mock(WP_REST_Request::class);

        $first = $this->webhookControl->disable_webhook($request);
        $second = $this->webhookControl->disable_webhook($request);

        $this->assertEquals(200, $first->get_status());
        $this->assertEquals(200, $second->get_status());
        $this->assertEquals('disabled', $first->get_data()['data']['webhook_status']);
        $this->assertEquals('disabled', $second->get_data()['data']['webhook_status']);
    }

    /**
     * Test that is_enabled() defaults to true when the option has never been set.
     */
    public function test_is_enabled_defaults_to_true() {
        \WP_Mock::userFunction('get_option', [
            'args' => [WebhookControl::OPTION_NAME, '1'],
            'return' => '1',
        ]);

        $this->assertTrue(WebhookControl::is_enabled());
    }

    /**
     * Test that is_enabled() reflects a persisted disabled state.
     */
    public function test_is_enabled_reflects_disabled_option() {
        \WP_Mock::userFunction('get_option', [
            'args' => [WebhookControl::OPTION_NAME, '1'],
            'return' => '0',
        ]);

        $this->assertFalse(WebhookControl::is_enabled());
    }
}
