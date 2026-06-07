<?php
/**
 * File upload helpers for intake and attachments.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles public intake file uploads into the WordPress media library.
 */
class RWF_Uploads {
	const MAX_SCREENSHOTS = 5;
	const MAX_LOG_FILES   = 3;

	/**
	 * Process intake form uploads and append attachment URLs to item meta.
	 *
	 * @param int $post_id Item post ID.
	 */
	public static function handle_intake_uploads( $post_id ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$screenshot_urls = self::upload_file_group(
			'rwf_screenshot_files',
			self::get_screenshot_mimes(),
			self::MAX_SCREENSHOTS
		);
		$log_urls        = self::upload_file_group(
			'rwf_log_file_uploads',
			self::get_log_mimes(),
			self::MAX_LOG_FILES
		);

		if ( ! empty( $screenshot_urls ) ) {
			self::append_meta_urls( $post_id, 'screenshots', $screenshot_urls );
		}

		if ( ! empty( $log_urls ) ) {
			self::append_meta_urls( $post_id, 'log_files', $log_urls );
		}
	}

	/**
	 * Upload a group of files from $_FILES.
	 *
	 * @param string $field_name Form field name.
	 * @param array  $mimes      Allowed mime types.
	 * @param int    $max_files  Maximum number of files.
	 * @return array Uploaded file URLs.
	 */
	private static function upload_file_group( $field_name, $mimes, $max_files ) {
		if ( empty( $_FILES[ $field_name ] ) || ! is_array( $_FILES[ $field_name ]['name'] ) ) {
			return array();
		}

		$urls  = array();
		$files = self::normalize_files_array( $_FILES[ $field_name ] );

		foreach ( array_slice( $files, 0, $max_files ) as $file ) {
			if ( empty( $file['name'] ) || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
				continue;
			}

			if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
				continue;
			}

			$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
			if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $mimes, true ) ) {
				continue;
			}

			$upload = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
					'mimes'     => $mimes,
				)
			);

			if ( isset( $upload['error'] ) ) {
				continue;
			}

			$attachment_id = wp_insert_attachment(
				array(
					'post_mime_type' => $upload['type'],
					'post_title'     => sanitize_file_name( wp_basename( $upload['file'] ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$upload['file']
			);

			if ( is_wp_error( $attachment_id ) ) {
				continue;
			}

			if ( wp_attachment_is_image( $attachment_id ) ) {
				wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
			}

			$url = wp_get_attachment_url( $attachment_id );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Normalize a multi-file $_FILES entry.
	 *
	 * @param array $file_entry Raw $_FILES entry.
	 * @return array
	 */
	private static function normalize_files_array( $file_entry ) {
		$normalized = array();
		$count      = count( $file_entry['name'] );

		for ( $index = 0; $index < $count; $index++ ) {
			$normalized[] = array(
				'name'     => $file_entry['name'][ $index ],
				'type'     => $file_entry['type'][ $index ],
				'tmp_name' => $file_entry['tmp_name'][ $index ],
				'error'    => $file_entry['error'][ $index ],
				'size'     => $file_entry['size'][ $index ],
			);
		}

		return $normalized;
	}

	/**
	 * Append URLs to a newline-delimited meta field.
	 *
	 * @param int    $post_id   Item post ID.
	 * @param string $field_key Meta field key.
	 * @param array  $urls      URLs to append.
	 */
	private static function append_meta_urls( $post_id, $field_key, $urls ) {
		$existing = RWF_CPT::get_meta( $post_id, $field_key );
		$lines    = array_filter( array_map( 'trim', explode( "\n", $existing ) ) );

		foreach ( $urls as $url ) {
			$lines[] = esc_url_raw( $url );
		}

		RWF_CPT::update_meta( $post_id, $field_key, implode( "\n", array_unique( $lines ) ) );
	}

	/**
	 * Allowed screenshot mime types.
	 *
	 * @return array
	 */
	private static function get_screenshot_mimes() {
		return array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);
	}

	/**
	 * Allowed log mime types.
	 *
	 * @return array
	 */
	private static function get_log_mimes() {
		return array(
			'txt'  => 'text/plain',
			'log'  => 'text/plain',
			'csv'  => 'text/csv',
		);
	}
}
