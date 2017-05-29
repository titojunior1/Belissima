<?php
/**
 *
 * Classe de gerenciamento de Pre�os com a VTEX 
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
	 * @param string $ws Endere�o do Webservice.
	 * @param string $login Login de Conex�o do Webservice.
	 * @param string $pass Senha de Conex�o do Webservice.
	 */
	public function __construct ( ) {
		$this->_initVtex ();
	}
	
	/**
	 * busca um determinado RefId
	 * @param string $refId
	 */
	public function buscaCadastroProduto( $refId ) {
	
		if ( empty( $refId ) ) {
			throw new Exception ( 'Dados do produto inv�lidos' );
		}
	
		try {
	
			$result = $this->_client->StockKeepingUnitGetByRefId($refId);
			return $result;
	
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}
	
	
	/**
	 * Inicializa webservice VTEX.
	 */
	protected function _initVtex (  ) {

		// Gera objeto de conex�o WebService
		if ( isset ( $this->_vtex ) ) {
			unset ( $this->_vtex );
		}
		$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex();
		$this->_client = $this->_vtex->_client;
	}
}