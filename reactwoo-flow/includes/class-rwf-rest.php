<?php
/**
 * REST API endpoints.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers ReactWoo Flow REST endpoints.
 */
class RWF_REST {
	const NAMESPACE = 'reactwoo-flow/v1';

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/items/(?P<id>\d+)/analyse',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'analyse_item' ),
				'permission_callback' => array( __CLASS__, 'can_analyse_item' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/items/(?P<id>\d+)/generate-specification',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'generate_specification' ),
				'permission_callback' => array( __CLASS__, 'can_analyse_item' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/items/(?P<id>\d+)/prepare-development-handoff',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'prepare_development_handoff' ),
				'permission_callback' => array( __CLASS__, 'can_analyse_item' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Permission check for analysis endpoint.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_analyse_item( $request ) {
		$post_id = absint( $request['id'] );

		return $post_id && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Analyze an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function analyse_item( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::analyse_and_save( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'     => true,
				'item_id'     => $post_id,
				'analysis'    => $result,
				'analysed_at' => RWF_CPT::get_meta( $post_id, 'ai_analyzed_at' ),
			)
		);
	}

	/**
	 * Generate a Markdown specification for an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_specification( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::generate_specification_and_save( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'item_id'      => $post_id,
				'specification' => $result,
				'generated_at' => RWF_CPT::get_meta( $post_id, 'specification_generated_at' ),
			)
		);
	}

	/**
	 * Prepare a development handoff package for Cursor/MCP.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function prepare_development_handoff( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::prepare_development_handoff_and_save( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'     => true,
				'item_id'     => $post_id,
				'handoff'     => $result,
				'prepared_at' => RWF_CPT::get_meta( $post_id, 'development_handoff_prepared_at' ),
			)
		);
	}
}
