<?php
/**
 * Silent upgrader skin for WP Dependency Installer.
 *
 * @link https://github.com/afragen/wp-dependency-installer
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

class WP_Dependency_Installer_Skin extends Plugin_Installer_Skin {
	public function header() {}
	public function footer() {}
	public function error( $errors ) {}
	public function feedback( $string, ...$args ) {}
}
