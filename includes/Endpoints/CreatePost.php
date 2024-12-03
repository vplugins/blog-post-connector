<?php

namespace VPlugins\SMPostConnector\Endpoints;

use VPlugins\SMPostConnector\Helper\BasePost;

/**
 * Class CreatePost
 *
 * Registers a REST API endpoint for creating posts.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class CreatePost extends BasePost {

    /**
     * Registers the REST API routes for the CreatePost endpoint.
     *
     * Adds a route for creating posts using a POST request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/create-post'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/create-post', [
            'methods' => 'POST',
            'callback' => [$this, 'create_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to create a post.
     *
     * This method calls the `handle_post_request` method from the BasePost class to process the post creation.
     *
     * @param \WP_REST_Request $request The request object containing the data for creating the post.
     * @return \WP_REST_Response The response object containing the result of the post creation.
     */
    public function create_post($request) {
        return $this->handle_post_request($request);
    }
}