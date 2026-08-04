<?php

namespace VPlugins\BlogPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Helper\Globals;
use VPlugins\BlogPostConnector\Helper\Response;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;

/**
 * Class WebhookControl
 *
 * Registers REST API endpoints that let Social Marketing enable or disable
 * webhook delivery for the current connection without deactivating the plugin.
 *
 * @package VPlugins\BlogPostConnector\Endpoints
 */
class WebhookControl {
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
     * Repeating a call is a no-op from the caller's perspective, which is what
     * makes both endpoints idempotent. The stored state is verified after the
     * write so a failed write is never reported as success.
     *
     * @param WP_REST_Request $request The incoming request, used for logging.
     * @param bool $enabled Whether webhook delivery should be enabled.
     * @return \WP_REST_Response
     */
    private function set_webhook_state(WP_REST_Request $request, $enabled) {
        if (!Globals::set_webhook_enabled($enabled)) {
            return $this->respond(
                $request,
                Response::internal_server_error('webhook_state_update_failed')
            );
        }

        return $this->respond(
            $request,
            Response::success(
                $enabled ? 'webhook_enabled' : 'webhook_disabled',
                ['webhook_status' => $enabled ? 'enabled' : 'disabled']
            )
        );
    }

    /**
     * Logs the request/response pair and returns the response.
     *
     * @param WP_REST_Request $request The incoming request.
     * @param \WP_REST_Response $response The response to return.
     * @return \WP_REST_Response
     */
    private function respond(WP_REST_Request $request, $response) {
        if (class_exists(LoggerMiddleware::class)) {
            $logger = new LoggerMiddleware();
            $logger->log($request, $response);
        }

        return $response;
    }
}
