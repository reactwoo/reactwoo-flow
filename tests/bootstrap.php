<?php
/**
 * PHPUnit bootstrap: minimal WordPress stubs for ReactWoo Flow unit tests.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/reactwoo-flow/' );
define( 'RWF_PLUGIN_DIR', dirname( __DIR__ ) . '/reactwoo-flow/' );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/** @var string */
		public $code;
		/** @var string */
		public $message;
		/** @var mixed */
		public $data;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Optional data.
		 */
		public function __construct( $code = '', $message = '', $data = null ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		/** @var int */
		public $ID = 1;
		/** @var string */
		public $display_name = 'Test User';
	}
}

$GLOBALS['rwf_test_post_meta'] = array();
$GLOBALS['rwf_test_options']     = array();
$GLOBALS['rwf_test_caps']        = array();

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return $text;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return is_scalar( $str ) ? trim( (string) $str ) : '';
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return is_scalar( $str ) ? trim( (string) $str ) : '';
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return (int) abs( (float) $maybeint );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return trim( (string) $url );
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' );
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key, $single = false ) {
		$store = $GLOBALS['rwf_test_post_meta'];
		if ( ! isset( $store[ $post_id ][ $key ] ) ) {
			return $single ? '' : array();
		}
		$value = $store[ $post_id ][ $key ];
		return $single ? $value : array( $value );
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['rwf_test_post_meta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return array_key_exists( $option, $GLOBALS['rwf_test_options'] ) ? $GLOBALS['rwf_test_options'][ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		$GLOBALS['rwf_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, $object_id = 0 ) {
		$key = (string) $capability;
		if ( $object_id ) {
			$key .= ':' . (int) $object_id;
		}
		return ! empty( $GLOBALS['rwf_test_caps'][ $key ] );
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return new WP_User();
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type ) {
		return 'mysql' === $type ? '2026-06-07 12:00:00' : '2026-06-07T12:00:00+00:00';
	}
}

$GLOBALS['rwf_test_posts'] = array();

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return isset( $GLOBALS['rwf_test_posts'][ $post_id ] ) ? $GLOBALS['rwf_test_posts'][ $post_id ] : null;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return is_object( $post ) ? (string) $post->post_title : '';
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'get_post_time' ) ) {
	function get_post_time( $type, $gmt, $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return '2026-06-07T12:00:00+00:00';
	}
}

if ( ! function_exists( 'rwf_test_query_meta_ids' ) ) {
	/**
	 * @param array<int, array<string, mixed>> $meta_query Meta query clauses.
	 * @return int[]
	 */
	function rwf_test_query_meta_ids( $meta_query ) {
		$store = isset( $GLOBALS['rwf_test_post_meta'] ) ? $GLOBALS['rwf_test_post_meta'] : array();
		$ids   = array();

		foreach ( $store as $post_id => $meta ) {
			$match = true;
			foreach ( $meta_query as $clause ) {
				if ( ! is_array( $clause ) || empty( $clause['key'] ) ) {
					continue;
				}
				$key   = (string) $clause['key'];
				$value = isset( $clause['value'] ) ? (string) $clause['value'] : '';
				if ( ! isset( $meta[ $key ] ) || (string) $meta[ $key ] !== $value ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				$ids[] = (int) $post_id;
			}
		}

		return $ids;
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		/** @var int[] */
		public $posts = array();

		/**
		 * @param array<string, mixed> $args Query args.
		 */
		public function __construct( $args = array() ) {
			if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
				$this->posts = rwf_test_query_meta_ids( $args['meta_query'] );
			}
		}
	}
}

$base = dirname( __DIR__ ) . '/reactwoo-flow/includes/';
require_once $base . 'class-rwf-capabilities.php';
require_once $base . 'class-rwf-cpt.php';
require_once $base . 'class-rwf-settings.php';
require_once $base . 'class-rwf-agent.php';
require_once $base . 'providers/interface-rwf-provider.php';
require_once $base . 'providers/class-rwf-provider-openai.php';
require_once $base . 'providers/class-rwf-provider-anthropic.php';
require_once $base . 'providers/class-rwf-provider-cursor-mcp.php';
require_once $base . 'class-rwf-automation.php';
require_once $base . 'class-rwf-ai.php';
require_once $base . 'class-rwf-handoff-markdown.php';
require_once $base . 'class-rwf-rest.php';
require_once $base . 'integrations/class-rwf-integration-http.php';
require_once $base . 'integrations/class-rwf-integration-jira.php';
require_once $base . 'integrations/class-rwf-integration-github.php';
require_once $base . 'integrations/class-rwf-integration-confluence.php';
require_once $base . 'integrations/class-rwf-integration-cursor-mcp.php';
require_once $base . 'class-rwf-integrations.php';
