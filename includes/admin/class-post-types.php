<?php 

/**
 * Class WC_Cart_Reports_Post_Types
 */

 class WC_Cart_Reports_Post_Types{

    /**
     * Constructor
     * 
     * @return null
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'init', [ $this, 'create_custom_tax' ], 0 ); //Init, create custom stuff first
        add_action( 'init', [ $this, 'cart_add_type_init' ] );
    }

    /**
     * Create custom taxonomy for wc-cart-reports
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function create_custom_tax() {
        register_taxonomy( 'wccr_shop_cart_status', array( 'wccr_carts' ), array(
			'hierarchical'          => false,
			'update_count_callback' => '_update_post_term_count',
			'labels'                => array(
				'name'              => __( 'Cart statuses', 'wc-cart-reports' ),
				'singular_name'     => __( 'Cart status', 'wc-cart-reports' ),
				'search_items'      => __( 'Search Cart statuses', 'wc-cart-reports' ),
				'all_items'         => __( 'All Cart statuses', 'wc-cart-reports' ),
				'parent_item'       => __( 'Parent Cart status', 'wc-cart-reports' ),
				'parent_item_colon' => __( 'Parent Cart status:', 'wc-cart-reports' ),
				'edit_item'         => __( 'Edit Cart status', 'wc-cart-reports' ),
				'update_item'       => __( 'Update Cart status', 'wc-cart-reports' ),
				'add_new_item'      => __( 'Add New Cart status', 'wc-cart-reports' ),
				'new_item_name'     => __( 'New Cart status Name', 'wc-cart-reports' )
			),
			'show_in_nav_menus'     => false,
			'public'                => false,
			'show_ui'               => false,
			'query_var'             => is_admin(),
			'rewrite'               => false,
		) );
		$cart_status = array( 'open', 'converted' );
		foreach ( $cart_status as $status ) {
			if ( ! get_term_by( 'slug', sanitize_title( $status ), 'wccr_shop_cart_status' ) ) {
				wp_insert_term( $status, 'wccr_shop_cart_status' );
			}
		}
    }

    /**
     * Create register custom post type for wc-cart-reports
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function cart_add_type_init() {
        register_post_type( 'wccr_carts', array(
			'label'               => __( 'Carts', 'wc-cart-reports' ),
			'description'         => '',
			'public'              => false,
			'show_ui'             => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'rewrite'             => array( 'slug' => '' ),
			'query_var'           => true,
			'supports'            => array( 'title', 'author' ),
			'labels'              => array(
				'name'               => __( 'Carts', 'wc-cart-reports' ),
				'singular_name'      => __( 'Cart', 'wc-cart-reports' ),
				'menu_name'          => __( 'Carts', 'wc-cart-reports' ),
				'add_new'            => __( 'Add Cart', 'wc-cart-reports' ),
				'add_new_item'       => '',
				'edit'               => __( 'Edit', 'wc-cart-reports' ),
				'edit_item'          => __( 'Cart Details', 'wc-cart-reports' ),
				'new_item'           => __( 'New Cart', 'wc-cart-reports' ),
				'view'               => __( 'View Cart', 'wc-cart-reports' ),
				'view_item'          => __( 'View Cart', 'wc-cart-reports' ),
				'search_items'       => __( 'Search Carts', 'wc-cart-reports' ),
				'not_found'          => __( 'No Carts Found', 'wc-cart-reports' ),
				'not_found_in_trash' => __( 'No Carts Found in Trash', 'wc-cart-reports' ),
				'parent'             => __( 'Parent Cart', 'wc-cart-reports' ),

			),
			'exclude_from_search' => true,
			'show_in_menu'        => 'woocommerce',
			'show_in_nav_menus'   => true,
			'capabilities'        => array(
				'create_posts' => false,
			),
			'map_meta_cap'        => true,
		) );
    }
 }

 new WC_Cart_Reports_Post_Types();