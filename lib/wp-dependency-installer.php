<?php
/**
 * WP Dependency Installer
 *
 * @package   WP_Dependency_Installer
 * @author    Andy Fragen, Matt Gibbs, Raruto
 * @license   MIT
 * @link      https://github.com/afragen/wp-dependency-installer
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WP_Dependency_Installer' ) ) {
	class WP_Dependency_Installer {
		private $config;
		private $current_slug;
		private static $caller;
		private static $source;
		private $notices;

		public static function instance( $caller = false ) {
			static $instance = null;
			if ( null === $instance ) {
				$instance = new self();
			}
			self::$caller = $caller;
			self::$source = ! $caller ? false : basename( $caller );
			return $instance;
		}

		private function __construct() {
			$this->config  = [];
			$this->notices = [];
		}

		public function load_hooks() {
			add_action( 'admin_init', [ $this, 'admin_init' ] );
			add_action( 'admin_footer', [ $this, 'admin_footer' ] );
			add_action( 'admin_notices', [ $this, 'admin_notices' ] );
			add_action( 'network_admin_notices', [ $this, 'admin_notices' ] );
			add_action( 'wp_ajax_dependency_installer', [ $this, 'ajax_router' ] );
			add_filter( 'http_request_args', [ $this, 'add_basic_auth_headers' ], 15, 2 );
			add_filter(
				'wp_dependency_notices',
				function( $notices, $slug ) {
					foreach ( array_keys( $notices ) as $key ) {
						if ( ! is_wp_error( $notices[ $key ] ) && $notices[ $key ]['slug'] === $slug ) {
							$notices[ $key ]['nonce'] = $this->config[ $slug ]['nonce'];
						}
					}
					return $notices;
				},
				10,
				2
			);
			new \WP_Dismiss_Notice();
		}

		public function run( $caller = false ) {
			$caller = ! $caller ? self::$caller : $caller;
			$config = $this->json_file_decode( $caller . '/wp-dependencies.json' );
			if ( ! empty( $config ) ) {
				$this->register( $config, $caller );
			}
			if ( ! empty( $this->config ) ) {
				$this->load_hooks();
			}
			return $this;
		}

		public function json_file_decode( $json_path ) {
			$config = [];
			if ( file_exists( $json_path ) ) {
				$config = file_get_contents( $json_path );
				$config = json_decode( $config, true );
			}
			return $config;
		}

		public function register( $config, $caller = false ) {
			$source = ! self::$source ? basename( $caller ) : self::$source;
			foreach ( $config as $dependency ) {
				$dependency['source']    = $source;
				$dependency['sources'][] = $source;
				$slug                    = $dependency['slug'];
				$dependency['nonce']     = \wp_create_nonce( 'wp-dependency-installer_' . $slug );
				if ( isset( $this->config[ $slug ] ) ) {
					$dependency['sources'] = array_merge( $this->config[ $slug ]['sources'], $dependency['sources'] );
				}
				if ( ! isset( $this->config[ $slug ] ) || $this->is_required( $dependency ) ) {
					$this->config[ $slug ] = $dependency;
				}
			}
			return $this;
		}

		private function apply_config() {
			foreach ( $this->config as $dependency ) {
				$download_link = null;
				$uri           = $dependency['uri'];
				$slug          = $dependency['slug'];
				$uri_args      = parse_url( $uri );
				$port          = isset( $uri_args['port'] ) ? $uri_args['port'] : null;
				$api           = isset( $uri_args['host'] ) ? $uri_args['host'] : null;
				$api           = ! $port ? $api : "{$api}:{$port}";
				$scheme        = isset( $uri_args['scheme'] ) ? $uri_args['scheme'] : null;
				$scheme        = null !== $scheme ? $scheme . '://' : 'https://';
				$path          = isset( $uri_args['path'] ) ? $uri_args['path'] : null;
				$owner_repo    = str_replace( '.git', '', trim( $path, '/' ) );

				switch ( $dependency['host'] ) {
					case 'wordpress':
						$download_link = $this->get_dot_org_latest_download( basename( $owner_repo ) );
						break;
					case 'github':
						$base          = null === $api || 'github.com' === $api ? 'api.github.com' : $api;
						$download_link = "{$scheme}{$base}/repos/{$owner_repo}/zipball/{$dependency['branch']}";
						break;
					case 'direct':
						$download_link = filter_var( $uri, FILTER_VALIDATE_URL );
						break;
				}

				$dependency['download_link'] = apply_filters( 'wp_dependency_download_link', $download_link, $dependency );
				$this->config[ $slug ]        = apply_filters( 'wp_dependency_config', $dependency );
			}
		}

		private function get_dot_org_latest_download( $slug ) {
			$download_link = get_site_transient( 'wpdi-' . md5( $slug ) );
			if ( ! $download_link ) {
				$url      = add_query_arg(
					[
						'action'                        => 'plugin_information',
						rawurlencode( 'request[slug]' ) => $slug,
					],
					'https://api.wordpress.org/plugins/info/1.2/'
				);
				$response      = wp_remote_get( $url );
				$response      = json_decode( wp_remote_retrieve_body( $response ) );
				$download_link = empty( $response )
					? "https://downloads.wordpress.org/plugin/{$slug}.zip"
					: $response->download_link;
				set_site_transient( 'wpdi-' . md5( $slug ), $download_link, DAY_IN_SECONDS );
			}
			return $download_link;
		}

		public function admin_init() {
			$this->apply_config();
			foreach ( $this->config as $slug => $dependency ) {
				$is_required = $this->is_required( $dependency );
				if ( $is_required ) {
					$this->modify_plugin_row( $slug );
				}
				if ( ! wp_verify_nonce( $dependency['nonce'], 'wp-dependency-installer_' . $slug ) ) {
					return false;
				}
				if ( $this->is_active( $slug ) ) {
					// active — nothing to do
				} elseif ( $this->is_installed( $slug ) ) {
					$this->notices[] = $is_required ? $this->activate( $slug ) : $this->activate_notice( $slug );
				} else {
					$this->notices[] = $is_required ? $this->install( $slug ) : $this->install_notice( $slug );
				}
				$this->notices = apply_filters( 'wp_dependency_notices', $this->notices, $slug );
			}
		}

		public function admin_footer() {
			?>
			<script>
				(function ($) {
					$(function () {
						$(document).on('click', '.wpdi-button', function () {
							var $this = $(this);
							var $parent = $(this).closest('p');
							$parent.html('Running...');
							$.post(ajaxurl, {
								action: 'dependency_installer',
								method: $this.attr('data-action'),
								slug  : $this.attr('data-slug'),
								nonce : $this.attr('data-nonce')
							}, function (response) {
								$parent.html(response);
							});
						});
						$(document).on('click', '.dependency-installer .notice-dismiss', function () {
							var $this = $(this);
							$.post(ajaxurl, {
								action: 'dependency_installer',
								method: 'dismiss',
								slug  : $this.attr('data-slug')
							});
						});
					});
				})(jQuery);
			</script>
			<?php
		}

		public function ajax_router() {
			if ( ! isset( $_POST['nonce'], $_POST['slug'] )
				|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wp-dependency-installer_' . sanitize_text_field( wp_unslash( $_POST['slug'] ) ) )
			) {
				return;
			}
			$method    = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';
			$slug      = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
			$whitelist = [ 'install', 'activate', 'dismiss' ];
			if ( in_array( $method, $whitelist, true ) ) {
				$response = $this->$method( $slug );
				$message  = is_wp_error( $response ) ? $response->get_error_message() : $response['message'];
				esc_html_e( $message );
			}
			wp_die();
		}

		public function is_required( &$plugin ) {
			if ( empty( $this->config ) ) {
				return false;
			}
			if ( is_string( $plugin ) && isset( $this->config[ $plugin ] ) ) {
				$dependency = &$this->config[ $plugin ];
			} else {
				$dependency = &$plugin;
			}
			if ( isset( $dependency['required'] ) ) {
				return true === $dependency['required'] || 'true' === $dependency['required'];
			}
			if ( isset( $dependency['optional'] ) ) {
				return false === $dependency['optional'] || 'false' === $dependency['optional'];
			}
			return false;
		}

		public function is_installed( $slug ) {
			return isset( get_plugins()[ $slug ] );
		}

		public function is_active( $slug ) {
			return is_plugin_active( $slug );
		}

		public function install( $slug ) {
			if ( $this->is_installed( $slug ) || ! current_user_can( 'update_plugins' ) ) {
				return false;
			}
			$this->current_slug = $slug;
			add_filter( 'upgrader_source_selection', [ $this, 'upgrader_source_selection' ], 10, 2 );
			$skin     = new WP_Dependency_Installer_Skin( [ 'type' => 'plugin', 'nonce' => wp_nonce_url( $this->config[ $slug ]['download_link'] ) ] );
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $this->config[ $slug ]['download_link'] );
			if ( is_wp_error( $result ) ) {
				return [ 'status' => 'error', 'message' => $result->get_error_message() ];
			}
			if ( null === $result ) {
				return [ 'status' => 'error', 'message' => esc_html__( 'Plugin download failed' ) ];
			}
			wp_cache_flush();
			if ( $this->is_required( $slug ) ) {
				$result = $this->activate( $slug );
				if ( ! is_wp_error( $result ) ) {
					return [
						'status'  => 'updated',
						'slug'    => $slug,
						'message' => sprintf( esc_html__( '%s has been installed and activated.' ), $this->config[ $slug ]['name'] ),
						'source'  => $this->config[ $slug ]['source'],
					];
				}
			}
			if ( is_wp_error( $result ) || ( true !== $result && 'error' === $result['status'] ) ) {
				return $result;
			}
			return [
				'status'  => 'updated',
				'message' => sprintf( esc_html__( '%s has been installed.' ), $this->config[ $slug ]['name'] ),
				'source'  => $this->config[ $slug ]['source'],
			];
		}

		public function install_notice( $slug ) {
			$dependency = $this->config[ $slug ];
			return [
				'action'  => 'install',
				'slug'    => $slug,
				'message' => sprintf( esc_html__( 'The %s plugin is recommended.' ), $dependency['name'] ),
				'source'  => $dependency['source'],
			];
		}

		public function activate( $slug ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return new WP_Error( 'wpdi_activate_plugins', __( 'Current user cannot activate plugins.' ), $this->config[ $slug ]['name'] );
			}
			$result = is_network_admin() ? activate_plugin( $slug, null, true ) : activate_plugin( $slug );
			if ( is_wp_error( $result ) ) {
				return [ 'status' => 'error', 'message' => $result->get_error_message() ];
			}
			return [
				'status'  => 'updated',
				'slug'    => $slug,
				'message' => sprintf( esc_html__( '%s has been activated.' ), $this->config[ $slug ]['name'] ),
				'source'  => $this->config[ $slug ]['source'],
			];
		}

		public function activate_notice( $slug ) {
			$dependency = $this->config[ $slug ];
			return [
				'action'  => 'activate',
				'slug'    => $slug,
				'message' => sprintf( esc_html__( 'Please activate the %s plugin.' ), $dependency['name'] ),
				'source'  => $dependency['source'],
			];
		}

		public function dismiss() {
			return [ 'status' => 'updated', 'message' => '' ];
		}

		public function upgrader_source_selection( $source, $remote_source ) {
			$new_source = trailingslashit( $remote_source ) . dirname( $this->current_slug );
			$this->move_dir( $source, $new_source, true );
			return trailingslashit( $new_source );
		}

		private function move_dir( $from, $to, $overwrite = false ) {
			global $wp_filesystem;
			if ( trailingslashit( strtolower( $from ) ) === trailingslashit( strtolower( $to ) ) ) {
				return new \WP_Error( 'source_destination_same_move_dir', __( 'The source and destination are the same.' ) );
			}
			if ( $wp_filesystem->exists( $to ) ) {
				if ( ! $overwrite ) {
					return new \WP_Error( 'destination_already_exists_move_dir', __( 'The destination folder already exists.' ), $to );
				} elseif ( ! $wp_filesystem->delete( $to, true ) ) {
					return new \WP_Error( 'destination_not_deleted_move_dir', __( 'The destination directory already exists and could not be removed.' ) );
				}
			}
			$result = false;
			if ( 'direct' === $wp_filesystem->method ) {
				if ( $wp_filesystem->delete( $to, true ) ) {
					$result = @rename( $from, $to );
				}
			} else {
				$result = $wp_filesystem->move( $from, $to, $overwrite );
			}
			if ( $result ) {
				usleep( 200000 );
			}
			if ( ! $result ) {
				if ( ! $wp_filesystem->is_dir( $to ) ) {
					if ( ! $wp_filesystem->mkdir( $to, FS_CHMOD_DIR ) ) {
						return new \WP_Error( 'mkdir_failed_move_dir', __( 'Could not create directory.' ), $to );
					}
				}
				$result = copy_dir( $from, $to, [ basename( $to ) ] );
				if ( ! is_wp_error( $result ) ) {
					$wp_filesystem->delete( $from, true );
				}
			}
			return $result;
		}

		public function admin_notices() {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return false;
			}
			foreach ( $this->notices as $notice ) {
				$status      = isset( $notice['status'] ) ? $notice['status'] : 'updated';
				$source      = isset( $notice['source'] ) ? $notice['source'] : __( 'Dependency' );
				$class       = esc_attr( $status ) . ' notice is-dismissible dependency-installer';
				$label       = esc_html( $this->get_dismiss_label( $source ) );
				$message     = isset( $notice['message'] ) ? esc_html( $notice['message'] ) : '';
				$action      = '';
				$dismissible = '';
				if ( isset( $notice['action'] ) ) {
					$action = sprintf(
						' <a href="javascript:;" class="wpdi-button" data-action="%1$s" data-slug="%2$s" data-nonce="%3$s">%4$s Now &raquo;</a> ',
						esc_attr( $notice['action'] ),
						esc_attr( $notice['slug'] ),
						esc_attr( $notice['nonce'] ),
						esc_html( ucfirst( $notice['action'] ) )
					);
				}
				if ( isset( $notice['slug'] ) ) {
					$timeout     = apply_filters( 'wp_dependency_timeout', '7', $source );
					$dependency  = dirname( $notice['slug'] );
					$dismissible = empty( $timeout ) ? '' : sprintf( 'dependency-installer-%1$s-%2$s', esc_attr( $dependency ), esc_attr( $timeout ) );
				}
				if ( \WP_Dismiss_Notice::is_admin_notice_active( $dismissible ) ) {
					printf(
						'<div class="%1$s" data-dismissible="%2$s"><p><strong>[%3$s]</strong> %4$s%5$s</p></div>',
						esc_attr( $class ),
						esc_attr( $dismissible ),
						esc_html( $label ),
						esc_html( $message ),
						$action // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				}
			}
		}

		private function modify_plugin_row( $plugin_file ) {
			add_filter( 'network_admin_plugin_action_links_' . $plugin_file, [ $this, 'unset_action_links' ], 10, 2 );
			add_filter( 'plugin_action_links_' . $plugin_file, [ $this, 'unset_action_links' ], 10, 2 );
		}

		public function unset_action_links( $actions, $plugin_file ) {
			if ( apply_filters( 'wp_dependency_unset_action_links', true ) ) {
				unset( $actions['delete'], $actions['deactivate'] );
			}
			return $actions;
		}

		private function get_dismiss_label( $source ) {
			$label = ucwords( str_replace( '-', ' ', $source ) );
			$label = str_ireplace( 'wp ', 'WP ', $label );
			return apply_filters( 'wp_dependency_dismiss_label', $label, $source );
		}

		public function add_basic_auth_headers( $args, $url ) {
			if ( null === $this->current_slug ) {
				return $args;
			}
			$package = $this->config[ $this->current_slug ];
			if ( 'wordpress' === $package['host'] ) {
				unset( $args['headers']['Authorization'] );
			}
			remove_filter( 'http_request_args', [ $this, 'add_basic_auth_headers' ] );
			return $args;
		}
	}
}
