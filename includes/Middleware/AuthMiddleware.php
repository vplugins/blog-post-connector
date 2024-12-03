<?php

namespace VPlugins\BlogPostConnector\Middleware;

use WP_REST_Request;
use WP_Error;

/**
 * Class AuthMiddleware
 *
 * This class handles authorization checks for REST API requests. It verifies
 * the presence and validity of the authorization token in the request headers.
 */
class AuthMiddleware {
    /**
     * AuthMiddleware constructor.
     *
     * Initializes the middleware class. There are no hooks added here as this is a utility class.
     */
    public function __construct() {
        // No need to add hooks here, this is a utility class
    }

    /**
     * Checks permissions for REST API requests by validating the authorization token.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return true|WP_Error Returns true if the token is valid; otherwise, returns a WP_Error object with a 403 Forbidden status.
     */
    public function permissions_check(WP_REST_Request $request) {
        // Get the 'Authorization' header from the request
        $auth_header = $request->get_header('authorization');

        // Check if the 'Authorization' header is present and properly formatted
        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            return new WP_Error('rest_forbidden', __('Authorization header not found or malformed.', 'blog-post-connector'), array('status' => 403));
        }

        // Extract the token from the 'Authorization' header
        $token = substr($auth_header, 7); // Remove 'Bearer ' from the beginning

        // Retrieve the saved token from the options table
        $saved_token = get_option('sm_post_connector_token');

        // Validate the token
        if (empty($saved_token) || !hash_equals($saved_token, $token)) {
            return new WP_Error('rest_forbidden', __('Invalid token.', 'blog-post-connector'), array('status' => 403));
        }

        // If the token is valid, grant permission
        return true; // Permission granted
    }
}