<?php
/**
 * Plugin Name:       Monitor WordPress API
 * Plugin URI:        https://github.com/Darciro/WP-Monitor-API
 * Description:       Expande a API padrão do WordPress permitindo um maior monitoramento e acompanhamento do site
 * Version:           1.1.0
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
            add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'register_styles' ) );
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			add_action( 'admin_menu' , array( $this , 'wp_monitor_settings_menu' ) );
			add_action( 'admin_init', array( $this , 'wp_monitor_api_register_settings' ) );
            add_action( 'wp_ajax_get_md5_hash', array($this, 'get_md5_hash'));
			add_action( 'rest_api_init', array( $this , 'wp_monitor_cors' ), 15 );
		}

		/**
		 * Add a sub menu page
         *
		 */
		public function wp_monitor_settings_menu () {
			add_submenu_page(
				'options-general.php',
				'WP Monitor API',
				'WP Monitor API',
				'manage_options',
				'wp-monitor-api-settings',
				array( $this, 'wp_monitor_api_settings' )
			);
		}

		/**
		 * Register the settings for our plugin options
         *
		 */
	    public function wp_monitor_api_register_settings() {
			register_setting(
                'wp-monitor-api-settings-group',
                'wp_monitor_api_key',
				array( $this, 'wp_monitor_api_options_sanitize' )
            );
		}

		/**
		 * View for the options page
		 *
		 */
		public function wp_monitor_api_settings() { ?>
            <div class="wrap">
                <h1>WP Monitor API</h1>
                <form method="post" action="options.php">
					<?php settings_fields( 'wp-monitor-api-settings-group' ); ?>
					<?php do_settings_sections( 'wp-monitor-api-settings-group' ); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="wp_monitor_api_key">API Key</label>
                            </th>
                            <td>
                                <div class="input-token-api">
                                    <input type="text" id="wp_monitor_api_key" name="wp_monitor_api_key" value="<?php echo esc_attr( get_option('wp_monitor_api_key') ); ?>" />
                                    <button id="wp-monitor-api-generate-token-btn">Gerar novo token</button>
                                </div>
                            </td>
                        </tr>
                    </table>

					<?php submit_button(); ?>

                </form>
            </div>
		<?php }

		/**
		 * Sanitize the raw value for our plugin options
		 *
		 */
		public function wp_monitor_api_options_sanitize ($input) {
			if ( isset( $input ) ) {
				// return wp_hash_password( $input );
				return $input;
			}
        }

        /**
         * Generate hash from a randon number
         *
         */
        public function get_md5_hash () {
		    $rand = rand();
		    echo md5( $rand );
		    die;
        }

        /**
         * Register scritps for our plugin
         *
         */
        public function register_scripts() {
            wp_register_script( 'wp-monitor-api-scripts', plugin_dir_url( __FILE__ ) . 'assets/wp-monitor-api-scripts.js' );
            wp_enqueue_script( 'wp-monitor-api-scripts' );

            $monitor = array(
                'ajaxurl' => admin_url( 'admin-ajax.php' )
            );

            wp_localize_script( 'wp-monitor-api-scripts', 'monitor', $monitor );
        }

        /**
         * Register styles for our plugin
         *
         */
        public function register_styles() {
            wp_register_style( 'wp-monitor-api-styles', plugin_dir_url( __FILE__ ) . 'assets/wp-monitor-api-styles.css' );
            wp_enqueue_style( 'wp-monitor-api-styles' );
        }

		/**
         * Check whether the user has sent a header with authentication data and if this data is correct
         * @TODO Redefine the token as MD5 encryption
         *
		 * @return bool
		 */
		public function permission_check () {
			$username = '';
			$password = '';
			$mod = NULL;

			// Apache mod_php
			if ( isset( $_SERVER['PHP_AUTH_USER'] ) ):
				$username = $_SERVER['PHP_AUTH_USER'];
				$password = $_SERVER['PHP_AUTH_PW'];
				$mod = 'PHP_AUTH_USER';

			// Others servers
			elseif ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ):
				if ( preg_match( '/^basic/i', $_SERVER['HTTP_AUTHORIZATION'] ) )
					list( $username, $password ) = explode( ':', base64_decode( substr( $_SERVER['HTTP_AUTHORIZATION'], 6 ) ) );
				$mod = 'HTTP_AUTHORIZATION';

			endif;

			// If the authentication was not sent
			if ( is_null( $username ) ):

				header('WWW-Authenticate: Basic realm="'. get_bloginfo( 'name' ) .' - WP Monitor API"');
				header('HTTP/1.0 401 Unauthorized');
				die('Acesso negado. A autenticação não foi enviada');

			// Check auth data sent
			else:
				header('WWW-Authenticate: Basic realm="'. get_bloginfo( 'name' ) .' - WP Monitor API"');
				header('HTTP/1.0 200 OK');

				// @TODO Pensar numa forma de adicionar CORS
				header('Access-Control-Allow-Origin: http://104.131.81.174');
				header('Access-Control-Allow-Credentials: true');

				if( $username === 'wp-monitor-api' ){
					// var_dump( $username, $password, $mod );
					$wp_monitor_api_key = get_option( 'wp_monitor_api_key' );
					// var_dump( $password, md5($password), $wp_monitor_api_key );

                    // If the token sent match the option saved in WP
					if( $password === $wp_monitor_api_key ){
						return true;
					}
				}

			endif;

			return false;
		}

		/**
		 * Define the routes for our monitor plugin extending the default API
         *
		 */
		public function register_routes() {
			register_rest_route( 'wp-monitor-api/v1', '/server/info', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_server_info' ),
				'permission_callback' => array( $this, 'permission_check' )
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/info', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_info' ),
				'permission_callback' => array( $this, 'permission_check' )
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/themes', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_themes' ),
				'permission_callback' => array( $this, 'permission_check' )
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/plugins', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_plugins' ),
				'permission_callback' => array( $this, 'permission_check' )
			) );

			register_rest_route( 'wp-monitor-api/v1', '/site/users', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_site_users' ),
				'permission_callback' => array( $this, 'permission_check' )
			) );
		}

		/**
         * Retrieve data from the server where WordPress is installed
         *
		 * @return array
		 */
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

		/**
		 * Retrieve data from the core, such as WP version and blog info
         *
		 * @return array
		 */
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

		/**
         * Retrieve info from themes installed
         *
		 * @return array
		 */
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

		/**
		 * Retrieve info from plugins installed
		 *
		 * @return array
		 */
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

		/**
		 * Retrieve info from all users subscribed to the site
         * @TODO Check the return from a single installation WP
		 *
		 * @return array
		 */
		public function get_site_users () {
			if( is_multisite() ){
				global $wpdb;
				$users = array();
				$user_ids = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}users", ARRAY_A );
				foreach( $user_ids as $i => $user_id ) {
					$user_meta = get_userdata($user_id['ID']);
					if( $user_meta->roles[0] ) {
						$users[$i] = $user_meta->roles[0];
					} else {
						$users[$i] = 'subscriber';
					}
				}

				$users = array_count_values( $users );
				$users['total'] = count( $user_ids );
			} else {
				$users = get_users( 'count_total=true' );
				// print_r( $users );
			}
			return $users;
		}

		public function wp_monitor_cors () {
			remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
			add_filter( 'rest_pre_serve_request', function( $value ) {

				header( 'Access-Control-Allow-Origin: http://wp-monitor-dashboard.localhost' );
				header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
				header( 'Access-Control-Allow-Headers: Authorization, X-Requested-With, Content-Type, Origin, Accept' );
				header( 'Access-Control-Allow-Credentials: true' );
				return $value;
			});

		}

	}

	// Initialize our plugin
	$gewp = new WPMonitorAPI();

endif;