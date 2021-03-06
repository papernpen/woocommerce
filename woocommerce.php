<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: http://www.woothemes.com/woocommerce/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Version: 2.1-bleeding
 * Author: WooThemes
 * Author URI: http://woothemes.com
 * Requires at least: 3.5
 * Tested up to: 3.5
 *
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 * @category Core
 * @author WooThemes
 */
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce' ) ) :

/**
 * Main WooCommerce Class
 *
 * @class WooCommerce
 * @version	2.1.0
 */
final class WooCommerce {

	/**
	 * @var string
	 */
	public $version = '2.1-bleeding';

	/**
	 * @var WooCommerce The single instance of the class
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Main WooCommerce Instance
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see WC()
	 * @return Main WooCommerce instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.1' );
	}

	/**
	 * WooCommerce Constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Auto-load classes on demand
		if ( function_exists( "__autoload" ) )
			spl_autoload_register( "__autoload" );

		spl_autoload_register( array( $this, 'autoload' ) );

		// Define constants
		define( 'WOOCOMMERCE_PLUGIN_FILE', __FILE__ );
		define( 'WOOCOMMERCE_VERSION', $this->version );
		define( 'WOOCOMMERCE_TEMPLATE_PATH', $this->template_path() );

		// Include required files
		$this->includes();

		// Init API
		$this->api = new WC_API();

		// Hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_action( 'widgets_init', array( $this, 'include_widgets' ) );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'include_template_functions' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );

		// Loaded action
		do_action( 'woocommerce_loaded' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( method_exists( $this, $key ) )
			return $this->$key();
		else switch( $key ) {
			case 'template_url':
				_deprecated_argument( 'Woocommerce->template_url', '2.1', 'WOOCOMMERCE_TEMPLATE_DIR constant' );
				return WOOCOMMERCE_TEMPLATE_DIR;
			case 'messages':
				_deprecated_argument( 'Woocommerce->messages', '2.1', 'The "messages" field is moved to the messages helper class.' );
				return $this->session->get( 'wc_messages', array() );
			case 'errors':
				_deprecated_argument( 'Woocommerce->errors', '2.1', 'The "errors" field is moved to the messages helper class.' );
				return $this->session->get( 'wc_errors', array() );
			default:
				return false;
		}
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param mixed $links
	 * @return array
	 */
	public function action_links( $links ) {
		return array_merge( array(
			'<a href="' . admin_url( 'admin.php?page=woocommerce_settings' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
			'<a href="http://docs.woothemes.com/documentation/plugins/woocommerce/">' . __( 'Docs', 'woocommerce' ) . '</a>',
			'<a href="http://support.woothemes.com/">' . __( 'Premium Support', 'woocommerce' ) . '</a>',
		), $links );
	}

	/**
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @param mixed $class
	 * @return void
	 */
	public function autoload( $class ) {

		$class = strtolower( $class );

		if ( strpos( $class, 'wc_gateway_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/gateways/' . trailingslashit( substr( str_replace( '_', '-', $class ), 11 ) );
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_shipping_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/shipping/' . trailingslashit( substr( str_replace( '_', '-', $class ), 12 ) );
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_shortcode_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/shortcodes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_meta_box' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/admin/post-types/meta-boxes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}
		}

		if ( strpos( $class, 'wc_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		include( 'includes/wc-core-functions.php' );
		include( 'includes/class-wc-install.php' );
		include( 'includes/class-wc-download-handler.php' );
		include( 'includes/class-wc-comments.php' );

		if ( is_admin() )
			include_once( 'includes/admin/class-wc-admin.php' );

		if ( defined('DOING_AJAX') )
			$this->ajax_includes();

		if ( ! is_admin() || defined('DOING_AJAX') )
			$this->frontend_includes();

		// Query class
		$this->query = include( 'includes/class-wc-query.php' );				// The main query class

		// Post types
		include_once( 'includes/class-wc-post-types.php' );						// Registers post types

		// API Class
		include_once( 'includes/class-wc-api.php' );

		// Include abstract classes
		include_once( 'includes/abstracts/abstract-wc-product.php' );			// Products
		include_once( 'includes/abstracts/abstract-wc-settings-api.php' );		// Settings API (for gateways, shipping, and integrations)
		include_once( 'includes/abstracts/abstract-wc-shipping-method.php' );	// A Shipping method
		include_once( 'includes/abstracts/abstract-wc-payment-gateway.php' ); 	// A Payment gateway
		include_once( 'includes/abstracts/abstract-wc-integration.php' );		// An integration with a service

		// Classes (used on all pages)
		include_once( 'includes/class-wc-product-factory.php' );				// Product factory
		include_once( 'includes/class-wc-countries.php' );						// Defines countries and states
		include_once( 'includes/class-wc-integrations.php' );					// Loads integrations
		include_once( 'includes/class-wc-cache-helper.php' );					// Cache Helper
		include_once( 'includes/class-wc-https.php' );							// https Helper

		// Include Core Integrations - these are included sitewide
		include_once( 'includes/integrations/google-analytics/class-wc-google-analytics.php' );
		include_once( 'includes/integrations/sharethis/class-wc-sharethis.php' );
		include_once( 'includes/integrations/sharedaddy/class-wc-sharedaddy.php' );

		// Include template hooks in time for themes to remove/modify them
		include_once( 'includes/wc-template-hooks.php' );
	}

	/**
	 * Include required ajax files.
	 */
	public function ajax_includes() {
		include_once( 'woocommerce-ajax.php' );	// Ajax functions for admin and the front-end
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once( 'includes/class-wc-template-loader.php' );		// Template Loader
		include_once( 'includes/class-wc-frontend-scripts.php' );		// Frontend Scripts
		include_once( 'includes/class-wc-form-handler.php' );			// Form Handlers
		include_once( 'includes/class-wc-cart.php' );					// The main cart class
		include_once( 'includes/class-wc-tax.php' );					// Tax class
		include_once( 'includes/class-wc-customer.php' ); 				// Customer class
		include_once( 'includes/abstracts/abstract-wc-session.php' ); 	// Abstract for session implementations
		include_once( 'includes/class-wc-session-handler.php' );   		// WC Session class
		include_once( 'includes/class-wc-shortcodes.php' );				// Shortcodes class
	}

	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once( 'includes/wc-template-functions.php' );
	}

	/**
	 * Include core widgets
	 */
	public function include_widgets() {
		include_once( 'includes/abstracts/abstract-wc-widget.php' );
		include_once( 'includes/widgets/class-wc-widget-cart.php' );
		include_once( 'includes/widgets/class-wc-widget-products.php' );
		include_once( 'includes/widgets/class-wc-widget-layered-nav.php' );
		include_once( 'includes/widgets/class-wc-widget-layered-nav-filters.php' );
		include_once( 'includes/widgets/class-wc-widget-price-filter.php' );
		include_once( 'includes/widgets/class-wc-widget-product-categories.php' );
		include_once( 'includes/widgets/class-wc-widget-product-search.php' );
		include_once( 'includes/widgets/class-wc-widget-product-tag-cloud.php' );
		include_once( 'includes/widgets/class-wc-widget-recent-reviews.php' );
		include_once( 'includes/widgets/class-wc-widget-recently-viewed.php' );
		include_once( 'includes/widgets/class-wc-widget-top-rated-products.php' );
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action
		do_action( 'before_woocommerce_init' );

		// Set up localisation
		$this->load_plugin_textdomain();

		// Load class instances
		$this->product_factory      = new WC_Product_Factory();     // Product Factory to create new product instances
		$this->countries 			= new WC_Countries();			// Countries class
		$this->integrations			= new WC_Integrations();		// Integrations class

		// Classes/actions loaded for the frontend and for ajax requests
		if ( ! is_admin() || defined('DOING_AJAX') ) {

			// Session class, handles session data for customers - can be overwritten if custom handler is needed
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );

			// Class instances
			$this->session  = new $session_class();
			$this->cart     = new WC_Cart();				// Cart class, stores the cart contents
			$this->customer = new WC_Customer();			// Customer class, handles data such as customer location
		}

		// Email Actions
		$email_actions = array( 'woocommerce_low_stock', 'woocommerce_no_stock', 'woocommerce_product_on_backorder', 'woocommerce_order_status_pending_to_processing', 'woocommerce_order_status_pending_to_completed', 'woocommerce_order_status_pending_to_on-hold', 'woocommerce_order_status_failed_to_processing', 'woocommerce_order_status_failed_to_completed', 'woocommerce_order_status_completed', 'woocommerce_new_customer_note', 'woocommerce_created_customer' );

		foreach ( $email_actions as $action )
			add_action( $action, array( $this, 'send_transactional_email' ), 10, 10 );

		// Init action
		do_action( 'woocommerce_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce' );

		// Admin Locale
		if ( is_admin() ) {
			load_textdomain( 'woocommerce', WP_LANG_DIR . "/woocommerce/woocommerce-admin-$locale.mo" );
			load_textdomain( 'woocommerce', "i18n/languages/woocommerce-admin-$locale.mo" );
		}

		// Frontend Locale
		load_textdomain( 'woocommerce', WP_LANG_DIR . "/woocommerce/woocommerce-$locale.mo" );

		if ( apply_filters( 'woocommerce_load_alt_locale', false ) )
			load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages/alt" );
		else
			load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages" );
	}

	/**
	 * Ensure theme and server variable compatibility and setup image sizes..
	 */
	public function setup_environment() {
		// Post thumbnail support
		if ( ! current_theme_supports( 'post-thumbnails', 'product' ) ) {
			add_theme_support( 'post-thumbnails' );
			remove_post_type_support( 'post', 'thumbnail' );
			remove_post_type_support( 'page', 'thumbnail' );
		} else {
			add_post_type_support( 'product', 'thumbnail' );
		}

		// Add image sizes
		$shop_thumbnail = wc_get_image_size( 'shop_thumbnail' );
		$shop_catalog	= wc_get_image_size( 'shop_catalog' );
		$shop_single	= wc_get_image_size( 'shop_single' );

		add_image_size( 'shop_thumbnail', $shop_thumbnail['width'], $shop_thumbnail['height'], $shop_thumbnail['crop'] );
		add_image_size( 'shop_catalog', $shop_catalog['width'], $shop_catalog['height'], $shop_catalog['crop'] );
		add_image_size( 'shop_single', $shop_single['width'], $shop_single['height'], $shop_single['crop'] );

		// IIS
		if ( ! isset($_SERVER['REQUEST_URI'] ) ) {
			$_SERVER['REQUEST_URI'] = substr( $_SERVER['PHP_SELF'], 1 );
			if ( isset( $_SERVER['QUERY_STRING'] ) )
				$_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING'];
		}

		// NGINX Proxy
		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_SERVER['HTTP_REMOTE_ADDR'] ) )
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_REMOTE_ADDR'];

		if ( ! isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTP_HTTPS'] ) )
			$_SERVER['HTTPS'] = $_SERVER['HTTP_HTTPS'];

		// Support for hosts which don't use HTTPS, and use HTTP_X_FORWARDED_PROTO
		if ( ! isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = '1';
	}

	/** Helper functions ******************************************************/

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'woocommerce_template_path', 'woocommerce/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Return the WC API URL for a given request
	 *
	 * @param mixed $request
	 * @param mixed $ssl (default: null)
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( get_option('permalink_structure') ) {
			return esc_url_raw( trailingslashit( home_url( '/wc-api/' . $request, $scheme ) ) );
		} else {
			return esc_url_raw( add_query_arg( 'wc-api', $request, trailingslashit( home_url( '', $scheme ) ) ) );
		}
	}

	/**
	 * Init the mailer and call the notifications for the current filter.
	 *
	 * @param array $args (default: array())
	 * @return void
	 */
	public function send_transactional_email() {
		$this->mailer();
		$args = func_get_args();
		do_action_ref_array( current_filter() . '_notification', $args );
	}

	/** Load Instances on demand **********************************************/

	/**
	 * Get Checkout Class.
	 *
	 * @return WC_Checkout
	 */
	public function checkout() {
		return WC_Checkout::instance();
	}

	/**
	 * Get gateways class
	 *
	 * @return WC_Payment_Gateways
	 */
	public function payment_gateways() {
		return WC_Payment_Gateways::instance();
	}

	/**
	 * Get shipping class
	 *
	 * @return WC_Shipping
	 */
	public function shipping() {
		return WC_Shipping::instance();
	}

	/**
	 * Email Class.
	 *
	 * @return WC_Email
	 */
	public function mailer() {
		return WC_Emails::instance();
	}

	/** Deprecated methods *********************************************************/

	// Deprecated 2.1.0
	public function get_image_size( $image_size ) {
		_deprecated_function( 'Woocommerce->get_image_size', '2.1', 'wc_get_image_size()' );
		return wc_get_image_size( $image_size );
	}

	// Deprecated 2.1.0
	public function logger() {
		_deprecated_function( 'Woocommerce->logger', '2.1', 'new WC_Logger()' );
		return new WC_Logger();
	}

	// Deprecated 2.1.0
	public function validation() {
		_deprecated_function( 'Woocommerce->validation', '2.1', 'new WC_Validation()' );
		return new WC_Validation();
	}

	// Deprecated 2.1.0
	public function setup_product_data( $post ) {
		_deprecated_function( 'Woocommerce->setup_product_data', '2.1', 'wc_setup_product_data' );
		return wc_setup_product_data( $post );
	}

	// Deprecated 2.1.0 Access via the WC_Transient_Helper helper
	public function force_ssl( $content ) {
		_deprecated_function( 'Woocommerce->force_ssl', '2.1', 'WC_HTTPS::force_https_url' );
		return WC_HTTPS::force_https_url( $content );
	}

	// Deprecated 2.1.0 Access via the WC_Transient_Helper helper
	public function clear_product_transients( $post_id = 0 ) {
		_deprecated_function( 'Woocommerce->clear_product_transients', '2.1', 'wc_delete_product_transients' );
		wc_delete_product_transients( $post_id );
	}

	// Deprecated 2.1.0 Access via the WC_Inline_Javascript_Helper helper
	public function add_inline_js( $code ) {
		_deprecated_function( 'Woocommerce->add_inline_js', '2.1', 'wc_enqueue_js' );
		wc_enqueue_js( $code );
	}

	// Deprecated 2.1.0
	public function nonce_field( $action, $referer = true , $echo = true ) {
		_deprecated_function( 'Woocommerce->nonce_field', '2.1', 'wp_nonce_field' );
		return wp_nonce_field('woocommerce-' . $action, '_wpnonce', $referer, $echo );
	}

	// Deprecated 2.1.0
	public function nonce_url( $action, $url = '' ) {
		_deprecated_function( 'Woocommerce->nonce_url', '2.1', 'wp_nonce_url' );
		return wp_nonce_url( $url , 'woocommerce-' . $action );
	}

	// Deprecated 2.1.0 Access via the WC_Nonce_Helper helper
	public function verify_nonce( $action, $method = '_POST', $error_message = false ) {
		_deprecated_function( 'Woocommerce->verify_nonce', '2.1', 'WC_Nonce_Helper->verify_nonce' );
		return wp_verify_nonce( $$_method[ '_wpnonce' ], 'woocommerce-' . $action );
	}

	// Deprecated 2.1.0 Access via the WC_Shortcode_Helper helper
	public function shortcode_wrapper( $function, $atts = array(), $wrapper = array( 'class' => 'woocommerce', 'before' => null, 'after' => null ) ) {
		_deprecated_function( 'Woocommerce->shortcode_wrapper', '2.1', 'WC_Shortcodes::shortcode_wrapper' );
		return WC_Shortcodes::shortcode_wrapper( $function, $atts, $wrapper );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function get_attribute_taxonomies() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomies', '2.1', 'wc_get_attribute_taxonomies' );
		return wc_get_attribute_taxonomies();
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function attribute_taxonomy_name( $name ) {
		_deprecated_function( 'Woocommerce->attribute_taxonomy_name', '2.1', 'wc_attribute_taxonomy_name' );
		return wc_attribute_taxonomy_name( $name );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function attribute_label( $name ) {
		_deprecated_function( 'Woocommerce->attribute_label', '2.1', 'wc_attribute_label' );
		return wc_attribute_label( $name );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function attribute_orderby( $name ) {
		_deprecated_function( 'Woocommerce->attribute_orderby', '2.1', 'wc_attribute_orderby' );
		return wc_attribute_orderby( $name );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function get_attribute_taxonomy_names() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomy_names', '2.1', 'wc_get_attribute_taxonomy_names' );
		return wc_get_attribute_taxonomy_names();
	}

	// Deprecated 2.1.0
	public function get_coupon_discount_types() {
		_deprecated_function( 'Woocommerce->get_coupon_discount_types', '2.1', 'wc_get_coupon_types' );
		return wc_get_coupon_types();
	}

	// Deprecated 2.1.0
	public function get_coupon_discount_type( $type = '' ) {
		_deprecated_function( 'Woocommerce->get_coupon_discount_type', '2.1', 'wc_get_coupon_type' );
		return wc_get_coupon_type( $type );
	}

	// Deprecated 2.1.0 Access via the WC_Body_Class_Helper helper
	public function add_body_class( $class ) {
		_deprecated_function( 'Woocommerce->add_body_class', '2.1' );
	}

	// Deprecated 2.1.0 Access via the WC_Body_Class_Helper helper
	public function output_body_class( $classes ) {
		_deprecated_function( 'Woocommerce->output_body_class', '2.1' );
	}

	// Deprecated 2.1.0
	public function add_error( $error ) {
		_deprecated_function( 'Woocommerce->add_error', '2.1', 'wc_add_error' );
		wc_add_error( $error );
	}

	// Deprecated 2.1.0
	public function add_message( $message ) {
		_deprecated_function( 'Woocommerce->add_message', '2.1', 'wc_add_message' );
		wc_add_message( $message );
	}

	// Deprecated 2.1.0
	public function clear_messages() {
		_deprecated_function( 'Woocommerce->clear_messages', '2.1', 'wc_clear_messages' );
		wc_clear_messages();
	}

	// Deprecated 2.1.0
	public function error_count() {
		_deprecated_function( 'Woocommerce->error_count', '2.1', 'wc_error_count' );
		return wc_error_count();
	}

	// Deprecated 2.1.0
	public function message_count() {
		_deprecated_function( 'Woocommerce->message_count', '2.1', 'wc_message_count' );
		return wc_message_count();
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function get_errors() {
		_deprecated_function( 'Woocommerce->get_errors', '2.1', 'WC_Messages_Helper->get_errors' );
		return $this->session->get( 'wc_errors', array() );
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function get_messages() {
		_deprecated_function( 'Woocommerce->get_messages', '2.1', 'WC_Messages_Helper->get_messages' );
		return $this->session->get( 'wc_messages', array() );
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function show_messages() {
		_deprecated_function( 'Woocommerce->show_messages', '2.1', 'wc_print_messages()' );
		wc_print_messages();
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function set_messages() {
		_deprecated_function( 'Woocommerce->set_messages', '2.1' );
	}
}

endif;

/**
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return  object WooCommerce
 */
function WC() {
	return WooCommerce::instance();
}

// Global for backwards compatibilty.
$GLOBALS['woocommerce'] = WC();
