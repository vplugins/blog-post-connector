<?php

namespace VPlugins\BlogPostConnector\Middleware;

use WP_REST_Request;
use WP_Error;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;

/**
 * Class AuthMiddleware
 *
 * Handles authorization for REST API requests.
 */
class AuthMiddleware {
    protected $logger;

    public function __construct() {
        // Prevent fatal if logger class is unavailable
        if (class_exists(LoggerMiddleware::class)) {
            $this->logger = new LoggerMiddleware();
        }
    }

    /**
     * Checks permissions for REST API requests by validating the authorization token.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return true|WP_Error Returns true if the token is valid; otherwise, returns a WP_Error.
     */
    public function permissions_check(WP_REST_Request $request) {
        $auth_header = $request->get_header('authorization');

        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            $error = new WP_Error('rest_forbidden', __('Authorization header not found or malformed.', 'blog-post-connector'), ['status' => 403]);
            $this->log_if_possible($request, $error);
            return $error;
        }

        $token = substr($auth_header, 7);
        $saved_token = get_option('sm_post_connector_token');

        if (empty($saved_token) || !hash_equals($saved_token, $token)) {
            $error = new WP_Error('rest_forbidden', __('Invalid token.', 'blog-post-connector'), ['status' => 403]);
            $this->log_if_possible($request, $error);
            return $error;
        }

        return true;
    }

    /**
     * Logs the request and response if logger is available.
     */
    private function log_if_possible(WP_REST_Request $request, $response) {
        if ($this->logger instanceof LoggerMiddleware) {
            $this->logger->log($request, $response);
        }
    }
}