<?php
/**
 * TNT tracking code email.
 *
 * @package WooCommerce_Tnt/Classes/Emails
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TNT Tracking code email.
 */
class WC_Tnt_Tracking_Email extends WC_Email {

	/**
	 * Initialize tracking template.
	 */
	public function __construct() {
		$this->id               = 'tnt_tracking';
		$this->title            = __( 'Tnt Tracking Code', 'woocommerce-tnt' );
		$this->enabled          = 'yes';
		$this->description      = __( 'This email is sent when configured a tracking code within an order.', 'woocommerce-tnt' );
		$this->heading          = __( 'Your order has been sent', 'woocommerce-tnt' );
		$this->subject          = __( '[{blogname}] Your order {order_number} has been sent by TNT', 'woocommerce-tnt' );
		$this->message          = __( 'Hi there. Your recent order on {blogname} has been sent by TNT.', 'woocommerce-tnt' )
		                          . PHP_EOL . PHP_EOL
		                          . __( 'To track your delivery, use the following the tracking code: {tracking_code}.', 'woocommerce-tnt' )
		                          . PHP_EOL . PHP_EOL
		                          . __( 'The delivery service is the responsibility of the TNT, but if you have any questions, please contact us.', 'woocommerce-tnt' );
		$this->tracking_message = $this->get_option( 'tracking_message', $this->message );
		$this->template_html    = 'emails/tnt-tracking-code.php';
		$this->template_plain   = 'emails/plain/tnt-tracking-code.php';

		// Call parent constructor.
		parent::__construct();

		$this->template_base = WC_Tnt::get_templates_path();
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'subject'          => array(
				'title'       => __( 'Subject', 'woocommerce-tnt' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-tnt' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading'          => array(
				'title'       => __( 'Email Heading', 'woocommerce-tnt' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-tnt' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),
			'tracking_message' => array(
				'title'       => __( 'Email Content', 'woocommerce-tnt' ),
				'type'        => 'textarea',
				'description' => sprintf( __( 'This controls the initial content of the email. Leave blank to use the default content: <code>%s</code>.', 'woocommerce-tnt' ), $this->message ),
				'placeholder' => '',
				'default'     => '',
			),
			'email_type'       => array(
				'title'       => __( 'Email type', 'woocommerce-tnt' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce-tnt' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'     => __( 'Plain text', 'woocommerce-tnt' ),
					'html'      => __( 'HTML', 'woocommerce-tnt' ),
					'multipart' => __( 'Multipart', 'woocommerce-tnt' ),
				),
			),
		);
	}

	/**
	 * Get email tracking message.
	 *
	 * @return string
	 */
	public function get_tracking_message() {
		return apply_filters( 'woocommerce_tnt_email_tracking_message', $this->format_string( $this->tracking_message ), $this->object );
	}

	/**
	 * Get tracking code url.
	 *
	 * @param  string $tracking_code Tracking code.
	 *
	 * @return string
	 */
	public function get_tracking_code_url( $tracking_code ) {
		return '#';
	}

	/**
	 * Trigger email.
	 *
	 * @param  WC_Order $order Order data.
	 * @param  string $tracking_code Tracking code.
	 */
	public function trigger( $order, $tracking_code ) {
		if ( is_object( $order ) ) {
			$this->object    = $order;
			$this->recipient = $this->object->billing_email;

			$this->find[]    = '{order_number}';
			$this->replace[] = $this->object->get_order_number();

			$this->find[]    = '{date}';
			$this->replace[] = date_i18n( wc_date_format(), time() );

			$this->find[]    = '{tracking_code}';
			$this->replace[] = $this->get_tracking_code_url( $tracking_code );
		}

		if ( ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();

		wc_get_template( $this->template_html, array(
			'order'            => $this->object,
			'email_heading'    => $this->get_heading(),
			'tracking_message' => $this->get_tracking_message(),
			'sent_to_admin'    => false,
			'plain_text'       => false,
		), '', $this->template_base );

		return ob_get_clean();
	}

	/**
	 * Get content plain text.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();

		wc_get_template( $this->template_plain, array(
			'order'            => $this->object,
			'email_heading'    => $this->get_heading(),
			'tracking_message' => $this->get_tracking_message(),
			'sent_to_admin'    => false,
			'plain_text'       => true,
		), '', $this->template_base );

		return ob_get_clean();
	}

}

return new WC_Tnt_Tracking_Email();