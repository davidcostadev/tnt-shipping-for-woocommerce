<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_tnt_sanitize_postcode( $postcode ) {
	return preg_replace( '([^0-9])', '', sanitize_text_field( $postcode ) );
}

function wc_tnt_get_estimating_delivery( $name, $days, $additional_days = 0 ) {
	$additional_days = intval( $additional_days );

	if ( $additional_days > 0 ) {
		$days += intval( $additional_days );
	}

	if ( $days > 0 ) {
		$name .= ' (' . sprintf( _n( 'Entrega em até %d dia útil', 'Entrega em até %d dias úteis', $days, 'woocommerce-tnt' ), $days ) . ')';
	}

	return $name;
}

function wc_tnt_normalize_price( $value ) {
	$value = str_replace( '.', '', $value );
	$value = str_replace( ',', '.', $value );

	return $value;
}