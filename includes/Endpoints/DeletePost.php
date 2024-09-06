<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Response;

/**
 * Class DeletePost
 *
 * Registers a REST API endpoint for deleting posts.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class DeletePost {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * DeletePost constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API routes for the DeletePost endpoint.
     *
     * Adds a route for deleting posts using a DELETE request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/delete-post'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/delete-post', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to delete a post.
     *
     * Deletes a post based on the provided ID. The post can either be moved to trash or permanently deleted.
     *
     * @param WP_REST_Request $request The request object containing the parameters for deleting the post.
     * 
     * @return \WP_REST_Response The response object containing the result of the delete operation.
     */
    public function delete_post(WP_REST_Request $request) {
        $post_id = $request->get_param('id');
        $trash = $request->get_param('trash');

        if (!$post_id) {
            return Response::error('post_id_required', 400);
        }

        $post = get_posts([
            'include' => [$post_id],
            'post_type' => 'any',
            'post_status' => ['any', 'trash'],
            'numberposts' => 1,
        ]);

        if (empty($post)) {
            return Response::error('post_not_found', 404);
        }

        $force_delete = ($trash === 'true');

        $deleted = $force_delete ? wp_delete_post($post_id, true) : wp_trash_post($post_id);

        if ($deleted) {
            return Response::success(
                $force_delete ? 'post_permanently_deleted' : 'post_moved_to_trash',
                []
            );
        }

        return Response::error('failed_to_delete_post', 500);
    }
}