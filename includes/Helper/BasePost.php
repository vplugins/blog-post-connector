<?php 
namespace VPlugins\BlogPostConnector\Helper;

use WP_REST_Request;
use WP_REST_Response;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;
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
     * @var LoggerMiddleware Instance of LoggerMiddleware for logging requests and responses.
     */
    protected $logger;

    /**
     * Constructor
     * 
     * Initializes the authentication middleware and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        $this->logger = new LoggerMiddleware();
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
            $response = Response::error('post_id_required', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        if ($is_update) {
            $post = get_post($post_id);
            if (!$post) {
                $response = Response::error('post_not_found', 404);
                $this->logger->log($request, $response);
                return $response;
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
        $slug = $request->get_param('slug');

        $categories_array = is_string($categories)
            ? array_map('intval', array_filter(array_map('trim', explode(',', $categories))))
            : (is_array($categories) ? array_map('intval', $categories) : []);

        $tags_array = is_string($tags)
            ? array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', $tags))))
            : (is_array($tags) ? array_filter(array_map('sanitize_text_field', $tags)) : []);

        if (empty($categories_array) && !$is_update) {
            $default_category = get_option('sm_post_connector_default_category', 1);
            $categories_array = [$default_category];
        }

        if (empty($author_id) && !$is_update) {
            $author_id = get_option('sm_post_connector_default_author', 1);
        }

        $valid_statuses = ['publish', 'future', 'draft'];
        if ($status && !in_array($status, $valid_statuses)) {
            $response = Response::error('invalid_post_status', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        if ($status === 'future' && empty($date)) {
            $response = Response::error('date_required_for_future_posts', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        if ($status === 'future' && !empty($date) && strtotime((string) $date) === false) {
            $response = Response::error('invalid_date_format', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        if ($status === 'publish' && !empty($date) && strtotime($date) > time()) {
            $response = Response::error('date_for_publish_status_must_be_past', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        if (!$is_update && $title) {
            $existing = new \WP_Query([
                'post_type'      => 'post',
                'title'          => $title,
                'post_status'    => get_post_stati(),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);
            if ($existing->have_posts()) {
                $response = Response::error('post_with_title_exists', 400);
                $this->logger->log($request, $response);
                return $response;
            }
        }

        if (!get_user_by('ID', $author_id)) {
            $response = Response::error('invalid_author_id', 400);
            $this->logger->log($request, $response);
            return $response;
        }

        $attachment_id = 0;
        if (!empty($featured_image_url)) {
            $image_data = $this->download_image($featured_image_url);
            if ($image_data['status'] === 'error') {
                $response = Response::error($image_data['message'], 400);
                $this->logger->log($request, $response);
                return $response;
            }
            $attachment_id = $this->upload_image($image_data['file_path']);
        }

        $post_data = [
            'post_title'    => $title ? sanitize_text_field($title) : $post->post_title,
            'post_content'  => $content ? wp_kses_post($content) : $post->post_content,
            'post_status'   => $status ? $status : $post->post_status,
            'post_date'     => ($status === 'future' && ($ts = strtotime((string) $date)) !== false) ? date('Y-m-d H:i:s', $ts) : current_time('mysql'),
            'post_author'   => $author_id,
            'post_category' => $categories_array,
            'tags_input'    => $tags_array,
            'meta_input'    => $is_update ? ['updated_by_sm_plugin' => true] : ['added_by_sm_plugin' => true]
        ];

        if (!empty($slug)) {
            $post_data['post_name'] = sanitize_title($slug);
        }

        if ($is_update) {
            $post_data['ID'] = $post_id;
            $result_post_id = wp_update_post($post_data);
        } else {
            $result_post_id = wp_insert_post($post_data);
        }

        if ($result_post_id && $attachment_id) {
            set_post_thumbnail($result_post_id, $attachment_id);
        }

        if ($result_post_id) {
            $post_url = get_permalink($result_post_id);
            $response = Response::success(
                $is_update ? 'post_updated_successfully' : 'post_created_successfully',
                ['post_id' => $result_post_id, 'post_url' => $post_url]
            );
        } else {
            $response = Response::error($is_update ? 'failed_to_update_post' : 'failed_to_create_post', 500);
        }

        $this->logger->log($request, $response);
        return $response;
    }

    /**
     * Downloads an image from the given URL.
     * 
     * @param string $image_url The URL of the image to download.
     * @return array An array containing the status and file path or error message.
     */
    protected function download_image($image_url) {
        $response = wp_remote_get($image_url);
    
        // Check for errors in the HTTP response
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return ['status' => 'error', 'message' => 'Failed to download image.'];
        }
    
        // Get the content type from the headers to determine the image type
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $image_extension = '';
        switch ($content_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image_extension = 'jpg';
                break;
            case 'image/png':
                $image_extension = 'png';
                break;
            case 'image/gif':
                $image_extension = 'gif';
                break;
            case 'image/webp':
                $image_extension = 'webp';
                break;
            default:
                return ['status' => 'error', 'message' => 'Unsupported image type.'];
        }
    
        // Generate a unique file name using a hash
        $unique_name = uniqid('image_', true) . '.' . $image_extension;
    
        // Get the WordPress uploads directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $unique_name;
    
        // Save the image to the uploads directory
        $image_data = wp_remote_retrieve_body($response);
        if (file_put_contents($file_path, $image_data) === false) {
            return ['status' => 'error', 'message' => 'Failed to save image.'];
        }
    
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