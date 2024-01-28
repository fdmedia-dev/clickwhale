<?php

namespace clickwhale_pro\includes\front;

use WC_Order;

/**
 * @since 1.3.6
 */
class Clickwhale_Pro_Tracking_Code_Conversion {

	public array $tracking_code;

	public function __construct( array $tracking_code ) {
		$this->tracking_code = $tracking_code;
		$position            = maybe_unserialize( $tracking_code['position'] );

		switch ( $position['conversion'] ) {
			case 'product':
				add_action( 'woocommerce_thankyou', [ $this, 'woo_thankyou' ], - 10 );
				break;
			case 'download':
				add_action( 'edd_payment_receipt_after_table', [ $this, 'edd_thankyou' ] );
				break;
		}

	}

	public function woo_thankyou( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order             = [];
		$order['ORDERID']  = $order_id;
		$wc_order          = new WC_Order( $order_id );
		$order['CURRENCY'] = $wc_order->get_currency();
		$order['FULLNAME'] = $wc_order->get_billing_first_name();

		if ( $wc_order->get_billing_last_name() != '' ) {
			$order['FULLNAME'] .= ' ' . $wc_order->get_billing_last_name();
		}

		$order['EMAIL']    = $wc_order->get_billing_email();
		$order['PRODUCTS'] = [];

		foreach ( $wc_order->get_items() as $k => $v ) {
			$k = intval( $v['product_id'] );
			if ( $k > 0 ) {
				$v                       = $v['name'];
				$order['PRODUCTS'][ $k ] = $v;
			}
		}

		$total             = $wc_order->get_total();
		$tax               = $wc_order->get_total_tax();
		$shipping          = $wc_order->get_shipping_total();
		$amount            = $total - $tax - $shipping;
		$order['AMOUNT']   = number_format( $amount, 2 );
		$order['TAX']      = $tax;
		$order['SHIPPING'] = $shipping;
		$order['TOTAL']    = $total;

		$this->do_conversion_code( $this->tracking_code, $order );
	}

	public function edd_thankyou( $payment ) {

		if ( ! $payment ) {
			return;
		}

		$order             = [];
		$order['ORDERID']  = $payment->ID;
		$edd_order         = edd_get_order( $payment->ID );
		$order['CURRENCY'] = $payment->currency;
		$order['FULLNAME'] = $payment->first_name;

		if ( $payment->last_name != '' ) {
			$order['FULLNAME'] .= ' ' . $payment->last_name;
		}
		$order['EMAIL'] = $payment->email;

		$cart = edd_get_payment_meta_cart_details( $payment->ID, true );
		foreach ( $cart as $k => $v ) {
			$order['PRODUCTS'][ $v['id'] ] = $v['name'];
		}
		$order['AMOUNT'] = number_format( $edd_order->subtotal, 2 );
		$order['TAX']    = number_format( $edd_order->tax, 2 );
		$order['TOTAL']  = number_format( $edd_order->total, 2 );

		$this->do_conversion_code( $this->tracking_code, $order );
	}

	public function do_conversion_code( array $tracking_code, array $order ) {
		$position = maybe_unserialize( $tracking_code['position'] );

		if ( ! isset( $position['conversion_items'] ) ) {
			return;
		}

		$code                   = $tracking_code['code'];
		$products_in_order      = '';
		$products_in_conversion = $position['conversion_items'][ $position['conversion'] ]['ids'];

		foreach ( $order as $k => $v ) {
			if ( $k === 'PRODUCTS' ) {
				$products_in_order = array_keys( $v );
				$v                 = implode( ',', $v );
			}
			$code = str_replace( '@@' . $k . '@@', $v, $code );
		}

		if ( ! array_intersect( $products_in_order, $products_in_conversion )
		     && ! in_array( 'all', $products_in_conversion ) ) {
			return;
		}

		add_action( 'wp_footer', function () use ( $code ) {
			echo wp_unslash( $code );
		} );
	}

}