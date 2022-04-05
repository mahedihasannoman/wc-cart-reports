<?php 

/**
 * Delete Cart Data
 * 
 * @param bool
 * 
 * @since 1.0.0
 *
 * @return void
 */
function wccr_abandoned_carts_delete( $older_than_in_days = false ) {
    global $wpdb;
    $sql = 'SELECT * FROM ' . $wpdb->prefix . "posts WHERE post_type = 'wccr_carts'";
    if ( $older_than_in_days ) {
        $sql .= " AND post_date < DATE_SUB(CURDATE(),INTERVAL $older_than_in_days DAY)";
    }
    $result = $wpdb->get_results( $sql );
    foreach ( $result as $cart ) {
        $delete_meta_sql = 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE post_id = '" . $cart->ID . "'";
        $wpdb->query( $delete_meta_sql );
        $delete_sql = 'DELETE FROM ' . $wpdb->prefix . "posts WHERE ID = '" . $cart->ID . "'";
        $wpdb->query( $delete_sql );
    }
}

/**
 * Detect Search Engine
 *
 * @since 1.0.0
 * 
 * @param string $useragent
 * 
 * @return bool
 */
function wccr_detect_search_engines( $useragent ) {
	$search_engines   = array(
		'Googlebot',
		'Slurp',
		'search.msn.com',
		'nutch',
		'simpy',
		'bot',
		'ASPSeek',
		'crawler',
		'msnbot',
		'Libwww-perl',
		'FAST',
		'Baidu',
	);
	$is_search_engine = false;
	foreach ( $search_engines as $search_engine ) {
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) && false !== stripos( $useragent, $search_engine ) ) {
			$is_search_engine = true;
			break;
		}
	}
	if ( $is_search_engine ) {
		return true;
	}
	return false;
}

/**
 * Check is restricted role
 * 
 * @since 1.0.0
 *
 * @return bool
 */
function wccr_is_restricted_role() {
	global $current_user;
	global $wc_cart_reports_options;
	$excluded_roles = $wc_cart_reports_options['trackedroles'];
	if ( is_user_logged_in() ) :
		$user       = new WP_User( $current_user->ID );
		$user_roles = $user->roles;
		$current_u  = array_shift( $user_roles );
	else :
		$current_u = 'guest';
	endif;

	if ( isset( $excluded_roles ) && ! empty( $excluded_roles ) ) {
		foreach ( $excluded_roles as $excluded_role ) :
			if ( $current_u == $excluded_role ) {
				return true;
			}
		endforeach;
	}
	return false;
}

/**
 * Print out tool tip code, input contains desired text, requires
 * 
 * @since 1.0.0
 * 
 * @return string
 */
function wccr_tooltip( $text, $print = true ) {
	global $woocommerce;
	$disp = '<img class="help_tip" data-tip="' . $text . '" src="' . $woocommerce->plugin_url(
		) . '/assets/images/help.png" />';
	if ( $print ) {
		echo $disp;
	} else {
		return $disp;
	}
}

/**
 * Generate at-a-glance stats for the dashboard widget. Generates both ranged-induced
 * and lifetime values and returns an array.
 * 
 * @since 1.0.0
 * 
 * @param bool $range
 * 
 * @return array $vals
 */
function wccr_woocommerce_cart_numbers( $range = false ) {
	//meta_value is the cart type you'd like to count
	global $wpdb;
	$args = array(
		'numberposts'      => - 1,
		'offset'           => 0,
		'orderby'          => 'post_date',
		'order'            => 'DESC',
		'post_type'        => 'wccr_carts',
		'post_status'      => 'publish',
		'suppress_filters' => false,

		'tax_query' => array(
			array(
				'taxonomy' => 'wccr_shop_cart_status',
				'terms'    => 'open',
				'field'    => 'slug',
				'operator' => 'IN'
			)
		)
	);

	if ( $range ) {
		add_filter( 'posts_where', 'wccr_dashboard_stats_where_abandoned_range' );
	} else {
		add_filter( 'posts_where', 'wccr_dashboard_stats_where_abandoned_lifetime' );
	}
	$abandoned = count( get_posts( $args ) );
	if ( $range ) {
		remove_filter( 'posts_where', 'wccr_dashboard_stats_where_abandoned_range' );
	} else {
		remove_filter( 'posts_where', 'wccr_dashboard_stats_where_abandoned_lifetime' );
	}

	$args = array(
		'numberposts'      => - 1,
		'offset'           => 0,
		'orderby'          => 'post_date',
		'order'            => 'DESC',
		'post_type'        => 'wccr_carts',
		'post_status'      => 'publish',
		'suppress_filters' => false,

		'tax_query' => array(
			array(
				'taxonomy' => 'wccr_shop_cart_status',
				'terms'    => 'open',
				'field'    => 'slug',
				'operator' => 'IN'
			)
		)

	);

	add_filter( 'posts_where', 'wccr_dashboard_stats_where_open' );
	$open = count( get_posts( $args ) );
	remove_filter( 'posts_where', 'wccr_dashboard_stats_where_open' );

	$args = array(
		'numberposts'      => - 1,
		'offset'           => 0,
		'orderby'          => 'post_date',
		'order'            => 'DESC',
		'post_type'        => 'wccr_carts',
		'post_status'      => 'publish',
		'suppress_filters' => false,

		'tax_query' => array(
			array(
				'taxonomy' => 'wccr_shop_cart_status',
				'terms'    => 'converted',
				'field'    => 'slug',
				'operator' => 'IN'
			)
		)
	);

	if ( $range ) {
		add_filter( 'posts_where', 'wccr_dashboard_stats_where_converted' );
	}
	$converted = count( get_posts( $args ) );
	if ( $range ) {
		remove_filter( 'posts_where', 'wccr_dashboard_stats_where_converted' );
	}

	$vals = array( 'Converted' => $converted, 'Abandoned' => $abandoned, 'Open' => $open );

	return $vals;
}

