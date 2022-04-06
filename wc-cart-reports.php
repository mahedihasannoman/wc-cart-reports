<?php
/**
 * Plugin Name: WC Cart Reports
 * Plugin URI:  https://www.braintim.com/
 * Description: WC Cart Reports is an Addon plugin for WooCommerce that allows site admins to keep track of Abandoned, Open, and Converted Carts.
 * Version:     1.0.0
 * Author:      Md. Mahedi Hasan
 * Author URI:  https://www.braintum.com
 * Donate link: https://www.braintum.com
 * License:     GPLv2+
 * Text Domain: wc-cart-reports
 * Domain Path: /i18n/languages/
 * Tested up to: 5.9
 * WC requires at least: 3.0.0
 * WC tested up to: 4.0.1
 */

/**
 * Copyright (c) 2019 Braintum (email : mahedi@braintum.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main initiation class
 *
 * @since 1.0.0
 */

/**
 * Main WC_Cart_Reports Class.
 *
 * @class WC_Cart_Reports
 */
final class WC_Cart_Reports {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var WC_Cart_Reports
	 */
	protected static $instance = null;

	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_name = 'WC Cart Reports';
	/**
	 * WC_Cart_Reports version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * admin notices
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Main WC_Cart_Reports Instance.
	 *
	 * Ensures only one instance of WC_Cart_Reports is loaded or can be loaded.
	 *
	 * @return WC_Cart_Reports - Main instance.
	 *
	 * @since 1.0.0
	 *
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * EverProjects Constructor.
	 */
	public function setup() {
		$this->define_constants();
		add_action( 'woocommerce_loaded', array( $this, 'init_plugin' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_admin_notices' ) );
        add_action( 'init', array( $this, 'localization_setup' ) );
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
	    register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	/**
	 * Define EverProjects Constants.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		define( 'WC_CART_REPORTS_VERSION', $this->version );
		define( 'WC_CART_REPORTS_FILE', __FILE__ );
		define( 'WC_CART_REPORTS_PATH', dirname( WC_CART_REPORTS_FILE ) );
		define( 'WC_CART_REPORTS_INCLUDES', WC_CART_REPORTS_PATH . '/includes' );
		define( 'WC_CART_REPORTS_URL', plugins_url( '', WC_CART_REPORTS_INCLUDES ) );
		define( 'WC_CART_REPORTS_ASSETS_URL', WC_CART_REPORTS_URL . '/assets' );
        define( 'WC_CART_REPORTS_TEMPLATES_DIR', WC_CART_REPORTS_PATH . '/templates' );
        define( 'WCCR_CONVERTED', 'Converted' );
        define( 'WCCR_ABANDONED', 'Abandoned' );
        define( 'WCCR_OPEN', 'Open' );
        define( 'WCCR_ONEDAY', 86400 );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		if ( ! $this->is_wc_installed() ) {
			return;
		}
		//core
		include_once WC_CART_REPORTS_INCLUDES . '/admin/class-post-types.php';
		include_once WC_CART_REPORTS_INCLUDES . '/core-functions.php';
        include_once WC_CART_REPORTS_INCLUDES . '/class-cart-receipt.php';
		include_once WC_CART_REPORTS_INCLUDES . '/class-cart-reports.php';
		include_once WC_CART_REPORTS_INCLUDES . '/class-cart-action.php';
		//admin includes
		if ( $this->is_request( 'admin' ) ) {
			require_once WC_CART_REPORTS_INCLUDES . '/admin/class-settings.php';
			require_once WC_CART_REPORTS_INCLUDES . '/admin/class-cart-edit-interface.php';
			require_once WC_CART_REPORTS_INCLUDES . '/admin/class-cart-index-interface.php';
			require_once WC_CART_REPORTS_INCLUDES . '/admin/class-cart-report-dashboard.php';
			require_once WC_CART_REPORTS_INCLUDES . '/admin/class-cart-report-page.php';
		}
		do_action( 'wc_cart_reports_loaded' );
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 *
	 * @return string
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
		}
	}

	/**
     * Trigger when plugin activate
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public static function activate() {
		//Check first to see if we need to upgrade
		global $wpdb;
		$check_sql       = 'SELECT meta_value FROM ' . $wpdb->prefix . "postmeta WHERE meta_key = 'wccr8_cartitems'";
		$upgrade_needed  = false;
		$check_meta_vals = $wpdb->get_results( $check_sql );
		foreach ( $check_meta_vals as $check_meta_val ) :
			if ( strpos( $check_meta_val->meta_value, 'WC_Product' ) ) :
				$upgrade_needed = true;
			endif;
		endforeach;

		if ( $upgrade_needed ) {
			//Upgrade needed.
			$check_sql = 'SELECT * from ' . $wpdb->prefix . "postmeta WHERE meta_key = 'wccr8_cartitems'";

			$meta_vals = $wpdb->get_results( $check_sql );
			$counter   = 0;
			foreach ( $meta_vals as $meta_key ) :
				$new_meta_value = str_replace( 'O:10:"WC_Product"', 'O:8:"stdclass"', $meta_key->meta_value );
				$upgrade_sql    = 'UPDATE ' . $wpdb->prefix . "postmeta SET meta_value = '" . $new_meta_value . "'WHERE meta_id = '" . $meta_key->meta_id . "' AND meta_key = '" . $meta_key->meta_key . "'";
				$wpdb->query( $upgrade_sql );
				$counter ++;
			endforeach;
		}
		delete_option( 'wc_cart_reports_version' );
		add_option( 'wc_cart_reports_version', wc_cart_reports()->version );
    }

    /**
     * Trigger when plugin deactivate
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public static function deactivate() {

	}
	
	/**
	 * Initialize plugin for localization
	 *
	 * @return void
     * 
	 * @since 1.0.0
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wc-cart-reports', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );
	}

	/**
	 * Determines if the woocommerce installed.
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function is_wc_installed() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active( 'woocommerce/woocommerce.php' ) == true;
	}

	/**
	 * Adds notices if the wocoomerce is not activated
	 *
	 * @return null
	 *
	 * @since 1.0.0
	 */
	public function woocommerce_admin_notices() {
		if ( false === $this->is_wc_installed() ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'Woocommerce is not installed or inactive. Please install and active woocommerce plugin.', 'wc-cart-reports' ); ?></p>
            </div>
			<?php
		}
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
     * 
	 * @since 1.0.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WC_CART_REPORTS_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
     * 
	 * @since 1.0.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WC_CART_REPORTS_FILE ) );
	}

