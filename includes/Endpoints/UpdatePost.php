<?php

namespace VPlugins\SMPostConnector\Endpoints;

use VPlugins\SMPostConnector\Helper\BasePost;

/**
 * Class UpdatePost
 *
 * Registers a REST API endpoint for updating an existing post.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class UpdatePost extends BasePost {
    /**
     * Registers the REST API route for updating a post.
     *
     * Adds a route for updating a post using a POST request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/update-post'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/update-post', [
            'methods' => 'POST',
            'callback' => [$this, 'update_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to update an existing post.
     *
     * Uses the `handle_post_request` method from the `BasePost` class to process the request,
     * with the flag set to true indicating an update operation.
     *
     * @param WP_REST_Request $request The request object containing the post data.
     * 
     * @return \WP_REST_Response The response object containing the result of the update operation.
     */
    public function update_post($request) {
        return $this->handle_post_request($request, true);
    }
}