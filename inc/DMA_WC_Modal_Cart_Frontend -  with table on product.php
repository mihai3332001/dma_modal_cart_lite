<?php
namespace DMAWMCA;

if (!defined('ABSPATH')) :
	exit;
endif;

use DMAWMCA\DMA_Utils;

/**
 * Class DMA_WC_Modal_Cart_Frontend
 *
 *
 * @since 1.0.0
 */
class DMA_WC_Modal_Cart_Frontend {

	public static $instance = null;

	private $get_options;

	/**
    * Holds the option css name
    */
    private $option_name; 

	public function __construct() {
		$this->option_name = DMA_Utils::PLUGIN_OPTION_NAME;
		$this->get_hooks();
	}



	/**
 	* Returns the instance.
 	*
 	* @return instance
 	*/
	public static function get_frontend_instance() {
		if (self::$instance == null) {
            self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_hooks() {
		add_action( 'wp_enqueue_scripts',  array( $this, 'frontend_enqueue_scripts' ) );
		add_filter('woocommerce_add_to_cart_fragments', array($this, 'dma_woocommerce_header_add_to_cart_fragment') );
		add_filter('woocommerce_add_to_cart_fragments', array($this, 'dma_woocommerce_header_add_to_cart_fragment_related_products') );
		add_filter('woocommerce_add_to_cart_fragments', array($this, 'dma_woocommerce_count_cart_fragment') );
		add_action('woocommerce_before_mini_cart', array($this, 'dma_woocommerce_before_mini_cart'));
		if(version_compare(WC_VERSION, '3.7.0') >= 0){
			add_action('woocommerce_widget_shopping_cart_total', array($this, 'dma_add_total'), 10);
		} 	
		add_action('wp_head', array($this, 'customize_css') );
		add_action('woocommerce_widget_shopping_cart_buttons', array($this, 'dma_buttons_shopping_cart'), 10 );
		add_action( 'wp_footer', array( $this, 'dma_create_div_for_modal' ) );
	}

	/**
	 * WP Frontend Enqueue Scripts.
	 */
	public function frontend_enqueue_scripts() {
		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );
		$dma_modal_cart_show_ajax_on_loop_products = $get_options['dma_modal_cart_show_ajax_on_loop_products'] ?? null;
		$dma_modal_cart_show_ajax_on_single_product = $get_options['dma_modal_cart_show_ajax_on_single_product'] ?? null;
		$dma_modal_cart_effects = $get_options['dma_modal_cart_effects'] ?? null;
		wp_enqueue_style( 'dma_frontend_css', DMA_DIR . 'assets/css/dma_frontend_style.css');
		wp_enqueue_style( 'dma_frontend_icons', DMA_DIR . 'assets/css/icons.css');
		wp_enqueue_style( 'dma_frontend_bootstrap', DMA_DIR . 'assets/css/bootstrap.min.css');
		wp_enqueue_script('bootstrap_js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js');
		wp_enqueue_script( 'dma_frontend_js', DMA_DIR . 'assets/js/dma_frontend.js',array('jquery'), '1.0.0', true );
		wp_localize_script( 'dma_frontend_js', 'ajax_object', array(
    	'ajax_url'    => admin_url( 'admin-ajax.php' ),
    	'nonce' => wp_create_nonce( "unique_id_nonce" ),
    	'dma_ajax_cart_loop_products' 	=> $dma_modal_cart_show_ajax_on_loop_products,
    	'dma_ajax_cart_single_product' 	=> $dma_modal_cart_show_ajax_on_single_product,
    	'dma_ajax_cart_effects'			=> $dma_modal_cart_effects,
		) );
	}

		/**
 	* Returns the instance.
 	*
 	* @return mini-cart products
 	*/
	public function dma_woocommerce_header_add_to_cart_fragment_widget ($fragments) {

	}

	/**
 	* Returns the instance.
 	*
 	* @return related products
 	*/
	public function dma_woocommerce_header_add_to_cart_fragment_related_products ($fragments) {

		global $woocommerce, $product, $post;

		ob_start();	

		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );
		
		if($get_options == false){
	       return;
		}
		extract($get_options);
		$get_linked_products = array();	

		$limit = 5;
		$ids = array();
		$cart = WC()->cart->get_cart();
		if($cart != null) :
		foreach ($cart as $key=>$value) {
			$product_id = $value['product_id'];
			$ids[] = $product_id;
		}
		$id = end($cart)['product_id'];

		$wc_product = wc_get_product( $id );

			if($wc_product) :

				if(!empty($dma_modal_cart_create_woo_linked_products) ) {
					$get_linked_products = get_post_meta( $id, '_dma_modal_cart_create_woo_linked_products', true );
				}  
				else if($dma_modal_cart_woo_linked_products == 'Cross-sells') {
					$get_linked_products = $wc_product->get_cross_sell_ids();
				} 
				else if($dma_modal_cart_woo_linked_products == 'Upsells') {
					$get_linked_products = $wc_product->get_upsell_ids();
				}

				$diff_related_ids = array_diff($get_linked_products, $ids);
				$related_ids = array_slice($diff_related_ids, 0, $dma_modal_cart_limit_linked_products);
			endif;

			if ( is_array($related_ids) && !empty($related_ids)) : ?>

				<div class="cross-sells <?php esc_attr_e($dma_modal_cart_section_linked_products_custom_class_css); ?>">

    				<?php woocommerce_product_loop_start(); ?>

      					<h2 class="title_before_linked_products <?php esc_attr_e($dma_modal_cart_title_linked_products_custom_class_css); ?>"><?php esc_html_e( $dma_modal_cart_title_before_linked_products, 'dma-woo-modal-cart-lite' ) ?></h2>

        				<?php foreach ( $related_ids as $related_ids ) : 

                		$post_object = get_post( $related_ids );

                		setup_postdata( $GLOBALS['post'] =& $post_object );

                		wc_get_template_part( 'content', 'product' ); ?>

        				<?php endforeach; ?>

    				<?php woocommerce_product_loop_end(); ?>

				</div>

			<?php endif;

    		$fragments['related_products'] = ob_get_clean();
    		endif;
		return $fragments;
	}

