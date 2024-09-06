<?php

namespace VPlugins\SMPostConnector\Tests\Endpoints;

use VPlugins\SMPostConnector\Endpoints\CreatePost;
use WP_Mock\Tools\TestCase;

class CreatePostTest extends TestCase {

    /**
     * @var CreatePost
     */
    private $createPost;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->createPost = new CreatePost();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for creating a post is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/create-post',
                [
                    'methods' => 'POST',
                    'callback' => [$this->createPost, 'create_post'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->createPost->register_routes();

        \WP_Mock::assertHooksAdded();
    }

}