<?php

namespace DMAWMCA;

if (!defined('ABSPATH')) :
	exit;
endif;

/**
 * Class DWMCL_Utils
 *
 *
 * @since 1.0.0
 */
class DWMCL_Utils {

		const COOKIE_NAME = 'dma_cookies_';
		const PLUGIN_OPTIONS = 'dmanumca_options';
		const PLUGIN_SECTIONS = 'dmanumca_sections';
		const PLUGIN_OPTION_NAME = 'dmanumca_name';
		const PLUGIN_PREFIX	 = 'dma_modal_cart_';

		private static $instance = null;

		/**
	 	* Returns the instance.
	 	*
	 	* @return instance
	 	*/
		public static function get_instance() {
			if(is_null(self::$instance)) {
			self::$instance = new self();
			return self::$instance;
			}
		}

		/**
	 	* Returns if ssl is used
	 	*
	 	* @return true or false 
	 	*/	
		public static function is_secure_ssl() {
			return is_ssl() && parse_url(get_site_url(), PHP_URL_SCHEME) === 'https';
		}

		/**
	 	*
	 	* @return cookie name 
	 	*/	
		public function get_md5_cookies($value) {
			if($value == 'notice_features' || $value == 'no') {
				return self::COOKIE_NAME . $value;
			} else {
				return self::COOKIE_NAME . md5($value);			
			}
		}

		/**
		 * Set cookies
		 */
		public static function set_cookies($name, $value, $expires, $path, $domain, $secure, $httponly ) {
			if(version_compare(PHP_VERSION, '5.2.0') >= 0) {
				setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
			} else {
				setcookie($name, $value, $expires, $path, $domain, $secure);
			}
		}
		
		/**
		 * default values
		 */
		public static function default_values() {
			return $values = array(
				self::PLUGIN_PREFIX . 'position' => 'center',
				self::PLUGIN_PREFIX . 'effects' => 'slideIn',
				self::PLUGIN_PREFIX . 'title' => 'Shopping Cart',
				self::PLUGIN_PREFIX . 'show_subtotal' => 1,
				self::PLUGIN_PREFIX . 'show_total' => 1,
				self::PLUGIN_PREFIX . 'show_button_view_cart' => 1,
				self::PLUGIN_PREFIX . 'show_button_checkout' => 1,
				self::PLUGIN_PREFIX . 'title_before_linked_products' => 'You may be interested in...',
				self::PLUGIN_PREFIX . 'woo_linked_products' => 'Cross-sells',
				self::PLUGIN_PREFIX . 'limit_linked_products' => 3,
				self::PLUGIN_PREFIX . 'show_ajax_on_loop_products' => 1,
				self::PLUGIN_PREFIX . 'show_ajax_on_single_product' => 1,
				self::PLUGIN_PREFIX . 'show_close_button' => 1,
				self::PLUGIN_PREFIX . 'background_color' => '#00c5cc',
				self::PLUGIN_PREFIX . 'background_opacity' => 1,
				self::PLUGIN_PREFIX . 'text_color' => '#fff',
				self::PLUGIN_PREFIX . 'width_modal' => '500px',
				self::PLUGIN_PREFIX . 'custom_class_css' => '',
				self::PLUGIN_PREFIX . 'custom_id_attribute' => '',
				self::PLUGIN_PREFIX . 'title_color' => '#fff',
				self::PLUGIN_PREFIX . 'title_font_size' => '18',
				self::PLUGIN_PREFIX . 'title_align' => 'left',
				self::PLUGIN_PREFIX . 'title_font_weight' => '300',
				self::PLUGIN_PREFIX . 'title_text_transform' => 'unset',
				self::PLUGIN_PREFIX . 'title_padding_top' => '20px',
				self::PLUGIN_PREFIX . 'title_padding_bottom' => '0',
				self::PLUGIN_PREFIX . 'title_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'button_view_cart_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_view_cart_border_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_view_cart_text_color' => '#000',
				self::PLUGIN_PREFIX . 'button_view_cart_font_size' => '18',
				self::PLUGIN_PREFIX . 'button_view_cart_font_weight' => '300',
				self::PLUGIN_PREFIX . 'button_view_cart_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'button_checkout_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_checkout_border_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_checkout_text_color' => '#000',
				self::PLUGIN_PREFIX . 'button_checkout_font_size' => '18',
				self::PLUGIN_PREFIX . 'button_checkout_font_weight' => '300',
				self::PLUGIN_PREFIX . 'button_checkout_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'buttons_padding_top' => '20px',
				self::PLUGIN_PREFIX . 'buttons_padding_bottom' => '0',
				self::PLUGIN_PREFIX . 'section_linked_products_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'title_linked_products_color' => '#fff',
				self::PLUGIN_PREFIX . 'title_linked_products_font_size' => '18',
				self::PLUGIN_PREFIX . 'title_linked_products_align' => 'left',
				self::PLUGIN_PREFIX . 'title_linked_products_font_weight' => '300',
				self::PLUGIN_PREFIX . 'title_linked_products_text_transform' => 'unset',
				self::PLUGIN_PREFIX . 'title_linked_products_padding_top' => '20px',
				self::PLUGIN_PREFIX . 'title_linked_products_padding_bottom' => '0',
				self::PLUGIN_PREFIX . 'title_linked_products_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'product_title_linked_products_color' => '#fff',
				self::PLUGIN_PREFIX . 'product_title_linked_products_font_size' => '16',
				self::PLUGIN_PREFIX . 'product_title_linked_products_align' => 'center',
				self::PLUGIN_PREFIX . 'product_title_linked_products_font_weight' => '300',
				self::PLUGIN_PREFIX . 'product_title_linked_products_text_transform' => 'unset',
				self::PLUGIN_PREFIX . 'product_title_linked_products_padding_top' => '10px',
				self::PLUGIN_PREFIX . 'product_title_linked_products_padding_bottom' => '0',
				self::PLUGIN_PREFIX . 'button_add_to_cart_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_add_to_cart_border_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_add_to_cart_text_color' => '#000',
				self::PLUGIN_PREFIX . 'button_add_to_cart_font_size' => '14',
				self::PLUGIN_PREFIX . 'button_add_to_cart_font_weight' => '300',
				self::PLUGIN_PREFIX . 'button_add_to_cart_text_transform' => 'unset',
				self::PLUGIN_PREFIX . 'button_add_to_cart_margin_top' => '10px',
				self::PLUGIN_PREFIX . 'button_add_to_cart_margin_bottom' => '0',
				self::PLUGIN_PREFIX . 'price_background_color' => '#2b2b2b',
				self::PLUGIN_PREFIX . 'price_text_color' => '',
				self::PLUGIN_PREFIX . 'close_button_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'close_button_color' => '#2b2b2b',
				self::PLUGIN_PREFIX . 'close_button_font_size' => '14',
				self::PLUGIN_PREFIX . 'google_font_family' => '',
				self::PLUGIN_PREFIX . 'price_custom_class' => '',
				self::PLUGIN_PREFIX . 'show_button_count' => 1,
				self::PLUGIN_PREFIX . 'button_count_background' => '#00c5cc',
				self::PLUGIN_PREFIX . 'button_count_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_count_icon_size' => '35px',
				self::PLUGIN_PREFIX . 'button_count_icon' => 'icon-shopping-cart4',
			);
		}

