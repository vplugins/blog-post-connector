<?php

namespace VPlugins\BlogPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;
use VPlugins\BlogPostConnector\Helper\Globals;
use VPlugins\BlogPostConnector\Helper\Response;

/**
 * Class GetTags
 *
 * Registers a REST API endpoint for retrieving tags.
 */
class GetTags {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * @var LoggerMiddleware
     */
    protected $logger;

    /**
     * GetTags constructor.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        $this->logger = new LoggerMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving tags.
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
     * @param WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
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

        $response = Response::success(
            Globals::get_success_message('tags_retrieved'),
            [
                'tags' => $formattedTags
            ]
        );

        // Log the request and response
        $this->logger->log($request, $response);

        return $response;
    }
}