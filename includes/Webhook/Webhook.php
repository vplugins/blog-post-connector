<?php 

namespace VPlugins\BlogPostConnector\Webhook;

use VPlugins\BlogPostConnector\Helper\Globals;

/**
 * Class Webhook
 *
 * Handles webhook requests triggered by post updates made through the WordPress admin panel.
 */
class Webhook {

    /**
     * Webhook constructor.
     *
     * Initializes the Webhook class and sets up the webhook endpoint.
     */
    public function __construct() {
        add_action('save_post', [$this, 'trigger_webhook_on_post_update'], 10, 3);
        add_action('before_delete_post', [$this, 'trigger_webhook_on_post_delete'], 10, 1);
        add_action('wp_trash_post', [$this, 'trigger_webhook_on_post_trash'], 10, 1);
        add_action('untrash_post', [$this, 'trigger_webhook_on_post_restore'], 10, 1);
    }

    /**
     * Sends a POST request to the specified webhook URL.
     *
     * @param array $data The data to send in the webhook payload.
     * @return void
     */
    public static function trigger_webhook($data = []) {
        $webhook_url = Globals::get_webhook_url();

        if (empty($webhook_url)) {
            error_log('Webhook URL is not configured.');
            return; // Exit if no webhook URL is configured.
        }

        // Set up the request arguments, including headers and payload.
        $args = [
            'body'    => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 10, // Set a reasonable timeout for the request.
        ];

        // Send the POST request to the specified webhook URL.
        $response = wp_remote_post($webhook_url, $args);

        // Log any errors that occur during the request.
        if (is_wp_error($response)) {
            error_log('Failed to send webhook: ' . $response->get_error_message());
        } else {
            error_log('Webhook triggered successfully for Post ID: ' . $data['post_id']);
        }
    }

    /**
     * Triggers a webhook notification when a post is created or updated via the WordPress admin panel.
     *
     * This method is hooked to the `save_post` action and will only execute for standard WordPress posts
     * that contain specific meta values (`added_by_sm_plugin` or `updated_by_sm_plugin`) when edited via the admin panel.
     *
     * @param int     $post_id   The ID of the post being saved.
     * @param WP_Post $post      The post object being saved.
     * @param bool    $update    Indicates if this is an existing post being updated.
     * @return void
     */
    public function trigger_webhook_on_post_update($post_id, $post, $update) {
        // Exit early for specific scenarios
        if (
            $this->is_sm_plugin_api_call() ||  // Check if this is a Plugin API call
            defined('DOING_AJAX') ||           // Ignore AJAX calls
            defined('DOING_CRON') ||           // Ignore CRON jobs
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || // Ignore autosaves
            wp_is_post_revision($post_id)     // Ignore post revisions
        ) {
            return;
        }

        // Proceed only for standard posts
        if ($post->post_type !== 'post') {
            error_log('Webhook not triggered: Post type is not "post".');
            return;
        }

        // Retrieve custom post meta to determine if this post was added/updated by the plugin.
        $added_by_sm_plugin = get_post_meta($post_id, 'added_by_sm_plugin', true);
        $updated_by_sm_plugin = get_post_meta($post_id, 'updated_by_sm_plugin', true);

        // Only trigger the webhook if the post was added or updated by the plugin.
        error_log('Added by Blog Post Plugin: ' . $post->post_status);
        if ($added_by_sm_plugin || $updated_by_sm_plugin) {
            if( $post->post_status == 'trash' ) {
                return;
            }
            // Prepare the data to send to the webhook, including the domain for context.
            $data = [
                'post_id'   => $post_id,
                'action'    => $update ? 'updated' : 'created',
                'domain'    => esc_url(home_url()), // Include the domain name in the payload.
                'post'      => [
                    'title' => $post->post_title,
                    'content' => $post->post_content,
                    'status' => $post->post_status,
                    'author' => get_the_author_meta('display_name', $post->post_author),
                    'date' => $post->post_date,
                    'modified' => $post->post_modified,
                    'permalink' => get_permalink($post_id),
                    'categories' => wp_get_post_categories($post_id),
                    'tags' => wp_get_post_tags($post_id),
                ]
            ];

            // Trigger the webhook with the prepared data.
            self::trigger_webhook($data);
        }
    }

    /**
     * Triggers the webhook when a post is permanently deleted.
     *
     * @param int $post_id The ID of the post being deleted.
     */
    public function trigger_webhook_on_post_delete($post_id) {
        // Get the post object
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return; // Only handle standard posts
        }

        // Check if the post has the required meta fields
        $added_by_sm_plugin = get_post_meta($post_id, 'added_by_sm_plugin', true);
        $updated_by_sm_plugin = get_post_meta($post_id, 'updated_by_sm_plugin', true);

        if ($added_by_sm_plugin || $updated_by_sm_plugin) {
            // Prepare data to send to the webhook
            $data = [
                'post_id' => $post_id,
                'title'   => $post->post_title,
                'status'  => 'deleted',
                'action'  => 'deleted',
                'domain'  => home_url(),
            ];

            // Trigger the webhook
            self::trigger_webhook($data);
        }
    }

    /**
     * Triggers the webhook when a post is moved to the trash.
     *
     * @param int $post_id The ID of the post being trashed.
     */
    public function trigger_webhook_on_post_trash($post_id) {
        // Get the post object
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return; // Only handle standard posts
        }

        // Check if the post has the required meta fields
        $added_by_sm_plugin = get_post_meta($post_id, 'added_by_sm_plugin', true);
        $updated_by_sm_plugin = get_post_meta($post_id, 'updated_by_sm_plugin', true);

        if ($added_by_sm_plugin || $updated_by_sm_plugin) {
            // Prepare data to send to the webhook
            $data = [
                'post_id' => $post_id,
                'title'   => $post->post_title,
                'status'  => 'trashed',
                'action'  => 'trashed',
                'domain'  => home_url(),
            ];

            // Trigger the webhook
            self::trigger_webhook($data);
        }
    }

    /**
     * Triggers the webhook when a post is restored from the trash.
     *
     * @param int $post_id The ID of the post being restored.
     */
    public function trigger_webhook_on_post_restore($post_id) {
        // Get the post object
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return; // Only handle standard posts
        }

        // Check if the post has the required meta fields
        $added_by_sm_plugin = get_post_meta($post_id, 'added_by_sm_plugin', true);
        $updated_by_sm_plugin = get_post_meta($post_id, 'updated_by_sm_plugin', true);

        if ($added_by_sm_plugin || $updated_by_sm_plugin) {
            // Prepare data to send to the webhook
            $data = [
                'post_id' => $post_id,
                'title'   => $post->post_title,
                'status'  => 'restored',
                'action'  => 'restored',
                'domain'  => home_url(),
            ];

            // Trigger the webhook
            self::trigger_webhook($data);
        }
    }

    private function is_sm_plugin_api_call() {
        // Retrieve the Authorization header
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    
        // Retrieve the stored Plugin API token
        $sm_plugin_token = get_option('sm_post_connector_token', '');
    
        if (!$auth_header || !$sm_plugin_token) {
            return false; // Not an Plugin API call
        }
    
        // Extract the Bearer token from the Authorization header
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
            return hash_equals($sm_plugin_token, $token); // Validate token securely
        }
    
        return false;
    }
}