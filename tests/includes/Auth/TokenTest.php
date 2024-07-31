<?php

namespace VPlugins\SMPostConnector\Tests\Auth;

use VPlugins\SMPostConnector\Auth\Token;
use WP_Mock\Tools\TestCase;

class TokenTest extends TestCase {

    private $token;

    public function setUp(): void {
        \WP_Mock::setUp();
        $this->token = new Token();
    }

    public function tearDown(): void {
        \WP_Mock::tearDown();
    }

    public function test_validate_token_with_correct_token() {
        $test_token = bin2hex(random_bytes(16));
        
        // Mock the get_option function to return a specific token
        \WP_Mock::userFunction('get_option', [
            'args' => ['sm_post_connector_token'],
            'return' => $test_token
        ]);

        // Call the validate_token method with the correct token
        $result = $this->token->validate_token($test_token);

        // Assert that the result is true
        $this->assertTrue($result);
    }

}