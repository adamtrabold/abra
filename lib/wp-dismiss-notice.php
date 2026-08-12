<?php
/**
 * WP Dismiss Notice — bundled for Abra, JS path hardcoded to lib/js/.
 *
 * @link https://github.com/afragen/wp-dismiss-notice
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_Dismiss_Notice {

	public static function init() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'load_script' ] );
		add_action( 'wp_ajax_wp_dismiss_notice', [ __CLASS__, 'dismiss_admin_notice' ] );
	}

	public static function load_script() {
		if ( is_customize_preview() ) {
			return;
		}
		wp_enqueue_script(
			'dismissible-notices',
			get_theme_file_uri( '/lib/js/dismiss-notice.js' ),
			[ 'jquery', 'common' ],
			'1.0.4',
			true
		);
		wp_localize_script(
			'dismissible-notices',
			'wp_dismiss_notice',
			[
				'nonce'   => wp_create_nonce( 'wp-dismiss-notice' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	public static function dismiss_admin_notice() {
		$option_name        = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : false;
		$dismissible_length = isset( $_POST['dismissible_length'] ) ? sanitize_text_field( wp_unslash( $_POST['dismissible_length'] ) ) : 14;
		if ( 'forever' !== $dismissible_length ) {
			$dismissible_length = ( 0 === absint( $dismissible_length ) ) ? 14 : $dismissible_length;
			$dismissible_length = strtotime( absint( $dismissible_length ) . ' days' );
		}
		check_ajax_referer( 'wp-dismiss-notice', 'nonce' );
		self::set_admin_notice_cache( $option_name, $dismissible_length );
		wp_die();
	}

	public static function is_admin_notice_active( $arg ) {
		$array = explode( '-', $arg );
		array_pop( $array );
		$option_name = implode( '-', $array );
		$db_record   = self::get_admin_notice_cache( $option_name );
		if ( 'forever' === $db_record ) {
			return false;
		} elseif ( absint( $db_record ) >= time() ) {
			return false;
		} else {
			return true;
		}
	}

	public static function get_admin_notice_cache( $id = false ) {
		if ( ! $id ) {
			return false;
		}
		$cache_key = 'wpdn-' . md5( $id );
		$timeout   = get_site_option( $cache_key );
		$timeout   = 'forever' === $timeout ? time() + 60 : $timeout;
		if ( empty( $timeout ) || time() > $timeout ) {
			return false;
		}
		return $timeout;
	}

	public static function set_admin_notice_cache( $id, $timeout ) {
		$cache_key = 'wpdn-' . md5( $id );
		update_site_option( $cache_key, $timeout );
		return true;
	}
}

add_action( 'admin_init', [ 'WP_Dismiss_Notice', 'init' ] );
