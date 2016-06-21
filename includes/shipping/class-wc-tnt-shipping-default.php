<?php
/**
 * TNT Default shipping method.
 *
 * @package WooCommerce_Tnt/Classes/Shipping
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Tnt_Shipping_Default extends WC_Tnt_Shipping {

	public function __construct() {
		$this->id           = 'tnt_default';
		$this->method_title = __( 'TNT', 'woocommerce-tnt' );

		parent::__construct();
	}

}