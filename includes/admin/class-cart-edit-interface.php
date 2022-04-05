<?php

/**
 * Class WCCR_Edit_Interface
 */

class WCCR_Edit_Interface {

    /**
     * Receipt
     * 
     * @since 1.0.0
     *
     * @var object
     */
	public $receipt;

    /**
     * Constructor
     * 
     * @since 1.0.0
     * 
     * @return null
     */
	public function __construct() {
		global $post;
		$this->receipt = new WCCR_Cart_Receipt();
		if ( isset( $post ) ) {
			$this->receipt->load_receipt( $post->ID );
		}
		//Add in title, since we removed the "Title Meta Box"
		add_action( 'admin_enqueue_scripts', array( &$this, 'tooltip_scripts' ) );
		//these meta boxes default to the right
		add_action( 'add_meta_boxes', array( $this, 'cart_status_meta_boxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'cart_action_meta_boxes' ) );
		//these meta boxes default to the left
		add_action( 'add_meta_boxes', array( $this, 'cart_status_customer_meta_boxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_cart_items_boxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'cart_useragent_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'remove_title_box' ) );
		add_action( 'admin_head', array( $this, 'remove_woocustom_box' ) );
		add_action( 'admin_menu', array( $this, 'remove_publish_box' ) );
		add_action( 'admin_menu', array( $this, 'remove_author_box' ) );
		add_action( 'admin_menu', array( $this, 'remove_slugdiv_box' ) );
	}

	/**
	 * @param $title
     * 
     * @since 1.0.0
	 *
	 * @return string
	 */
	public function custom_edit_title( $title ) {
		return 'View Cart ' . $title;
	}

	/**
	 * Callback for admin_enqueue_scripts
     * 
     * @since 1.0.0
     * 
     * @return null 
	 */
	public function tooltip_scripts() {
		global $pagenow;
		if ( is_admin() ) {
			if ( $pagenow == 'post.php' && get_post_type( get_post( $_GET['post'] ) ) == 'wccr_carts' ) {
				global $woocommerce;
				wp_enqueue_script( 'woocommerce_admin' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui' );
				wp_enqueue_script( 'ajax-chosen' );
				wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
				wp_enqueue_style(
					'wc_cart_report_admin_edit_css',
					WC_CART_REPORTS_ASSETS_URL . '/css/cart_reports_admin_edit.css'
				);
				wp_register_script(
					'jquery-tiptip',
					WC_CART_REPORTS_ASSETS_URL . '/js/jquery.tipTip.minified.js'
				);
				wp_enqueue_script( 'jquery-tiptip' );
				wp_enqueue_script( 'moment-js', WC_CART_REPORTS_ASSETS_URL . '/js/moment.min.js' );
				wp_enqueue_script(
					'moment-duration',
					WC_CART_REPORTS_ASSETS_URL . '/js/moment-duration-format.js',
					array( 'moment-js' )
				);
				$inline_js = "jQuery('.help_tip').tipTip({
					'attribute' : 'data-tip',
					'fadeIn' : 50,
					'fadeOut' : 50,
					'delay' : 200
				});";
				if ( function_exists( 'wc_enqueue_js' ) ) { //Check for compatibility
					wc_enqueue_js( $inline_js );
				} else {
					$woocommerce->add_inline_js( $inline_js );
				}

			}
		}
	}

	/**
	 * Set up the the cart status metabox and point to our handy callback - cart_status_metabox
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_status_meta_boxes() {
		add_meta_box(
			'cart_status_meta_boxes',
			__( 'Cart Status', 'wc-cart-reports' ),
			array(
				$this,
				'cart_status_metabox',
			),
			'wccr_carts',
			'side',
			'default'
		);
	}

	/**
	 * Set up Customer actions metabox
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_action_meta_boxes() {
		add_meta_box(
			'cart_action_meta_boxes',
			__( 'Customer Actions', 'wc-cart-reports' ),
			array(
				$this,
				'cart_action_customer_metabox',
			),
			'wccr_carts',
			'side',
			'default'
		);
	}

	/**
	 * Cart Customer metabox, show customer name where available.
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_status_customer_meta_boxes() {
		add_meta_box(
			'cart_status_customer_meta_boxes',
			__( 'Cart Customer', 'wc-cart-reports' ),
			array(
				$this,
				'cart_status_customer_metabox',
			),
			'wccr_carts',
			'normal',
			'default'
		);
	}

	/**
	 * Add metabox to show items in the cart, complete with a bunch of info about the items
	 * Layout was taken from the order details page( thanks woo!)
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function add_cart_items_boxes() {
		add_meta_box(
			'woocommerce-cart-items',
			__( 'Cart Items', 'wc-cart-reports' ),
			array(
				$this,
				'woocommerce_cart_items_meta_box',
			),
			'wccr_carts',
			'normal',
			'default'
		);
	}

	/**
	 *
	 * Set up the "Cart Data" holding the front-facing fields for "last online, "last_updated", "ip address" and "cart
	 * age/time to conversion"
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_useragent_meta_boxes() {
		add_meta_box(
			'cart_useragent_meta_boxes',
			__( 'Cart Data', 'wc-cart-reports' ),
			array(
				$this,
				'cart_useragent_meta_box',
			),
			'wccr_carts',
			'normal',
			'default'
		);
	}

	/**
	 * "Customer Name" box implementation
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_status_customer_metabox() {
		$full_name = $this->receipt->full_name();
		$author_id = $this->receipt->post_author;
		if ( $author_id > 0 ) {
			if ( WP_DEBUG == true ) {
				assert( $full_name != '' && $full_name != ' ' );
			}
			$user_edit_url = admin_url( 'user-edit.php?user_id=' . $author_id );
			echo __( '<a href="' . $user_edit_url . '">' . $full_name . '</a>' );
		} elseif ( $full_name != false ) {
			if ( WP_DEBUG == true ) {
				assert( $full_name != '' && $full_name != ' ' );
			}
			echo __( '<p>' . $full_name . '</p>' );
			//Print out actions
		} elseif ( $this->receipt->status() == 'Converted' ) {
			$order_id = $this->receipt->get_order_id();
			if ( WP_DEBUG == true ) {
				assert( $order_id > 0 );
			}
			$order = new WC_Order( $order_id );
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				echo __(
					'<p>' . ucwords( $order->billing_first_name . ' ' . $order->billing_last_name ) . ' (' . __(
						'Guest',
						'wc-cart-reports'
					) . ')</p>'
				);
			} else {
				echo __(
					'<p>' . ucwords(
						$order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
					) . ' (' . __( 'Guest', 'wc-cart-reports' ) . ')</p>'
				);
			}
		} else {
			echo __( '<span style="color: gray">Name Not Available</span>', 'wc-cart-reports' ) . wccr_tooltip(
					__(
						'No customer name information available for carts created by non-logged-in Guests.',
						'wc-cart-reports'
					),
					false
				);
		}

	}

	/**
	 * "Cart Status" Box implementation
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_status_metabox() {
		global $wc_cart_reports_options;
		global $post;
		$tooltip = '';
		$this->receipt->load_receipt( $post->ID );
		$timeout = $wc_cart_reports_options['timeout'];
		//Show Cart State
		$show_custom_state = $this->receipt->status();
		$timeout_sec       = $timeout;
		$timeout_min       = $timeout_sec / 60;
		if ( WP_DEBUG == true ) {
			assert(
				$show_custom_state == 'Abandoned' || $show_custom_state == 'Converted' || $show_custom_state == 'Open'
			);
		}
		echo __(
			'<div><p><strong>Created:</strong><br/>' . date(
				'F j, Y \a\t g:i a',
				$this->receipt->created()
			) . '</p></div>'
		);

		switch ( $show_custom_state ) {

			case 'Abandoned':
				$tooltip = wccr_tooltip(
					__(
						"A cart becomes <i>Abandoned</i> when the cart's owner has not accessed the site in an amount of time exceeding your timeout set in the <i>WooCommerce Cart Reports</i> settings page. Your current timeout is set to $timeout_min Minutes. Don't worry! The cart will become open again when the customer returns.",
						'wc-cart-reports'
					),
					false
				);
				break;

			case 'Open':
				$tooltip = wccr_tooltip(
					__(
						"A cart is considered <i>Open</i> when the customer has accessed the site with items in the cart, within the timeout set in the <i>WooCommerce Cart Reports</i> settings page. Your current timeout is set to $timeout_min Minutes.",
						'wc-cart-reports'
					),
					false
				);
				break;

			case 'Converted':
				$tooltip = wccr_tooltip(
					__(
						'A cart becomes <i>Converted</i> when the customer purchases the cart contents. Congrats :) ',
						'wc-cart-reports'
					),
					false
				);
				break;
		}

		echo __(
			'<div id="edit_status"><mark class="color-wrapper ' . strtolower( $show_custom_state ) . '_edit">' . __(
				$show_custom_state,
				'wc-cart-reports'
			) . $tooltip . '</mark></div>'
		);
	}

	/**
	 * "Cart Data" box implementation
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_useragent_meta_box() {
		global $post;
		$this->receipt->load_receipt( $post->ID );
		$ip = $this->receipt->ip_address;
		echo '<div class = "woocommerce_cart_reports_clientdata_wrapper">';
		if ( $this->receipt->status() == 'Converted' ) {
			$width_p = '17';
		} else {
			$width_p = '20';
		}
		echo '<table cellpading="0" width="100%" cellspacing="0" class="woocommerce_cart_reports_clientdata_items">';
		echo '<thead>';
		echo sprintf(
			'<tr><th class="hist1" width="%s%%" style="text-align:left">%s%s</th>',
			$width_p,
			__(
				'Cart Last Updated',
				'wc-cart-reports'
			),
			wccr_tooltip(
				__(
					'<i>Cart Last Updated</i> indicates the last time the customer performed a cart-related action on your site. These actions include viewing the cart, adding new items to the cart, updating quantities, or removing products from the cart.',
					'wc-cart-reports'
				),
				false
			)
		);          //If the cart is converted, show how long it spent as abandoned/open
		if ( $this->receipt->status() == 'Converted' ) {
			echo sprintf(
				'<th class="ip" width="%s%%" style="text-align:left;">%s%s</th>',
				$width_p,
				__(
					'Time To Conversion',
					'wc-cart-reports'
				),
				wccr_tooltip(
					__(
						'<i>Time to Conversion</i> indicates total time elapsed from when the cart was first created until the actual conversion. (purchase)',
						'wc-cart-reports'
					),
					false
				)
			);
		} else {
			echo sprintf(
				'<th class="ip" width="%s%%" style="text-align:left;">%s%s</th>',
				$width_p,
				__(
					'Cart Age',
					'wc-cart-reports'
				),
				wccr_tooltip(
					__(
						'<i>Cart Age</i> indicates the total time elapsed since this non-converted cart has been created.',
						'wc-cart-reports'
					),
					false
				)
			);
		}
		echo sprintf(
			'<th class="ip" width="%s%%" style="text-align:left;">%s</th>',
			$width_p,
			__(
				'Customer IP Address',
				'wc-cart-reports'
			)
		);
		echo '</tr>';
		echo '</thead>';
		echo '<tbody id="client_data_list">';
		echo '<tr class="item td1">';
		?>
		<?php
		//History
		//First Print out the last date the cart was updated
		echo '<td class="lastUpdated"><p>';
		the_modified_date( 'F j, Y' );
		echo ' at ';
		the_modified_date( 'g:i a' );

		echo '</p>';
		echo '</td>';

		if ( $this->receipt->status() == 'Converted' ) {
			$disp = $this->receipt->get_age_text();
			if ( WP_DEBUG == true ) {
				assert( $disp != '' );
			}
			echo "<td><p>$disp</p></td>";
		} else {
			$created               = $this->receipt->created();
			$gmt_offset            = $this->receipt->get_timezone_offset();
			$hour_in_seconds       = 1 * 60 * 60;
			$gmt_offset_seconds    = abs( $gmt_offset * $hour_in_seconds );
			$created_with_timezone = abs( $created + $gmt_offset_seconds );
			if ( WP_DEBUG == true ) {
				assert( $created != '' );
			}
			echo "<td><div id='counter'><span style='color:lightgray;'>" . __(
					'Not Available',
					'wc-cart-reports'
				) . ' </span></div></td>';

			?>
			<script type='text/javascript'>
				function DaysHMSCounter( initDate, id ) {
					this.counterDate = moment( initDate, 'X' )
					this.container = document.getElementById( id )
					this.update()
				}
				DaysHMSCounter.prototype.calculate = function () {
					var now = moment()
					this.duration = moment.duration( now.diff( this.counterDate ) )
				}
				DaysHMSCounter.prototype.update = function () {
					this.calculate()
					this.container.innerHTML = '<p>' + this.duration.format(
						'[<strong>]d[</strong>] __ [<strong>]h[</strong>] _ [<strong>]m[</strong>] _ [<strong>]s[</strong>] _' ) + '</p>'
					var self = this
					setTimeout( function () {
							self.update()
						},
						(
							1000
						)
					)
				}
				window.onload = function () {
					new DaysHMSCounter( '<?php echo $created_with_timezone; ?>', 'counter' )
				}
			</script>
			<?php
		}
		echo '<td class="ip"><p>';

		//IP Address
		if ( $ip != '' ) {
			if ( WP_DEBUG == true ) {
				assert( $ip != '' );
			}
			echo $ip . '</p>';
		} else {
			echo "<span style='color:lightgray;'>" . __( 'Not Available', 'wc-cart-reports' ) . wccr_tooltip(
					__(
						'You have probably unchecked "Log IP Address" in the WooCommerce Cart Reports settings panel.',
						'wc-cart-reports'
					),
					false
				) . '</span><br />';
		}
		echo '</td></tr></table></div>';

	}

	/**
	 * Cart Actions Implementation
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function cart_action_customer_metabox() {
		global $post;
		$this->receipt->load_receipt( $post->ID );
		//Show customer / cart owner
		$this->receipt->print_cart_actions();
	}

    /**
     * Remove Title meta Box from "Cart Edit" page
     * 
     * @since 1.0.0
     *
     * @return void
     */
	public function remove_title_box() {
		remove_post_type_support( 'wccr_carts', 'title' );
	}


    /**
     * Remove Publish meta Box from "Cart Edit" page
     * 
     * @since 1.0.0
     *
     * @return void
     */
	public function remove_publish_box() {
		remove_meta_box( 'submitdiv', 'wccr_carts', 'side' );
	}


    /**
     * Remove Author meta Box from "Cart Edit" page
     * 
     * @since 1.0.0
     *
     * @return void
     */
	public function remove_author_box() {
		remove_meta_box( 'authordiv', 'wccr_carts', 'side' );
	}

	/**
	 * Remove the WooThemes' custom configuration box for posts and pages - not needed!
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function remove_woocustom_box() {
		remove_meta_box( 'woothemes-settings', 'wccr_carts', 'normal' );
	}

	/**
	 * Remove box that shows post slug - we don't need it!
     * 
     * @since 1.0.0
     * 
     * @return null
	 */
	public function remove_slugdiv_box() {
		remove_meta_box( 'slugdiv', 'wccr_carts', 'normal' );
	}

    /**
     * Add Cart Products Box
     *
     * @param object $post
     * 
     * @return void
     */
	public function woocommerce_cart_items_meta_box( $post ) {
		$order_items = (array) maybe_unserialize( get_post_meta( $post->ID, 'wccr_cartitems', true ) );
		?>
		<div class="woocommerce_cart_reports_items_wrapper">
			<?php if ( count( $order_items ) > 0 ) : ?>
			<table cellpadding="0" width="100%" cellspacing="0" class="woocommerce_cart_reports_items">
				<thead>
				<tr>
					<th class="thumb" width="60px" style="text-align:left;">
					</th>
					<th class="sku" style="text-align:left">
						<?php _e( 'SKU', 'wc-cart-reports' ); ?>
					</th>
					<th class="name" style="text-align:left">
						<?php _e( 'Name', 'wc-cart-reports' ); ?>
					</th>
					<th class="price" style="text-align:left">
						<?php _e( 'Price', 'wc-cart-reports' ); ?>
					</th>
					<th class="quantity" style="text-align:left">
						<?php _e( 'Qty', 'wc-cart-reports' ); ?>
					</th>
				</tr>
				</thead>
				<tbody id="cart_items_list">
				<?php
				endif;
				$loop = 0;
			if ( count( $order_items ) > 0 ) {
				foreach ( $order_items as $item ) :
					$_product = wc_get_product( $item['product_id'] );

					if ( $loop % 2 == 0 ) {
						$table_color = ' td1 ';
					} else {
						$table_color = ' td2 ';
					}
					?>
					<?php if ( isset( $_product ) && $_product != false ) : ?>
						<tr class="item <?php echo $table_color; ?>" rel="<?php echo $loop; ?>">
							<td class="thumb">
								<a href="
								<?php
								echo esc_url(
									admin_url( 'post.php?post=' . $_product->get_id() . '&action=edit' )
								);
								?>
								"
								   class="help_tip cart-product-thumbnail" data-tip="
								   <?php
									echo '<strong>' . __(
										'Product ID:',
										'wc-cart-reports'
									) . '</strong> ' . $_product->get_id();
								   echo '<br/><strong>' . __( 'Variation ID:', 'wc-cart-reports' ) . '</strong> ';
								   if ( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
									   echo $item['variation_id'];
								   } else {
									   echo '-';
								   }
								   echo '<br/><strong>' . __( 'Product SKU:', 'wc-cart-reports' ) . '</strong> ';
								   if ( $_product->get_sku() ) {
									   echo $_product->get_sku();
								   } else {
									   echo '-';
								   }
									?>
								">
									<?php echo $_product->get_image(); ?>
								</a>
							</td>
							<td class="sku">
								<?php
								if ( $_product->get_sku() ) {
									echo $_product->get_sku();
								} else {
									echo '-';
								}
								?>
								<input type="hidden" class="item_id" name="item_id[<?php echo $loop; ?>]"
									   value="
									   <?php
										if ( isset( $item->id ) && $item->id != '' ) {
											echo esc_attr( $item->id );
										}
										?>
									   "
								/>
								<input type="hidden" name="item_name[<?php echo $loop; ?>]"
									   value="
									   <?php
										if ( isset( $item->id ) && $item->id != '' ) {
											echo esc_attr( $item->id );
										}
										?>
									   "
								/>
								<?php if ( isset( $item['variation_id'] ) ) : ?>
									<input type="hidden" name="item_variation[<?php echo $loop; ?>]"
										   value="<?php echo esc_attr( $item['variation_id'] ); ?>"
									/>
								<?php endif; ?>
							</td>
							<td class="name">
								<a href="
								<?php
								echo esc_url(
									admin_url( 'post.php?post=' . $item['product_id'] . '&action=edit' )
								);
								?>
								">
									<strong>
										<?php echo $_product->get_title(); ?>
									</strong>
								</a>
								<?php
								if ( isset( $item['variation'] ) && is_array( $item['variation'] ) && count( $item['variation'] ) > 0 ) {
									$variation_data = wc_get_formatted_variation( $item['variation'] );
									echo '&nbsp;' . $variation_data;
								}
								?>
							</td>
							<td class="price">
								<p>
									<?php
									// if we have the properly filtered price, display it
									// otherwise fall back to the old method
									if ( isset( $item['price'] ) ) {
										echo $item['price'];
									} else {
										echo $_product->get_price_html();
									}
									?>
								</p>
							</td>
							<td class="quantity">
								<p>
									<?php echo $item['quantity']; ?>
								</p>
							</td>

						</tr>
					<?php endif; ?>
						<?php
						$loop ++;
					endforeach;
			} else {
				//Explain to the user why no products could show up in a recently abandoned / opened cart
				?>
					<span style="color:gray;"><?php echo __( 'No Products In The Cart', 'wc-cart-reports' ) ?></span>
				<?php
				wccr_tooltip(
					__(
						'When a customer adds
			 items to a cart, then abandons the cart for a considerable amount of time, the browser often deletes the cart data. The
			 cart still belongs to the customer, but their browser removed the products. :( But hey! This indicates that they came back.
			 And might be ready to purchase. ',
						'wc-cart-reports'
					)
					);
				?>
					<?php
			}
			?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

new WCCR_Edit_Interface();