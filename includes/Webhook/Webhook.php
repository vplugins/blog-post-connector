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

        add_action('create_category', [$this, 'trigger_webhook_on_category_create'], 10, 1);
        add_action('edit_category', [$this, 'trigger_webhook_on_category_update'], 10, 1);
        add_action('delete_category', [$this, 'trigger_webhook_on_category_delete'], 10, 1);

        add_action('create_post_tag', [$this, 'trigger_webhook_on_tag_create'], 10, 1);
        add_action('edit_post_tag', [$this, 'trigger_webhook_on_tag_update'], 10, 1);
        add_action('delete_post_tag', [$this, 'trigger_webhook_on_tag_delete'], 10, 1);

        add_action('user_register', [$this, 'trigger_webhook_on_author_register'], 10, 1);
        add_action('profile_update', [$this, 'trigger_webhook_on_author_update'], 10, 2);
        add_action('delete_user', [$this, 'trigger_webhook_on_author_delete'], 10, 1);
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
            GLOBALS::bp_error_log('Webhook URL is not configured.');
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
            GLOBALS::bp_error_log('Failed to send webhook: ' . $response->get_error_message());
        } else {
            GLOBALS::bp_error_log('Webhook triggered successfully');
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
            GLOBALS::bp_error_log('Webhook not triggered: Post type is not "post".');
            return;
        }

        // Retrieve custom post meta to determine if this post was added/updated by the plugin.
        $added_by_sm_plugin = get_post_meta($post_id, 'added_by_sm_plugin', true);
        $updated_by_sm_plugin = get_post_meta($post_id, 'updated_by_sm_plugin', true);

        // Only trigger the webhook if the post was added or updated by the plugin.
        GLOBALS::bp_error_log('Added by Blog Post Plugin: ' . $post->post_status);
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

    /**
     * Trigger webhook when a category is created.
     *
     * @param int $term_id Term ID of the newly created category.
     */
    public function trigger_webhook_on_category_create($term_id) {
        $category = get_term($term_id);
        $data = [
            'action' => 'category_created',
            'domain' => esc_url(home_url()),
            'category' => [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ]
        ];
        self::trigger_webhook($data);
    }

    /**
     * Trigger webhook when a category is updated.
     *
     * @param int $term_id Term ID of the updated category.
     */
    public function trigger_webhook_on_category_update($term_id) {
        $category = get_term($term_id);
        $data = [
            'action' => 'category_updated',
            'domain' => esc_url(home_url()),
            'category' => [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ]
        ];
        self::trigger_webhook($data);
    }

    /**
     * Trigger webhook when a category is deleted.
     *
     * @param int $term_id Term ID of the deleted category.
     */
    public function trigger_webhook_on_category_delete($term_id) {
        $data = [
            'action' => 'category_deleted',
            'domain' => esc_url(home_url()),
            'category_id' => $term_id
        ];
        self::trigger_webhook($data);
    }

    /**
     * Trigger webhook when a tag is created.
     *
     * @param int $term_id Term ID of the newly created tag.
     */
    public function trigger_webhook_on_tag_create($term_id) {
        $tag = get_term($term_id);
        $data = [
            'action' => 'tag_created',
            'domain' => esc_url(home_url()),
            'tag' => [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'description' => $tag->description,
            ]
        ];
        self::trigger_webhook($data);
    }

    /**
     * Trigger webhook when a tag is updated.
     *
     * @param int $term_id Term ID of the updated tag.
     */
    public function trigger_webhook_on_tag_update($term_id) {
        $tag = get_term($term_id);
        $data = [
            'action' => 'tag_updated',
            'domain' => esc_url(home_url()),
            'tag' => [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'description' => $tag->description,
            ]
        ];
        self::trigger_webhook($data);
    }

    /**
     * Trigger webhook when a tag is deleted.
     *
     * @param int $term_id Term ID of the deleted tag.
     */
    public function trigger_webhook_on_tag_delete($term_id) {
        $data = [
            'action' => 'tag_deleted',
            'domain' => esc_url(home_url()),
            'tag_id' => $term_id
        ];
        self::trigger_webhook($data);
    }

    /**
     * Trigger webhook when a new author or user with author capabilities is registered.
     *
     * @param int $user_id The ID of the newly registered user.
     */
    public function trigger_webhook_on_author_register($user_id) {
        $user = get_userdata($user_id);
        if ($user && (in_array('author', $user->roles, true) || user_can($user, 'edit_posts'))) {
            $data = [
                'action' => 'author_registered',
                'domain' => esc_url(home_url()),
                'author' => [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'role' => $user->roles,
                ],
            ];
            self::trigger_webhook($data);
        }
    }

    /**
     * Trigger webhook when an author's profile or user with author capabilities is updated.
     *
     * @param int $user_id The ID of the updated user.
     * @param WP_User $old_user_data The old user data before the update.
     */
    public function trigger_webhook_on_author_update($user_id, $old_user_data) {
        $user = get_userdata($user_id);
        if ($user && (in_array('author', $user->roles, true) || user_can($user, 'edit_posts'))) {
            $data = [
                'action' => 'author_updated',
                'domain' => esc_url(home_url()),
                'author' => [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'role' => $user->roles,
                ],
            ];
            self::trigger_webhook($data);
        }
    }

    /**
     * Trigger webhook when an author or user with author capabilities is deleted.
     *
     * @param int $user_id The ID of the deleted user.
     */
    public function trigger_webhook_on_author_delete($user_id) {
        $user = get_userdata($user_id);
        if ($user && (in_array('author', $user->roles, true) || user_can($user, 'edit_posts'))) {
            $data = [
                'action' => 'author_deleted',
                'domain' => esc_url(home_url()),
                'author_id' => $user->ID,
            ];
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