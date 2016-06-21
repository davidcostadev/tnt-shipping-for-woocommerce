<?php
/**
 * Admin orders actions.
 *
 * @package WooCommerce_Tnt/Admin/Orders
 * @since   1.0.0
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TNT orders.
 */
class WC_Tnt_Admin_Orders {

	/**
	 * Initialize the order actions.
	 */
	public function __construct() {
		//add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		//add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_tracking_code' ) );
	}

	/**
	 * Register tracking code metabox.
	 */
	public function register_metabox() {
		add_meta_box(
			'wc_tnt',
			'TNT',
			array( $this, 'metabox_content' ),
			'shop_order',
			'side',
			'default'
		);
	}

	/**
	 * Tracking code metabox content.
	 *
	 * @param WC_Post $post Post data.
	 */
	public function metabox_content( $post ) {
		echo '<label for="tnt_tracking">' . esc_html__( 'Tracking code:', 'woocommerce-tnt' ) . '</label><br />';
		echo '<input type="text" id="tnt_tracking" name="tnt_tracking" value="' . esc_attr( get_post_meta( $post->ID, 'tnt_tracking', true ) ) . '" style="width: 100%;" />';
	}

	/**
	 * Save tracking code.
	 *
	 * @param int $post_id Current post type ID.
	 */
	public function save_tracking_code( $post_id ) {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}
		if ( isset( $_POST['tnt_tracking'] ) ) {
			$old = get_post_meta( $post_id, 'tnt_tracking', true );
			$new = sanitize_text_field( wp_unslash( $_POST['tnt_tracking'] ) );
			if ( $new && $new != $old ) {
				update_post_meta( $post_id, 'tnt_tracking', $new );
				// Gets order data.
				$order = wc_get_order( $post_id );
				// Add order note.
				$order->add_order_note( sprintf( __( 'Added a TNT tracking code: %s', 'woocommerce-tnt' ), $new ) );
				// Send email notification.
				$this->trigger_email_notification( $order, $new );
			} elseif ( '' == $new && $old ) {
				delete_post_meta( $post_id, 'tnt_tracking', $old );
			}
		}
	}

	/**
	 * Trigger email notification.
	 *
	 * @param object $order Order data.
	 * @param string $tracking_code The TNT tracking code.
	 */
	protected function trigger_email_notification( $order, $tracking_code ) {
		$mailer       = WC()->mailer();
		$notification = $mailer->emails['WC_Tnt_Tracking_Email'];
		$notification->trigger( $order, $tracking_code );
	}

}