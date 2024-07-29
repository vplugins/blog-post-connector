<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use WP_REST_Response;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;

class CreatePost {
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/create-post', [
            'methods' => 'POST',
            'callback' => [$this, 'create_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function create_post(WP_REST_Request $request) {
        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $status = $request->get_param('status');
        $date = $request->get_param('date');
        $author_id = $request->get_param('author');
        $categories = $request->get_param('category');
        $tags = $request->get_param('tag');
        $featured_image_url = $request->get_param('featured_image');
    
        // Validate required parameters
        if (empty($title) || empty($content) || empty($status) || empty($author_id)) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Missing required parameters.'], 400);
        }
    
        // Validate post status
        $valid_statuses = ['publish', 'future', 'draft'];
        if (!in_array($status, $valid_statuses)) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Invalid post status.'], 400);
        }
    
        // Validate date parameter if required
        if ($status === 'future' && empty($date)) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Date is required for future posts.'], 400);
        }
    
        if ($status === 'publish' && !empty($date) && strtotime($date) > time()) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Date for publish status must be a past date.'], 400);
        }
    
        // Check if post with the same title already exists
        $existing_post = get_page_by_title($title, OBJECT, 'post');
        if ($existing_post) {
            return new WP_REST_Response(['status' => 400, 'message' => 'A post with this title already exists.'], 400);
        }
    
        // Handle featured image
        $attachment_id = 0;
        if (!empty($featured_image_url)) {
            $image_data = $this->download_image($featured_image_url);
            if ($image_data['status'] === 'error') {
                return new WP_REST_Response(['status' => 400, 'message' => $image_data['message']], 400);
            }
            $attachment_id = $this->upload_image($image_data['file_path']);
        }
    
        // Create the post
        $post_data = [
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_status'  => $status,
            'post_date'    => ($status === 'future') ? date('Y-m-d H:i:s', strtotime($date)) : current_time('mysql'),
            'post_author'  => (int) $author_id,
            'post_category'=> !empty($categories) ? array_map('intval', $categories) : [],
            'tags_input'   => !empty($tags) ? array_map('sanitize_text_field', $tags) : [],
            'meta_input'   => ['added_by_sm_plugin' => true]
        ];
    
        // Insert the post into the database
        $post_id = wp_insert_post($post_data);
    
        if ($post_id && $attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    
        if ($post_id) {
            $post_url = get_permalink($post_id);
            return new WP_REST_Response([
                'status' => 200,
                'data'   => [
                    'post_id'  => $post_id,
                    'post_url' => $post_url,
                ]
            ], 200);
        }
    
        return new WP_REST_Response(['status' => 500, 'message' => 'Failed to create post.'], 500);
    }
    
    private function download_image($image_url) {
        $response = wp_remote_get($image_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return ['status' => 'error', 'message' => 'Failed to download image.'];
        }
    
        $file_path = wp_upload_dir()['path'] . '/' . basename($image_url);
        file_put_contents($file_path, wp_remote_retrieve_body($response));
    
        return ['status' => 'success', 'file_path' => $file_path];
    }
    
    private function upload_image($file_path) {
        $wp_filetype = wp_check_filetype(basename($file_path), null);
        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name(basename($file_path)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];
    
        $attach_id = wp_insert_attachment($attachment, $file_path);
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
    
        return $attach_id;
    }
}