<?php

namespace VPlugins\BlogPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;
use VPlugins\BlogPostConnector\Helper\Response;

/**
 * Class GetPost
 *
 * Provides an endpoint for retrieving a single post's details.
 */
class GetPost {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * @var LoggerMiddleware
     */
    protected $logger;

    /**
     * GetPost constructor.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        $this->logger = new LoggerMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving a post.
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
     * @param WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_post(WP_REST_Request $request) {
        $post_id = $request->get_param('id');

        if (empty($post_id)) {
            $response = Response::error('post_id_required', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        $post = get_post($post_id);

        if (!$post || $post->post_status === 'trash') {
            $response = Response::error('post_not_found', 404);
            $this->logger->log($request, $response);
            return $response;
        }

        $post_data = [
            'id'             => $post->ID,
            'title'          => $post->post_title,
            'content'        => apply_filters('the_content', $post->post_content),
            'status'         => $post->post_status,
            'author'         => get_the_author_meta('display_name', $post->post_author),
            'categories'     => wp_get_post_categories($post->ID, ['fields' => 'names']),
            'tags'           => wp_get_post_tags($post->ID, ['fields' => 'names']),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'full'),
            'date'           => $post->post_date,
            'modified_date'  => $post->post_modified,
        ];

        $response = Response::success('post_retrieved', $post_data);
        $this->logger->log($request, $response);

        return $response;
    }
}