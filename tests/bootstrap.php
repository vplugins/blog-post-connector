<?php

// First we need to load the composer autoloader so we can use WP Mock
require_once __DIR__ . '/../vendor/autoload.php';

// Now call the bootstrap method of WP Mock
WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

define( 'SM_PLUGIN_BASENAME', basename( __DIR__ . '/../blog-post-connector.php' ) );

if ( defined( 'WP_TESTS_MULTISITE' ) ) {
	// Tells the plugin it is network active.
	define( 'SM_IS_NETWORK', true );
} else {
	define( 'SM_IS_NETWORK', false );
}

/**
 * WP_Mock ships no WordPress classes, so stub the few the plugin constructs
 * directly. Only classes are stubbed here, never functions -- functions must
 * stay undefined so tests can mock them via WP_Mock::userFunction().
 */
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		protected $data;
		protected $status;

		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status() {
			return $this->status;
		}
	}
}

/**
 * Stand-in for the users get_users() and get_user_by() return. Mirrors the real
 * WP_User's public properties and magic getter, so a test sees every field a
 * real user object carries, including user_pass and user_email.
 */
if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		public $data;
		public $ID = 0;
		public $caps = array();
		public $cap_key;
		public $roles = array();
		public $allcaps = array();
		public $filter = null;

		public function __construct( $data = array() ) {
			$this->data = (object) $data;
			$this->ID   = isset( $this->data->ID ) ? (int) $this->data->ID : 0;
		}

		public function __get( $key ) {
			return isset( $this->data->$key ) ? $this->data->$key : null;
		}

		public function __isset( $key ) {
			return isset( $this->data->$key );
		}
	}
}

/**
 * Stand-in for the WP_Query the post handler builds to look for a post with the same
 * title. It never finds one, so a create request proceeds to the insert.
 */
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public $query_vars;

		public function __construct( $query = array() ) {
			$this->query_vars = $query;
		}

		public function have_posts() {
			return false;
		}
	}
}

/**
 * The plugin's classes are all reachable through Composer's PSR-4 autoloader,
 * so the main plugin file is deliberately not required here: it calls
 * register_activation_hook() and other WordPress functions at the top level,
 * which WP_Mock does not define, and requiring it aborts the whole run.
 */
