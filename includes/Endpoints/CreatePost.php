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
        // Fetch parameters using get_param()
        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $status = $request->get_param('status') ? sanitize_text_field($request->get_param('status')) : 'draft';
        $date = $request->get_param('date') ? sanitize_text_field($request->get_param('date')) : null;
        $author = $request->get_param('author') ? intval($request->get_param('author')) : get_current_user_id();
        $category = $request->get_param('category') ? array_map('intval', (array)$request->get_param('category')) : [];
        $tag = $request->get_param('tag') ? array_map('sanitize_text_field', (array)$request->get_param('tag')) : [];
    
        // Validate required parameters
        if (empty($title) || empty($content)) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Title and content are required'], 400);
        }
    
        // Validate post status
        $valid_statuses = ['publish', 'pending', 'draft', 'future', 'private'];
        if (!in_array($status, $valid_statuses, true)) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Invalid post status'], 400);
        }
    
        // Validate date if status is "future" or if date is provided
        if ($status === 'future') {
            if (empty($date)) {
                return new WP_REST_Response(['status' => 400, 'message' => 'Date parameter is required for future posts'], 400);
            }
            
            $date_timestamp = strtotime($date);
            if ($date_timestamp === false || $date_timestamp <= current_time('timestamp')) {
                return new WP_REST_Response(['status' => 400, 'message' => 'Date must be a future date'], 400);
            }
        } elseif ($status === 'publish' && !empty($date)) {
            $date_timestamp = strtotime($date);
            if ($date_timestamp === false || $date_timestamp > current_time('timestamp')) {
                return new WP_REST_Response(['status' => 400, 'message' => 'Date must be a past date for published posts'], 400);
            }
        }
    
        // Validate categories
        foreach ($category as $cat_id) {
            if (!term_exists($cat_id, 'category')) {
                return new WP_REST_Response(['status' => 400, 'message' => 'Invalid category ID'], 400);
            }
        }
    
        // Validate tags
        foreach ($tag as $tag_name) {
            if (!is_string($tag_name) || strlen($tag_name) > 255) {
                return new WP_REST_Response(['status' => 400, 'message' => 'Invalid tag name'], 400);
            }
        }
    
        // Sanitize title and content
        $title = sanitize_text_field($title);
        $content = sanitize_textarea_field($content);
    
        // Create the post array
        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => $status,
            'post_date'    => $status === 'future' ? $date : current_time('mysql'),
            'post_author'  => $author,
            'post_category'=> $category,
        ];
    
        // Insert the post into the database
        $post_id = wp_insert_post($post_data);
    
        if (is_wp_error($post_id)) {
            return new WP_REST_Response(['status' => 500, 'message' => 'Post creation failed'], 500);
        }
    
        if ($post_id === 0) {
            return new WP_REST_Response(['status' => 400, 'message' => 'Invalid parameter'], 400);
        }
    
        // Add tags
        wp_set_post_tags($post_id, $tag);
    
        // Add custom boolean field
        update_post_meta($post_id, '_sm_post_connector_added', true);
    
        // Get the post URL
        $post_url = get_permalink($post_id);
    
        // Return success response
        $response_data = [
            'status' => 200,
            'data'   => [
                'post_id' => $post_id,
                'post_url'=> $post_url,
            ]
        ];
    
        return new WP_REST_Response($response_data, 200);
    }
}