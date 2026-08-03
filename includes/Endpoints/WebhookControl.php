<?php

namespace VPlugins\BlogPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Helper\Response;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;

/**
 * Class WebhookControl
 *
 * Registers REST API endpoints that let SM enable or disable webhook
 * delivery for the current connection without deactivating the plugin.
 *
 * @package VPlugins\BlogPostConnector\Endpoints
 */
class WebhookControl {

    /**
     * @const string OPTION_NAME The option storing whether webhook delivery is enabled.
     */
    const OPTION_NAME = 'sm_post_connector_webhook_enabled';

    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * WebhookControl constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API routes for enabling and disabling webhook delivery.
     *
     * Routes are registered under the namespace 'sm-connect/v1'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/webhook/enable', [
            'methods' => 'POST',
            'callback' => [$this, 'enable_webhook'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);

        register_rest_route('sm-connect/v1', '/webhook/disable', [
            'methods' => 'POST',
            'callback' => [$this, 'disable_webhook'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to enable webhook delivery.
     *
     * @param WP_REST_Request $request The incoming request.
     * @return \WP_REST_Response The response indicating webhook delivery is enabled.
     */
    public function enable_webhook(WP_REST_Request $request) {
        return $this->set_webhook_state($request, true);
    }

    /**
     * Handles the request to disable webhook delivery.
     *
     * @param WP_REST_Request $request The incoming request.
     * @return \WP_REST_Response The response indicating webhook delivery is disabled.
     */
    public function disable_webhook(WP_REST_Request $request) {
        return $this->set_webhook_state($request, false);
    }

    /**
     * Persists the desired webhook state and returns a standardized response.
     *
     * Writing the same value the option already holds is a no-op from the
     * caller's perspective, which is what makes both endpoints idempotent.
     *
     * @param WP_REST_Request $request The incoming request, used for logging.
     * @param bool $enabled Whether webhook delivery should be enabled.
     * @return \WP_REST_Response
     */
    private function set_webhook_state(WP_REST_Request $request, $enabled) {
        update_option(self::OPTION_NAME, $enabled ? '1' : '0');

        $data = ['webhook_status' => $enabled ? 'enabled' : 'disabled'];
        $response = Response::success($enabled ? 'webhook_enabled' : 'webhook_disabled', $data);

        if (class_exists(LoggerMiddleware::class)) {
            $logger = new LoggerMiddleware();
            $logger->log($request, $response);
        }

        return $response;
    }

    /**
     * Determines whether webhook delivery is currently enabled.
     *
     * Webhooks are enabled by default so existing connections keep working
     * until SM explicitly disables them.
     *
     * @return bool True if webhook delivery is enabled, false otherwise.
     */
    public static function is_enabled() {
        return get_option(self::OPTION_NAME, '1') === '1';
    }
}
