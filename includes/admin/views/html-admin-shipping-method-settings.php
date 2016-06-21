<?php
/**
 * Shipping methods admin settings.
 *
 * @package WooCommerce_Tnt/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
wp_enqueue_script( 'wc-tnt', plugins_url( 'assets/js/admin/shipping-methods' . $suffix . '.js', WC_Tnt::get_main_file() ), array( 'jquery' ), WC_Tnt::VERSION, true );

?>

<h3><?php echo esc_html( $this->method_title ); ?></h3>

<p>
	<?php echo esc_html( $this->get_method_title() ); ?>
</p>

<?php include 'html-admin-help-message.php'; ?>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>