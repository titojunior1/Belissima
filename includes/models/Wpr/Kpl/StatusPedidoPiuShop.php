<?php
/**
 * 
 * Classe de gerenciamento de atualização de status de pedido com a Kpl
 * @author Tito Junior 
 *
 */

class Model_Wpr_Kpl_StatusPedidoPiuShop extends Model_Wpr_Kpl_KplWebService {	
	
	/**
	 * Variavel  de Objeto da Classe Kpl.
	 *
	 * @var Model_Wpr_Kpl_KplWebService
	 */
	public $_kpl;
	
	/**
	 * Variavel  de Objeto da Classe Vtex.
	 *	 
	 */
	private $_vtex;

    /*
     *  URL para integração via REST VTEX
    */
    private $_url;

    /*
    *  Token para integração via REST VTEX
    */
    private $_token;

    /*
     *  Chave para integração via REST VTEX
    */
    private $_key;
	
	/**
	 * Construtor.
	 * @param string $ws
	 * @param String $key	  
	 */
	public function __construct( $ws, $key, $url, $key, $token ) {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $ws, $key );
		}
		$this->_url = $url;
		$this->_key = $key;
		$this->_token = $token;
	
	}
	
	/**
	 * Método que faz muda o status de um pedido para faturado
	 */
	private function _getNumeroTransacaoPedido ( $idTransacaoPedido ){
	
		if ( empty( $idTransacaoPedido ) ){
			throw new InvalidArgumentException( "Id {$idTransacaoPedido} invalido." );
		}

        $url = sprintf($this->_url, 'oms/pvt/orders/');
        $url = $url . $idTransacaoPedido;

        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-VTEX-API-AppKey' => $this->_key,
            'X-VTEX-API-AppToken' => $this->_token
        );

        $request = Requests::get($url, $headers);

        if (! $request->success) {
            throw new RuntimeException('Falha ao buscar dados do Pedido. [' . $request->body . ']');
        }

        $pedido = json_decode($request->body);
		
		$idPedido = $pedido->sequence;

		return $idPedido;
	
	}
	
	/**
	 * 
	 * Processar status dos pedidos via webservice.
	 * @param array $request
	 */
	function ProcessaStatusWebservice ( $request, $dadosCliente ) {

		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_status = array ();
		
		echo "Conectando ao WebService Vtex... " . PHP_EOL;
		$this->_vtex = new Model_Wpr_Vtex_Status( $dadosCliente['VTEX_API_URL'], $dadosCliente['VTEX_API_KEY'], $dadosCliente['VTEX_API_TOKEN'] );
		echo "Conectado!" . PHP_EOL;
		echo PHP_EOL;
		
		if ( ! is_array ( $request ['DadosStatusPedido'] [0] ) ) {
					
			$array_status [0] ['ProtocoloStatusPedido'] = $request ['DadosStatusPedido'] ['ProtocoloStatusPedido'];
			$array_status [0] ['NumeroPedido'] = $request ['DadosStatusPedido'] ['NumeroPedido'];
			$array_status [0] ['CodigoStatus'] = $request ['DadosStatusPedido'] ['CodigoStatus'];
			$array_status [0] ['StatusPedido'] = $request ['DadosStatusPedido'] ['StatusPedido'];
			$array_status [0] ['CodigoMotivoCancelamento'] = $request ['DadosStatusPedido'] ['CodigoMotivoCancelamento'];
			$array_status [0] ['MotivoCancelamento'] = $request ['DadosStatusPedido'] ['MotivoCancelamento'];
            $array_status [0] ['NumeroObjeto'] = $request ['DadosStatusPedido'] ['NumeroObjeto'];
            $array_status [0] ['NumeroNota'] = $request ['DadosStatusPedido'] ['NumeroNota'];
			
		} else {
			
			foreach ( $request ["DadosStatusPedido"] as $i => $d ) {
				
				$array_status [$i] ['ProtocoloStatusPedido'] = $d ['ProtocoloStatusPedido'];
				$array_status [$i] ['NumeroPedido'] = $d ['NumeroPedido'];
				$array_status [$i] ['CodigoStatus'] = $d ['CodigoStatus'];
				$array_status [$i] ['StatusPedido'] = $d ['StatusPedido'];
				$array_status [$i] ['CodigoMotivoCancelamento'] = $d ['CodigoMotivoCancelamento'];
				$array_status [$i] ['MotivoCancelamento'] = $d ['MotivoCancelamento'];
                $array_status [$i] ['NumeroObjeto'] = $d ['NumeroObjeto'];
                $array_status [$i] ['NumeroNota'] = $d ['NumeroNota'];

			}
		}
		
		$qtdStatus = count($array_status);
		
		echo PHP_EOL;
		echo "Status encontrados para integracao: " . $qtdStatus . PHP_EOL;
		echo PHP_EOL;

        echo "Conectando ao WebService Vtex... " . PHP_EOL;
        $this->_vtex = new Model_Wpr_Vtex_Status( $dadosCliente['VTEX_WSDL'], $dadosCliente['VTEX_USUARIO'], $dadosCliente['VTEX_SENHA'] );
        echo "Conectado!" . PHP_EOL;
        echo PHP_EOL;
		
		// Percorrer array de preços
		foreach ( $array_status as $indice => $dados_status ) {
			$erros_status = 0;			
			
			if ( $dados_status ['NumeroPedido'] == NULL ) {
				echo "Status do pedido {$dados_status['NumeroPedido']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Status do Pedido {$dados_status['NumeroPedido']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_status ++;
			}
			if ( $erros_status == 0 ) {
				
				try {
				
					//Tratar status a ser enviado
					switch ($dados_status['CodigoStatus']){
						
						case '3': //Faturado
							$dados_status['StatusEnvio'] = 'ETR';
							echo "Faturando pedido " . $dados_status['NumeroPedido'] . PHP_EOL;
							$this->_vtex->_atualizaStatusPedido($dados_status);
							echo "Pedido faturado. " . PHP_EOL;							
						break;
// 						case '5': //Entregue
// 							$dados_status['StatusEnvio'] = 'ETR';
// 							echo "Faturando pedido " . $dados_status['NumeroPedido'] . PHP_EOL;
// 							$this->_vtex->_atualizaStatusPedido($dados_status);
// 							echo "Pedido faturado. " . PHP_EOL;
// 							break;
						case '6': // Cancelado
							$dados_status['StatusEnvio'] = 'CAN';
							$dados_status['ComentarioStatus'] = utf8_decode( $dados_status['MotivoCancelamento'] );
							echo "Cancelando pedido " . $dados_status['NumeroPedido'] . PHP_EOL;
							$this->_vtex->_cancelaPedido($dados_status);
							echo "Pedido Cancelado. " . PHP_EOL;
                        case '7': // Cadastrado
                            $dados_status['StatusEnvio'] = 'CAN';
                            $dados_status['ComentarioStatus'] = utf8_decode( $dados_status['MotivoCancelamento'] );
                            if(!empty($dados_status['NumeroObjeto'])){
                                echo "Atualizando Tracking " . $dados_status['NumeroPedido'] . PHP_EOL;
                                $this->_vtex->_atualizaTracking($dados_status);
                                echo "Tracking Atualizado. " . PHP_EOL;
                            }
						break;
						
					}
										
					$this->_kpl->confirmarRecebimentoStatusPedido( $dados_status ['ProtocoloStatusPedido'] );
					echo "Protocolo Status: {$dados_status ['ProtocoloStatusPedido']} enviado com sucesso" . PHP_EOL;
					echo PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao atualizar status {$dados_status['NumeroPedido']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao atualizar status Pedido {$dados_status['NumeroPedido']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
			
			unset($dados_status['StatusEnvio']);
		}	
		
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}	

}
