<?php

namespace VPlugins\BlogPostConnector\Tests\Endpoints;

use VPlugins\BlogPostConnector\Endpoints\WebhookControl;
use VPlugins\BlogPostConnector\Helper\Globals;
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
     * Mocks get_option so the webhook flag reads back as $stored and logging stays off.
     *
     * @param string $stored The value the webhook option should read back as.
     */
    private function mock_get_option($stored) {
        \WP_Mock::userFunction('get_option', [
            'return' => function ($name, $default = null) use ($stored) {
                if ($name === Globals::WEBHOOK_ENABLED_OPTION) {
                    return $stored;
                }

                return false; // Keeps LoggerMiddleware inert.
            },
        ]);
    }

    /**
     * Returns a stand-in request object for the endpoint callbacks.
     */
    private function mock_request() {
        return \Mockery::mock(WP_REST_Request::class);
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
            'args' => [Globals::WEBHOOK_ENABLED_OPTION, '1'],
            'return' => true,
        ]);
        $this->mock_get_option('1');

        $response = $this->webhookControl->enable_webhook($this->mock_request());

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('enabled', $response->get_data()['data']['webhook_status']);
    }

    /**
     * Test that disabling the webhook persists the disabled state and reports it back.
     */
    public function test_disable_webhook_sets_option_and_returns_disabled_status() {
        \WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => [Globals::WEBHOOK_ENABLED_OPTION, '0'],
            'return' => true,
        ]);
        $this->mock_get_option('0');

        $response = $this->webhookControl->disable_webhook($this->mock_request());

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('disabled', $response->get_data()['data']['webhook_status']);
    }

    /**
     * Test that repeated calls to disable an already-disabled webhook remain a success no-op.
     *
     * update_option() returns false when the value is unchanged, which must not
     * be mistaken for a failed write.
     */
    public function test_disable_webhook_is_idempotent() {
        \WP_Mock::userFunction('update_option', [
            'times' => 2,
            'args' => [Globals::WEBHOOK_ENABLED_OPTION, '0'],
            'return' => false, // Unchanged value.
        ]);
        $this->mock_get_option('0');

        $first = $this->webhookControl->disable_webhook($this->mock_request());
        $second = $this->webhookControl->disable_webhook($this->mock_request());

        $this->assertEquals(200, $first->get_status());
        $this->assertEquals(200, $second->get_status());
        $this->assertEquals('disabled', $first->get_data()['data']['webhook_status']);
        $this->assertEquals('disabled', $second->get_data()['data']['webhook_status']);
    }

    /**
     * Test that a write which does not land is reported as an error rather than success.
     */
    public function test_disable_webhook_reports_error_when_write_does_not_persist() {
        \WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => [Globals::WEBHOOK_ENABLED_OPTION, '0'],
            'return' => false,
        ]);
        $this->mock_get_option('1'); // Still enabled: the write did not stick.

        $response = $this->webhookControl->disable_webhook($this->mock_request());

        $this->assertEquals(500, $response->get_status());
    }
}
