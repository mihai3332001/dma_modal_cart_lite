<?php
/**
 * DMA WOO Modal Cart Lite 
 *
 * @package     DMA_WC_Modal_Cart_Lite
 * @author      DMA Expert IT
 * @license     GPL-2.0-or-later
 *
 * Plugin Name:       DMA WOO Modal Cart Lite
 * Plugin URI:        https://dmaexpertit.com/
 * Description:       Woocommerce modal cart lite with linked products.
 * Version:           1.0.0
 * Requires at least: 4.2
 * Requires PHP:      5.6
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dma-woo-modal-cart-lite
 * Domain Path:       /languages
 */



defined('ABSPATH') or die('No script kiddies please!');

define('DWMCL_PATH', plugin_dir_path(__FILE__));
define('DWMCL_DIR', plugin_dir_url(__FILE__));
define('DWMCL_BASE', plugin_basename(__FILE__));


require_once(DWMCL_PATH . '/libs/vendor/autoload.php');
/**
 *
 */
use DMAWMCA\DWMCL_Admin;
use DMAWMCA\DWMCL_Frontend;
use DMAWMCA\DWMCL_Utils;

/**
 * Main Class
 *
 *
 * @since 1.0.0
 */
class DWMCL
{

    private static $instance = null;
    /**
    * class constructor
    */
	public function __construct()
	{	
			$this->get_hooks();
	}

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
	 * get_hooks
	 */
	public function get_hooks() {
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'activate_all_features' ), 99 );
			add_filter( 'plugin_action_links', array( $this, 'pro_version' ), 10, 4 );
			add_action( 'init', array($this, 'load_admin') ); 
		} else {
			add_action( 'init', array($this, 'load_frontend') ); 
		}

	}

	/**
	* load_admin_settings
	*/
	public function load_admin() {
		DWMCL_Admin::get_admin_instance();
	}

	/**
	* load_frontend_settings
	*/
	public function load_frontend() {
		DWMCL_Frontend::get_frontend_instance();
	}


	/**
	 * missing_woocommerce
	 */
	public static function error_admin_missing_wc() {
		echo '<div class="error"><p>Error 105: DMA WC Modal Cart Lite requires Woocommerce to work!</p></div>';
	}

	/**
	 * activate plugin features
	 */
	public function activate_all_features() {
		$utils = DWMCL_Utils::get_instance();
		$cookie_no = isset($_COOKIE[$utils->get_md5_cookies('no')]) ? sanitize_key($_COOKIE[$utils->get_md5_cookies('no')]) : '';
		$notice_features = isset($_COOKIE[$utils->get_md5_cookies('notice_features')]) ? sanitize_key($_COOKIE[$utils->get_md5_cookies('notice_features')]) : null;
		if(isset($cookie_no) && isset($notice_features)) {	
			DWMCL_Utils::set_cookies($utils->get_md5_cookies('notice_features'), '', time() - 3600, '/', DWMCL_Utils::is_secure_ssl(), false, true);
		} else if(empty($cookie_no)){
			$plugin_html_notice =  '<div class="notice" id="dma_upgrade_product"><p>Do you want all features of DMA WOO Modal Cart  product? <a href="https://dmaexpertit.com/" target="_blank" class="upgrade_yes">Yes</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a class="upgrade_no">No</a></p></div>';
			DWMCL_Utils::set_cookies($utils->get_md5_cookies('notice_features'), $plugin_html_notice, time() + (60*60*24*30), '/', DWMCL_Utils::is_secure_ssl(), false, true);
			echo $plugin_html_notice;
		}
		//setcookie('dma_cookies_no', '', time() -3600, '/');
	}

	/**
 	*
 	* @return additional settings 
 	*/
	public function pro_version($actions, $file, $data, $context ) {
		if( $file == DWMCL_BASE) {
		array_unshift($actions, '<a href="https://dmaexpertit.com/">Docs</a>');
		array_unshift($actions, '<a href="'. admin_url('admin.php?page=dma-woo-modal-cart-lite') .'">Settings</a>');
		array_unshift($actions, '<a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" class="pro_version">Pro version</a>');
		}
		return $actions;
	}
}


	/**
 	*
 	* @return string
 	*/
	function error_loading() {
		return DWMCL::error_admin_missing_wc();
	}


	function DWMCL_Activate_Plugin() {
		if(!function_exists('WC')) {
			add_action( 'admin_notices', 'error_loading');
			$DWMCL = new DWMCL();
		} else {
			DWMCL::get_instance();
		}
	}

add_action('plugins_loaded', 'DWMCL_Activate_Plugin');




 