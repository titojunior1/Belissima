<?php
/**
 *
 * Classe de gerenciamento de Produtos com a VTEX 
 *
 */
class Model_Wpr_Vtex_Produto {

	/**
	 * Array para armazenar produtos pai consultados nas rotinas de importação de pais e filhos.
	 *
	 */
	private $_array_produtos_pai = array ();

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
	public function __construct ( $ws, $login, $pass ) {
		$this->_initVtex ( $ws, $login, $pass );
	}

	/**
	 * Função para Adicionar um Produto Pai
	 * @param string $id_vtex Id do Produto no VTEX
	 * @return retorna o dados do produto_pai
	 */
	public function enviaProdutoPai ( $dadosProduto ) {

		try {
			$result = $this->_client->ProductInsertUpdate($dadosProduto);
			return $result;
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa um determinado sku
	 * @param Array $sku_dados DTO de produto filho
	 */
	public function enviaProdutoFilho ( $sku_dados ) {

		if ( ! is_array ( $sku_dados ) ) {
			throw new Exception ( 'Dados do SKU inválidos' );
		}		

		try {

			$result = $this->_client->StockKeepingUnitInsertUpdate( $sku_dados );
			return $result;
	
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}

	}
	
	/**
	 * busca um determinado RefId
	 * @param string $refId
	 */
	public function buscaCadastroProdutoPai ( $refId ) {
	
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
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice. 
	 */
	protected function _initVtex ( $ws, $login, $pass ) {

		// Gera objeto de conexão WebService
		if ( isset ( $this->_vtex ) ) {
			unset ( $this->_vtex );
		}
		$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex( $ws, $login, $pass );
		$this->_client = $this->_vtex->_client;
	}
}