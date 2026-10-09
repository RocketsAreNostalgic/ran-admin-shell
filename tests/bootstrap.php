<?php
/** Test bootstrap. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param mixed $value Synthetic escaping input, converted to a string.
	 * @return string
	 */
	function esc_html( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param mixed $value Synthetic escaping input, converted to a string.
	 * @return string
	 */
	function esc_attr( $value ) {
		return esc_html( $value );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * @param mixed $value Synthetic escaping input, converted to a string.
	 * @return string
	 */
	function esc_url( $value ) {
		$value = filter_var( (string) $value, FILTER_SANITIZE_URL );
		return esc_attr( $value );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $value Synthetic JSON input.
	 * @param int $flags Native JSON flags.
	 * @return string|false
	 */
	function wp_json_encode( $value, $flags = 0 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Controlled WordPress JSON stub delegates to native JSON without calling itself recursively.
		return json_encode( $value, $flags );
	}
}

require_once dirname( __DIR__ ) . '/tools/SyncCommand.php';
