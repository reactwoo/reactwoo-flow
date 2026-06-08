<?php
/**
 * WordPress plugin updates via api.reactwoo.com.
 *
 * ReactWoo Flow is a free catalog slug (see API UPDATES_FREE_SLUGS); no license JWT is sent.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers ReactWoo Flow with the ReactWoo updates API.
 */
class RWF_Updater {
	const CATALOG_SLUG = 'reactwoo-flow';

	/**
	 * @var bool
	 */
	private static $hooks_added = false;

	/**
	 * Wire update checker hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$hooks_added ) {
			return;
		}
		self::$hooks_added = true;

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'filter_update_transient' ), 10, 1 );
		add_filter( 'plugins_api', array( __CLASS__, 'filter_plugins_api' ), 10, 3 );
	}

	/**
	 * Default ReactWoo API base URL.
	 *
	 * @return string
	 */
	public static function get_api_base() {
		$base = apply_filters( 'rwf_updates_api_base', 'https://api.reactwoo.com' );

		return untrailingslashit( is_string( $base ) ? trim( $base ) : 'https://api.reactwoo.com' );
	}

	/**
	 * @param stdClass $transient Update plugins transient.
	 * @return stdClass
	 */
	public static function filter_update_transient( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) || ! is_array( $transient->checked ) ) {
			return $transient;
		}

		$basename = plugin_basename( RWF_PLUGIN_FILE );
		if ( ! isset( $transient->checked[ $basename ] ) ) {
			return $transient;
		}

		$current = defined( 'RWF_VERSION' ) ? (string) RWF_VERSION : '';
		$offer   = self::request_update_offer( $current );

		if ( null === $offer ) {
			return $transient;
		}

		$plugin_info              = new stdClass();
		$plugin_info->slug        = self::CATALOG_SLUG;
		$plugin_info->plugin      = $basename;
		$plugin_info->new_version = $offer['version'];
		$plugin_info->package     = $offer['package'];

		if ( ! empty( $offer['tested'] ) ) {
			$plugin_info->tested = $offer['tested'];
		}
		if ( ! empty( $offer['requires'] ) ) {
			$plugin_info->requires = $offer['requires'];
		}

		$transient->response[ $basename ] = $plugin_info;

		return $transient;
	}

	/**
	 * @param false|object|array $result Result.
	 * @param string             $action API action.
	 * @param object             $args   Request args.
	 * @return false|object|array
	 */
	public static function filter_plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || self::CATALOG_SLUG !== sanitize_key( (string) $args->slug ) ) {
			return $result;
		}

		$data = self::request_check_payload( '0.0.0' );
		if ( null === $data || empty( $data['version'] ) ) {
			return $result;
		}

		$info               = new stdClass();
		$info->name         = __( 'ReactWoo Flow', 'reactwoo-flow' );
		$info->slug         = self::CATALOG_SLUG;
		$info->version      = (string) $data['version'];
		$info->requires     = ! empty( $data['min_wp'] ) ? (string) $data['min_wp'] : '';
		$info->tested       = ! empty( $data['tested_up_to'] ) ? (string) $data['tested_up_to'] : '';
		$info->requires_php = ! empty( $data['min_php'] ) ? (string) $data['min_php'] : '';
		$info->author       = '<a href="https://reactwoo.com">ReactWoo</a>';
		$info->homepage     = 'https://reactwoo.com';
		$info->sections     = array(
			'description' => __( 'Agent-orchestrated product intake and support operations platform for ReactWoo.', 'reactwoo-flow' ),
			'changelog'   => ! empty( $data['changelog_html'] ) ? (string) $data['changelog_html'] : '',
		);

		return $info;
	}

	/**
	 * @param string $current_version Installed version.
	 * @return array{version: string, package: string, tested?: string, requires?: string}|null
	 */
	private static function request_update_offer( $current_version ) {
		$data = self::request_check_payload( $current_version );
		if ( null === $data || empty( $data['update'] ) || empty( $data['version'] ) || empty( $data['download_url'] ) ) {
			return null;
		}
		if ( version_compare( $current_version, (string) $data['version'], '>=' ) ) {
			return null;
		}

		$out = array(
			'version' => (string) $data['version'],
			'package' => (string) $data['download_url'],
		);
		if ( ! empty( $data['tested_up_to'] ) ) {
			$out['tested'] = (string) $data['tested_up_to'];
		}
		if ( ! empty( $data['min_wp'] ) ) {
			$out['requires'] = (string) $data['min_wp'];
		}

		return $out;
	}

	/**
	 * @param string $current_version Version sent to the API.
	 * @return array<string, mixed>|null
	 */
	private static function request_check_payload( $current_version ) {
		$api_base = self::get_api_base();
		if ( '' === $api_base ) {
			return null;
		}

		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$site_host = is_string( $site_host ) && '' !== $site_host ? $site_host : 'localhost';

		$response = wp_remote_post(
			trailingslashit( $api_base ) . 'api/v5/updates/check',
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'slug'            => self::CATALOG_SLUG,
						'current_version' => (string) $current_version,
						'channel'         => 'stable',
						'site_host'       => $site_host,
					)
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $data ) ? $data : null;
	}
}
