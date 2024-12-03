<?php

namespace VPlugins\SMPostConnector\Tests\Endpoints;

use VPlugins\SMPostConnector\Endpoints\UpdatePost;
use WP_Mock\Tools\TestCase;
use WP_REST_Request;
use WP_REST_Response;

class UpdatePostTest extends TestCase {

    /**
     * @var UpdatePost
     */
    private $updatePost;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->updatePost = new UpdatePost();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for updating a post is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/update-post',
                [
                    'methods' => 'POST',
                    'callback' => [$this->updatePost, 'update_post'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->updatePost->register_routes();

        \WP_Mock::assertHooksAdded();
    }

}