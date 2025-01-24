<?php

namespace VPlugins\BlogPostConnector\Helper;

/**
 * Class Globals
 *
 * This class provides static methods to access plugin-specific information
 * and perform common tasks related to the plugin.
 */
class Globals {
    /**
     * @const string PLUGIN_VERSION The current version of the plugin.
     */
    const PLUGIN_VERSION = '1.0.2';
    const WEBHOOK_URL = 'https://social-posts-prod.apigateway.co/vplugin/webhook/blog-post';

    /**
     * Retrieves the plugin slug.
     *
     * @return string The plugin slug.
     */
    public static function get_plugin_slug() {
        return 'blog-post-connector';  // Your plugin slug
    }

    /**
     * Retrieves the path to the main plugin file.
     *
     * @return string The path to the main plugin file.
     */
    public static function get_plugin_file() {
        return 'blog-post-connector/blog-post-connector.php';  // Path to the main plugin file
    }

    /**
     * Retrieves the GitHub organization or username.
     *
     * @return string The GitHub organization or username.
     */
    public static function get_github_user() {
        return 'vplugins';  // GitHub organization or username
    }

    /**
     * Retrieves the GitHub repository name.
     *
     * @return string The GitHub repository name.
     */
    public static function get_github_repo() {
        return 'blog-post-connector';  // GitHub repository name
    }

    /**
     * Retrieves the GitHub API URL for the latest release.
     *
     * @return string The GitHub API URL for the latest release.
     */
    public static function get_github_api_url() {
        return 'https://api.github.com/repos/'.self::get_github_user().'/'.self::get_github_repo().'/releases/latest';
    } 

    /**
     * Retrieves the current plugin version.
     *
     * @return string The current plugin version.
     */
    public static function get_version() {
        return self::PLUGIN_VERSION;
    }

    /**
     * Retrieves a list of all categories.
     *
     * @return array An array of category objects.
     */
    public static function get_categories() {
        return get_categories([
            'hide_empty' => false
        ]);
    }

    /**
     * Retrieves a list of all tags.
     *
     * @return array An array of tag objects.
     */
    public static function get_tags() {
        return get_tags([
            'hide_empty' => false
        ]);
    }

    /**
     * Retrieves a list of users with specific roles.
     *
     * @return array An array of user objects.
     */
    public static function get_authors() {
        $args = [
            'role__in' => ['Author', 'Editor', 'Administrator'],
            'orderby' => 'display_name',
            'order' => 'ASC'
        ];
        return get_users($args);
    }

    /**
     * Retrieves a success message based on a given key.
     *
     * @param string $key The key for the desired success message.
     * @return string The success message associated with the given key.
     */
    public static function get_success_message($key) {
        $messages = [
            'status_retrieved' => __('Status information retrieved successfully', 'blog-post-connector'),
            'post_created' => __('Post created successfully', 'blog-post-connector'),
            'post_updated' => __('Post updated successfully', 'blog-post-connector'),
            'post_deleted' => __('Post deleted successfully', 'blog-post-connector'),
            'categories_retrieved' => __('Categories retrieved successfully', 'blog-post-connector'),
            'tags_retrieved' => __('Tags retrieved successfully', 'blog-post-connector'),
            'authors_retrieved' => __('Authors retrieved successfully', 'blog-post-connector'),
            'invalid_author_id' => __('Invalid author ID', 'blog-post-connector'),
            'post_id_required' => __('Post ID is required', 'blog-post-connector'),
            'post_not_found' => __('Post not found', 'blog-post-connector'),
            'post_moved_to_trash' => __('Post moved to trash successfully', 'blog-post-connector'),
            'post_permanently_deleted' => __('Post permanently deleted successfully', 'blog-post-connector'),
            'failed_to_delete_post' => __('Failed to delete post', 'blog-post-connector'),
            'missing_required_parameters' => __('Missing required parameters', 'blog-post-connector'),
            'invalid_post_status' => __('Invalid post status', 'blog-post-connector'),
            'date_required_for_future_posts' => __('Date is required for future posts', 'blog-post-connector'),
            'date_for_publish_status_must_be_past' => __('Date for publish status must be in the past', 'blog-post-connector'),
            'post_with_title_exists' => __('A post with the same title already exists', 'blog-post-connector'),
            'post_updated_successfully' => __('Post updated successfully', 'blog-post-connector'),
            'post_created_successfully' => __('Post created successfully', 'blog-post-connector'),
            'failed_to_update_post' => __('Failed to update post', 'blog-post-connector'),
            'failed_to_create_post' => __('Failed to create post', 'blog-post-connector'),
            'error' => __('An error occurred', 'blog-post-connector')
        ];

        return $messages[$key] ?? __( $key , 'blog-post-connector');
    }

    /**
     * Retrieves the webhook URL.
     *
     * @return string The webhook URL.
     */
    public static function get_webhook_url() {
        return self::WEBHOOK_URL;
    }

    /**
     * Custom error log function.
     *
     * Logs errors to the debug.log file only when WP_DEBUG is true.
     *
     * @param mixed  $message The message or data to log.
     * @param string $level   The log level (e.g., 'INFO', 'ERROR', 'DEBUG'). Default is 'DEBUG'.
     */
    public static function bp_error_log($message, $level = 'DEBUG') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $output = '[' . current_time('Y-m-d H:i:s') . '] [' . $level . '] ';
            
            if (is_array($message) || is_object($message)) {
                $output .= print_r($message, true);
            } else {
                $output .= $message;
            }

            error_log($output);
        }
    }
}