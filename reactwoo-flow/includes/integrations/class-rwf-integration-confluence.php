<?php
/**
 * Confluence Cloud REST integration.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Publishes specifications to Confluence.
 */
class RWF_Integration_Confluence {
	/**
	 * Whether Confluence credentials are configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::get_wiki_base_url()
			&& '' !== RWF_Settings::get( 'rwf_confluence_space_key' )
			&& '' !== RWF_Settings::get( 'rwf_jira_email' )
			&& '' !== RWF_Settings::get( 'rwf_jira_api_token' );
	}

	/**
	 * Publish the item specification to Confluence.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function publish_specification( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		if ( ! self::is_configured() ) {
			return new WP_Error( 'rwf_confluence_not_configured', __( 'Confluence is not configured. Add Jira credentials and a Confluence space key in Settings.', 'reactwoo-flow' ) );
		}

		$markdown = RWF_CPT::get_meta( $post_id, 'specification_markdown' );
		if ( '' === trim( $markdown ) ) {
			return new WP_Error( 'rwf_confluence_no_spec', __( 'Generate a specification before publishing to Confluence.', 'reactwoo-flow' ) );
		}

		$title = sprintf(
			/* translators: %s: item title */
			__( 'Flow Spec: %s', 'reactwoo-flow' ),
			get_the_title( $post )
		);

		$payload = array(
			'type'  => 'page',
			'title' => $title,
			'space' => array(
				'key' => RWF_Settings::get( 'rwf_confluence_space_key' ),
			),
			'body'  => array(
				'storage' => array(
					'value'          => self::markdown_to_storage_html( $markdown ),
					'representation' => 'storage',
				),
			),
		);

		$parent_id = RWF_Settings::get( 'rwf_confluence_parent_page_id' );
		if ( '' !== $parent_id ) {
			$payload['ancestors'] = array(
				array( 'id' => (int) $parent_id ),
			);
		}

		$result = RWF_Integration_Http::request_json(
			'POST',
			trailingslashit( self::get_wiki_base_url() ) . 'rest/api/content',
			array(
				'headers' => array(
					'Authorization' => self::auth_header(),
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$data = is_array( $result['data'] ) ? $result['data'] : array();
		$page_id = isset( $data['id'] ) ? (string) $data['id'] : '';
		$page_url = '';

		if ( isset( $data['_links']['webui'] ) && is_string( $data['_links']['webui'] ) ) {
			$site = untrailingslashit( RWF_Settings::get( 'rwf_jira_url' ) );
			$page_url = $site . $data['_links']['webui'];
		}

		if ( '' !== $page_id ) {
			RWF_CPT::update_meta( $post_id, 'confluence_page_id', $page_id );
		}
		if ( '' !== $page_url ) {
			RWF_CPT::update_meta( $post_id, 'confluence_page_url', $page_url );
		}

		return array(
			'confluence_page_id'  => $page_id,
			'confluence_page_url' => $page_url,
			'title'               => $title,
		);
	}

	/**
	 * @return string
	 */
	private static function get_wiki_base_url() {
		$jira_url = untrailingslashit( RWF_Settings::get( 'rwf_jira_url' ) );
		if ( '' === $jira_url ) {
			return '';
		}

		return $jira_url . '/wiki';
	}

	/**
	 * @return string
	 */
	private static function auth_header() {
		$email = RWF_Settings::get( 'rwf_jira_email' );
		$token = RWF_Settings::get( 'rwf_jira_api_token' );

		return 'Basic ' . base64_encode( $email . ':' . $token ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * @param string $markdown Specification markdown.
	 * @return string
	 */
	public static function markdown_to_storage_html( $markdown ) {
		$escaped = esc_html( (string) $markdown );

		return '<pre>' . $escaped . '</pre>';
	}
}
