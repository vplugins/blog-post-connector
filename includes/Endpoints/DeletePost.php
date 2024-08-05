<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use WP_REST_Response;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;

class DeletePost {
    protected $auth_middleware;

    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/delete-post', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function delete_post(WP_REST_Request $request) {
        // Get post ID and trash flag from request
        $post_id = $request->get_param('id');
        $trash = $request->get_param('trash');

        if (!$post_id) {
            return new WP_REST_Response([
                'status' => 400,
                'message' => 'Post ID is required'
            ], 400);
        }

        // Check if post exists
        $post = get_posts([
            'include' => [$post_id],
            'post_type' => 'any',
            'post_status' => array( 'any', 'trash' ),
            'numberposts' => 1,
        ]);

        if (empty($post)) {
            return new WP_REST_Response([
                'status' => 404,
                'message' => 'Post not found'
            ], 404);
        }

        // Determine if the post should be trashed or permanently deleted
        // By default, if trash is not provided, it will be set to true (move to trash)
        $force_delete = ($trash === 'true') ? true : false;

        // Delete the post
        if ($force_delete) {
            $deleted = wp_delete_post($post_id, true);
        } else {
            $deleted = wp_trash_post($post_id);
        }

        if ($deleted) {
            return new WP_REST_Response([
                'status' => 200,
                'message' => $force_delete ? 'Post permanently deleted successfully' : 'Post moved to trash successfully'
            ], 200);
        } else {
            return new WP_REST_Response([
                'status' => 500,
                'message' => 'Failed to delete post'
            ], 500);
        }
    }
}