		/**
		 * default values
		 */
		public static function preset_values() {
			return $values = array(
				self::PLUGIN_PREFIX . 'position' => 'center',
				self::PLUGIN_PREFIX . 'show_subtotal' => 1,
				self::PLUGIN_PREFIX . 'show_total' => 1,
				self::PLUGIN_PREFIX . 'show_ajax_on_loop_products' => 1,
				self::PLUGIN_PREFIX . 'show_ajax_on_single_product' => 1,
				self::PLUGIN_PREFIX . 'show_close_button' => 1,
				self::PLUGIN_PREFIX . 'width_modal' => '500px',
				self::PLUGIN_PREFIX . 'custom_class_css' => '',
				self::PLUGIN_PREFIX . 'custom_id_attribute' => '',
				self::PLUGIN_PREFIX . 'title_color' => '#fff',
				self::PLUGIN_PREFIX . 'title_font_size' => '18',
				self::PLUGIN_PREFIX . 'title_align' => 'left',
				self::PLUGIN_PREFIX . 'title_font_weight' => '300',
				self::PLUGIN_PREFIX . 'title_text_transform' => 'unset',
				self::PLUGIN_PREFIX . 'title_padding_top' => '20px',
				self::PLUGIN_PREFIX . 'title_padding_bottom' => '0',
				self::PLUGIN_PREFIX . 'title_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'button_view_cart_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_view_cart_border_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_view_cart_text_color' => '#000',
				self::PLUGIN_PREFIX . 'button_view_cart_font_size' => '18',
				self::PLUGIN_PREFIX . 'button_view_cart_font_weight' => '300',
				self::PLUGIN_PREFIX . 'button_view_cart_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'button_checkout_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_checkout_border_color' => '#fff',
				self::PLUGIN_PREFIX . 'button_checkout_text_color' => '#000',
				self::PLUGIN_PREFIX . 'button_checkout_font_size' => '18',
				self::PLUGIN_PREFIX . 'button_checkout_font_weight' => '300',
				self::PLUGIN_PREFIX . 'button_checkout_custom_class_css' => '',
				self::PLUGIN_PREFIX . 'buttons_padding_top' => '20px',
				self::PLUGIN_PREFIX . 'buttons_padding_bottom' => '0',
				self::PLUGIN_PREFIX . 'price_text_color' => '',
				self::PLUGIN_PREFIX . 'close_button_background_color' => '#fff',
				self::PLUGIN_PREFIX . 'close_button_color' => '#2b2b2b',
				self::PLUGIN_PREFIX . 'close_button_font_size' => '14',
				self::PLUGIN_PREFIX . 'google_font_family' => '',
				self::PLUGIN_PREFIX . 'price_custom_class' => '',
				self::PLUGIN_PREFIX . 'button_count_icon' => 'icon-shopping-cart4',
			);
		}
	}

?>