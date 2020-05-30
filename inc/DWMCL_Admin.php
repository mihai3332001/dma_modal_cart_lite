<?php
namespace DMAWMCA;

if (!defined('ABSPATH')) :
	exit;
endif;

use DMAWMCA\DWMCL_Utils;
use DMAWMCA\DWMCL_Frontend;

/**
 * Class DWMCL_Admin
 *
 *
 * @since 1.0.0
 */
class DWMCL_Admin {

	/**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
	/**
     * Holds the values to be used in the section fields
     */
    private $sections = array(); 

	/**
     * Holds the option css name
     */
    private $option_name; 

	public static $instance = null;

	private $prefix;

	private $fields = array();

    /**
     * Start up
     */
	public function __construct() {
		$this->options = DWMCL_Utils::PLUGIN_OPTIONS;
		$this->sections = DWMCL_Utils::PLUGIN_SECTIONS;
		$this->option_name = DWMCL_Utils::PLUGIN_OPTION_NAME;
		$this->prefix = DWMCL_Utils::PLUGIN_PREFIX;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'setup_dma_woo_page' ) );
		add_action( 'admin_init', array( $this, 'dma_css_page_init' ) );
		add_action( 'wp_ajax_dmanumca', array($this, 'dma_upgrade_modal_cart' ) );
		add_action( 'wp_ajax_dmaExportJson', array($this, 'dma_export_json' ) );
		add_action( 'wp_ajax_dmaImportJson', array($this, 'dma_import_json' ) );
		add_action( 'admin_notices', array($this, 'dma_admin_notices_action') );
		add_action( 'admin_head', array($this, 'dma_admin_load_css') );
	}

	/**
	 * Enqueue stylesheets and scripts in the WordPress admin.
	 */
	public function admin_enqueue_scripts($hook) {
		if($hook == 'woocommerce_page_dma-woo-modal-cart-lite'){
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'jquery_ui_css', DWMCL_DIR . 'assets/css/jquery-ui.min.css');
			wp_enqueue_style( 'dma_backend_admin_css', DWMCL_DIR . 'assets/css/dma_admin_style.css');
			wp_enqueue_style( 'dma_backend_bootstrap', DWMCL_DIR . 'assets/css/bootstrap.min.css');
			wp_enqueue_style( 'dma_backend_icons', DWMCL_DIR . 'assets/css/icons.css');
			wp_enqueue_style( 'chosen_css', DWMCL_DIR . 'assets/css/chosen.min.css');
			wp_enqueue_script( 'chosen_js', DWMCL_DIR . 'assets/js/chosen.jquery.min.js', array('jquery') );
			wp_enqueue_script( 'bootstrap_js', DWMCL_DIR . 'assets/js/bootstrap.min.js', array('jquery') );
			wp_enqueue_script( 'dma_backend_admin_js', DWMCL_DIR . 'assets/js/dma_admin.js', array('jquery', 'wp-color-picker', 'jquery-ui-accordion') );
			wp_localize_script( 'dma_backend_admin_js', 'dma_var_plugin', array(
    			'ajax_url' => admin_url( 'admin-ajax.php' ),
    			'nonce'    => $this->create_nonce_wp(),
    			'dma_hook_page' => true, // It is common practice to comma after
			) ); 
		} else {
			wp_enqueue_script( 'dma_backend_admin_js', DWMCL_DIR . 'assets/js/dma_admin.js', array('jquery'), '0.0.1', true );
			wp_enqueue_style( 'dma_backend_admin_css', DWMCL_DIR . 'assets/css/dma_admin_style.css');
			wp_localize_script( 'dma_backend_admin_js', 'dma_var_plugin', array(
    			'ajax_url' => admin_url( 'admin-ajax.php' ),
    			'nonce'    => $this->create_nonce_wp(), // It is common practice to comma after
			) );   
		}
	}


	public function dma_export_json() {
		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, 'dma_modal_nonce_plugin' ) ) {
		wp_die ( 'Error nonce!' );
		}

		$options_array = array();
		$get_options = get_option( $this->option_name, DWMCL_Utils::default_values() );
		if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
			echo wp_json_encode($get_options);
			wp_die();
		} else {
			echo json_encode($get_options);
			wp_die();
		}
	}

	public function dma_import_json() {
		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, 'dma_modal_nonce_plugin' ) ) {
		wp_die ( 'Error nonce!' );
		}
		$get_options = get_option( $this->option_name, DWMCL_Utils::default_values() );

		if(isset($_FILES)) {
			$file = $_FILES['file'];
		} else {
			exit;
		}

		if($file['error'] !== 0) {
			exit('Error file upload');
		}

		if(isset($file['name'])) {
			$file['name'] = sanitize_file_name($file['name']);
		}

		$uploaded_file = $this->check_file_type($file);

		if($uploaded_file != false) {
				
			$tmp_file = file_get_contents($file['tmp_name']);
			$json_data = json_decode($tmp_file, true);
				$message = array();
					$merged_options = wp_parse_args( $json_data, $get_options );
					$update = update_option($this->option_name, $merged_options);
					if($update == true) {
						$message = array(
							'update' => __('Successfully update!'),
						);
						wp_send_json($message);
					} else {
						$message = array(
							'nothing' => __('Nothing to update!'),
						);
						wp_send_json($message);
					}
		}
	}


	public function check_file_type($file_name) {

		$file_type = array(
			'json' => 'application/json',
		);

		$overrides = array(
				'test_form' => false,
				'mimes'     => $file_type,
		);

		$filetype = wp_check_filetype( $file_name['name'], $file_type );
		if ( in_array( $filetype['type'], $file_type, true ) ) {

			return true;

		} else {

			return false;

		}

	
	}

	public function dma_upgrade_modal_cart() {
		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, 'dma_modal_nonce_plugin' ) ) {
		die ( 'Error nonce!' );
		}

		if(isset($_POST['upgrade'])) {
			$utils = DWMCL_Utils::get_instance();
			$upgrade = "<p>Thank you for using our plugin - DMA WOO Modal Cart!</p>";	
			DWMCL_Utils::set_cookies($utils->get_md5_cookies('no'), $upgrade, time() + (60*60*24*30), '/', DWMCL_Utils::is_secure_ssl(), false, true);
			echo $upgrade;
		}

		wp_die();
	}

	public static function get_admin_instance() {
		if(self::$instance == NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function setup_dma_woo_page() {	
		$submenu_page_dma_woo_modal_cart = add_submenu_page( 'woocommerce', 'DMA WOO Modal Cart Lite', 'DMA WOO Modal Cart Lite', 'manage_options', 'dma-woo-modal-cart-lite', array( $this,'dma_options_page' ) );
		add_action('load-' . $submenu_page_dma_woo_modal_cart, array($this, 'dma_modal_cart_help_tab'));
	}

	public function dma_modal_cart_help_tab() {
		$screen = get_current_screen();
		$help =  '<p>DMA Modal Cart Lite is a mini-cart that adds products to the cart instantly without the need to reload the page.</p>';
		$help .= '<p>You can set an effect for modal cart. See See General Settings-> Modal Cart Effects</p>';
		$help .= '<p>You can write your text when the mini-cart is show. See General Settings-> Modal Cart Title</p>';
		$help .= '<p>You can add your custom class. See Css Settings-> Modal Cart Additional Css Class</p>';
		$help .= '<p>You can change the display of your text. See Css Settings-> Title Settings</p>';
		$help .= '<p>You can change the display of your cart buttons. See Css Settings-> Buttons Settings</p>';
		$screen->add_help_tab(
			array(
				'id' 		=> 'dma_modal_cart_help_tab',
				'title' 	=> __('Help message', 'dma-woo-modal-cart-lite'),
				'content' 	=> $help,
			)
		);
	}

    /**
     * Options page callback
     */
	public function dma_options_page() {
		global $post, $wp_query;

		$args = array(
		    'limit' => 1,
		);
		$products = wc_get_products( $args );

		$product_cross = array();
        ?>
        <div class="wrap">
        	<div class="container-fluid dma-container pt-5">
        		<div class="row">
        			<div class="col-lg-6 col-12 dma_col">
        				<h1><?php esc_html_e('Settings DMA WOO Modal Cart Lite'); ?></h1>
        				<div id="accordion">
				            <form method="POST" action="options.php" id="form_dma">
				            <?php            	
				            settings_fields( $this->options );
				            do_settings_sections( $this->sections ); 
				            submit_button();
				            ?>
				            </form>
				           <h1><?php esc_html_e('Import/Export'); ?></h1>
				            <nav>
							  <div class="nav nav-tabs" id="nav-tab" role="tablist">
							    <a class="nav-item nav-link active" id="nav-import-tab" data-toggle="tab" href="#nav-import" role="tab" aria-controls="nav-import" aria-selected="true">Import</a>
							    <a class="nav-item nav-link" id="nav-export-tab" data-toggle="tab" href="#nav-export" role="tab" aria-controls="nav-export" aria-selected="false">Export</a>
							  </div>
							</nav>
							<div class="tab-content" id="nav-tabContent">
							  	<div class="tab-pane fade show active" id="nav-import" role="tabpanel" aria-labelledby="nav-import-tab">
							  	
								  	<form method="post" action="" enctype="multipart/form-data" id="dma_modal_cart_form">
					        			<div class="custom-file mt-3 mb-3">
					        				<label class="btn custom-file-label" for="dma_file"> 
					        					Import file
										  	<input type="file" class="custom-file-input" id="dma_file" name="file" style="display: none">
										 	</label>
										</div>
								        <div >
								            <input type="button" class="button" value="Upload" id="dma_modal_cart_import" multiple=”false”>
								        </div>
							    	</form>
							    	<div class="alert-success my-3 dma_notice"></div>
							  	</div><!--tab-->

								<div class="tab-pane fade" id="nav-export" role="tabpanel" aria-labelledby="nav-export-tab">

								  	 <?php  printf(
					            	'<a href="#" id="%1$s" title="[%1$s]" class="btn btn_export mt-3 mb-3"/>%2$s</a>',
					            	$this->prefix . 'export',
					            	'Export Settings'
					        		);  ?>

								</div><!--tab-->
							</div><!--tab-content-->
		        	 	</div><!--accordion-->
		         
        			</div><!--col-lg-6-->

        			<div class="col-lg-6 col-12 dma_col2">

        				<?php if ( class_exists( 'woocommerce' ) ) : 

        				$get_options = get_option( $this->option_name, DWMCL_Utils::default_values() );
	        			extract($get_options);
	        				
        				?>
        		  		<h1><?php esc_html_e('Preview DMA WOO Modal Cart Lite'); ?></h1>
        				<div class="dma_preview_modal" id="dma_preview_modal">
        					
        					<div class="popupCart">
        						
        						<div class="widget_shopping_cart_content">
        							<div class="disabled_version">Only in premium version</div>
        							<button type="button" class="btn dma_modal_close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        							<div class="preview_title_dma_modal_cart"><?php esc_attr_e($dma_modal_cart_title); ?></div>

        							<?php foreach($products as $product) : ?>

	        							<ul class="woocommerce-mini-cart cart_list product_list_widget ">
											<li class="woocommerce-mini-cart-item mini_cart_item">
												<a href="#">×</a>	

												<?php

												$post_thumbnail_id = $product->get_image_id();

												if ( $product->get_image_id() ) {
													$html = wc_get_gallery_image_html( $post_thumbnail_id, true );
												} else {	
													$html = sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
												}

												echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); 
							 					echo '<div class="product_name">' . $product->get_name() . '</div>'; ?>
										
												<span class="quantity">1 × <span class="woocommerce-Price-amount amount"><?php echo $product->get_price_html(); ?></span></span>				
											</li>
										</ul>

									<?php endforeach;?>

										<table cellspacing="0" class="shop_table shop_table_responsive">

											<tbody>
												<tr class="cart-subtotal">
													<th>Subtotal</th>
													<td data-title="Subtotal"><span class="woocommerce-Price-amount amount"><?php echo $product->get_price_html(); ?></td>
												</tr>
												<tr class="order-total">
													<th>Total</th>
													<td data-title="Total"><strong><span class="woocommerce-Price-amount amount"><?php echo $product->get_price_html(); ?></strong> </td>
												</tr>
											</tbody>
										</table>

									<p class="woocommerce-mini-cart__buttons buttons"><a href="#" class="button wc-forward" id="view-cart">View cart</a><a href="#" class="button checkout wc-forward" id="checkout">Checkout</a></p>
								</div><!--widgetshopping_cart_content-->

									
							</div><!--popupCart-->

        				</div><!--preview_modal-->

        				<?php endif;?>

        			</div><!--col-lg-6-->

        		</div><!--row-->

        	</div><!--container-->

        </div>

        <?php
	}


    /**
     * Register and add settings
     */
	public function dma_css_page_init() {
		$get_option = get_option( $this->option_name );

		register_setting(
            $this->options, // Option group
           	$this->option_name, // Option name
           	array(
            'type'              => 'string',
            'show_in_rest'      => true,
            'sanitize_callback' => array( $this, 'dmanumca_sanitize' ),
        	)
             // Sanitize
        );


       	add_settings_section(
            'general_settings', // ID
            '', // Title
            array( $this, 'dmanumca_settings_callback' ), // Callback
            $this->sections // Page
        );  

        add_settings_section(
            'dma_css_settings', // ID
            '', // Title
            array( $this, 'dmanumca_settings_callback' ), // Callback
            $this->sections // Page
        );  

       	add_settings_section(
            'dma_font_settings', // ID
            '', // Title
            array( $this, 'dmanumca_settings_callback' ), // Callback
            $this->sections // Page
        );  

        add_settings_section(
            'dma_mini_cart_settings', // ID
            '', // Title
            array( $this, 'dmanumca_settings_callback' ), // Callback
            $this->sections // Page
        );  

       	$this->fields = array (

       		array (
	            	'name' 				=> 'position',
	            	'title'				=> 'Modal Cart Position',
	            	'type' 				=> 'select',
					'default' 			=> 'left',
					'section'			=> 'general_settings',
					'options'			=> array(
											'center'	=> 'center',
										),
					'label_for'			=> 'position',
					'disabled'			=> 'disabled',
            ),

           	array (
	            	'name' 				=> 'effects',
	            	'title'				=> 'Modal Cart - Effects',
	            	'type' 				=> 'select',
					'default' 			=> 'none',
					'section'			=> 'general_settings',
					'options'			=> array(
											'none'			=> 'none',
											'slideIn'		=> 'SlideIn',
										),
					'label_for'			=> 'effects',
					'limited'			=> 'limited',
            ),

       		array (
	            	'name' 				=> 'title',
	                'title'				=> 'Modal Cart Title',
	            	'type' 				=> 'text',
					'default' 			=> __('Add text'),
					'section'			=> 'general_settings',
					'label_for' 		=> 'title',
       		),

	   		array (
	   				'name' 				=> 'show_subtotal',
	                'title'				=> 'Show Subtotal',
	        		'type' 				=> 'checkbox',
					'section'			=> 'general_settings',
					'default' 			=> '',
					'label_for' 		=> 'show_subtotal',
					'disabled'			=> 'disabled',
	   		),

	   		array (
	   				'name' 				=> 'show_total',
	                'title'				=> 'Show Total',
	        		'type' 				=> 'checkbox',
	        		'default' 			=> '',
					'section'			=> 'general_settings',
					'label_for' 		=> 'show_total',
					'disabled'			=> 'disabled',
	   		),

	   		array(
	   				'name' 				=> 'show_button_view_cart',
	   				'title'				=> 'Show Button "View Cart"',
            		'type' 				=> 'checkbox',
					'default'			=> '',
					'section'			=> 'general_settings',
					'label_for' 		=> 'show_button_view_cart',
            ),

           	array(
	   				'name' 				=> 'show_button_checkout',
	   				'title'				=> 'Show Button "Checkout"',
            		'type' 				=> 'checkbox',
					'default'			=> '',
					'section'			=> 'general_settings',
					'label_for' 		=> 'show_button_checkout',
            ),

           	array(
	           		'name' 				=> 'title_before_linked_products',
	           		'title'				=> 'Title Before Linked Products',
	            	'type' 				=> 'text',
					'default' 			=> 'You may be intereseted in...',
					'section'			=> 'general_settings',
					'label_for' 		=> 'title_before_linked_products',
					'disabled'			=> 'disabled',
           	),

           	array (
           			'name' 				=> 'woo_linked_products',
	           		'title'				=> 'Title Before Linked Products',
	            	'type' 				=> 'select',
					'default' 			=> 'Cross-sells',
					'section'			=> 'general_settings',
					'options'			=> array(
											'None' 	=> __('None'),
										),
					'label_for' 		=> 'woo_linked_products',
					'disabled'			=> 'disabled',
           	),

           	array(
	           		'name' 				=> 'create_woo_linked_products',
	           		'title'				=> 'Woocommerce Create Linked Products',
	           		'type' 				=> 'text',
					'default' 			=> 'Create Linked Products',
					'section'			=> 'general_settings',
					'label_for' 		=> 'create_woo_linked_products',
					'description' 		=> __('If you don\'t want to use the default linked products (cross-sells, upsells), you could create one'),
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'limit_linked_products',
	           		'title'				=> 'Woocommerce Limit Linked Products',
	            	'type' 				=> 'number',
					'default' 			=> '0',
					'section'			=> 'general_settings',
					'label_for' 		=> 'limit_linked_products',
					'min'				=> '0',
					'max'				=> '1',
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'show_ajax_on_loop_products',
	           		'title'				=> 'Enable/Disable Ajax On Loop Products',
	            	'type' 				=> 'checkbox',
					'default' 			=> '',
					'section'			=> 'general_settings',
					'label_for' 		=> 'show_ajax_on_loop_products',
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'show_ajax_on_single_product',
	           		'title'				=> 'Enable/Disable Ajax On Single Product',
	            	'type' 				=> 'checkbox',
					'default' 			=> '',
					'section'			=> 'general_settings',
					'label_for' 		=> 'show_ajax_on_single_product',
					'disabled'			=> 'disabled',
           	),

           	array (
           			'name' 				=> 'show_close_button',
	   				'title'				=> 'Modal Cart Show Close Button',
            		'type' 				=> 'checkbox',
					'default'			=> '',
					'section'			=> 'general_settings',
					'label_for' 		=> 'show_close_button',
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'background_color',
	   				'title'				=> 'Modal Cart - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'background_color',	
           	),

           	array(
           			'name' 				=> 'background_opacity',
	   				'title'				=> 'Modal Cart - Opacity Background Color',
            		'type' 				=> 'number',
					'default'			=> '1',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'background_opacity',
					'min'				=> '0',
					'max'				=> '1',	
           	),	

           	array(
           			'name' 				=> 'text_color',
	   				'title'				=> 'Modal Cart - Text Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'text_color',	
           	),

           	array(
           			'name' 				=> 'width_modal',
	   				'title'				=> 'Width Of Modal',
            		'type' 				=> 'text',
					'default'			=> '500px',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'width_modal',
					'disabled'			=> 'disabled',	
           	),

           	array(
           			'name' 				=> 'custom_class_css',
	   				'title'				=> 'Modal Cart Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'custom_class_css',	
           	),

           	array(
           			'name' 				=> 'custom_id_attribute',
	   				'title'				=> 'Modal Cart ID attribute',
            		'type' 				=> 'text',
					'default'			=> 'ID attribute',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'custom_id_attribute',	
           	),

           	array(
           			'name' 				=> 'title_section',
	   				'title'				=> 'Title Settings',
            		'class' 			=> 'subsection_title',
					'section'			=> 'dma_css_settings',
           	),

           	array(
           			'name' 				=> 'title_color',
	   				'title'				=> 'Title - Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'title_color',	
           	),

           	array(
           			'name' 				=> 'title_font_size',
	   				'title'				=> 'Title - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'title_font_size',
					'min'				=> '0',
					'max'				=> '100',	
           	),	

           	array (
	            	'name' 				=> 'title_align',
	            	'title'				=> 'Title - Text Align',
	            	'type' 				=> 'select',
					'default' 			=> 'left',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'left'		=> 'left',
											'center'	=> 'center',
											'right'  	=> 'right',
										),
					'label_for'			=> 'title_align',
            ),

            array (
	            	'name' 				=> 'title_font_weight',
	            	'title'				=> 'Title - Font Weight',
	            	'type' 				=> 'select',
					'default' 			=> '300',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'100'		=> '100',
											'200'		=> '200',
											'300'		=> '300',
											'400'		=> '400',
											'500'  		=> '500',
											'600'  		=> '600',
											'700'  		=> '700',
											'800'  		=> '800',
											'900'  		=> '900',
										),
					'label_for'			=> 'title_font_weight',
            ),

            array (
	            	'name' 				=> 'title_text_transform',
	            	'title'				=> 'Title - Text Transform',
	            	'type' 				=> 'select',
					'default' 			=> 'unset',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'capitalize'		=> 'capitalize',
											'lowercase'		=> 'lowercase',
											'unset'			=> 'unset',
											'uppercase'		=> 'uppercase',
										),
					'label_for'			=> 'title_text_transform',
            ),

            array (
	            	'name' 				=> 'title_padding_top',
	            	'title'				=> 'Title - Padding Top',
	            	'type' 				=> 'select',
					'default' 			=> '20',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										'10px'		=> '10',
										'20px'		=> '20',
										'30px'		=> '30',
										'40px'		=> '40',
										'50px'		=> '50',
										'60px'		=> '60',
										'70px'		=> '70',
										'80px'		=> '80',
										'90px'		=> '90',
										'100px'		=> '100',
										),
					'label_for'			=> 'title_padding_top',
            ),

            array (
	            	'name' 				=> 'title_padding_bottom',
	            	'title'				=> 'Title - Padding Bottom',
	            	'type' 				=> 'select',
					'default' 			=> '0',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										'10px'		=> '10',
										'20px'		=> '20',
										'30px'		=> '30',
										'40px'		=> '40',
										'50px'		=> '50',
										'60px'		=> '60',
										'70px'		=> '70',
										'80px'		=> '80',
										'90px'		=> '90',
										'100px'		=> '100',
										),
					'label_for'			=> 'title_padding_bottom',
            ),

            array(
           			'name' 				=> 'title_custom_class_css',
	   				'title'				=> 'Title - Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'title_custom_class_css',	
           	),

            array(
           			'name' 				=> 'buttons_section',
	   				'title'				=> 'Buttons Settings',
            		'class' 			=> 'subsection_title',
					'section'			=> 'dma_css_settings',
           	),

           	array(
           			'name' 				=> 'button_view_cart_background_color',
	   				'title'				=> 'Button "View cart" - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_view_cart_background_color',	
           	),

           	array(
           			'name' 				=> 'button_view_cart_border_color',
	   				'title'				=> 'Button "View cart" - Border Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_view_cart_border_color',	
           	),

           	array(
           			'name' 				=> 'button_view_cart_text_color',
	   				'title'				=> 'Button "View cart" - Text Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_view_cart_text_color',	
           	),

           	array(
           			'name' 				=> 'button_view_cart_font_size',
	   				'title'				=> 'Button "View cart" - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_view_cart_font_size',
					'min'				=> '0',
					'max'				=> '100',	
           	),	

           	array (
	            	'name' 				=> 'button_view_cart_font_weight',
	            	'title'				=> 'Button "View cart" - Font Weight',
	            	'type' 				=> 'select',
					'default' 			=> '300',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'100'		=> '100',
											'200'		=> '200',
											'300'		=> '300',
											'400'		=> '400',
											'500'  		=> '500',
											'600'  		=> '600',
											'700'  		=> '700',
											'800'  		=> '800',
											'900'  		=> '900',
										),
					'label_for'			=> 'button_view_cart_font_weight',
            ),

            array(
           			'name' 				=> 'button_view_cart_custom_class_css',
	   				'title'				=> 'Button "View cart" - Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_view_cart_custom_class_css',	
           	),

            array(
           			'name' 				=> 'button_checkout_background_color',
	   				'title'				=> 'Button "Checkout" - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_checkout_background_color',	
           	),

           	array(
           			'name' 				=> 'button_checkout_border_color',
	   				'title'				=> 'Button "Checkout" - Border Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_checkout_border_color',	
           	),

           	array(
           			'name' 				=> 'button_checkout_text_color',
	   				'title'				=> 'Button "Checkout" - Text Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_checkout_text_color',	
           	),

           	array(
           			'name' 				=> 'button_checkout_font_size',
	   				'title'				=> 'Button "Checkout" - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_checkout_font_size',
					'min'				=> '0',
					'max'				=> '100',	
           	),	

           	array (
	            	'name' 				=> 'button_checkout_font_weight',
	            	'title'				=> 'Button "Checkout" - Font Weight',
	            	'type' 				=> 'select',
					'default' 			=> '300',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'100'		=> '100',
											'200'		=> '200',
											'300'		=> '300',
											'400'		=> '400',
											'500'  		=> '500',
											'600'  		=> '600',
											'700'  		=> '700',
											'800'  		=> '800',
											'900'  		=> '900',
										),
					'label_for'			=> 'button_checkout_font_weight',
            ),

            array(
           			'name' 				=> 'button_checkout_custom_class_css',
	   				'title'				=> 'Button "Checkout" - Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_checkout_custom_class_css',	
           	),

            array (
	            	'name' 				=> 'buttons_padding_top',
	            	'title'				=> 'Cart Buttons  - Padding Top',
	            	'type' 				=> 'select',
					'default' 			=> '20',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										'10px'		=> '10',
										'20px'		=> '20',
										'30px'		=> '30',
										'40px'		=> '40',
										'50px'		=> '50',
										'60px'		=> '60',
										'70px'		=> '70',
										'80px'		=> '80',
										'90px'		=> '90',
										'100px'		=> '100',
										),
					'label_for'			=> 'buttons_padding_top',
            ),

            array (
	            	'name' 				=> 'buttons_padding_bottom',
	            	'title'				=> 'Cart Buttons - Padding Bottom',
	            	'type' 				=> 'select',
					'default' 			=> '0',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										'10px'		=> '10',
										'20px'		=> '20',
										'30px'		=> '30',
										'40px'		=> '40',
										'50px'		=> '50',
										'60px'		=> '60',
										'70px'		=> '70',
										'80px'		=> '80',
										'90px'		=> '90',
										'100px'		=> '100',
										),
					'label_for'			=> 'buttons_padding_bottom',
            ),

            array(
           			'name' 				=> 'linked_products_section',
	   				'title'				=> 'Linked Products Settings',
            		'class' 			=> 'subsection_title',
					'section'			=> 'dma_css_settings',
           	),

           	array(
           			'name' 				=> 'section_linked_products_custom_class_css',
	   				'title'				=> 'Section Linked Products - Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'section_linked_products_custom_class_css',	
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'title_linked_products_color',
	   				'title'				=> 'Title Linked Products - Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'title_linked_products_color',	
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'title_linked_products_font_size',
	   				'title'				=> 'Title Linked Products - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'title_linked_products_font_size',
					'min'				=> '0',
					'max'				=> '1',	
					'disabled'			=> 'disabled',
           	),	

           	array (
	            	'name' 				=> 'title_linked_products_align',
	            	'title'				=> 'Title Linked Products - Text Align',
	            	'type' 				=> 'select',
					'default' 			=> 'left',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'left'		=> 'left',
										),
					'label_for'			=> 'title_linked_products_align',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'title_linked_products_font_weight',
	            	'title'				=> 'Title Linked Products - Font Weight',
	            	'type' 				=> 'select',
					'default' 			=> '300',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'300'		=> '300',
										),
					'label_for'			=> 'title_linked_products_font_weight',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'title_linked_products_text_transform',
	            	'title'				=> 'Title Linked Products - Text Transform',
	            	'type' 				=> 'select',
					'default' 			=> 'unset',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'unset'			=> 'unset',
										),
					'label_for'			=> 'title_linked_products_text_transform',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'title_linked_products_padding_top',
	            	'title'				=> 'Title Linked Products  - Padding Top',
	            	'type' 				=> 'select',
					'default' 			=> '20',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'20px'		=> '20',
										),
					'label_for'			=> 'title_linked_products_padding_top',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'title_linked_products_padding_bottom',
	            	'title'				=> 'Title Linked Products - Padding Bottom',
	            	'type' 				=> 'select',
					'default' 			=> '0',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										),
					'label_for'			=> 'title_linked_products_padding_bottom',
					'disabled'			=> 'disabled',
            ),

            array(
           			'name' 				=> 'title_linked_products_custom_class_css',
	   				'title'				=> 'Title Linked Products - Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'title_linked_products_custom_class_css',	
					'disabled'			=> 'disabled',
           	),

            array(
           			'name' 				=> 'product_title_linked_products_color',
	   				'title'				=> 'Products Title - Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'product_title_linked_products_color',	
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'product_title_linked_products_font_size',
	   				'title'				=> 'Products Title - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'product_title_linked_products_font_size',
					'min'				=> '0',
					'max'				=> '1',	
					'disabled'			=> 'disabled',
           	),	

           	array (
	            	'name' 				=> 'product_title_linked_products_align',
	            	'title'				=> 'Products Title - Text Align',
	            	'type' 				=> 'select',
					'default' 			=> 'left',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'center'	=> 'center',
										),
					'label_for'			=> 'product_title_linked_products_align',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'product_title_linked_products_font_weight',
	            	'title'				=> 'Products Title - Font Weight',
	            	'type' 				=> 'select',
					'default' 			=> '300',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'300'		=> '300',
										),
					'label_for'			=> 'product_title_linked_products_font_weight',
					'disabled'			=> 'disabled',
            ),


            array (
	            	'name' 				=> 'product_title_linked_products_text_transform',
	            	'title'				=> 'Products Title - Text Transform',
	            	'type' 				=> 'select',
					'default' 			=> 'unset',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'unset'			=> 'unset',
										),
					'label_for'			=> 'product_title_linked_products_text_transform',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'product_title_linked_products_padding_top',
	            	'title'				=> 'Products Title  - Padding Top',
	            	'type' 				=> 'select',
					'default' 			=> '20',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'10px'		=> '10',
										),
					'label_for'			=> 'product_title_linked_products_padding_top',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'product_title_linked_products_padding_bottom',
	            	'title'				=> 'Products Title - Padding Bottom',
	            	'type' 				=> 'select',
					'default' 			=> '0',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										),
					'label_for'			=> 'product_title_linked_products_padding_bottom',
					'disabled'			=> 'disabled',
            ),

             array(
           			'name' 				=> 'button_add_to_cart_background_color',
	   				'title'				=> 'Button "Add to cart" - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_add_to_cart_background_color',	
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'button_add_to_cart_border_color',
	   				'title'				=> 'Button "Add to cart" - Border Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_add_to_cart_border_color',
					'disabled'			=> 'disabled',	
           	),

           	array(
           			'name' 				=> 'button_add_to_cart_text_color',
	   				'title'				=> 'Button "Add to cart" - Text Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_add_to_cart_text_color',	
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'button_add_to_cart_font_size',
	   				'title'				=> 'Button "Add to cart" - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'button_add_to_cart_font_size',
					'min'				=> '0',
					'max'				=> '1',
					'disabled'			=> 'disabled',	
           	),	

           	array (
	            	'name' 				=> 'button_add_to_cart_font_weight',
	            	'title'				=> 'Button "Add to cart" - Font Weight',
	            	'type' 				=> 'select',
					'default' 			=> '300',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'300'		=> '300',
										),
					'label_for'			=> 'button_add_to_cart_font_weight',
					'disabled'			=> 'disabled',
            ),

              array (
	            	'name' 				=> 'button_add_to_cart_text_transform',
	            	'title'				=> 'Button "Add to cart" - Text Transform',
	            	'type' 				=> 'select',
					'default' 			=> 'unset',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
											'unset'			=> 'unset',
										),
					'label_for'			=> 'button_add_to_cart_text_transform',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'button_add_to_cart_margin_top',
	            	'title'				=> 'Button "Add to cart" - Margin Top',
	            	'type' 				=> 'select',
					'default' 			=> '20',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'10px'		=> '10',
										),
					'label_for'			=> 'button_add_to_cart_margin_top',
					'disabled'			=> 'disabled',
            ),

            array (
	            	'name' 				=> 'button_add_to_cart_margin_bottom',
	            	'title'				=> 'Button "Add to cart" - Margin Bottom',
	            	'type' 				=> 'select',
					'default' 			=> '0',
					'section'			=> 'dma_css_settings',
					'options'			=> array(
										'0'			=> '0',
										),
					'label_for'			=> 'button_add_to_cart_margin_bottom',
					'disabled'			=> 'disabled',
            ),


           	array(
           			'name' 				=> 'additional_section',
	   				'title'				=> 'Additional Settings',
            		'class' 			=> 'subsection_title',
					'section'			=> 'dma_css_settings',
           	),

           	array(
           			'name' 				=> 'price_background_color',
	   				'title'				=> 'Section Subtotal & Total - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'price_background_color',	
           	),

           	array(
           			'name' 				=> 'price_text_color',
	   				'title'				=> 'Section Subtotal & Total - Text Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'price_text_color',
					'disabled'			=> 'disabled',	
           	),

           	array(
           			'name' 				=> 'price_custom_class',
	   				'title'				=> 'Section Subtotal & Total - Additional CSS Class',
            		'type' 				=> 'text',
					'default'			=> 'Additional CSS Class',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'price_custom_class',
					'disabled'			=> 'disabled',		
           	),

           	array(
           			'name' 				=> 'close_button_background_color',
	   				'title'				=> 'Close Button - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'close_button_background_color',	
					'disabled'			=> 'disabled',	
           	),

           	array(
           			'name' 				=> 'close_button_color',
	   				'title'				=> 'Close Button - Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'close_button_color',
					'disabled'			=> 'disabled',		
           	),

           	array(
           			'name' 				=> 'close_button_font_size',
	   				'title'				=> 'Close Button - Font Size',
            		'type' 				=> 'number',
					'default'			=> '14',
					'section'			=> 'dma_css_settings',
					'label_for' 		=> 'close_button_font_size',
					'min'				=> '0',
					'max'				=> '1',	
					'disabled'			=> 'disabled',	
           	),	

           	array(
           			'name' 				=> 'google_font_family',
	   				'title'				=> 'Enable Google Font',
            		'type' 				=> 'select',
					'default'			=> '',
					'section'			=> 'dma_font_settings',
					'options'			=> 	array('none' => 'none'),
					'label_for' 		=> 'google_font_family',
					'description' 		=> 'Import google font family',
					'class'				=> 'google_fonts',
					'disabled'			=> 'disabled',	
           	),

           	array(
           			'name' 				=> 'font_family',
	   				'title'				=> 'Modal Cart Your Theme Font Family',
            		'type' 				=> 'text',
					'default'			=> 'font-family',
					'section'			=> 'dma_font_settings',
					'label_for' 		=> 'font_family',
					'description' 		=> 'If you don\'t want to import google fonts use your own font family',
					'class'				=> 'google_fonts',
					'disabled'			=> 'disabled',
           	),

           	array(
           			'name' 				=> 'show_button_count',
	   				'title'				=> 'Show Count Button Cart',
            		'type' 				=> 'checkbox',
					'default'			=> '',
					'section'			=> 'dma_mini_cart_settings',
					'label_for' 		=> 'show_button_count',
					'description' 		=> 'Show count button cart on every page',
           	),

           	array(
           			'name' 				=> 'button_count_background',
	   				'title'				=> 'Count Button - Background Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_mini_cart_settings',
					'label_for' 		=> 'button_count_background',	
           	),

           	array(
           			'name' 				=> 'button_count_color',
	   				'title'				=> 'Count Button - Color',
            		'type' 				=> 'color',
					'default'			=> '#000000',
					'section'			=> 'dma_mini_cart_settings',
					'label_for' 		=> 'button_count_color',	
           	),

           	array(
           			'name' 				=> 'button_count_icon_size',
	   				'title'				=> 'Count Button Icon - Font Size',
            		'type' 				=> 'text',
					'section'			=> 'dma_mini_cart_settings',
					'label_for' 		=> 'button_count_icon_size',	
           	),

           	array(
           			'name' 				=> 'button_count_icon',
	   				'title'				=> 'Select Count Button Icon',
            		'type' 				=> 'select_icons',
					'default'			=> 'icon-shopping-cart4',
					'section'			=> 'dma_mini_cart_settings',
					'options'			=> 	array( 'icon-shopping-cart4' => '&#xe912;'),
					'label_for' 		=> 'button_count_icon',
					'class'				=> 'google_fonts',
					'disabled'			=> 'disabled',	
           	),
       	);


       	foreach($this->fields as $field) {
       		add_settings_field( 
       			$this->prefix . $field['name'],
       			__($field['title'], 'dma-woo-modal-cart-lite'),
       			array( $this, 'dma_render_fields' ), 
       			$this->sections, 
       			$field['section'],
       			$args = array(
		   			'name' 			=> $field['name'],
		        	'type' 			=> isset($field['type']) ? $field['type'] :'',
					'default' 		=> isset($field['default']) ? $field['default'] : '',
					'label_for' 	=> $this->prefix . $field['name'],
					'options'  		=> isset($field['options']) ? $field['options'] : '',
					'min'			=> isset($field['min']) ?  $field['min'] : '',
					'max'			=> isset($field['max']) ? $field['max'] : '',
					'description'   => isset($field['description']) ? $field['description'] : '',
					'class'			=> isset($field['class']) ? $field['class'] : '',
					'disabled'			=> isset($field['disabled']) ? $field['disabled'] : '',
					'limited'			=> isset($field['limited']) ? $field['limited'] : '',
       			)
       		);
       	}

     
	}

	public function dma_label_fields() {

	}

	public function dma_render_fields($args) {

		$get_options = get_option( $this->option_name, DWMCL_Utils::default_values() );
		if(!empty($get_options[$this->prefix . $args['name']])) {
			$value = $get_options[$this->prefix . $args['name']];
		}

		$description =  isset($args['description']) ? $args['description'] : '';

		$label = isset($args['label'])  ? $args['label'] : '';

		$name = isset($args['name']) ? $args['name'] : '';
	
		$default =  isset($args['default']) ? $args['default'] : '';

		$min =  isset($args['min']) ? $args['min'] : '';

		$max = isset($args['max']) ? $args['max'] : '';

		$disabled = isset($args['disabled']) ? $args['disabled'] : '';

		$limited = isset($args['limited']) ? $args['limited'] : '';

		$args_options = isset($args['options']) ? $args['options'] : '';

		switch ($args['type']) {
			case 'color':
					printf(
			            '<input type="text" class="%1$s" name="' . $this->option_name . '[%2$s]" value="%3$s" data-default-color="%4$s" '. $disabled . '/>',
			            'dma_colors_picker',
			            $this->prefix . $name,
			            (!empty($value)) ? $value : '',
			            $default
			        );
			        if(!empty($disabled)) {
							echo '<p class="premium_version">Feature Only Available On <a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" target="_blank" alt="dma modal cart premium version">Premium Version</a></p>';
					}
				break;
			case 'text':
					printf(
			            '<input type="text" class="%1$s" name="' . $this->option_name . '[%1$s]" value="%2$s" placeholder="%3$s" '.$disabled.'/>',
			            $this->prefix . $name,
			            (!empty($value)) ? $value : '',
			            $default
			        );
			        echo '<p>'. $description .'</p>';
			        if(!empty($disabled)) {
							echo '<p class="premium_version">Feature Only Available On <a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" target="_blank" alt="dma modal cart premium version">Premium Version</a></p>';
					}	
			        break;
			case 'number':
					if($max == 1) :
					 	printf(
			            '<input type="number" class="%1$s" name="' . $this->option_name . '[%1$s]" value="%2$s" step="0.01" min="%3$s" max="%4$s" '.$disabled.' />',
			            $this->prefix . $name,
			            (!empty($value)) ? $value : $default,
			            $min,
			            $max
			        );
					else :
						printf(
			            '<input type="number" class="%1$s" name="' . $this->option_name . '[%1$s]" value="%2$s" min="%3$s" max="%4$s"/>',
			            $this->prefix . $name,
			            (!empty($value)) ? $value : $default,
			            $min,
			            $max
			        );  
			        endif;
			        if(!empty($disabled)) {
							echo '<p class="premium_version">Feature Only Available On <a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" target="_blank" alt="dma modal cart premium version">Premium Version</a></p>';
					}		
			        break; 

			case 'select': 	
				        echo  '<select class="'.$this->prefix . $name.'" name="' . $this->option_name . '[' . $this->prefix . $name . ']" '.$disabled.'/>';
				            foreach($args_options as $opt_name=>$opt_value) {
				   
				            	if(!empty($value)) {
				            		$selected = ($value == $opt_name) ? 'selected="selected"' : '';
				            	} else {
				            		$selected = ($default == $opt_value) ? 'selected="selected"' : '';
				            	}
				            	echo '<option value="'. $opt_name. '" '. $selected . '>'. $opt_value .'</option>';
				            }         
						echo '</select>';
						if(!empty($disabled) || !empty($limited)) {
							echo '<p class="premium_version">Feature Only Available On <a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" target="_blank" alt="dma modal cart premium version">Premium Version</a></p>';
						}
			        break;

			case 'select_icons': 	
				        echo  '<select size="2" class="'.$this->prefix . $name.'" name="' . $this->option_name . '[' . $this->prefix . $name . ']"/>';
				            foreach($args_options as $opt_name=>$opt_value) {
				            	if(!empty($value)) {
				            		$selected = ($value == $opt_name) ? 'selected="selected"' : '';
				            	} else {
				            		$selected = ($default == $opt_name) ? 'selected="selected"' : '';
				            	}
				            	echo '<option value="'. $opt_name. '"  '. $selected . '>'. $opt_value. '</option>';
				            }         
						echo '</select>';
						if(!empty($disabled)) {
							echo '<p class="premium_version">Feature Only Available On <a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" target="_blank" alt="dma modal cart premium version">Premium Version</a></p>';
						}	
			        break;

			case 'checkbox': 
				    printf(
			            '<p class="onoff"><input type="checkbox" id="%1$s" name="' . $this->option_name . '[%1$s]" value="1" %2$s '.$disabled.'/><label for="%1$s"></label></p>',
			            $this->prefix . $name,
			            isset($value) ? 'checked' : ''
			        );  
			        echo '<p>'. $description .'</p>';
			        if(!empty($disabled)) {
							echo '<p class="premium_version">Feature Only Available On <a href="https://dmaexpertit.com/downloads/dma-woo-modal-cart/" target="_blank" alt="dma modal cart premium version">Premium Version</a></p>';
					}	
			        break;
			case 'link': 
				    printf(
			            '<a href="#" id="%1$s" title="' . $this->option_name . '[%1$s]" class="btn btn_export"/>%2$s</a>',
			            $this->prefix . $name,
			            $description
			        );  
			        break;
			case 'file':
				    printf(
			            '<input type="file" id="%1$s" class="[%1$s]" />',
			            $this->prefix . $name
			        );  

			break;
		}	

	}

	public function dma_admin_load_css() {
		$get_option = get_option( $this->option_name, DWMCL_Utils::default_values() );

    		extract($get_option);
			$dma_modal_cart_show_subtotal = isset($dma_modal_cart_show_subtotal) ? 'table-row' : 'none'; 
			$dma_modal_cart_show_total = isset($dma_modal_cart_show_total) ? 'table-row' : 'none'; 
			$dma_modal_cart_show_close_button = isset($dma_modal_cart_show_close_button) ? 'flex' : 'none';
			$dma_modal_cart_show_button_view_cart = isset($dma_modal_cart_show_button_view_cart) ? 'inline-flex' : 'none';
			$dma_modal_cart_show_button_checkout = isset($dma_modal_cart_show_button_checkout) ? 'inline-flex' : 'none';
    	

    	if(isset($dma_modal_cart_background_color) && isset($dma_modal_cart_background_opacity)) {
      		$dma_modal_cart_background_preview = DWMCL_Frontend::hex2rgba($dma_modal_cart_background_color, $dma_modal_cart_background_opacity);  		
    	}

		 ?>

			<style type="text/css">

						.dma_preview_modal .popupCart {
							background-color: <?php esc_attr_e($dma_modal_cart_background_preview); ?>;
							color:<?php esc_attr_e($dma_modal_cart_text_color); ?>;
						}

						.dma_preview_modal .preview_title_dma_modal_cart {
  							color:<?php esc_attr_e($dma_modal_cart_title_color); ?>;
  							font-size: <?php esc_attr_e($dma_modal_cart_title_font_size); ?>px;
  							text-align: <?php esc_attr_e($dma_modal_cart_title_align); ?>;
  							font-weight: <?php esc_attr_e($dma_modal_cart_title_font_weight); ?>;
  							text-transform: <?php esc_attr_e($dma_modal_cart_title_text_transform); ?>;
  							padding-top: <?php esc_attr_e($dma_modal_cart_title_padding_top); ?>;
  							padding-bottom: <?php esc_attr_e($dma_modal_cart_title_padding_bottom); ?>;
  						}

  						.dma_preview_modal .cart-subtotal {
							display: <?php esc_attr_e($dma_modal_cart_show_subtotal); ?>;
						}
						.dma_preview_modal .order-total {
							display: <?php esc_attr_e($dma_modal_cart_show_total); ?>;
						}

						.dma_preview_modal .shop_table_responsive {
						    background-color: <?php esc_attr_e($dma_modal_cart_price_background_color); ?>;
						    color: <?php esc_attr_e($dma_modal_cart_price_text_color); ?>;
						}

  						.dma_preview_modal .woocommerce-mini-cart__buttons {
  							padding-top: <?php esc_attr_e($dma_modal_cart_buttons_padding_top); ?>;
  							padding-bottom: <?php esc_attr_e($dma_modal_cart_buttons_padding_bottom); ?>;
  						}

						.dma_preview_modal #view-cart {
							display: <?php esc_attr_e($dma_modal_cart_show_button_view_cart); ?>;
							background-color: <?php esc_attr_e($dma_modal_cart_button_view_cart_background_color); ?>;
							border-color: <?php esc_attr_e($dma_modal_cart_button_view_cart_border_color); ?>;
							color: <?php esc_attr_e($dma_modal_cart_button_view_cart_text_color); ?>;
							font-size: <?php esc_attr_e($dma_modal_cart_button_view_cart_font_size); ?>px;
							font-weight: <?php esc_attr_e($dma_modal_cart_button_view_cart_font_weight); ?>;
						}
						.dma_preview_modal #checkout {
							display: <?php esc_attr_e($dma_modal_cart_show_button_checkout); ?>;
							background-color: <?php esc_attr_e($dma_modal_cart_button_checkout_background_color); ?>;
							border-color: <?php esc_attr_e($dma_modal_cart_button_checkout_border_color); ?>;
							color: <?php esc_attr_e($dma_modal_cart_button_checkout_text_color); ?>;
							font-size: <?php esc_attr_e($dma_modal_cart_button_checkout_font_size); ?>px;
							float: right;
							font-weight: <?php esc_attr_e($dma_modal_cart_button_checkout_font_weight); ?>;
						}

  						.dma_preview_modal .title_before_linked_products {
  							color:<?php esc_attr_e($dma_modal_cart_title_linked_products_color); ?>;
  							font-size: <?php esc_attr_e($dma_modal_cart_title_linked_products_font_size); ?>px;
  							text-align: <?php esc_attr_e($dma_modal_cart_title_linked_products_align); ?>;
  							font-weight: <?php esc_attr_e($dma_modal_cart_title_linked_products_font_weight); ?>;
  							text-transform: <?php esc_attr_e($dma_modal_cart_title_linked_products_text_transform); ?>;
  							padding-top: <?php esc_attr_e($dma_modal_cart_title_linked_products_padding_top); ?>;
  							padding-bottom: <?php esc_attr_e($dma_modal_cart_title_linked_products_padding_bottom); ?>;
  						}

  						.dma_preview_modal .cross-sells .featured_product .woocommerce-loop-product__title {
  	  						color:<?php esc_attr_e($dma_modal_cart_product_title_linked_products_color); ?>;
  							font-size: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_font_size); ?>px;
  							text-align: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_align); ?>;
  							font-weight: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_font_weight); ?>;	
  							text-transform: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_text_transform); ?>;
  							padding-top: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_padding_top); ?>;
  							padding-bottom: <?php esc_attr_e($dma_modal_cart_product_title_linked_products_padding_bottom); ?>;					
  						}

  						.dma_preview_modal .cross-sells .featured_product .add_to_cart_button {
  							background-color:<?php esc_attr_e($dma_modal_cart_button_add_to_cart_background_color); ?>;
  							border-color:<?php esc_attr_e($dma_modal_cart_button_add_to_cart_border_color); ?>;  
  	  	  					color:<?php esc_attr_e($dma_modal_cart_button_add_to_cart_text_color); ?>;
  							font-size: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_font_size); ?>px;
  							font-weight: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_font_weight); ?>;	
  							text-transform: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_text_transform); ?>;
  							margin-top: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_margin_top); ?>;
  							margin-bottom: <?php esc_attr_e($dma_modal_cart_button_add_to_cart_margin_bottom); ?>;								
  						}

  						.dma_preview_modal .dma_modal_close {
  							background-color: <?php esc_attr_e($dma_modal_cart_close_button_background_color); ?>;
  							display: <?php esc_attr_e($dma_modal_cart_show_close_button); ?>;
  						}

  						.dma_preview_modal .dma_modal_close span{
  							color: <?php esc_attr_e($dma_modal_cart_close_button_color); ?>;
  							font-size: <?php esc_attr_e($dma_modal_cart_close_button_font_size); ?>;
  						}

			</style>

			<?php
	}

	public static function create_nonce_wp() {
		return $dma_modal_nonce_plugin = wp_create_nonce( 'dma_modal_nonce_plugin' );
	}

	public function dmanumca_subsection_callback() {

	}

    /** 
     * Get the settings option array and print one of its values
     */
	public function dmanumca_settings_callback( $arg ) {
		switch ($arg['id']) {
			case 'general_settings':
				echo '<h3>General Settings</h3>'; 
				break;
			case 'dma_font_settings':
				echo '<h3>Font Settings</h3>'; 
				break;
			case 'dma_css_settings':
				echo '<h3>CSS Settings</h3>'; 
				break;
			case 'dma_mini_cart_settings':
				echo '<h3>Count Button Cart Settings</h3>'; 
				break;				
		}
	}


    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
	public function dmanumca_sanitize($values) {
		$preset_options = DWMCL_Utils::preset_values();
		$all_values = array_merge($preset_options, $values);
		
		$valid_fields = array();
		foreach($this->fields as $args) {
			foreach($all_values as $key=>$value ) {
				if($key === $this->prefix . $args['name']) {
					switch ($args['type']) {
						case 'text':
							$valid_fields[$key] = sanitize_text_field( stripslashes($value));
							break;
						case 'color':
							$trim_color = sanitize_text_field( stripslashes($value));
							if( null === sanitize_hex_color( $trim_color ) ) {
        					// Set the error message
        						add_settings_error( 'dma_errors', 'invalid_bg_color', 'Insert a valid color for background color', 'error' );  
        					// Get the previous valid value
    						} else {
       							$valid_fields[$key] = $trim_color;  
    						}
							break;
						case 'number':
						$validate_decimal = $this->filter_decimal($value);
							if($validate_decimal == true) {
								$valid_fields[$key] = $value; 
							} else {
								add_settings_error( 'dma_errors', 'invalid_opacity_number', 'Insert a valid opacity for background color', 'error' );  
							}
							break;
						case 'select':
							$valid_fields[$key] = sanitize_text_field( stripslashes($value));
							break;
						case 'select_icons':
							$valid_fields[$key] = sanitize_text_field( stripslashes($value));
							break;
						case 'select_fonts':
							$valid_fields[$key] = sanitize_text_field( stripslashes($value));
							break;	
						case 'checkbox':
							$valid_fields[$key] = filter_var( $value, FILTER_SANITIZE_NUMBER_INT );
							break;				
					}
				}
			}
		
		}
     
    return $valid_fields;

	}

	/** 
    * Return true or false
    */
	public function filter_decimal($value) {
		if(preg_match('/(\d{1}.\d)|(\d)/', $value)) {
			return true;
		} else {
			return false;
		}
	}

	/** 
    * settings errors for plugin
    */
	public function dma_admin_notices_action() {
		settings_errors('dma_errors');
	}

 	public function woo_general_product_data_custom_field_linked_products() {
		global $woocommerce, $post;
		$options = get_option( $this->option_name );
		$dma_modal_cart_create_woo_linked_products = $options['dma_modal_cart_create_woo_linked_products'] ? $options['dma_modal_cart_create_woo_linked_products'] : '';
		$product_ids = get_post_meta( $post->ID, '_dma_modal_cart_create_woo_linked_products', true );
		?>


		<div class="options_group">
		  <p class="form-field">
		      <label for="dma_modal_cart_create_woo_linked_products"><?php esc_html_e( $dma_modal_cart_create_woo_linked_products, 'woocommerce' ); ?></label>
		      <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="dma_modal_cart_create_woo_linked_products" name="dma_modal_cart_create_woo_linked_products[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
		        <?php
		        $product_ids = get_post_meta( $post->ID, '_dma_modal_cart_create_woo_linked_products', true );
		        foreach ( $product_ids as $product_id ) {
		          $product = wc_get_product( $product_id );
		          if ( is_object( $product ) ) {
		            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
		          }
		        }
		        ?>
		      </select>
		       <?php echo wc_help_tip( __( 'Select your product...', 'woocommerce' ) ); // WPCS: XSS ok. ?>
		    </p>
		  </div>
		    <?php
 	}

}


