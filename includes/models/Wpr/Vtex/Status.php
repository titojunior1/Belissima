<?php
/**
 *
 * Classe para processar as atualizações de status via webservice no ERP da VTEX
 *
 * @author Tito Junior
 *
 */
class Model_Wpr_Vtex_Status{
	
	/**
	 *
	 * Objeto Vtex
	 * @var Model_Wpr_Vtex_Vtex
	 */
	private $_vtex;
	
	/**
	 * Variavel  de Objeto da Classe StubVtex.
	 * @var Model_Wpr_Vtex_StubVtex
	 */
	public $_client;
	/**
	 * Contrutor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 */
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
	public function __construct ( $url, $key, $token ) {
		//$this->_initVtex ( $ws, $login, $pass );
        $this->_url = $url;
        $this->_key = $key;
        $this->_token = $token;
	}
	
	/**
	 * Inicializa webservice VTEX.
	 */
	protected function _initVtex ( $ws, $login, $pass ) {
	
		// Gera objeto de conexão WebService
		if ( isset ( $this->_vtex ) ) {
			unset ( $this->_vtex );
		}
		$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex( $ws, $login, $pass );
		$this->_client = $this->_vtex->_client;
	}
	
	/**
	 * Método que faz a atualização do status de um pedido
	 */
	public function _atualizaStatusPedido( $dados_pedido ){
	
		$idPedido = $dados_pedido['NumeroPedido'];
		$comentarioStatus = $dados_pedido['ComentarioStatus'];
		$statusPedido = $dados_pedido['StatusEnvio'];
	
		$result = $this->_client->OrderChangeStatus($idPedido, $statusPedido);
		return $result;
	
	}

    /**
     * Método que faz o cancelamento de um pedido
     */
    public function _cancelaPedido( $dados_pedido ){

        $idPedido = $dados_pedido['NumeroPedido'];
        $comentarioStatus = $dados_pedido['ComentarioStatus'];

        $url = sprintf($this->_url, "oms/pvt/orders/{$idPedido}/cancel");
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-VTEX-API-AppKey' => $this->_key,
            'X-VTEX-API-AppToken' => $this->_token
        );

        $request = Requests::post($url, $headers);

        if (! $request->success) {
            throw new RuntimeException('Falha na comunicação com o webservice. [' . $request->body . ']');
        }

    }

    /**
     * Método que faz muda o status de um pedido para faturado
     */
    public function _faturaPedido( $dados_pedido ){

        $idPedido = $dados_pedido['NumeroPedido'];
        $comentarioStatus = $dados_pedido['ComentarioStatus'];
        $trackingNumber = $dados_pedido['NumeroObjeto'];
        $nota = $dados_pedido['NumeroNota'];

        $url = sprintf($this->_url, "oms/pvt/orders/{$idPedido}/invoice");
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-VTEX-API-AppKey' => $this->_key,
            'X-VTEX-API-AppToken' => $this->_token
        );
        $data = array(
            'issuanceDate'=> date('Y-m-d' ),
            'invoiceNumber' => $nota,
            //'invoiceValue' => $nota,
            'trackingNumber' => $trackingNumber
        );

        $request = Requests::post($url, $headers, json_encode($data));

        if (! $request->success) {
            throw new RuntimeException('Falha na comunicação com o webservice. [' . $request->body . ']');
        }

    }
}