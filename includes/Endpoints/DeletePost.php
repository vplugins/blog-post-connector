<?php

namespace VPlugins\BlogPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;
use VPlugins\BlogPostConnector\Helper\Response;

/**
 * Class DeletePost
 *
 * Registers a REST API endpoint for deleting posts.
 */
class DeletePost {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * @var LoggerMiddleware
     */
    protected $logger;

    /**
     * DeletePost constructor.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        $this->logger = new LoggerMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API routes for the DeletePost endpoint.
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
     * @param WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function delete_post(WP_REST_Request $request) {
        $post_id = $request->get_param('id');
        $trash = $request->get_param('trash');

        if (!$post_id) {
            $response = Response::error('post_id_required', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        $post = get_posts([
            'include' => [$post_id],
            'post_type' => 'any',
            'post_status' => ['any', 'trash'],
            'numberposts' => 1,
        ]);

        if (empty($post)) {
            $response = Response::error('post_not_found', 404);
            $this->logger->log($request, $response);
            return $response;
        }

        $force_delete = ($trash === 'true');
        $deleted = $force_delete ? wp_delete_post($post_id, true) : wp_trash_post($post_id);

        if ($deleted) {
            $response = Response::success(
                $force_delete ? 'post_permanently_deleted' : 'post_moved_to_trash',
                []
            );
            $this->logger->log($request, $response);
            return $response;
        }

        $response = Response::error('failed_to_delete_post', 500);
        $this->logger->log($request, $response);
        return $response;
    }
}