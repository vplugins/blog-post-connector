<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Response;

/**
 * Class GetPost
 *
 * Provides an endpoint for retrieving a single post's details.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class GetPost {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * GetPost constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving a post.
     *
     * Adds a route for retrieving a single post using a GET request.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/get-post', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to retrieve a single post.
     *
     * Retrieves the post details by ID and returns it in the response.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object containing the post details or an error.
     */
    public function get_post(WP_REST_Request $request) {
        $post_id = $request->get_param('id');

        // Validate the post ID
        if (empty($post_id)) {
            return Response::error('post_id_required', 400);
        }

        $post = get_post($post_id);

        // Check if the post exists
        if (!$post || $post->post_status === 'trash') {
            return Response::error('post_not_found', 404);
        }

        // Prepare the post data for response
        $post_data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => apply_filters('the_content', $post->post_content),
            'status' => $post->post_status,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'categories' => wp_get_post_categories($post->ID, ['fields' => 'names']),
            'tags' => wp_get_post_tags($post->ID, ['fields' => 'names']),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'full'),
            'date' => $post->post_date,
            'modified_date' => $post->post_modified,
        ];

        return Response::success('post_retrieved', $post_data);
    }
}