    /**
     * Include necessary files
     * If Woocommerce is activated
     * Callback for woocommerce_loaded hook
     * 
     * @since 1.0.0
     *
     * @return void
     */
	public function init_plugin() {
		//need to define this constant after woocommerce load
		define( 'WCCR_COOKIEVALUE', $this->get_session_cookie_carts() );
		//Include necessary files
        $this->includes();
	}

	/**
	 * Get session id from Cookie
	 * 
	 * @since 1.0.0
	 *
	 * @return bool|string
	 */
	public function get_session_cookie_carts(){
		if ( class_exists( 'WC_Session' ) ) {
			if ( function_exists( 'WC' ) ) {
				$cookieid = 'wp_woocommerce_session_' . COOKIEHASH;
			} else {
				$cookieid = 'wc_session_cookie_' . COOKIEHASH;
			}
			if ( isset( $_COOKIE[ $cookieid ] ) && $_COOKIE[ $cookieid ] != false ) {
				list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode(
					'||',
					$_COOKIE[ $cookieid ]
				);
				$customer_id = $customer_id;
	
				return $customer_id;
			}
		}
		return false;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-cart-reports' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-cart-reports' ), '1.0.0' );
	}

}

/**
 * The main function responsible for returning the one true WC Cart Reports
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return WC_Cart_Reports
 * @since 1.0.0
 */
function wc_cart_reports() {
	return WC_Cart_Reports::instance();
}

//lets go.
wc_cart_reports();
