<?php 

/**
 * class WCCR_Cart_Reports
 */
class WCCR_Cart_Reports{

    /**
     * Existing ID
     * 
     * @since 1.0.0
     *
     * @var [type]
     */
    public $existing_id;

    /**
     * Receipt
     * 
     * @since 1.0.0
     *
     * @var [type]
     */
    public $receipt;

    /**
     * Contructor
     * 
     * @since 1.0.0
     * 
     * @return null
     * 
     */
    public function __construct() {
        global $wpdb;
        global $wc_cart_reports_options;

        /* Timeout */
        $wc_cart_reports_options['timeout'] = get_option( 'wccr_timeout' );

        add_action( 'woocommerce_cart_reset', array( $this, 'scheduled_cart_reset' ) );
        /* Product Index */

        $productsindex = get_option( 'wccr_productsindex' );
        if ( $productsindex == 'yes' ) {
            $wc_cart_reports_options['productsindex'] = true;
        } else {
            $wc_cart_reports_options['productsindex'] = false;
        }

        /* Tracked Roles */
        $trackedroles = get_option( 'wccr_trackedroles' );
        if ( is_array( $trackedroles ) && ! empty( $trackedroles ) ) {
            $wc_cart_reports_options['trackedroles'] = $trackedroles;
        } else {
            $wc_cart_reports_options['trackedroles'] = false;
        }

        /* Log IP */
        $logips = get_option( 'wccr_logip' );
        if ( $logips == 'yes' ) {
            $wc_cart_reports_options['logip'] = true;
        } else {
            $wc_cart_reports_options['logip'] = false;
        }

        /* Cart Expiration Opt-In Checkbox*/
        $wc_cart_reports_expiration_opt_in = get_option( 'wccr_expiration_opt_in' );
        if ( $wc_cart_reports_expiration_opt_in == 'yes' ) {
            $wc_cart_reports_options['wccr_expiration_opt_in'] = true;
        } else {
            $wc_cart_reports_options['wccr_expiration_opt_in'] = false;
        }

        /* Cart Expiration Day range */
        $cart_expiration_opt_in = get_option( 'wccr_expiration_opt_in' );
        $cart_expiration        = get_option( 'wccr_expiration' );

        if ( $cart_expiration > 0 && $cart_expiration_opt_in != 'no' ) {
            if ( ! wp_next_scheduled( 'woocommerce_cart_reset' ) ) {
                wp_schedule_event( time(), 'hourly', 'woocommerce_cart_reset' );
            }
            $wc_cart_reports_options['wccr_expiration'] = $cart_expiration;
        } else {
            if ( wp_next_scheduled( 'woocommerce_cart_reset' ) ) {
                $timestamp = wp_next_scheduled( 'woocommerce_cart_reset' );
                wp_unschedule_event( $timestamp, 'woocommerce_cart_reset' );
            }
            $wc_cart_reports_options['wccr_expiration'] = false;
        }

        /* Dashboard Range */
        $wc_cart_reports_options['dashboardrange'] = get_option( 'wccr_dashboardrange' );
        if ( ! is_numeric( (int) $wc_cart_reports_options['dashboardrange'] ) || $wc_cart_reports_options['dashboardrange'] < 1 ) {
            $wc_cart_reports_options['dashboardrange'] = 2;
        }
        if ( ! is_numeric( (int) $wc_cart_reports_options['timeout'] ) || $wc_cart_reports_options['timeout'] < 1 ) {
            $wc_cart_reports_options['timeout'] = 1800;
        }

        $wc_cart_reports_options['timeout'] = (int) $wc_cart_reports_options['timeout'];
        if ( function_exists( 'get_product' ) && defined( 'WCCR_COOKIEVALUE' ) ) {
            $session = WCCR_COOKIEVALUE;
        } else {
            $session = session_id();
        }
        $this->receipt = new WCCR_Cart_Receipt( $session );
        add_action( 'woocommerce_checkout_update_order_review', array( $this, 'save_from_ajax' ) );
        add_action( 'woocommerce_cart_updated', array( $this, 'save_receipt' ) );
        add_action( 'woocommerce_created_customer', array( $this, 'save_user_id' ) );
        add_action( 'woocommerce_new_order', array( $this, 'save_order_id' ) );
    }

