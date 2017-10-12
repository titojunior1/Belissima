<?php
/**
 *
 * Classe de gerenciamento de Preços com a VTEX 
 *
 */
class Model_Wpr_Vtex_Preco {

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
	public function __construct ($ws, $login, $pass ) {
		$this->_initVtex ( $ws, $login, $pass );
	}
	
	/**
	 * busca um determinado RefId
	 * @param string $refId
	 */
	public function buscaCadastroProduto( $refId ) {
	
		if ( empty( $refId ) ) {
			throw new Exception ( 'Dados do produto inválidos' );
		}
	
		try {
	
			$result = $this->_client->StockKeepingUnitGetByRefId($refId);
			return $result;
	
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}
	
	/**
	 * busca um determinado RefId
	 * @param string $refId
	 */
	public function buscaCadastroProdutoPai( $refId ) {
	
		if ( empty( $refId ) ) {
			throw new Exception ( 'Dados do produto inválidos' );
		}
	
		try {
	
			$result = $this->_client->ProductGetByRefId($refId);
			return $result;
	
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}
	
	
	/**
	 * Inicializa webservice VTEX.
	 */
	protected function _initVtex ( $ws, $login, $pass  ) {

		// Gera objeto de conexão WebService
		if ( isset ( $this->_vtex ) ) {
			unset ( $this->_vtex );
		}
		if ( isset ( $this->_client ) ) {
			unset ( $this->_client );
		}
		$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex( $ws, $login, $pass );
		$this->_client = $this->_vtex->_client;
	}
}