	public function dma_woocommerce_header_add_to_cart_fragment( $fragments ) {

    ob_start();   

    	if ( !class_exists( 'woocommerce' ) ) {
    		return;
    	} 
    ?>

    	<div class="dma_widget_shopping_cart_content">
    	<?php $this->mini_cart(); ?>
    	<button type="button" class="btn dma_modal_close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    	</div><!--dma_widget_shopping_cart_content-->

    <?php

    $fragments['div.dma_widget_shopping_cart_content'] = ob_get_clean();

    return $fragments;

  	}

  	public function dma_woocommerce_count_cart_fragment( $fragments ) {

    ob_start();   

    	if ( class_exists( 'woocommerce' ) ) : ?>

                    <span id="dma_count_cart"><?php echo wp_kses_data(WC()->cart->get_cart_contents_count()); ?></span>

        <?php endif;

    $fragments['#dma_count_cart'] = ob_get_clean();

    return $fragments;

  	}

  	public function dma_create_div_for_modal() {
  		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );
  		if($get_options == false) :
  			exit;
  		endif;

  		extract($get_options);
  		echo '<div id="modal_bootstrap" class="dma_modal site-main dma_modal_'. $dma_modal_cart_position . ' ' . $dma_modal_cart_effects. '" role="dialog"><div class="popupCart dma_popup_cart_'. $dma_modal_cart_position .' '. $dma_modal_cart_custom_class_css . ' ' . $dma_modal_cart_effects.'" id="'.$dma_modal_cart_custom_id_attribute.'"></div></div>';
  		
