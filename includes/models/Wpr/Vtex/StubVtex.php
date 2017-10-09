<?php
/**
 * 
 * Classe para trabalhar com o Webservice da VTEX (Stub de Serviço) 
 *
 */
class Model_Wpr_Vtex_StubVtex {
	/**
	 * Endereço do WebService.
	 *
	 * @var string
	 */
	private $_ws;
	
	/**
	 * instancia do WebService
	 *
	 * @var SoapClient
	 */
	private $_webservice;
	
	/**
	 * Login de Acesso do WebService.
	 *
	 * @var string
	 */
	private $_login;
	/**
	 * Senha de Acesso do WebService.
	 *
	 * @var string
	 */
	private $_pass;
	/**
	 * Array de Mensagens de Erros.
	 *
	 * @var array
	 */
	private $_array_erros = array ();
	/**
	 * Habilita função de Degbug.
	 *
	 * @var boolean
	 */
	private $_debug = false;
	/**
	 * Array de Mensagens de Debug.
	 *
	 * @var array
	 */
	private $_debugMSG = array ();
	/**
	 * Contrutor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 * @param string $cli_id Id do Cliente.
	 * @param string $empwh_id Id do Armazém.
	 */
	public function __construct($ws, $login, $pass) {
		if (empty ( $ws )) {
			throw new InvalidArgumentException ( 'Endereço do Webservice inválido' );
		}
		if (empty ( $login )) {
			throw new InvalidArgumentException ( 'Login do Webservice inválido' );
		}
		if (empty ( $pass )) {
			throw new InvalidArgumentException ( 'Senha do Webservice inválida' );
		}
		
		try {
			
			$this->_ws = $ws;
			$this->_login = $login;
			$this->_pass = $pass;
			
			$options = array(
					'soap_version'=>1,
					'trace'=>true,
					'exceptions'=>true,
					'login' => $this->_login,
					'password' => $this->_pass,
					//'uri'=>'http://webservice-belissimabeta.vtexcommerce.com.br/service.svc?wsdl',
					//'binding'=> 'basicHttpBinding',
					//'style'=>SOAP_RPC,
					'use'=>SOAP_ENCODED,
					'encoding'=>'UTF-8',
					'cache_wsdl'=>WSDL_CACHE_NONE,
					'connection_timeout'=>60,
			);			
			
			$this->_webservice = new SoapClient($this->_ws, $options );
			
		} catch ( Exception $e ) {
			throw new Exception ( 'Erro ao conectar no WebService' );
		}
	
	}
	/**
	 * Adiciona mensagem de erro ao array
	 * @param String $mensagem Mensagem de Erro
	 */
	private function _Erro($mensagem) {
		$msg = "Data:" . date ( "d/m/Y H:i:s" ) . " <br>" . $mensagem;
		$this->_array_erros [] = $msg;
		throw new Exception ( $msg );
	}
	
	/**
	 * Chama uma action do WebService
	 * @param string $action Nome da Action do Webservice
	 * @param array $parans Array de parametros
	 * @return Objecto de retorno da Action 
	 */
	private function _wsCall($action, $parans) {
		try {
			$result = $this->_webservice->__soapCall( $action, $parans );
			
			if (! $result) {
				throw new ErrorException ( 'Erro na Execução do Webservice' );
			}
			return $result;
		
		} catch ( ErrorException $e ) {
			return $this->_webservice->getError ();
		}
	
	}
	/**
	 * Impressão de Debug para WebService
	 * @param ('requisiçao' - Imprime Debug da Request envia do Webservice, 'resposta' - Imprime Debug da Resposta recebida do Webservice,
	 * 'debug' - Imprime debug do Webservice, 'todos' = Imprime todos os itens  
	 */
	private function _wsDebug($acao) {
		if ($this->_debug) {
			$this->_debug = '<h2>Request</h2>';
			$this->_debug = '<pre>' . htmlspecialchars ( $this->client->request, ENT_QUOTES ) . '</pre>';
			$this->_debug = '<h2>Response</h2>';
			$this->_debug = '<pre>' . htmlspecialchars ( $this->client->response, ENT_QUOTES ) . '</pre>';
			$this->_debug = '<h2>Debug</h2>';
			$this->_debug = '<pre>' . htmlspecialchars ( $this->client->debug_str, ENT_QUOTES ) . '</pre>';
		}
	}
	
