<?php

namespace VPlugins\SMPostConnector\Tests\Endpoints;

use VPlugins\SMPostConnector\Endpoints\GetCategories;
use WP_Mock\Tools\TestCase;
use WP_REST_Request;
use WP_REST_Response;

class GetCategoriesTest extends TestCase {

    /**
     * @var GetCategories
     */
    private $getCategories;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->getCategories = new GetCategories();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for retrieving categories is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/categories',
                [
                    'methods' => 'GET',
                    'callback' => [$this->getCategories, 'get_categories'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->getCategories->register_routes();

        \WP_Mock::assertHooksAdded();
    }

    
}