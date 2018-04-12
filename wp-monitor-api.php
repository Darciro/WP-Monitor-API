<?php
/**
 * Plugin Name:       Monitor WordPress
 * Plugin URI:        https://github.com/darciro/
 * Description:       @TODO
 * Version:           1.0.0
 * Author:            Ricardo Carvalho
 * Author URI:        https://github.com/darciro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WPMonitorAPI' ) ) :

	class WPMonitorAPI {

		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public function register_routes() {
			/* register_rest_route( 'wp-monitor-api/v1', '/author/(?P<id>\d+)', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'my_awesome_func' ),
			) ); */

			register_rest_route( 'wp-monitor-api/v1', '/server/info', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_server_info' ),
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/info', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_info' ),
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/themes', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_themes' ),
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/plugins', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_plugins' ),
			) );
		}

		public function get_server_info() {
			$data = array(
				'operating-system'      => sprintf( '%s (%d Bit)', PHP_OS, PHP_INT_SIZE * 8 ),
				'datetime-and-timezone' => date( 'd/m/Y == H:i:s' ) . ' (' . date_default_timezone_get() . ')',
				'server'                => isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
				'php-version'           => substr( phpversion(), 0, 5 ),
				'abs-path'				=> ABSPATH
			);

			return $data;
		}

		public function get_site_info() {
			if ( ! function_exists( 'get_core_updates' ) ) {
				require_once ABSPATH . 'wp-admin/includes/update.php';
			}

			$data = array(
				'site-name'         => get_bloginfo( 'name' ),
				'site-url'          => get_bloginfo( 'url' ),
				'admin-email'       => get_bloginfo( 'admin_email' ),
				'core-version'      => get_bloginfo( 'version' ),
				'core-updates'      => get_core_updates(),
				'is-multisite'      => is_multisite()
			);

			return $data;
		}

		public function get_site_themes() {
			$themes    = wp_get_themes();
			$wp_themes = array();

			if ( ! function_exists( 'get_theme_updates' ) ) {
				require_once ABSPATH . 'wp-admin/includes/update.php';
			}

			foreach ( $themes as $i => $theme ) {
				$theme_updates = get_theme_updates($i);
				$theme_has_update = ( array_key_exists($i, $theme_updates) ) ? true :false;

				$wp_themes[ $i ]['name']       = $theme->get( 'Name' );
				$wp_themes[ $i ]['slug']       = $i;
				$wp_themes[ $i ]['version']    = $theme->get( 'Version' );
				$wp_themes[ $i ]['has-update'] = $theme_has_update;
				$wp_themes[ $i ]['theme-uri']  = $theme->get( 'ThemeURI' );
				$wp_themes[ $i ]['author']     = $theme->get( 'Author' );
				$wp_themes[ $i ]['author-uri'] = $theme->get( 'AuthorURI' );
				$wp_themes[ $i ]['template']   = $theme->get( 'Template' );
				$wp_themes[ $i ]['status']     = $theme->get( 'Status' );
			}

			return $wp_themes;
		}

		public function get_site_plugins() {
			if ( ! function_exists( 'get_plugin_updates' ) ) {
				require_once ABSPATH . 'wp-admin/includes/update.php';
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_updates = get_plugin_updates();
			$wp_plugins = get_plugins();
			foreach ( $wp_plugins as $i => $plugin ) {
				$wp_plugins[$i]['has-update'] = ( array_key_exists($i, $plugin_updates) ) ? true :false;
			}
			return $wp_plugins;
		}

	}

	// Initialize our plugin
	$gewp = new WPMonitorAPI();

endif;