/**
 * Filter for the range-induced abandoned section on the dashboard
 * 
 * @since 1.0.0
 *
 * @param string $where
 * 
 * @return string $where
 */
function wccr_dashboard_stats_where_abandoned_range( $where ) {
	global $wc_cart_reports_options;
	global $offset;
	if ( WP_DEBUG == true ) {
		assert( is_numeric( $offset ) );
	}
	$where .= " AND post_modified > '" . date(
			'Y-m-d G:i:s',
			time() + ( $offset * 3600 ) - ( $wc_cart_reports_options['dashboardrange'] * 24 * 60 * 60 )
		);

	$where .= "' and post_modified < '" . date(
			'Y-m-d G:i:s',
			time() + ( $offset * 3600 ) - $wc_cart_reports_options['timeout']
		) . "' ";
	return $where;
}

/**
 * Filter for the abandoned lifetime fields on the dashboard widget
 * 
 * @since 1.0.0
 *
 * @param string $where
 * 
 * @return string $where
 */
function wccr_dashboard_stats_where_abandoned_lifetime( $where ) {
	global $wc_cart_reports_options;
	global $offset;
	if ( WP_DEBUG == true ) {
		assert( is_numeric( $offset ) );
	}
	$where .= " AND post_modified < '" . date(
			'Y-m-d G:i:s',
			time() + ( $offset * 3600 ) - $wc_cart_reports_options['timeout']
		) . "' ";
	return $where;
}

/**
 * wp filter for the open filter on the index page
 * 
 * @since 1.0.0
 *
 * @param string $where
 * 
 * @return string $where
 */
function wccr_dashboard_stats_where_open( $where ) {
	global $wc_cart_reports_options;
	global $offset;
	if ( WP_DEBUG == true ) {
		assert( is_numeric( $offset ) );
	}
	$where .= " AND post_modified > '" . date(
			'Y-m-d G:i:s',
			time() + ( $offset * 3600 ) - $wc_cart_reports_options['timeout']
		) . "' ";
	return $where;
}

/**
 * wp filter for the converted filter on the index page
 * 
 * @since 1.0.0
 *
 * @param string $where
 * 
 * @return string $where
 */
function wccr_dashboard_stats_where_converted( $where ) {
	global $wc_cart_reports_options;
	global $offset;
	if ( WP_DEBUG == true ) {
		assert( is_numeric( $offset ) );
	}
	$where .= " AND post_modified > '" . date(
			'Y-m-d G:i:s',
			time() + ( $offset * 3600 ) - ( $wc_cart_reports_options['dashboardrange'] * 60 * 60 * 24 )
		) . "' ";
	return $where;
}

/**
 * Cart Abandoned within range
 * 
 * @since 1.0.0
 *
 * @param string $where
 * @return string $where
 */
function wccr_carts_abandoned_within_range( $where = '' ) {
	global $start_date, $end_date, $woocommerce_cart_reports_options, $offset;
	$timeout = $woocommerce_cart_reports_options['timeout'];
	$current_date = date( 'Y-m-d' );
	if ( $end_date == strtotime( $current_date ) ) {
		$end_date = time() + ( $offset * 3600 );
		$before   = date( 'Y-m-d G:i:s', $end_date - $timeout );
	} else {
		$before = date( 'Y-m-d', strtotime( '+1 day', $end_date ) );
	}
	$timeout = $woocommerce_cart_reports_options['timeout'];
	$after   = date( 'Y-m-d', (int) $start_date );
	$where  .= " AND post_modified > '$after'";
	$where  .= " AND post_modified < '$before'";

	return sprintf( ' AND post_modified > %s AND post_modified < %s', $after, $before );
}