    /**
     * Callback for Woocommerce_cart_reset
     * Function to periodically clear old carts, if this is configured in the settings.
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function scheduled_cart_reset() {
        global $wc_cart_reports_options;
        $expiration_days = $wc_cart_reports_options['wccr_expiration'];
        $opt_in_settings = $wc_cart_reports_options['wccr_expiration_opt_in'];
        if ( $expiration_days && $expiration_days > 0 && $opt_in_settings ) {
            wccr_abandoned_carts_delete( $expiration_days );
        }
    }

    /**
     * Hooks into the woo action for conversions. This function tells the model that it's now
     * a converted cart and should act as such
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function save_from_ajax( $data ) {
        global $wc_cart_reports_options;
        global $current_user;
        global $options;
        global $woocommerce;
        if ( isset( $_SERVER ) && isset( $wc_cart_reports_options['logip'] ) && $wc_cart_reports_options['logip'] == 'on' ) {
            $this->ip_address = $_SERVER['SERVER_ADDR'];
            $this->user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }
        parse_str( $data, $data_array );
        $billing_first_name = ( isset( $data_array['billing_first_name'] ) ) ? $data_array['billing_first_name'] : '';
        $billing_last_name = ( isset( $data_array['billing_last_name'] ) ) ? $data_array['billing_last_name'] : '';
        $billing_company   = ( isset( $data_array['billing_company'] ) ) ? $data_array['billing_company'] : '';
        $billing_address_1 = ( isset( $data_array['billing_address_1'] ) ) ? $data_array['billing_address_1'] : '';
        $billing_address_2 = ( isset( $data_array['billing_address_2'] ) ) ? $data_array['billing_address_2'] : '';
        $billing_city      = ( isset( $data_array['billing_city'] ) ) ? $data_array['billing_city'] : '';
        $billing_state     = ( isset( $data_array['billing_state'] ) ) ? $data_array['billing_state'] : '';
        $billing_zip       = ( isset( $data_array['billing_zip'] ) ) ? $data_array['billing_zip'] : '';
        $billing_phone     = ( isset( $data_array['billing_phone'] ) ) ? $data_array['billing_phone'] : '';
        $billing_email     = ( isset( $data_array['billing_email'] ) ) ? $data_array['billing_email'] : 'test@test.com';
        $save_arr = array(
            'billing_first_name' => $billing_first_name,
            'billing_last_name'  => $billing_last_name,
            'billing_company'    => $billing_company,
            'billing_address_1'  => $billing_address_1,
            'billing_address_2'  => $billing_address_2,
            'billing_city'       => $billing_city,
            'billing_state'      => $billing_state,
            'billing_zip'        => $billing_zip,
            'billing_phone'      => $billing_phone,
            'billing_email'      => $billing_email
        );
        if ( function_exists( 'get_product' ) ) {
            $session = WCCR_COOKIEVALUE;
        } else {
            $session = session_id();
        }
        $receipt = new WCCR_Cart_Receipt( $session );
        $id      = $receipt->get_id_from_session( $session );
        if ( $id > 0 && $id != '' ) {
            update_post_meta( $id, '_customer_data', $save_arr );
        }
    }

    /**
     * This is the main routine that acts when the visitor makes a change to their cart.
     * First we save the user id and useragent info (if the option is set to "on") Next we
     * populate the receipt object with the products, owner (if exists) and session id.
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function save_receipt() {
        global $wc_cart_reports_options, $woocommerce;

        $user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ( isset( $_SERVER ) && isset( $wc_cart_reports_options['logip'] ) && $wc_cart_reports_options['logip'] == 'on' ) {
            $this->ip_address = $_SERVER['REMOTE_ADDR'];
            $this->user_agent = $user_agent;
        }
        //Get current user, generate full name for use later
        $person = get_current_user_id(); //$person is '' if guest
        if ( function_exists( 'get_product' ) ) {
            $session = WCCR_COOKIEVALUE;
        } else {
            $session = session_id();
        }
        // Don't save if is a search engine
        if ( ! wccr_detect_search_engines( $user_agent ) && ! wccr_is_restricted_role() ) {
            $receipt = new WCCR_Cart_Receipt( $session );
            $receipt->set_owner( $person );
            $receipt->set_products( $woocommerce ); //Grab products from woocommerce global object
            $receipt->save_receipt(); //Save the object to the database
        }
    }

    /**
     * If the user selected "create account" on the checkout page,we send the user id info to the model for saving.
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function save_user_id( $user_id ) {
        if ( function_exists( 'get_product' ) ) {
            $session = WCCR_COOKIEVALUE;
        } else {
            $session = session_id();
        }
        if ( WP_DEBUG == true ) {
            assert( is_numeric( $user_id ) );
        }
        if ( ! is_restricted_role() ) :
            $post_id = $this->receipt->get_id_from_session( $session );
            $this->receipt->save_user_id( $user_id, $post_id );
        endif;
    }

    /**
     * Save the order id of the newly created order in the post meta of the cart object
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function save_order_id( $order_id ) {
        if ( function_exists( 'get_product' ) ) {
            $session = WCCR_COOKIEVALUE;
        } else {
            $session = session_id();
        }
        if ( WP_DEBUG == true ) {
            assert( $order_id > 0 );
        }
        if ( ! wccr_is_restricted_role() ) :
            $receipt = new WCCR_Cart_Receipt( $session );
            $id      = $receipt->get_id_from_session( $session );
            $receipt->save_conversion();
            $receipt->save_order_id( $order_id );
        endif;
    }

}

new WCCR_Cart_Reports();