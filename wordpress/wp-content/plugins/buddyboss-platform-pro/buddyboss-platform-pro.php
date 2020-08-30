<?php
/**
 * Plugin Name: BuddyBoss Platform Pro
 * Plugin URI:  https://buddyboss.com/
 * Description: Adds premium features to BuddyBoss Platform.
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.0.4
 * Text Domain: buddyboss-pro
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * This file should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Platform_Pro' ) ) {

	/**
	 * Main Class
	 *
	 * @class BB_Platform_Pro
	 * @version	1.0.0
	 */
	final class BB_Platform_Pro {

		/**
		 * @var BB_Platform_Pro The single instance of the class
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * @var array Integrations.
		 */
		public $integrations = array();

		/**
		 * Main BB_Platform_Pro Instance
		 *
		 * Ensures only one instance of BB_Platform_Pro is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @see BB_Platform_Pro()
		 * @return BB_Platform_Pro - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 * @since 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddyboss-pro' ), '1.0.0' );
		}
		/**
		 * Unserializing instances of this class is forbidden.
		 * @since 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddyboss-pro' ), '1.0.0' );
		}

		/**
		 * BB_Platform_Pro Constructor.
		 */
		public function __construct() {
			$this->constants();
			$this->setup_globals();
			$this->includes();
			// Set up localisation.
			$this->load_plugin_textdomain();
		}

		/** Private Methods *******************************************************/

		/**
		 * Bootstrap constants.
		 *
		 * @since 1.0.0
		 */
		private function constants() {
			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_DIR' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_URL' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_BASENAME' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			}

			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_FILE' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_FILE', __FILE__ );
			}
		}

		/**
		 * Global variables.
		 *
		 * @since 1.0.0
		 */
		private function setup_globals() {
			$this->version        = '1.0.4';
			$this->db_version     = 111;
			$this->db_version_raw = (int) bp_get_option( '_bbp_pro_db_version' );

			// root directory
			$this->file       = __FILE__;
			$this->basename   = plugin_basename( __FILE__ );
			$this->plugin_dir = trailingslashit( constant( 'BB_PLATFORM_PRO_PLUGIN_DIR' ) );
			$this->plugin_url = trailingslashit( constant( 'BB_PLATFORM_PRO_PLUGIN_URL' ) );

			$this->root_plugin_dir = $this->plugin_url;
			$this->integration_dir = $this->plugin_dir . 'includes/integrations';
			$this->integration_url = $this->plugin_url . 'includes/integrations';
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			spl_autoload_register( array( $this, 'autoload' ) );

			require $this->plugin_dir . 'includes/bb-pro-core-update.php';
			require $this->plugin_dir . 'includes/bb-pro-core-actions.php';
			require $this->plugin_dir . 'includes/bb-pro-core-functions.php';
			require $this->plugin_dir . 'includes/bb-pro-core-loader.php';
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 */
		public function load_plugin_textdomain() {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'buddyboss-pro' );

			unload_textdomain( 'buddyboss-pro' );
			load_textdomain( 'buddyboss-pro', WP_LANG_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' . plugin_basename( dirname( __FILE__ ) ) . '-' . $locale . '.mo' );
			load_plugin_textdomain( 'buddyboss-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Autoload classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $class
		 */
		public function autoload( $class ) {
			$class_parts = explode( '_', strtolower( $class ) );

			if ( 'bp' !== $class_parts[0] && 'bb' !== $class_parts[0] ) {
				return;
			}

			// Sanitize class name.
			$class = strtolower( str_replace( '_', '-', $class ) );

			$paths = array(

				$this->plugin_dir . "/includes/classes/class-{$class}.php",

			);

			$integration_dir = $this->integration_dir;

			foreach ( $this->integrations as $integration ) {
				$paths[] = "{$integration_dir}/{$integration}/includes/class-{$class}.php";
				$paths[] = "{$integration_dir}/{$integration}/includes/classes/class-{$class}.php";
			}

			foreach ( $paths as $path ) {
				// Sanity check.
				if ( file_exists( $path ) ) {
					require $path;
				}
			}
		}
	}

	/**
	 * Returns the main instance of BB_Platform_Pro to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return BB_Platform_Pro
	 */
	function bb_platform_pro() {
		return BB_Platform_Pro::instance();
	}

	function bb_platform_pro_install_bb_platform_notice() {
		echo '<div class="error fade"><p>';
		_e('<strong>BuddyBoss Platform Pro</strong></a> requires the BuddyBoss Platform plugin to work. Please <a href="https://buddyboss.com/platform/" target="_blank">install BuddyBoss Platform</a> first.', 'buddyboss-pro');
		echo '</p></div>';
	}

	function bb_platform_pro_update_bb_platform_notice() {
		echo '<div class="error fade"><p>';
		_e('<strong>BuddyBoss Platform Pro</strong></a> requires BuddyBoss Platform plugin version 1.3.5 or higher to work. Please update BuddyBoss Platform.', 'buddyboss-pro');
		echo '</p></div>';
	}

	function bb_platform_pro_init() {
		if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
			add_action( 'admin_notices', 'bb_platform_pro_install_bb_platform_notice' );
			add_action( 'network_admin_notices', 'bb_platform_pro_install_bb_platform_notice' );
			return;
		} else if ( version_compare( BP_PLATFORM_VERSION,'1.3.4', '<' ) ) {
			add_action( 'admin_notices', 'bb_platform_pro_update_bb_platform_notice' );
			add_action( 'network_admin_notices', 'bb_platform_pro_update_bb_platform_notice' );
			return;
		} else {
			bb_platform_pro();
		}
	}

	add_action( 'plugins_loaded', 'bb_platform_pro_init', 9 );
}
