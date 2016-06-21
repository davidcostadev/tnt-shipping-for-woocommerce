<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Tnt_Package {

	protected $package = array();

	public function __construct( $package = array() ) {
		$this->package = $package;
	}

	private function fix_format( $value ) {
		$value = str_replace( ',', '.', $value );

		return $value;
	}

	private function format_price( $value ) {
		$value = number_format( $value, 2, '.', '' );

		return $value;
	}

	public function get_postcode() {
		return wc_tnt_sanitize_postcode( $this->package['destination']['postcode'] );
	}

	public function get_package() {
		return $this->package;
	}

	public function get_cubic_weight( $height, $width, $length ) {
		$cubic_weight = $height * $width * $length * 200;

		return $cubic_weight;
	}

	public function get_package_data() {
		$count       = 0;
		$height      = array();
		$width       = array();
		$length      = array();
		$weight      = array();
		$totals      = array();
		$total_items = 0;

		// Shipping per item.
		foreach ( $this->package['contents'] as $item_id => $values ) {
			$product = $values['data'];
			$qty     = $values['quantity'];
			$total   = $values['line_total'];

			if ( $qty > 0 && $product->needs_shipping() ) {
				for ( $i = 0; $i < $qty; $i ++ ) {
					$total_items ++;
					$_height  = wc_get_dimension( $this->fix_format( $product->height ), 'm' );
					$_width   = wc_get_dimension( $this->fix_format( $product->width ), 'm' );
					$_length  = wc_get_dimension( $this->fix_format( $product->length ), 'm' );
					$_weight  = wc_get_weight( $this->fix_format( $product->weight ), 'kg' );
					$_total   = $this->format_price( $total );
					$height[] = $_height;
					$width[]  = $_width;
					$length[] = $_length;
					$weight[] = $_weight;
					$totals[] = $_total;
				}
			}
		}

		$result = array(
			'height' => array_values( $height ),
			'length' => array_values( $length ),
			'width'  => array_values( $width ),
			'weight' => array_values( $weight ),
			'totals' => array_values( $totals ),
			'items'  => $total_items,
		);

		return $result;
	}

}