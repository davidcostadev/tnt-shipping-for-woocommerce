<?php
/**
 * TNT Webservice.
 *
 * @package WooCommerce_Tnt/Classes/Webservice
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TNT Webservice Integration class
 */
class WC_Tnt_Webservice {

	/**
	 * Webservice URL.
	 *
	 * @var string
	 */

	private $_webservice = 'http://ws.tntbrasil.com.br/servicos/CalculoFrete?wsdl';

	protected $destination_postcode = '';

	/**
	 * Debug mode.
	 *
	 * @var string
	 */
	protected $debug = 'no';

	/**
	 * Initialize webservice.
	 */
	public function __construct( $id = 'tnt', $shipping_instance = null ) {
		$this->id                = $id;
		$this->log               = new WC_Logger();
		$this->shipping_instance = $shipping_instance;
	}

	/**
	 * Get shipping instance
	 * @return WC_Tnt_Shipping Shipping instance
	 */
	public function get_shipping_instance() {
		return $this->shipping_instance;
	}

	public function get_shipping() {
		$shipping = null;

		$response = $this->soap_calcula_frete();

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'WP_Error: ' . $response->get_error_message() );
			}
		} else if ( false !== $response ) {
			$shipping = $response;
		}

		return $shipping;

	}

	/**
	 * Set package
	 *
	 * @param array $package
	 *
	 * @return WC_Tnt_Package
	 */
	public function set_package( $package = array() ) {
		$this->package = new WC_Tnt_Package( $package );

		return $this->package;
	}

	/**
	 * Set the debug mode.
	 *
	 * @param string $debug Yes or no.
	 */
	public function set_debug( $debug = 'no' ) {
		$this->debug = $debug;
	}

	/**
	 * Get package
	 * @return WC_Tnt_Package
	 */
	public function get_package() {
		return $this->package;
	}

	public function get_shipping_per_item() {
		$package           = $this->get_package();
		$package_data      = $package->get_package_data();
		$shipping_instance = $this->get_shipping_instance();
		$parameters        = array(
			'cepDestino'           => $package->get_postcode(),
			'nrIdentifClienteDest' => $shipping_instance->nr_identif_cliente_dest,
			'tpPessoaDestinatario' => $shipping_instance->tp_pessoa_destinatario,
		);
		$response          = array(
			'totals'        => 0,
			'delivery_time' => false,
		);
		for ( $i = 0; $i < $package_data['items']; $i ++ ) {
			$cubic_weight    = $package->get_cubic_weight( $package_data['height'][ $i ], $package_data['width'][ $i ], $package_data['length'][ $i ] );
			$parameters_item = wp_parse_args( array(
				'psReal'       => number_format( $cubic_weight, 3, '.', '' ),
				'vlMercadoria' => $package_data['totals'][ $i ],
			), $parameters );
			$soap            = $this->soap_calculate_shipping( $parameters_item );
			if ( $soap ) {
				$response['totals'] += (float) $soap['vlTotalFrete'];
				if ( false == $response['delivery_time'] || (int) $response['delivery_time'] < $soap['prazoEntrega'] ) {
					$response['delivery_time'] = $soap['prazoEntrega'];
				}
			} else {
				return false;
			}
		}

		return $response;
	}

	public function get_shipping_per_package() {
		$package                = $this->get_package();
		$package_data           = $package->get_package_data();
		$package_data['height'] = array_sum( $package_data['height'] );
		$package_data['length'] = array_sum( $package_data['length'] );
		$package_data['width']  = array_sum( $package_data['width'] );
		$package_data['weight'] = array_sum( $package_data['weight'] );
		$package_data['totals'] = array_sum( $package_data['totals'] );
		$shipping_instance      = $this->get_shipping_instance();
		$cubic_weight           = $package->get_cubic_weight( $package_data['height'], $package_data['width'], $package_data['length'] );
		$parameters             = array(
			'cepDestino'           => $package->get_postcode(),
			'nrIdentifClienteDest' => $shipping_instance->nr_identif_cliente_dest,
			'tpPessoaDestinatario' => $shipping_instance->tp_pessoa_destinatario,
			'psReal'               => $cubic_weight,
			'vlMercadoria'         => $package_data['totals'],
		);
		$response               = array(
			'totals'        => 0,
			'delivery_time' => false,
		);
		$soap                   = $this->soap_calculate_shipping( $parameters );
		if ( $soap ) {
			$response['totals'] += (float) $soap['vlTotalFrete'];
			$response['delivery_time'] = $soap['prazoEntrega'];
		} else {
			return false;
		}

		return $response;
	}

	/**
	 * Calculate shipping
	 *
	 * @param $parameters
	 *
	 * @return array|bool|null|WP_Error
	 */
	public function soap_calcula_frete() {
		if ( 'yes' == $this->get_shipping_instance()->shipping_per_item ) {
			return $this->get_shipping_per_item();
		} else {
			return $this->get_shipping_per_package();
		}
	}

	private function soap_calculate_shipping( $parameters ) {
		$shipping_instance   = $this->get_shipping_instance();
		$defaults_parameters = array(
			'cdDivisaoCliente'                 => 1,
			'cepDestino'                       => '',
			'cepOrigem'                        => wc_tnt_sanitize_postcode( $shipping_instance->cep_origem ),
			'login'                            => $shipping_instance->login,
			'nrIdentifClienteDest'             => '',
			'nrIdentifClienteRem'              => $shipping_instance->nr_identif,
			'nrInscricaoEstadualDestinatario'  => '',
			'nrInscricaoEstadualRemetente'     => $shipping_instance->nr_inscricao_estadual,
			'psReal'                           => '',
			'senha'                            => $shipping_instance->password,
			'tpFrete'                          => $shipping_instance->tp_frete,
			'tpPessoaDestinatario'             => '',
			'tpPessoaRemetente'                => $shipping_instance->tp_pessoa_remetente,
			'tpServico'                        => $shipping_instance->tp_servico,
			'tpSituacaoTributariaDestinatario' => $shipping_instance->tp_situacao_tributaria_destinatario,
			'tpSituacaoTributariaRemetente'    => $shipping_instance->tp_situacao_tributaria_remetente,
			'vlMercadoria'                     => '',
		);
		$parameters          = wp_parse_args( $parameters, $defaults_parameters );
		$request             = array(
			'in0' => $parameters
		);
		$reponse             = null;
		try {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Making request: ' . print_r( $parameters, true ) );
			}
			$soap   = new SoapClient( $this->_webservice );
			$call   = $soap->calculaFrete( $request );
			$out    = (array) $call->out;
			$errors = (array) $out['errorList'];
			if ( ! empty( $errors ) ) {
				if ( 'yes' == $this->debug ) {
					$str = "Errors in request: ";
					$this->log->add( $this->id, 'Errors in request: ' . implode( ',', $errors ) );
				}
			} else {
				$response = array(
					'errorList'                   => array(),
					'nmDestinatario'              => $out['nmDestinatario'],
					'nmMunicipioDestino'          => $out['nmMunicipioDestino'],
					'nmMunicipioOrigem'           => $out['nmMunicipioOrigem'],
					'nmRemetente'                 => $out['nmRemetente'],
					'nrDDDFilialDestino'          => $out['nrDDDFilialDestino'],
					'nrDDDFilialOrigem'           => $out['nrDDDFilialOrigem'],
					'nrTelefoneFilialDestino'     => $out['nrTelefoneFilialDestino'],
					'nrTelefoneFilialOrigem'      => $out['nrTelefoneFilialOrigem'],
					'prazoEntrega'                => $out['prazoEntrega'],
					'vlDesconto'                  => $out['vlDesconto'],
					'vlICMSubstituicaoTributaria' => $out['vlICMSubstituicaoTributaria'],
					'vlImposto'                   => $out['vlImposto'],
					'vlTotalCtrc'                 => $out['vlTotalCtrc'],
					'vlTotalFrete'                => $out['vlTotalFrete'],
					'vlTotalServico'              => $out['vlTotalServico'],
				);
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Response: ' . print_r( $out, true ) );
					$this->log->add( $this->id, 'Response prepared: ' . print_r( $response, true ) );
				}
			}
		} catch ( Exception $e ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Exception: ' . $e->getMessage() );
			}
		}

		return $response;
	}

}