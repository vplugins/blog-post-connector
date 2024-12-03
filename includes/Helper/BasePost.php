<?php 
namespace VPlugins\BlogPostConnector\Helper;

use WP_REST_Request;
use WP_REST_Response;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Helper\Response;

/**
 * Abstract class BasePost
 * 
 * Provides functionality for creating and updating WordPress posts via REST API endpoints.
 * Extend this class to implement specific post type handling.
 */
abstract class BasePost {
    /**
     * @var AuthMiddleware Instance of AuthMiddleware for handling authentication.
     */
    protected $auth_middleware;

    /**
     * Constructor
     * 
     * Initializes the authentication middleware and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers REST API routes
     * 
     * To be implemented by subclasses.
     */
    abstract public function register_routes();

    /**
     * Handles post creation or updating.
     * 
     * @param WP_REST_Request $request The incoming REST API request.
     * @param bool $is_update Indicates if the request is to update an existing post.
     * @return WP_REST_Response The response indicating success or failure.
     */
    protected function handle_post_request(WP_REST_Request $request, $is_update = false) {
        $post_id = $is_update ? $request->get_param('id') : null;

        if ($is_update && !$post_id) {
            return Response::error('post_id_required', 400);
        }

        if ($is_update) {
            $post = get_post($post_id);
            if (!$post) {
                return Response::error('post_not_found', 404);
            }
        }

        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $status = $request->get_param('status');
        $date = $request->get_param('date');
        $author_id = $request->get_param('author');
        $categories = $request->get_param('category');
        $tags = $request->get_param('tag');
        $featured_image_url = $request->get_param('featured_image');

        // Get the category and tag parameters
        $categories = $request->get_param('category');
        $tags = $request->get_param('tag');

        // Check if categories and tags are already arrays or comma-separated strings
        if (is_string($categories)) {
            $categories_array = array_map('intval', array_filter(array_map('trim', explode(',', $categories))));
        } elseif (is_array($categories)) {
            $categories_array = array_map('intval', $categories);
        } else {
            $categories_array = [];
        }

        if (is_string($tags)) {
            $tags_array = array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', $tags))));
        } elseif (is_array($tags)) {
            $tags_array = array_filter(array_map('sanitize_text_field', $tags));
        } else {
            $tags_array = [];
        }

        // Set default category if no categories are provided
        if (empty($categories_array) && !$is_update) {
            // Retrieve the default category from settings, or set a fallback category
            if (!$is_update) {
                $default_category = get_option('sm_post_connector_default_category', 1); // Default to category ID 1 if not set
                $categories_array = [$default_category];
            }
        }

        if (empty($author_id) && !$is_update) {
            $default_author = get_option('sm_post_connector_default_author', 1); // Default to author ID 1 if not set
            $author_id = $default_author;
        }

        // Validate status if provided
        $valid_statuses = ['publish', 'future', 'draft'];
        if ($status && !in_array($status, $valid_statuses)) {
            return Response::error('invalid_post_status', 400);
        }

        // Validate date if status is 'future'
        if ($status === 'future' && empty($date)) {
            return Response::error('date_required_for_future_posts', 400);
        }

        // Validate date if status is 'publish' and date is in the future
        if ($status === 'publish' && !empty($date) && strtotime($date) > time()) {
            return Response::error('date_for_publish_status_must_be_past', 400);
        }

        // Check for duplicate post title if creating a new post
        if (!$is_update && $title && get_page_by_title($title, OBJECT, 'post')) {
            return Response::error('post_with_title_exists', 400);
        }

        // Check if the author ID exists if provided
        if (!get_user_by('ID', $author_id)) {
            return Response::error('invalid_author_id', 400);
        }

        $attachment_id = 0;
        if (!empty($featured_image_url)) {
            $image_data = $this->download_image($featured_image_url);
            if ($image_data['status'] === 'error') {
                return Response::error($image_data['message'], 400);
            }
            $attachment_id = $this->upload_image($image_data['file_path']);
        }

        // Prepare the post data, but only include fields that are provided in the request
        $post_data = [
            'post_title'   => $title ? sanitize_text_field($title) : $post->post_title,
            'post_content' => $content ? wp_kses_post($content) : $post->post_content,
            'post_status'  => $status ? $status : $post->post_status,
            'post_date'    => ($status === 'future') ? date('Y-m-d H:i:s', strtotime($date)) : current_time('mysql'),
            'post_author'  => $author_id,
            'post_category'=> $categories_array,
            'tags_input'   => $tags_array,
            'meta_input'   => $is_update ? ['updated_by_sm_plugin' => true] : ['added_by_sm_plugin' => true]
        ];

        // If updating, set the post ID
        if ($is_update) {
            $post_data['ID'] = $post_id;
            $result_post_id = wp_update_post($post_data);
        } else {
            $result_post_id = wp_insert_post($post_data);
        }

        // Set the featured image if available
        if ($result_post_id && $attachment_id) {
            set_post_thumbnail($result_post_id, $attachment_id);
        }

        // Return success or failure response
        if ($result_post_id) {
            $post_url = get_permalink($result_post_id);
            return Response::success(
                $is_update ? 'post_updated_successfully' : 'post_created_successfully',
                ['post_id' => $result_post_id, 'post_url' => $post_url]
            );
        }

        return Response::error($is_update ? 'failed_to_update_post' : 'failed_to_create_post', 500);
    }

    /**
     * Downloads an image from the given URL.
     * 
     * @param string $image_url The URL of the image to download.
     * @return array An array containing the status and file path or error message.
     */
    protected function download_image($image_url) {
        $response = wp_remote_get($image_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return ['status' => 'error', 'message' => 'Failed to download image.'];
        }

        $file_path = wp_upload_dir()['path'] . '/' . basename($image_url);
        file_put_contents($file_path, wp_remote_retrieve_body($response));

        return ['status' => 'success', 'file_path' => $file_path];
    }

    /**
     * Uploads an image to the WordPress media library.
     * 
     * @param string $file_path The local file path of the image.
     * @return int The attachment ID of the uploaded image.
     */
    protected function upload_image($file_path) {
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