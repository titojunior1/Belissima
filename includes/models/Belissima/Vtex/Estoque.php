<?php
/**
 * 
 * Classe de gerenciamento de atualização de estoque com a VTEX
 *
 */
class Model_Belissima_Vtex_Estoque {
	
	/**
	 * 
	 * Objeto Vtex
	 * @var Model_Wms_Vtex_Vtex
	 */
	public $_vtex;
	
	/**
	 * Variavel  de Objeto da Classe StubVtex.
	 *
	 * @var Model_Wms_Vtex_StubVtex
	 */
	public $_client;
	
	private $_url;
	
	private $_token;
	
	private $_key;
	
	/**
	 * Contrutor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 */
	public function __construct() {
		if (empty ( $this->_vtex )) {
			// Gera objeto de conexão WebService
			$this->_vtex = Model_Belissima_Vtex_Vtex::getVtex();
			$this->_client = $this->_vtex->_client;
			$this->_url = VTEX_API_URL;
			$this->_key = VTEX_API_KEY;
			$this->_token = VTEX_API_TOKEN;
		}
	}
	
	/**
	 * Atualiza a quantidade de skus no estoque.
	 * @param string $idestoque Id do Estoque
	 * @param string $idsku     Ido do Sku
	 * @param int   $quantidade Quantidade a ser atualizada 
	 * @param date $dateofav Data da atualização
	 * @return retorna mensagem em caso de erro
	 */
	public function atualizaArmazemSkuSoap($idestoque, $idsku, $quantidade, $dateofav) {
		try {
			$armazem_sku = $this->_client->WareHouseIStockableUpdateV3 ( $idestoque, $idsku, $quantidade, $dateofav );
			if (is_array ( $armazem_sku ) && ! empty ( $armazem_sku ['faultcode'] )) {
				// lança o erro como exception
				$erro_ws = $armazem_sku ['faultstring'] ['!'];
				throw new RuntimeException ( $erro_ws );
			}
			if (! is_array ( $armazem_sku )) {
				$armazem_sku = strtolower ( $armazem_sku );
				if (strstr ( $armazem_sku, 'error' )) {
					throw new RuntimeException ( $armazem_sku );
				}
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	
	/**
	 * Atualiza a quantidade de skus no estoque.
	 * @param string $idestoque Id do Estoque
	 * @param string $idsku     Ido do Sku
	 * @param int   $quantidade Quantidade a ser atualizada
	 * @return retorna mensagem em caso de erro
	 */
	public function atualizaArmazemSkuRest($idestoque, $idsku, $quantidade) {
	 	$data = array(
            array(
                'wareHouseId' => $idestoque,
                'itemId' => $idsku,
            	'quantity' => $quantidade	
            )
        );

        $url = sprintf($this->_url, 'logistics/pvt/inventory/warehouseitems/setbalance');
        $headers = array(
            'Content-Type' => 'application/json',
        	'Accept' => 'application/json',
        	'X-VTEX-API-AppKey' => $this->_key,
        	'X-VTEX-API-AppToken' => $this->_token	
        );

        $request = Requests::post($url, $headers, json_encode($data));

        if (! $request->success) {
            throw new RuntimeException('Falha na comunicação com o webservice. [' . $request->body . ']');
        }
	}
	
	/**
	 * 
	 * trata erros e transforma em string
	 * @param Array $array_erros
	 */
	private function _trataErro($array_erros) {
		$str_retorno = "";
		foreach ( $array_erros as $prod_id => $erro ) {
			$str_retorno .= "Produto {$prod_id}: {$erro}" . PHP_EOL;
		}
		return $str_retorno;
	}
	
	/**
	 * Retorna dados do estoque de um produto.
	 * @param int $cli_warehouse_id
	 * @param int $ean_proprio
	 * @throws RuntimeException
	 * @throws Exception
	 */
	private function _verificarProdutoEmEstoque($cli_warehouse_id, $id) {
		try {
				$produto_estoque = $this->_client->WareHouseIStockableGetByStockKeepingUnit ( $cli_warehouse_id, $id );			
			
				if (is_array ( $produto_estoque ) && ! empty ( $produto_estoque ['faultcode'] )) {
					// lança o erro como exception
					$erro_ws = $produto_estoque ['faultstring'] ['!'];
					throw new RuntimeException ( $erro_ws );
				}
				if (! is_array ( $produto_estoque )) {
					$produto_estoque = strtolower ( $produto_estoque );
					if (strstr ( $produto_estoque, 'error' )) {
						throw new RuntimeException ( $armazem_sku );
					}
				}
			
			return $produto_estoque;
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}

}
