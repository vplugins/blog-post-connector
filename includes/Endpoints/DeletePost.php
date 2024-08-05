<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Response;

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