  		if(isset($dma_modal_cart_show_button_count)) :
  		?>	
		<div class="dma_fixed_cart">
			<i class="<?php echo esc_attr($dma_modal_cart_button_count_icon); ?>"></i>
			<span id="dma_count_cart">
				<?php echo wp_kses_data(WC()->cart->get_cart_contents_count()); ?>
			</span>
		</div>
		<?php
		endif;
  	}

  	public function dma_woocommerce_before_mini_cart() {
  		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );
  		if($get_options == false) :
  			exit;
  		endif;
  		extract($get_options);
  		?>
  		<div class="title_dma_modal_cart <?php esc_attr_e($dma_modal_cart_title_custom_class_css); ?>"><?php esc_html_e($dma_modal_cart_title, 'dma-woo-modal-cart-lite'); ?></div>
  		<?php
  	}

  	public function dma_buttons_shopping_cart() {
		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );
  		if($get_options == false) :
  			exit;
  		endif;
  		extract($get_options);

  		echo '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward '. esc_attr($dma_modal_cart_button_view_cart_custom_class_css) . '" id="view-cart">' . esc_html__( 'View cart', 'woocommerce' ) . '</a>';
  		echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="button checkout wc-forward '. esc_attr($dma_modal_cart_button_checkout_custom_class_css) . '"  id="checkout">' . esc_html__( 'Checkout', 'woocommerce' ) . '</a>';
  	}

  	public function dma_add_total() {
  		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );
  		if($get_options == false) :
  			exit;
  		endif;
  		extract($get_options);
  		?>
		<table cellspacing="0" class="shop_table shop_table_responsive <?php esc_attr_e($dma_modal_cart_price_custom_class); ?>">

		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>
		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>"><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>
		</table>
		<?php
  	}

	/**
	* 
	* customize css style of modal
	*/
  	public function customize_css() {
  		$get_options = get_option( $this->option_name, DMA_Utils::default_values() );

 		if($get_options == false) {
 			exit;
 		}
  			extract($get_options);
  			$dma_modal_cart_show_subtotal = isset($dma_modal_cart_show_subtotal) ? 'flex' : 'none'; 
			$dma_modal_cart_show_total = isset($dma_modal_cart_show_total) ? 'flex' : 'none'; 	
			$dma_modal_cart_show_close_button = isset($dma_modal_cart_show_close_button) ? 'flex' : 'none';
			$dma_modal_cart_show_button_view_cart = isset($dma_modal_cart_show_button_view_cart) ? 'inline-flex' : 'none';
			$dma_modal_cart_show_button_checkout = isset($dma_modal_cart_show_button_checkout) ? 'inline-flex' : 'none';

			if(isset($dma_modal_cart_background_color) && isset($dma_modal_cart_background_opacity)) {
      		$dma_modal_cart_background_preview = self::hex2rgba($dma_modal_cart_background_color, $dma_modal_cart_background_opacity);  		
    		}		

  			if(isset($dma_modal_cart_font_family)) { 
  			?>
				<style type="text/css">
					.popupCart {
						font-family: <?php esc_attr_e($dma_modal_cart_font_family); ?>;
					}
				</style>

			<?php } else if (!empty($dma_modal_cart_google_font_family)) {

				$font = str_replace(' ', '+', $dma_modal_cart_google_font_family);  ?>

				<style type="text/css">
					@import url('https://fonts.googleapis.com/css2?family=<?php echo $font; ?>&display=swap');
					.popupCart {
						font-family: <?php esc_attr_e($dma_modal_cart_google_font_family); ?>;
					}
				</style>

			<?php }  ?>

  				<style type="text/css">
	  				.popupCart {
					   	background: <?php echo $dma_modal_cart_background_preview; ?>;
					   	color:<?php esc_attr_e($dma_modal_cart_text_color); ?>;
	    				width: <?php echo $dma_modal_cart_width_modal; ?>;
					}

					.dma_modal .popupCart .title_dma_modal_cart {
						color:<?php esc_attr_e($dma_modal_cart_title_color); ?>;
						font-size: <?php esc_attr_e($dma_modal_cart_title_font_size); ?>px;
						text-align: <?php esc_attr_e($dma_modal_cart_title_align); ?>;
						font-weight: <?php esc_attr_e($dma_modal_cart_title_font_weight); ?>;
						text-transform: <?php esc_attr_e($dma_modal_cart_title_text_transform); ?>;
						padding-top: <?php esc_attr_e($dma_modal_cart_title_padding_top); ?>;
						padding-bottom: <?php esc_attr_e($dma_modal_cart_title_padding_bottom); ?>;
					}

					.dma_modal .popupCart .shop_table_responsive .cart-subtotal {
						display: <?php esc_attr_e($dma_modal_cart_show_subtotal); ?>;
					}

					.dma_modal .popupCart .shop_table_responsive .order-total {
						display: <?php esc_attr_e($dma_modal_cart_show_total); ?>;
					}
					.dma_modal .popupCart .shop_table_responsive {
						background-color: <?php esc_attr_e($dma_modal_cart_price_background_color); ?>;
						color: <?php esc_attr_e($dma_modal_cart_price_text_color); ?>;
						border-radius: 0;
						width: 100%;
    					border: 1px solid rgba(0,0,0,.1);
					}

					.popupCart .cross-sells {
						background: <?php echo $dma_modal_cart_background_preview; ?>;
					}

					.popupCart .woocommerce-mini-cart__buttons {
						padding-top: <?php esc_attr_e($dma_modal_cart_buttons_padding_top); ?>;
  						padding-bottom: <?php esc_attr_e($dma_modal_cart_buttons_padding_bottom); ?>;
					}

					.popupCart #view-cart {
						display: <?php esc_attr_e($dma_modal_cart_show_button_view_cart); ?>;
						background-color: <?php esc_attr_e($dma_modal_cart_button_view_cart_background_color); ?>;
						border-color: <?php esc_attr_e($dma_modal_cart_button_view_cart_border_color); ?>;
						color: <?php esc_attr_e($dma_modal_cart_button_view_cart_text_color); ?>;
						font-size: <?php esc_attr_e($dma_modal_cart_button_view_cart_font_size); ?>px;
						font-weight: <?php esc_attr_e($dma_modal_cart_button_view_cart_font_weight); ?>;
					}

					.popupCart #checkout {
						display: <?php esc_attr_e($dma_modal_cart_show_button_checkout); ?>;
						background-color: <?php esc_attr_e($dma_modal_cart_button_checkout_background_color); ?>;
						border-color: <?php esc_attr_e($dma_modal_cart_button_checkout_border_color); ?>;
						color: <?php esc_attr_e($dma_modal_cart_button_checkout_text_color); ?>;
						font-size: <?php esc_attr_e($dma_modal_cart_button_checkout_font_size); ?>px;
						float: right;
						font-weight: <?php esc_attr_e($dma_modal_cart_button_checkout_font_weight); ?>;
					}

					.dma_modal .popupCart .cross-sells .products .title_before_linked_products {
						color:<?php esc_attr_e($dma_modal_cart_title_linked_products_color); ?>;
						font-size: <?php esc_attr_e($dma_modal_cart_title_linked_products_font_size); ?>px;
						text-align: <?php esc_attr_e($dma_modal_cart_title_linked_products_align); ?>;
						font-weight: <?php esc_attr_e($dma_modal_cart_title_linked_products_font_weight); ?>;
						text-transform: <?php esc_attr_e($dma_modal_cart_title_linked_products_text_transform); ?>;
						padding-top: <?php esc_attr_e($dma_modal_cart_title_linked_products_padding_top); ?>;
						padding-bottom: <?php esc_attr_e($dma_modal_cart_title_linked_products_padding_bottom); ?>;
					}

					.dma_modal .popupCart .cross-sells .products .product .woocommerce-loop-product__title {
						color:<?php esc_attr_e($dma_modal_cart_product_title_linked_products_color); ?>;
						font-size: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_font_size); ?>px;
						text-align: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_align); ?>;
						font-weight: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_font_weight); ?>;	
						text-transform: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_text_transform); ?>;
						padding-top: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_padding_top); ?>;
						padding-bottom: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_padding_bottom); ?>;	
					}	

					.dma_modal .popupCart .cross-sells .products .product .price {
						color:<?php esc_attr_e($dma_modal_cart_text_color); ?>;
					}

					.dma_modal .cross-sells .products .product .add_to_cart_button {
						background-color:<?php esc_attr_e($dma_modal_cart_button_add_to_cart_background_color); ?>;
						border-color:<?php esc_attr_e($dma_modal_cart_button_add_to_cart_border_color); ?>;  
  	  					color:<?php esc_attr_e($dma_modal_cart_button_add_to_cart_text_color); ?>;
						font-size: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_font_size); ?>px;
						font-weight: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_font_weight); ?>;	
						text-transform: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_text_transform); ?>;
						margin-top: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_margin_top); ?>;
						margin-bottom: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_margin_bottom); ?>;	
						padding: 10px;
    					width: 100%;
    					text-align: center;					
  					}			

					.dma_modal .dma_modal_close {
					    padding: 2px 10px;
					    opacity: 1;
					    position: absolute;
					    background-color: <?php esc_attr_e($dma_modal_cart_close_button_background_color); ?>;
  						display: <?php esc_attr_e($dma_modal_cart_show_close_button); ?>;
  						border-radius: 0;
					}

					.dma_modal_close span {
  						color: <?php esc_attr_e($dma_modal_cart_close_button_color); ?>;
  						font-size: <?php esc_attr_e($dma_modal_cart_close_button_font_size); ?>;
					}

					.popupCart .product_list_widget li > a {
						color: <?php esc_attr_e($dma_modal_cart_text_color); ?>;
					}

					.dma_fixed_cart {
						background: <?php esc_attr_e($dma_modal_cart_button_count_background); ?>;
					}

					.dma_fixed_cart i {
						color: <?php esc_attr_e($dma_modal_cart_button_count_color); ?>;
    					font-size: <?php esc_attr_e($dma_modal_cart_button_count_icon_size); ?>;
					}

					.dma_fixed_cart #dma_count_cart {
						color: <?php esc_attr_e($dma_modal_cart_button_count_color); ?>;
					}

  		</style><!--style-->
  	<?php
  	}

  	/* Convert hexdec color string to rgb(a) string */
 
	public static function hex2rgba($color, $opacity = false) {

	//Sanitize $color if "#" is provided 
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }
 
        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
 
        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);
 
        //Check if opacity is set(rgba or rgb)
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }
 
        //Return rgb(a) color string
        return $output;
	}

	public function mini_cart() {

		do_action( 'woocommerce_before_mini_cart' ); 

		if ( ! WC()->cart->is_empty() ) : ?>

			<table class="dma_cart_list">
				<tbody>
			<?php

			do_action( 'woocommerce_before_mini_cart_contents' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

					$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
					$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
					$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
			?>
					<tr class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">

						<td class="dma_item_remove">
						<?php
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								esc_attr__( 'Remove this item', 'woocommerce' ),
								esc_attr( $product_id ),
								esc_attr( $cart_item_key ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
							);
						?>	
						</td>
						<?php if ( empty( $product_permalink ) ) : ?>
							<td class="dma_item_thumb">
								<?php echo $thumbnail; ?>
							</td>
							<td class="dma_item_name">
								<?php echo $product_name; ?>
							</td>
						<?php else : ?>
							<td class="dma_item_thumb">
								<a href="<?php echo esc_url( $product_permalink ); ?>">
								<?php echo $thumbnail; ?>
								</a>
							</td>
							<td class="dma_item_name">
								<a href="<?php echo esc_url( $product_permalink ); ?>">
								<?php echo $product_name; ?>
								</a>
							</td>
						<?php endif; ?>
						<td class="dma_item_price">
							<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
						<td class="dma_item_quantity">
							<?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<span class="quantity">' . sprintf( '%s &times; %s', $cart_item['quantity'], $product_price ) . '</span>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
						
					</tr>
				</tbody>
					<?php
				}
			}

			do_action( 'woocommerce_mini_cart_contents' );

			?>
			</table>

			<?php  if(version_compare(WC_VERSION, '3.7.0') >= 0) : ?>

				<p class="woocommerce-mini-cart__total total">
				<?php
				/**
				 * Hook: woocommerce_widget_shopping_cart_total.
				 *
				 * @hooked woocommerce_widget_shopping_cart_subtotal - 10
				 */
				do_action( 'woocommerce_widget_shopping_cart_total' );
				?>
				</p>

			<?php else :

			$this->dma_add_total();

			endif; ?>

			<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

			<p class="woocommerce-mini-cart__buttons buttons"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></p>

			<?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>

		<?php else : ?>

		<p class="woocommerce-mini-cart__empty-message"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>


     	<?php endif;
   		do_action( 'woocommerce_after_mini_cart' ); 
	}

}

	//remove actions
	remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
	remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
	if(version_compare(WC_VERSION, '3.7.0') >= 0){
	remove_action( 'woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10 );
	}

