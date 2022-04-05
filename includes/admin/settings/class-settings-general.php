<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
} // Exit if accessed directly

class WCCR_Cart_Reports_Settings_General extends WC_Settings_Page {

  /**
   * Constructor
   * 
   * @since 1.0.0
   * 
   * @return null
   */
  public function __construct() {
    $this->id    = 'wccr_cart_reports';
    $this->label = __( 'WC Cart Reports', 'wc-cart-reports' );
    parent::__construct();
  }

  /**
   * Get Roles
   * 
   * @since 1.0.0
   *
   * @return array $roles
   */
  public function get_roles() {
    global $wp_roles;
    $roles          = $wp_roles->get_names();
    $roles['guest'] = __( 'Guest', 'wc-cart-reports' ); //Need to add guest manually
    return $roles;
  }

  /**
   * Initialize Cart Report Settings
   * 
   * @since 1.0.0
   * 
   * @return null
   */
  public function get_settings() {
    $cart_reports_settings = array(
      array(
        'name' => __( 'Cart Reports Settings', 'wc-cart-reports' ),
        'type' => 'title',
        'desc' => '',
        'id'   => 'wc_cart_reports_options'
      ),
      array(
        'name'     => __( 'Cart Timeout (seconds)', 'wc-cart-reports' ),
        'desc'     => __( 'Site activity timeout length for cart abandonment, in seconds. Ex: 1800 for 30 minutes',
          'wc-cart-reports' ),
        'desc_tip' => false,
        'id'       => 'wccr_timeout',
        'type'     => 'number',
        'defualt'  => '1800'
      ),
      array(
        'name'     => __( 'Widget Time Range (days)', 'wc-cart-reports' ),
        'desc'     => __( 'Time-range displayed in the middle column of the "Recent Cart Activity" dashboard widget.',
          'wc-cart-reports' ),
        'desc_tip' => false,
        'id'       => 'wccr_dashboardrange',
        'default'  => '2',
        'type'     => 'number'
      ),
      array(
        'name'         => __( 'Show Products On The Cart Index Page', 'wc-cart-reports' ),
        'desc'         => __( 'Displaying cart products may slow down table listing when showing many carts at once.',
          'wc-cart-reports' ),
        'desc_tip'     => false,
        'id'           => 'wccr_productsindex',
        'default'      => 'yes',
        'type'         => 'checkbox',
        'tooltip_html' => __( 'Hello', 'wc-cart-reports' )
      ),
      array(
        'name'     => __( 'Excluded Roles', 'wc-cart-reports' ),
        'desc'     => __( 'Choose WP Roles to exclude from cart tracking', 'wc-cart-reports' ),
        'id'       => 'wccr_trackedroles',
        'type'     => 'multiselect',
        'desc_tip' => false,
        'options'  => $this->get_roles(),
      ),
      array(
        'name' => __( 'Log Customer IP Address', 'wc-cart-reports' ),
        'desc' => __( 'Logged IP addresses are visible in the edit cart view.<br/>NOTE: In order to comply with GDPR you must obtain a user\'s permission before collecting their IP address',
          'wc-cart-reports' ),
        'id'   => 'wccr_logip',
        'type' => 'checkbox'
      ),

      array(
        'name'     => __( 'Automatically delete carts?', 'wc-cart-reports' ),
        'desc'     => __( 'Saving a large number of carts can affect site performance. Automatically clearing the cart lists can help increase site speed.',
          'wc-cart-reports' ),
        'desc_tip' => false,
        'id'       => 'wccr_expiration_opt_in',
        'type'     => 'checkbox'
      ),
      array(
        'name'     => __( 'Clear carts older than (days)', 'wc-cart-reports' ),
        'desc'     => __( 'Any cart that becomes older than the number of days specified will be automatically deleted in the background. The deletion cannot be undone',
          'wc-cart-reports' ),
        'desc_tip' => false,
        'id'       => 'wccr_expiration',
        'default'  => '0',
        'type'     => 'number'
      ),
      array( 'type' => 'sectionend', 'id' => 'wc_cart_report_settings' ),
    );
    return apply_filters( 'wc_cart_reports_settings', $cart_reports_settings );
  }

  /**
   * Output
   * 
   * @since 1.0.0
   *
   * @return void
   */
  public function output() {
    $settings = $this->get_settings();
    WC_Admin_Settings::output_fields( $settings );
  }

  /**
   * Save
   * 
   * @since 1.0.0
   *
   * @return void
   */
  public function save() {
    $settings = $this->get_settings();
    WC_Admin_Settings::save_fields( $settings );
  }

}
return new WCCR_Cart_Reports_Settings_General();
