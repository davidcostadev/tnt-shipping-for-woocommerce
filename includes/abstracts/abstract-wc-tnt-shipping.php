<?php
/**
 * Abstract TNT shipping method.
 *
 * @package WooCommerce_Tnt/Abstracts
 * @since   1.0.0
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default TNT shipping method abstract class.
 *
 * This is a abstract method with default options for all methods.
 */
abstract class WC_Tnt_Shipping extends WC_Shipping_Method {

	/**
	 * Initialize the TNT shipping method.
	 */
	public function __construct() {
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Define user set variables.
		$this->enabled            = $this->get_option( 'enabled' );
		$this->title              = $this->get_option( 'title' );
		$this->show_delivery_time = $this->get_option( 'show_delivery_time' );
		$this->additional_time    = $this->get_option( 'additional_time' );
		$this->fee                = $this->get_option( 'fee' );
		$this->debug              = $this->get_option( 'debug' );
		$this->shipping_per_item  = $this->get_option( 'shipping_per_item' );
		// Settings for the API
		$this->login                               = $this->get_option( 'settings_login' );
		$this->password                            = $this->get_option( 'settings_password' );
		$this->nr_inscricao_estadual               = $this->get_option( 'settings_nr_inscricao_estadual' );
		$this->nr_identif                          = $this->get_option( 'settings_nr_identif' );
		$this->tp_situacao_tributaria_remetente    = $this->get_option( 'settings_tp_situacao_tributaria_remetente' );
		$this->tp_situacao_tributaria_destinatario = $this->get_option( 'settings_tp_situacao_tributaria_destinatario' );
		$this->cep_origem                          = $this->get_option( 'settings_cep_origem' );
		$this->tp_pessoa_remetente                 = $this->get_option( 'settings_tp_pessoa_remetente' );
		$this->tp_servico                          = $this->get_option( 'settings_tp_servico' );
		$this->tp_frete                            = $this->get_option( 'settings_tp_frete' );
		$this->nr_identif_cliente_dest             = $this->get_option( 'settings_nr_identif_cliente_dest' );
		$this->tp_pessoa_destinatario              = $this->get_option( 'settings_tp_pessoa_destinatario' );
		// Method variables.
		$this->availability = 'specific';
		$this->countries    = array( 'BR' );
		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}
		// Save admin options.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	protected function get_log_link() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
			return ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'View logs.', 'woocommerce-tnt' ) . '</a>';
		}
	}

	/**
	 * Get behavior options.
	 *
	 * @return array
	 */
	protected function get_behavior_options() {
		$fields['shipping_per_item'] = array(
			'title'   => __( 'Frete por item', 'woocommerce-tnt' ),
			'type'    => 'checkbox',
			'label'   => __( 'Ative isso para habilitar o cálculo de frete por item e não por pacote.', 'woocommerce-tnt' ),
			'default' => 'no',
		);

		return $fields;
	}

	protected function get_settings_options() {
		$fields                                                 = array();
		$fields['settings_login']                               = array(
			'title'       => __( 'Usuário', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'O usuário utilizado para fazer login na transportadora.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'placeholder' => 'user@example.com',
		);
		$fields['settings_password']                            = array(
			'title'       => __( 'Senha', 'woocommerce-tnt' ),
			'type'        => 'password',
			'description' => __( 'A senha utilizada para fazer login na transportadora.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'placeholder' => 'user@example.com',
		);
		$fields['settings_nr_inscricao_estadual']               = array(
			'title'       => __( 'Inscrição Estadual', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'Inscrição estadual do Remetente (Pessoa Jurídica)', 'woocommerce-tnt' ),
			'desc_tip'    => true,
		);
		$fields['settings_nr_identif']                          = array(
			'title'       => __( 'Identificador', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'Identificador do Remetente CGC ou CPF.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
		);
		$fields['settings_nr_identif_cliente_dest']             = array(
			'title'       => __( 'Identificador Destinatário', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'Identificador do destinatário CGC ou CPF.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
		);
		$fields['settings_tp_situacao_tributaria_remetente']    = array(
			'title'   => __( 'Situação Tributária Remetente', 'woocommerce-tnt' ),
			'type'    => 'select',
			'options' => array(
				''   => __( 'Selecionar', 'woocommerce-tnt' ),
				'CO' => __( 'Contribuinte', 'woocommerce-tnt' ),
				'NC' => __( 'Não Contribuinte', 'woocommerce-tnt' ),
				'CI' => __( 'Contrib Incentivado', 'woocommerce-tnt' ),
				'CM' => __( 'Cia Mista Contribuinte', 'woocommerce-tnt' ),
				'CN' => __( 'Cia Mista Não Contribuinte', 'woocommerce-tnt' ),
				'ME' => __( 'ME / EPP / Simples Nacional Contribuinte', 'woocommerce-tnt' ),
				'MN' => __( 'ME / EPP / Simples Nacional Não Contribuinte', 'woocommerce-tnt' ),
				'PR' => __( 'Produtor Rural Contribuinte', 'woocommerce-tnt' ),
				'PN' => __( 'Produtor Rural Não Contribuinte', 'woocommerce-tnt' ),
				'OP' => __( 'Órgão Público Contribuinte', 'woocommerce-tnt' ),
				'ON' => __( 'Órgão Público Não Contribuinte', 'woocommerce-tnt' ),
				'OF' => __( 'Órgão Público - Programas de fortalecimento e modernização Estadual', 'woocommerce-tnt' ),
			),
		);
		$fields['settings_tp_situacao_tributaria_destinatario'] = array(
			'title'   => __( 'Situação Tributária Destinatário', 'woocommerce-tnt' ),
			'type'    => 'select',
			'options' => array(
				''   => __( 'Selecionar', 'woocommerce-tnt' ),
				'CO' => __( 'Contribuinte', 'woocommerce-tnt' ),
				'NC' => __( 'Não Contribuinte', 'woocommerce-tnt' ),
				'CI' => __( 'Contrib Incentivado', 'woocommerce-tnt' ),
				'CM' => __( 'Cia Mista Contribuinte', 'woocommerce-tnt' ),
				'CN' => __( 'Cia Mista Não Contribuinte', 'woocommerce-tnt' ),
				'ME' => __( 'ME / EPP / Simples Nacional Contribuinte', 'woocommerce-tnt' ),
				'MN' => __( 'ME / EPP / Simples Nacional Não Contribuinte', 'woocommerce-tnt' ),
				'PR' => __( 'Produtor Rural Contribuinte', 'woocommerce-tnt' ),
				'PN' => __( 'Produtor Rural Não Contribuinte', 'woocommerce-tnt' ),
				'OP' => __( 'Órgão Público Contribuinte', 'woocommerce-tnt' ),
				'ON' => __( 'Órgão Público Não Contribuinte', 'woocommerce-tnt' ),
				'OF' => __( 'Órgão Público - Programas de fortalecimento e modernização Estadual', 'woocommerce-tnt' ),
			),
		);
		$fields['settings_cep_origem']                          = array(
			'title'       => __( 'CEP de Origem', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'CEP origem da Coleta.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'placeholder' => '00000-000',
		);
		$fields['settings_tp_pessoa_remetente']                 = array(
			'title'   => __( 'Pessoa Remetente', 'woocommerce-tnt' ),
			'type'    => 'select',
			'options' => array(
				''  => __( 'Selecionar', 'woocommerce-tnt' ),
				'F' => __( 'Física', 'woocommerce-tnt' ),
				'J' => __( 'Jurídica', 'woocommerce-tnt' ),
			),
		);
		$fields['settings_tp_pessoa_destinatario']              = array(
			'title'   => __( 'Pessoa Destinatário', 'woocommerce-tnt' ),
			'type'    => 'select',
			'options' => array(
				''  => __( 'Selecionar', 'woocommerce-tnt' ),
				'F' => __( 'Física', 'woocommerce-tnt' ),
				'J' => __( 'Jurídica', 'woocommerce-tnt' ),
			),
		);
		$fields['settings_tp_servico']                          = array(
			'title'   => __( 'Modalidade', 'woocommerce-tnt' ),
			'type'    => 'select',
			'options' => array(
				''    => __( 'Selecionar', 'woocommerce-tnt' ),
				'RNC' => __( 'Rodoviário Nacional', 'woocommerce-tnt' ),
				'ANC' => __( 'Aéreo Nacional', 'woocommerce-tnt' ),
			),
		);
		$fields['settings_tp_frete']                            = array(
			'title'   => __( 'Tipo de Cobrança do Frete', 'woocommerce-tnt' ),
			'type'    => 'select',
			'options' => array(
				''  => __( 'Selecionar', 'woocommerce-tnt' ),
				'C' => __( 'CIF', 'woocommerce-tnt' ),
				'F' => __( 'FOB', 'woocommerce-tnt' ),
			),
		);


		return $fields;
	}

	/**
	 * Admin options fields.
	 */
	public function init_form_fields() {
		$fields                     = array();
		$fields['enabled']          = array(
			'title'   => __( 'Ativado/Desativado', 'woocommerce-tnt' ),
			'type'    => 'checkbox',
			'label'   => __( 'Ative isso para habilitar o método de entrega', 'woocommerce-tnt' ),
			'default' => 'no',
		);
		$fields['title']            = array(
			'title'       => __( 'Título', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'Isto controla o título que o usuário irá visualizar durante o checkout.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'default'     => $this->method_title,
		);
		$fields['behavior_options'] = array(
			'title' => __( 'Opções de Comportamento', 'woocommerce-tnt' ),
			'type'  => 'title',
		);
		// Add custom behavior options.
		$fields                       = array_merge( $fields, $this->get_behavior_options() );
		$fields['show_delivery_time'] = array(
			'title'       => __( 'Prazo de entrega', 'woocommerce-tnt' ),
			'type'        => 'checkbox',
			'label'       => __( 'Exibe o prazo de entrega aproximado', 'woocommerce-tnt' ),
			'description' => __( 'Exibe o prazo de entrega estimado em dias úteis.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'default'     => 'no',
		);
		$fields['additional_time']    = array(
			'title'       => __( 'Dias adicionais', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'Dias úteis adicionais ao prazo de entrega.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'default'     => '0',
			'placeholder' => '0',
		);
		$fields['fee']                = array(
			'title'       => __( 'Taxa de Manuseio', 'woocommerce-tnt' ),
			'type'        => 'text',
			'description' => __( 'Coloque um valor, ex.: 2.50, ou porcentagem, ex.: 5%. Deixe em branco para desativar.', 'woocommerce-tnt' ),
			'desc_tip'    => true,
			'placeholder' => '0.00',
		);
		$fields['testing']            = array(
			'title' => __( 'Testes', 'woocommerce-tnt' ),
			'type'  => 'title',
		);
		$fields['debug']              = array(
			'title'       => __( 'Log de Depuração', 'woocommerce-tnt' ),
			'type'        => 'checkbox',
			'label'       => __( 'Ativar log', 'woocommerce-tnt' ),
			'default'     => 'no',
			'description' => __( 'Fazer log dos eventos, como requisições aos WebServices.', 'woocommerce-tnt' ) . $this->get_log_link(),
		);
		$fields['settings_options']   = array(
			'title' => __( 'Configurações', 'woocommerce-tnt' ),
			'type'  => 'title',
		);
		$fields                       = array_merge( $fields, $this->get_settings_options() );
		$this->form_fields            = $fields;
	}

	/**
	 * Get shipping method title.
	 *
	 * @return string
	 */
	public function get_method_title() {
		return sprintf( __( '%s é um método de entrega da TNT.', 'woocommerce-tnt' ), $this->method_title );
	}

	/**
	 * TNT options page.
	 */
	public function admin_options() {
		include WC_Tnt::get_plugin_path() . 'includes/admin/views/html-admin-shipping-method-settings.php';
	}

	/**
	 * Get shipping rate.
	 *
	 * @param  array $package Order package.
	 *
	 * @return SimpleXMLElement
	 */
	protected function get_rate( $package ) {
		if ( $package ) {
			$rate    = array(
				'id'       => $this->id,
				'label'    => $this->title,
				'cost'     => '',
				'calc_tax' => 'per_item'
			);
			$connect = new WC_Tnt_Webservice( $this->id, $this );
			$connect->set_debug( $this->debug );
			$connect->set_package( $package );
			$shipping              = $connect->get_shipping();
			$rate['cost']          = $shipping['totals'];
			$rate['delivery_time'] = $shipping['delivery_time'];
			if ( false !== $shipping && null !== $shipping['totals'] ) {
				return $rate;
			} else {
				return false;
			}
		}
	}

	/**
	 * Calculates the shipping rate.
	 *
	 * @param array $package Order package.
	 */
	public function calculate_shipping( $package = array() ) {
		// Check if valid to be calculeted.
		if ( '' === $package['destination']['postcode'] || 'BR' !== $package['destination']['country'] ) {
			return;
		}

		$shipping = $this->get_rate( $package );

		if ( false === $shipping ) {
			return;
		}

		$label = $this->get_shipping_method_label( $shipping['delivery_time'] );
		$cost  = $shipping['cost'];
		$fee   = $this->get_fee( str_replace( ',', '.', $this->fee ), $cost );

		$rate = apply_filters( 'woocommerce_tnt_' . $this->id . '_rate', array(
			'id'    => $this->id,
			'title' => $this->title,
			'label' => $label,
			'cost'  => $cost + $fee,
		) );

		$this->add_rate( $rate );
	}

	protected function get_shipping_method_label( $days ) {
		if ( 'yes' == $this->show_delivery_time ) {
			return wc_tnt_get_estimating_delivery( $this->title, $days, $this->additional_time );
		}

		return $this->title;
	}

}