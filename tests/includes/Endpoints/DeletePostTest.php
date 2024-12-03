<?php

namespace VPlugins\BlogPostConnector\Tests\Endpoints;

use VPlugins\BlogPostConnector\Endpoints\DeletePost;
use WP_Mock\Tools\TestCase;
use WP_REST_Request;
use WP_REST_Response;

class DeletePostTest extends TestCase {

    /**
     * @var DeletePost
     */
    private $deletePost;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->deletePost = new DeletePost();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for deleting a post is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/delete-post',
                [
                    'methods' => 'DELETE',
                    'callback' => [$this->deletePost, 'delete_post'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->deletePost->register_routes();

        \WP_Mock::assertHooksAdded();
    }

}