	/**
	 * Monta mensagem de erro em caso Exception.
	 * @param obejct $e Objeto do Exception 
	 */
	public function GetErrorReport($e) {
		$msg = "Erro: " . $e->getMessage () . "<br />";
		$msg .= "Arquivo: " . $e->getFile () . "<br />";
		$msg .= "Linha: " . $e->getLine () . "<br />";
		$msg .= "Trace: " . $e->getTraceAsString () . "<br />";
		$this->_Erro ( $msg );
	}
	
	/**
	 * Atualiza o status de um pedido
	 * @param int order_id Numero do Pedido
	 * @param string status Descrição do Status 
	 * @return retorna mensagem em caso de erro ou true se estiver tudo certo. 
	 */
	public function OrderChangeStatus($order_id, $status) {
		if (! ctype_digit ( $order_id )) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		try {
			return $this->_wsCall ( 'OrderChangeStatus', array ( array ('idOrder' => $order_id, 'statusOrder' => $status ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	/**
	 * Atualiza o status de um pedido
	 * @param int order_id Numero do Pedido
	 * @param string status Descrição do Status 
	 * @return retorna mensagem em caso de erro ou objeto do Webservice se estiver tudo certo. 
	 */
	public function OrderChangeTrackingNumber($order_id, $TrackNumber) {
		if (! ctype_digit ( $order_id )) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		try {
			return $result = $this->_wsCall ( 'OrderChangeTrackingNumber', array ('idOrder' => $order_id, 'trackingNumber' => $TrackNumber ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	/**
	 * Retorna todos os pedidos que estejam em um determinado status
	 * @param string $statusOrder Descrição do Status
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function OrderGetByStatus($statusOrder) {
		try {
			return $this->_wsCall ( 'OrderGetByStatus', array ('statusOrder' => $statusOrder ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	/**
	 * Retorna uma determinada quantidade de pedidos que estejam com um determinado status 
	 * @param string $statusOrder Descrição do Status
	 * @param int 	 $quantidade Quantidade de Pedidos
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function OrderGetByStatusByQuantity($statusOrder, $quantidade) {
		try {
			return $this->_wsCall ( 'OrderGetByStatusByQuantity', array ( array ('statusOrder' => $statusOrder, 'quantity' => $quantidade ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna um determinado pedido baseado no ID 
	 * @param string $statusOrder Descrição do Status
	 * @param int 	 $quantidade Quantidade de Pedidos
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function OrderGet($id_pedido) {
		try {
			return $this->_wsCall ( 'OrderGet', array ( array ('orderId' => $id_pedido ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Imprime o array de erros.
	 */
	public function PrintErros() {
		foreach ( $this->_array_erros as $key => $value ) {
			print_r ( $value );
		}
	}
	/**
	 * Retorna informações de um determinado Produto Pai 
	 * @param int $idProduct Id do produto
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function ProductGet($idProduct) {
		if (! ctype_digit ( $idProduct )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		try {
			return $this->_wsCall ( 'ProductGet', array ('idProduct' => $idProduct ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	/**
	 * Retorna informações de um determinado Produto Pai 
	 * @param int $idProduct Id do produto
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function ProductGetByRefId($refId) {
		if ( empty ( $refId )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		try {
			return $this->_wsCall ( 'ProductGetByRefId', array ( array ('refId' => $refId ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Insere/Atualiza um Produto Pai
	 * @param array $dadosProduto dados do produto
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function ProductInsertUpdate($dadosProduto) {
		
		try {
			return $this->_wsCall ( 'ProductInsertUpdate', array ( array ('productVO' => $dadosProduto ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
		
	}
	
	/**
	 * Insere/Atualizao um Produto Filho
	 * @param array $dadosProduto dados do produto
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function StockKeepingUnitInsertUpdate($dadosProduto) {
	
		try {
			return $this->_wsCall ( 'StockKeepingUnitInsertUpdate', array ( array ('stockKeepingUnitVO' => $dadosProduto ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	
	}
	
	/**
	 * Retorna informações de um determinado skus 
	 * @param int $idSku Id do Sku
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function StockKeepingUnitGet($idSku) {
		if (! is_numeric( $idSku )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		try {
			return $this->_wsCall ( 'StockKeepingUnitGet', array ( array ('id' => $idSku ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna informações de um determinado skus
	 * @param int $refId Id do Sku
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function StockKeepingUnitGetByRefId($refId) {
		if ( empty ( $refId )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		try {
			return $this->_wsCall ( 'StockKeepingUnitGetByRefId', array ( array ('refId' => $refId ) ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os skus atualizados ou inseridos a partir de uma determinada data
	 * @param date dateUpdated Data para pesquisa
	 */
	public function StockKeepingUnitGetAllFromUpdatedDate($dateUpdated) {
		try {
			return $this->_wsCall ( 'StockKeepingUnitGetAllFromUpdatedDate', array ('dateUpdated' => $dateUpdated ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os skus atualizados ou inseridos a partir de uma determinada data
	 * @param date dateUpdated Data para pesquisa
	 */
	public function StockKeepingUnitGetAllFromUpdatedDateAndId($dateUpdated, $startingStockKeepingUnitId, $topRows) {
		try {
			return $this->_wsCall ( 'StockKeepingUnitGetAllFromUpdatedDateAndId', array ('dateUpdated' => $dateUpdated, 'startingStockKeepingUnitId' => $startingStockKeepingUnitId, 'topRows' => $topRows ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	/**
	 * Retorna informações de um determinado Produto Filho através do Pai
	 * @param int $manufacturer Id do produto
	 * @return retorna mensagem em caso de erro ou array de dados se estiver certo
	 */
	public function StockKeepingUnitGetByManufacturerCode($manufacturer) {
		if (! ctype_digit ( $manufacturer )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		try {
			return $this->_wsCall ( 'StockKeepingUnitGetByManufacturerCode', array ('manufacturer' => $manufacturer ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Atualizar a quantidade de skus no estoque.
	 * @param string idestoque Id do Estoque
	 * @param string idsku     Ido do Sku
	 * @param int    quantidade Quantidade a ser atualizada 
	 * @param date dateofav Data da atualização
	 * @return retorna mensagem em caso de erro ou true se estiver tudo certo. 
	 */
	public function WareHouseIStockableUpdate($idestoque, $idsku, $quantidade, $dateofav) {
		if (! ctype_digit ( $idestoque )) {
			throw new InvalidArgumentException ( 'ID do Estoque inválido' );
		}
		
		if (! ctype_digit ( $idsku )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		
		try {
			$call = $this->_wsCall ( 'WareHouseIStockableUpdate', array ('IdEstoque' => $idestoque, 'IdSku' => $idsku, 'Quantidade' => $quantidade, 'dateOfAvailability' => $dateofav ) );
			return $call;
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Atualizar a quantidade de skus no estoque na nova versão.
	 * @param string idestoque Id do Estoque
	 * @param string idsku     Ido do Sku
	 * @param int    quantidade Quantidade a ser atualizada 
	 * @param date dateofav Data da atualização
	 * @return retorna mensagem em caso de erro ou true se estiver tudo certo. 
	 */
	public function WareHouseIStockableUpdateV3 ( $wareHouseId, $itemId, $availableQuantity, $dateofav ) {

		if ( empty ( $wareHouseId ) ) {
			throw new InvalidArgumentException ( 'ID do Estoque inválido' );
		}
		
		if ( ! ctype_digit ( $itemId ) ) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		
		try {
			$call = $this->_wsCall ( 'WareHouseIStockableUpdateV3', array ( 'wareHouseId' => $wareHouseId, 'itemId' => $itemId, 'availableQuantity' => $availableQuantity, 'dateOfAvailability' => $dateofav ) );
			return $call;
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Verificar a quantidade de itens no estoque de um determinado produto.
	 * @param int $idestoque
	 * @param int $idsku
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function WareHouseIStockableGetByStockKeepingUnit($idestoque, $idsku) {
		if (empty ( $idestoque )) {
			throw new InvalidArgumentException ( 'ID do Estoque inválido' );
		}
		
		if (! ctype_digit ( $idsku )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
		
		try {
			$call = $this->_wsCall ( 'WareHouseIStockableGetByStockKeepingUnit', array ('WareHouseId' => $idestoque, 'SkuId' => $idsku) );
			return $call;
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	
	}
	
	/**
	 * Verificar a quantidade de itens no estoque de um determinado produto.
	 * @param int $idestoque
	 * @param int $idsku
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function WareHouseIStockableGetByStockKeepingUnitV3($idestoque, $idsku) {
		if (empty ( $idestoque )) {
			throw new InvalidArgumentException ( 'ID do Estoque inválido' );
		}
	
		if (! ctype_digit ( $idsku )) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}
	
		try {
			$call = $this->_wsCall ( 'WareHouseIStockableGetByStockKeepingUnitV3', array ('WareHouseId' => $idestoque, 'SkuId' => $idsku) );
			return $call;
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	
	}

}
?>
