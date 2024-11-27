<?php 

namespace VPlugins\SMPostConnector\Webhook;

use VPlugins\SMPostConnector\Helper\Globals;

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
        // Ensure this function only runs in the admin panel, excluding AJAX and Cron requests.
        if (!is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        // Exit if this is an autosave or a post revision.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Only proceed for standard posts.
        if ($post->post_type !== 'post') {
            return;
        }

        // Retrieve custom post meta to determine if this post was added/updated by the SM plugin.
        $added_by_sm_plugin = get_post_meta($post_id, 'added_by_sm_plugin', true);
        $updated_by_sm_plugin = get_post_meta($post_id, 'updated_by_sm_plugin', true);

        // Only trigger the webhook if the post was added or updated by the SM plugin.
        if ($added_by_sm_plugin || $updated_by_sm_plugin) {
            // Prepare the data to send to the webhook, including the domain for context.
            $data = [
                'post_id'   => $post_id,
                'title'     => sanitize_text_field($post->post_title),
                'status'    => sanitize_text_field($post->post_status),
                'action'    => $update ? 'updated' : 'created',
                'domain'    => esc_url(home_url()), // Include the domain name in the payload.
            ];

            // Trigger the webhook with the prepared data.
            self::trigger_webhook($data);
        }
    }
}