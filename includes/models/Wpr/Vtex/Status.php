<?php
/**
 *
 * Classe para processar as atualiza��es de status via webservice no ERP da VTEX
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
	 * @param string $ws Endere�o do Webservice.
	 * @param string $login Login de Conex�o do Webservice.
	 * @param string $pass Senha de Conex�o do Webservice.
	 */
	public function __construct ( $ws, $login, $pass ) {
		$this->_initVtex ( $ws, $login, $pass );
	}
	
	/**
	 * Inicializa webservice VTEX.
	 */
	protected function _initVtex ( $ws, $login, $pass ) {
	
		// Gera objeto de conex�o WebService
		if ( isset ( $this->_vtex ) ) {
			unset ( $this->_vtex );
		}
		$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex( $ws, $login, $pass );
		$this->_client = $this->_vtex->_client;
	}
	
	/**
	 * M�todo que faz a atualiza��o do status de um pedido
	 */
	public function _atualizaStatusPedido( $dados_pedido ){
	
		$idPedido = $dados_pedido['NumeroPedido'];
		$comentarioStatus = $dados_pedido['ComentarioStatus'];
		$statusPedido = $dados_pedido['StatusEnvio'];
	
		$result = $this->_client->OrderChangeStatus($idPedido, $statusPedido);
		return $result;
	
	}
}