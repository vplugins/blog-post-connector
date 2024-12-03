<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;
use VPlugins\SMPostConnector\Helper\Response;

/**
 * Class GetTags
 *
 * Registers a REST API endpoint for retrieving tags.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class GetTags {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * GetTags constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving tags.
     *
     * Adds a route for retrieving tags using a GET request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/tags'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/tags', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tags'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to retrieve tags.
     *
     * Retrieves a list of tags, including their name, ID, and number of posts.
     *
     * @param WP_REST_Request $request The request object for retrieving tags.
     * 
     * @return \WP_REST_Response The response object containing the list of tags.
     */
    public function get_tags(WP_REST_Request $request) {
        $tags = Globals::get_tags();
        $formattedTags = [];
        $tagCount = 1;

        foreach ($tags as $tag) {
            $formattedTags[$tagCount] = [
                'name' => $tag->name,
                'id' => $tag->term_id,
                'num_posts' => $tag->count
            ];
            $tagCount++;
        }

        return Response::success(
            Globals::get_success_message('tags_retrieved'),
            [
                'tags' => $formattedTags
            ]
        );
    }
}