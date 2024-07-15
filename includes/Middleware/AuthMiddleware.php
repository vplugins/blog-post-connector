<?php

namespace VPlugins\SMPostConnector\Middleware;

use WP_REST_Request;

class AuthMiddleware {
    public function __construct() {
        // No need to add hooks here, this is a utility class
    }

    public function permissions_check(WP_REST_Request $request) {
        $auth_header = $request->get_header('authorization');
        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            return new \WP_Error('rest_forbidden', __('Authorization header not found or malformed.', 'sm-post-connector'), array('status' => 403));
        }

        $token = substr($auth_header, 7); // Remove 'Bearer ' from the beginning
        $saved_token = get_option('sm_post_connector_token');
        if (empty($saved_token) || !hash_equals($saved_token, $token)) {
            return new \WP_Error('rest_forbidden', __('Invalid token.', 'sm-post-connector'), array('status' => 403));
        }

        return true; // Permission granted
    }
}
