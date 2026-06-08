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
			'/items',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'list_items' ),
				'permission_callback' => array( __CLASS__, 'can_list_items' ),
				'args'                => array(
					's'           => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'product'     => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'item_type'   => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'status'      => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'integration' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'per_page'    => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/items/(?P<id>\d+)/context',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_item_context' ),
				'permission_callback' => array( __CLASS__, 'can_read_item' ),
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
			'/items/(?P<id>\d+)/analyse',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'analyse_item' ),
				'permission_callback' => array( __CLASS__, 'can_analyse_item' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					self::agent_override_args()
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
				'args'                => array_merge(
					array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					self::agent_override_args()
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
				'args'                => array_merge(
					array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					self::agent_override_args()
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/items/(?P<id>\d+)/generate-release-notes',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'generate_release_notes' ),
				'permission_callback' => array( __CLASS__, 'can_analyse_item' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
					self::agent_override_args()
				),
			)
		);

		$integration_routes = array(
			'/integrations/jira/create-issue'           => 'create_jira_issue',
			'/integrations/github/sync-pull-request'    => 'sync_github_pull_request',
			'/integrations/confluence/publish-specification' => 'publish_confluence_specification',
			'/integrations/cursor/send-handoff'         => 'send_cursor_handoff',
			'/integrations/jira/sync-status'            => 'sync_jira_status',
			'/run-qa-review'                            => 'run_qa_review',
			'/run-ux-review'                            => 'run_ux_review',
			'/apply-triage-suggestions'                 => 'apply_triage_suggestions',
		);

		foreach ( $integration_routes as $route => $callback ) {
			$args = array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			);
			if ( in_array( $callback, array( 'run_qa_review', 'run_ux_review' ), true ) ) {
				$args = array_merge( $args, self::agent_override_args() );
			}

			register_rest_route(
				self::NAMESPACE,
				'/items/(?P<id>\d+)' . $route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, $callback ),
					'permission_callback' => array( __CLASS__, 'can_analyse_item' ),
					'args'                => $args,
				)
			);
		}

		register_rest_route(
			self::NAMESPACE,
			'/integrations/github/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'github_webhook' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/integrations/health',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_integration_health' ),
					'permission_callback' => array( __CLASS__, 'can_manage_settings' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'test_integration_health' ),
					'permission_callback' => array( __CLASS__, 'can_manage_settings' ),
				),
			)
		);
	}

	/**
	 * Optional provider/model override arguments for agent endpoints.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function agent_override_args() {
		return array(
			'provider' => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_key',
			),
			'model'    => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Parse optional agent overrides from a REST request body.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, string>
	 */
	private static function parse_agent_overrides_from_request( $request ) {
		$overrides = array();
		$provider  = $request->get_param( 'provider' );
		$model     = $request->get_param( 'model' );

		if ( is_string( $provider ) && '' !== $provider ) {
			$overrides['provider'] = $provider;
		}
		if ( is_string( $model ) && '' !== trim( $model ) ) {
			$overrides['model'] = $model;
		}

		return $overrides;
	}

	/**
	 * Permission check for analysis endpoint.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_analyse_item( $request ) {
		$post_id = absint( $request['id'] );

		return $post_id && RWF_Capabilities::can_edit_item( $post_id );
	}

	/**
	 * Permission check for read-only item context endpoints.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_read_item( $request ) {
		$post_id = absint( $request['id'] );

		return $post_id && RWF_Capabilities::can_edit_item( $post_id );
	}

	/**
	 * Permission check for integration health endpoints.
	 *
	 * @return bool
	 */
	public static function can_manage_settings() {
		return RWF_Capabilities::can_manage();
	}

	/**
	 * Permission check for item list endpoint.
	 *
	 * @return bool
	 */
	public static function can_list_items() {
		return RWF_Capabilities::can_edit_items();
	}

	/**
	 * List flow items with optional filters.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_items( $request ) {
		$filters = array(
			's'           => (string) $request->get_param( 's' ),
			'product'     => (string) $request->get_param( 'product' ),
			'item_type'   => (string) $request->get_param( 'item_type' ),
			'status'      => (string) $request->get_param( 'status' ),
			'integration' => (string) $request->get_param( 'integration' ),
		);
		$per_page = absint( $request->get_param( 'per_page' ) );
		$query    = RWF_Admin::build_items_query( $filters, $per_page ? $per_page : 50 );
		$items    = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$summary = RWF_Admin::format_item_summary( get_the_ID() );
				if ( ! empty( $summary ) ) {
					$items[] = $summary;
				}
			}
			wp_reset_postdata();
		}

		return rest_ensure_response(
			array(
				'items' => $items,
				'total' => (int) $query->found_posts,
			)
		);
	}

	/**
	 * Get a structured item context package.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_item_context( $request ) {
		$post_id = absint( $request['id'] );
		$post    = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $post_id,
				'context' => RWF_AI::build_cursor_context( $post_id ),
			)
		);
	}

	/**
	 * Analyze an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function analyse_item( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::analyse_and_save( $post_id, self::parse_agent_overrides_from_request( $request ) );

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
		$result  = RWF_AI::generate_specification_and_save( $post_id, self::parse_agent_overrides_from_request( $request ) );

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
	 * Generate Markdown release notes for an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_release_notes( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::generate_release_notes_and_save( $post_id, self::parse_agent_overrides_from_request( $request ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'item_id'      => $post_id,
				'release_notes' => $result,
				'generated_at' => RWF_CPT::get_meta( $post_id, 'release_notes_generated_at' ),
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
		$result  = RWF_AI::prepare_development_handoff_and_save( $post_id, self::parse_agent_overrides_from_request( $request ) );

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

	/**
	 * Create a Jira issue from an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_jira_issue( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_Integration_Jira::create_issue_from_item( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $post_id,
				'jira'    => $result,
			)
		);
	}

	/**
	 * Sync GitHub pull request metadata for an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function sync_github_pull_request( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_Integration_GitHub::sync_pull_request( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $post_id,
				'github'  => $result,
			)
		);
	}

	/**
	 * Publish an item specification to Confluence.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function publish_confluence_specification( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_Integration_Confluence::publish_specification( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'    => true,
				'item_id'    => $post_id,
				'confluence' => $result,
			)
		);
	}

	/**
	 * Send a development handoff payload to Cursor MCP.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function send_cursor_handoff( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_Integration_Cursor_MCP::send_handoff( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $post_id,
				'cursor'  => $result,
			)
		);
	}

	/**
	 * Sync linked Jira issue status.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function sync_jira_status( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_Integration_Jira::sync_issue_status( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $post_id,
				'jira'    => $result,
			)
		);
	}

	/**
	 * Run the QA review agent for an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function run_qa_review( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::generate_qa_review_and_save( $post_id, self::parse_agent_overrides_from_request( $request ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'    => true,
				'item_id'    => $post_id,
				'qa_review'  => $result,
				'generated_at' => RWF_CPT::get_meta( $post_id, 'qa_review_generated_at' ),
			)
		);
	}

	/**
	 * Run the UX review agent for an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function run_ux_review( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::generate_ux_review_and_save( $post_id, self::parse_agent_overrides_from_request( $request ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'    => true,
				'item_id'    => $post_id,
				'ux_review'  => $result,
				'generated_at' => RWF_CPT::get_meta( $post_id, 'ux_review_generated_at' ),
			)
		);
	}

	/**
	 * Return integration configuration and last connectivity test results.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_integration_health() {
		return rest_ensure_response(
			array(
				'summary'    => RWF_Integrations::get_configuration_summary(),
				'last_test'  => RWF_Integrations::get_last_test_results(),
				'tested_at'  => get_option( 'rwf_integration_health_last_test', '' ),
			)
		);
	}

	/**
	 * Run remote connectivity tests for configured integrations.
	 *
	 * @return WP_REST_Response
	 */
	public static function test_integration_health() {
		$results = RWF_Integrations::test_connections();

		return rest_ensure_response(
			array(
				'success'   => true,
				'results'   => $results,
				'tested_at' => get_option( 'rwf_integration_health_last_test', '' ),
			)
		);
	}

	/**
	 * Receive GitHub webhook events.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function github_webhook( $request ) {
		$body      = $request->get_body();
		$signature = (string) $request->get_header( 'x_hub_signature_256' );
		$event     = (string) $request->get_header( 'x_github_event' );

		if ( ! RWF_Integration_GitHub::verify_webhook_signature( $body, $signature ) ) {
			return new WP_Error( 'rwf_github_webhook_unauthorized', __( 'Invalid GitHub webhook signature.', 'reactwoo-flow' ), array( 'status' => 401 ) );
		}

		$payload = json_decode( $body, true );
		$result  = RWF_Integration_GitHub::handle_webhook_event( $event, is_array( $payload ) ? $payload : array() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'event'   => $event,
				'result'  => $result,
			)
		);
	}

	/**
	 * Apply triage delivery hints to integration fields.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function apply_triage_suggestions( $request ) {
		$post_id = absint( $request['id'] );
		$result  = RWF_AI::apply_triage_suggestions( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $post_id,
				'applied' => $result,
			)
		);
	}
}
