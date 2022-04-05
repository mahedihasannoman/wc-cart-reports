<?php

/**
 * Class WCCR_Cart_Reports_Settings
 */
class WCCR_Cart_Reports_Settings {

    /**
     * Constructor
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts' ) );
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
    }

  /**
   * Initialize admin script
   * 
   * @since 1.0.0
   * 
   * @return null
   */
    public function enqueue_settings_scripts() {
        wp_enqueue_script( 'wc_cart_reports_settings_script', WC_CART_REPORTS_ASSETS_URL . '/js/admin-settings.js' );
    }

    /**
     * Add settings page in Woocommerce Settings
     *
     * @param array $settings
     * 
     * @return array $settings
     */
    public function add_settings_page( $settings ) {
        $settings[] = include WC_CART_REPORTS_INCLUDES . '/admin/settings/class-settings-general.php';
        return $settings;
    }
}

new WCCR_Cart_Reports_Settings();
