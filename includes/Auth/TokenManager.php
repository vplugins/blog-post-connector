<?php

namespace VPlugins\BlogPostConnector\Auth;

use VPlugins\BlogPostConnector\Admin\TokenSettingsPage;

/**
 * Class TokenManager
 *
 * Handles token generation, validation, and plugin settings page for the Blog Post Connector plugin.
 */
class TokenManager {

    /**
     * Generates a new random token and saves it in the database.
     *
     * @return string The generated token.
     */
    public function generate_token() {
        $token = bin2hex(random_bytes(16));
        update_option('sm_post_connector_token', $token);
        return $token;
    }

    /**
     * Validates a given token against the stored token.
     *
     * @param string $token The token to validate.
     * @return bool True if the token matches the stored token, false otherwise.
     */
    public function validate_token($token) {
        $saved_token = get_option('sm_post_connector_token');
        return hash_equals($saved_token, $token);
    }

}