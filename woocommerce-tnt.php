<?php

/**
 * Plugin Name: TNT Shipping for WooCommerce
 * Plugin URI: https://github.com/eliasjnior/tnt-shipping-for-woocommerce
 * Description: Calculate shipping for TNT Mercurio Brazil
 * Author: Elias Júnior
 * Author URI: http://eliasjrweb.com/
 * Version: 1.0.0-beta1
 * License: GPLv2 or later
 * Text Domain: woocommerce-tnt
 * Domain Path: languages/
 *
 * @package WooCommerce_Tnt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Tnt' ) ) {

	/**
	 * WooCommerce TNT main class
	 */
	class WC_Tnt {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		const VERSION = '1.0.0-beta1';

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin public actions.
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// Checks with WooCommerce is installed.
			if ( class_exists( 'WC_Shipping_Method' ) ) {

				$this->includes();

				if ( is_admin() ) {
					$this->admin_includes();
				}

				add_filter( 'woocommerce_integrations', array( $this, 'include_integrations' ) );
				add_filter( 'woocommerce_shipping_methods', array( $this, 'include_methods' ) );
				add_filter( 'woocommerce_email_classes', array( $this, 'include_emails' ) );

				// Ajax actions.
				add_action( 'wp_ajax_wc_tnt_simulator', array(
					'WC_Tnt_Product_Shipping_Simulator',
					'ajax_simulator'
				) );
				add_action( 'wp_ajax_nopriv_wc_tnt_simulator', array(
					'WC_Tnt_Product_Shipping_Simulator',
					'ajax_simulator'
				) );

				// Action links.
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
					$this,
					'plugin_action_links'
				) );

			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-tnt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Includes.
		 */
		private function includes() {
			include_once 'includes/wc-tnt-functions.php';
			include_once 'includes/class-wc-tnt-package.php';
			include_once 'includes/class-wc-tnt-webservice.php';

			// Shipping methods.
			include_once 'includes/abstracts/abstract-wc-tnt-shipping.php';
			foreach ( glob( plugin_dir_path( __FILE__ ) . 'includes/shipping/*.php' ) as $filename ) {
				include_once $filename;
			}
		}

		/**
		 * Admin includes.
		 */
		private function admin_includes() {
			include_once 'includes/admin/class-wc-tnt-admin-orders.php';
		}

		/**
		 * Action links.
		 *
		 * @param  array $links Plugin action links.
		 *
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			$plugin_links   = array();
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ) . '">' . __( 'Settings', 'woocommerce-tnt' ) . '</a>';

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Include TBT integration to WooCommerce.
		 *
		 * @param  array $integrations Default integrations.
		 *
		 * @return array
		 */
		public function include_integrations( $integrations ) {

			return $integrations;
		}

		/**
		 * Include TNT shipping methods to WooCommerce.
		 *
		 * @param  array $methods Default shipping methods.
		 *
		 * @return array
		 */
		public function include_methods( $methods ) {

			$methods[] = 'WC_Tnt_Shipping_Legacy';

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.6.0', '>=' ) ) {
				$methods['tnt_default'] = 'WC_Tnt_Shipping_Default';
			}


			return $methods;
		}

		/**
		 * Include emails.
		 *
		 * @param  array $emails Default emails.
		 *
		 * @return array
		 */
		public function include_emails( $emails ) {
			if ( ! isset( $emails['WC_Tnt_Tracking_Email'] ) ) {
				$emails['WC_Tnt_Tracking_Email'] = include( 'includes/emails/class-wc-tnt-tracking-email.php' );
			}

			return $emails;
		}

		/**
		 * WooCommerce fallback notice.
		 */
		public function woocommerce_missing_notice() {
			include 'includes/admin/views/html-admin-missing-dependencies.php';
		}

		/**
		 * Get main file.
		 *
		 * @return string
		 */
		public static function get_main_file() {
			return __FILE__;
		}

		/**
		 * Get plugin path.
		 *
		 * @return string
		 */
		public static function get_plugin_path() {
			return plugin_dir_path( __FILE__ );
		}

		/**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path() {
			return self::get_plugin_path() . 'templates/';
		}

	}

	add_action( 'plugins_loaded', array( 'WC_Tnt', 'get_instance' ) );

}
