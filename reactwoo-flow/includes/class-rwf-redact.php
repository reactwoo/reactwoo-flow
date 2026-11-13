<?php
/**
 * Redaction of personal data and secrets before context leaves ReactWoo.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes personal data, licence keys and secrets from context sent to AI
 * providers, Cursor, or any other external agent.
 *
 * Two layers:
 * 1. Field exclusion: whole fields that identify a customer or hold a secret
 *    are never included (see get_excluded_fields()).
 * 2. Text redaction: emails, URLs, IP addresses, key-like strings and
 *    password/token assignments are replaced inside every remaining string.
 */
class RWF_Redact {
	const EMAIL  = '[email]';
	const URL    = '[url]';
	const IP     = '[ip]';
	const KEY    = '[key]';
	const SECRET = '[secret]';

	/**
	 * Item fields never sent to external agents.
	 *
	 * @return string[]
	 */
	public static function get_excluded_fields() {
		$fields = array(
			'customer_email',
			'reporter',
			'license_key',
			'site_url',
			'screenshots',
			'log_files',
		);

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter the item fields withheld from external agents.
			 *
			 * Removing a field from this list sends it to AI providers. Only do
			 * that when a data processing agreement covers the provider.
			 *
			 * @param string[] $fields Field keys.
			 */
			$fields = (array) apply_filters( 'rwf_ai_excluded_fields', $fields );
		}

		return array_values( array_map( 'strval', $fields ) );
	}

	/**
	 * Whether a field is withheld from external agents.
	 *
	 * @param string $field_key Field key.
	 * @return bool
	 */
	public static function is_excluded_field( $field_key ) {
		return in_array( (string) $field_key, self::get_excluded_fields(), true );
	}

	/**
	 * Hosts whose URLs stay readable: our own systems and delivery tools.
	 *
	 * @return string[]
	 */
	public static function get_safe_url_hosts() {
		$hosts = array( 'github.com', 'api.github.com', 'reactwoo.com', 'atlassian.net' );

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter hosts whose URLs are not redacted. A host also covers its subdomains.
			 *
			 * @param string[] $hosts Host names.
			 */
			$hosts = (array) apply_filters( 'rwf_ai_safe_url_hosts', $hosts );
		}

		return array_values( array_map( 'strtolower', array_map( 'strval', $hosts ) ) );
	}

	/**
	 * Redact every string inside a value, recursively.
	 *
	 * Array entries keyed by an excluded field are removed, which also covers
	 * older stored context (agent run history) built before redaction existed.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public static function redact_value( $value ) {
		if ( is_string( $value ) ) {
			return self::redact_text( $value );
		}

		if ( is_array( $value ) ) {
			$excluded = self::get_excluded_fields();

			foreach ( $value as $key => $item ) {
				if ( is_string( $key ) && in_array( $key, $excluded, true ) ) {
					unset( $value[ $key ] );
					continue;
				}

				$value[ $key ] = self::redact_value( $item );
			}
		}

		return $value;
	}

	/**
	 * Whether a URL points at a safe host.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_safe_url( $url ) {
		$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		if ( '' === $host ) {
			return false;
		}

		foreach ( self::get_safe_url_hosts() as $safe ) {
			if ( $host === $safe || substr( $host, -strlen( '.' . $safe ) ) === '.' . $safe ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Redact personal data and secrets inside free text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function redact_text( $text ) {
		$text = (string) $text;

		if ( '' === $text ) {
			return $text;
		}

		// URLs first, so a token inside a query string goes with its URL.
		$text = preg_replace_callback(
			'#\b(?:https?|ftp)://[^\s<>"\'\)\]]+#i',
			static function ( $match ) {
				if ( self::is_safe_url( $match[0] ) && false === strpos( $match[0], '?' ) ) {
					return $match[0];
				}

				return self::URL;
			},
			$text
		);

		// Password, token or key assignments: "password: hunter2", "api_key=abc".
		$text = preg_replace(
			'/\b(pass(?:word|wd)?|pwd|secret|token|api[_\- ]?key|licen[cs]e[_\- ]?key|auth(?:orization)?)(\s*[:=]\s*|\s+is\s+)("[^"]*"|\'[^\']*\'|(?:(?:Bearer|Basic)\s+)?\S+)/i',
			'$1$2' . self::SECRET,
			$text
		);

		// Bearer / Basic auth headers.
		$text = preg_replace( '/\b(Bearer|Basic)\s+[A-Za-z0-9\-\._~\+\/]+=*/', '$1 ' . self::SECRET, $text );

		// Email addresses.
		$text = preg_replace( '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', self::EMAIL, $text );

		// IPv4 addresses. Version numbers such as 8.2.34 have three parts, so they are kept.
		$text = preg_replace( '/\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b/', self::IP, $text );

		// UUIDs.
		$text = preg_replace( '/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i', self::KEY, $text );

		// Licence-key shaped strings: groups of 4+ letters/digits joined by dashes, at least 4 groups.
		$text = preg_replace( '/\b[A-Z0-9]{4,}(?:-[A-Z0-9]{4,}){3,}\b/i', self::KEY, $text );

		// Long opaque tokens: 24+ characters with at least 4 digits and 4 letters.
		// Code identifiers such as woocommerce_checkout_update_order_review are left alone.
		$text = preg_replace_callback(
			'/\b[A-Za-z0-9_\-]{24,}\b/',
			static function ( $match ) {
				$token = $match[0];
				if ( preg_match_all( '/[0-9]/', $token ) >= 4 && preg_match_all( '/[A-Za-z]/', $token ) >= 4 ) {
					return self::KEY;
				}

				return $token;
			},
			$text
		);

		return $text;
	}
}
