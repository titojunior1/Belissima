<?php
/**
 *
 *
 *
 *
 *
 *
 * Cron para processar integração com sistema ERP Magento - Magento via webservice
 * @author Tito Junior <titojunior1@gmail.com>
 *        
 */
class Model_Wpr_Cron_MagentoCron {
	
	/**
	 *
	 *
	 *
	 *
	 *
	 *
	 * Objeto Magento (instância do webservice magento)
	 * @var Model_Wpr_Magento_MagentoWebService
	 */
	private $_magento;
	
	/**
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 * Array com clientes encontrados
	 * @var array
	 */
	private $_clientes;

	/**
	 * Construtor
	 * @param
	 *
	 *
	 *
	 *
	 *
	 */
	public function __construct() {
		echo "- Iniciando Cron para processar integracao com sistema ERP Magento via webservice" . PHP_EOL;
		
		// Carrega clientes do banco
		$this->_clientes = $this->CarregaClientes ();
	}

	/**
	 * Carrega clientes, utilizando clientes_erp
	 * @return array com Clientes
	 */
	public function CarregaClientes() {
		$clientes = new Model_Wpr_Clientes_ClientesIntegracao ();
		
		return $clientes->carregaClientes ();
	}

	/**
	 * Cadastrar CLientes disponíveis no ambiente Magento
	 */
	public function CadastraClientesMagento() {
		ini_set ( 'memory_limit', '512M' );
		
		// Solicita Pedidos Saida Disponíveis
		if (empty ( $this->_magento )) {
			$this->_magento = new Model_Wpr_Magento_MagentoWebService ();
		}
		
		echo "- importando clientes disponiveis do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
		
		$date = date ( 'Y-m-d H:i:s' );
		$timestamp = date ( 'Y-m-d H:i:s', strtotime ( "-5 hours", strtotime ( $date ) ) );
		$timestamp = '2016-08-19 19:20:00';
		
		//Filtro para consulta em ambiente Magento com base na data atual regredindo 5 horas
		$complexFilter = array ( 'complex_filter' => array ( array ( 'key' => 'created_at', 'value' => array ( 'key' => 'gt', 'value' => $timestamp ) ) ) );
		
		try {
			
			$clientesDisponiveis = $this->_magento->buscaClientesDisponiveis ( $complexFilter );
			if (! is_array ( $clientesDisponiveis )) {
				throw new Exception ( 'Erro ao buscar clientes' );
			}
			if (count ( $clientesDisponiveis ) == 0) {
				echo "Nao existem clientes disponiveis para integracao " . PHP_EOL;
			} else {
				$magento = new Model_Wpr_Magento_Clientes ();
				$retorno = $magento->ProcessaClientesWebservice ( $clientesDisponiveis );
				if (is_array ( $retorno )) {
					// gravar logs de erro
					$this->_log->gravaLogErros ( $retorno );
				}
			}
			
			echo "- importacao de pedidos do cliente Verden realizada com sucesso " . PHP_EOL;
		} catch ( Exception $e ) {
			echo "- erros ao importar os pedidos de saída do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_magento );
		
		echo "- Finalizando cron para cadastrar pedidos de saída da Kpl do cliente Verden " . PHP_EOL;
	}

	/**
	 * Cadastrar Pedidos Saida do Magento
	 */
	public function CadastraPedidosSaidaMagento() {
		ini_set ( 'memory_limit', '512M' );
		
		$timestamp = '2016-07-01 19:20:00';
		
		//Filtro para consulta de pedidos baseando-se por Status e Data a partir de novembro/2016
		$complexFilter = array ( 'complex_filter' => array ( array ( 'key' => 'created_at', 'value' => array ( 'key' => 'gt', 'value' => $timestamp ) ), array ( 'key' => 'status', 'value' => array ( 'key' => 'eq', 'value' => 'processing' ) ) ) );
		
		//Filtro para busca específica de pedido
		//$complexFilter = array ( 'complex_filter' => array ( array ( 'key' => 'increment_id', 'value' => array ( 'key' => 'eq', 'value' => 100000060 ) ) ) );
		

		foreach ( $this->_clientes as $cliente => $dadosCliente ) {
			
			try {
				
				$this->_magento = new Model_Wpr_Magento_MagentoWebService ( $dadosCliente ['MAGENTO_WSDL'], $dadosCliente ['MAGENTO_USUARIO'], $dadosCliente ['MAGENTO_SENHA'] );
				
				echo "- importando pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
				
				$pedidos_disponiveis = $this->_magento->buscaPedidosDisponiveis ( $complexFilter );
				
				switch ($cliente) {
					
					case 'VetorScan' :
						
						if (! is_array ( $pedidos_disponiveis )) {
							throw new Exception ( 'Erro ao buscar notas de saida' );
						}
						if (count ( $pedidos_disponiveis ) == 0) {
							echo "Nao existem pedidos de saida disponiveis para integracao " . PHP_EOL;
						} else {
							$magento = new Model_Wpr_Magento_PedidosVetorScan ( $dadosCliente ['MAGENTO_WSDL'], $dadosCliente ['MAGENTO_USUARIO'], $dadosCliente ['MAGENTO_SENHA'] );
							$retorno = $magento->ProcessaPedidosWebservice ( $pedidos_disponiveis, $dadosCliente );
							if (is_array ( $retorno )) {
								// gravar logs de erro						
								$this->_log->gravaLogErros ( $retorno );
							}
						}
						
						break;
					
					case 'CreaTech' :
						
						if (! is_array ( $pedidos_disponiveis )) {
							throw new Exception ( 'Erro ao buscar notas de saida' );
						}
						if (count ( $pedidos_disponiveis ) == 0) {
							echo "Nao existem pedidos de saida disponiveis para integracao " . PHP_EOL;
						} else {
							$magento = new Model_Wpr_Magento_PedidosCreaTech ( $dadosCliente ['MAGENTO_WSDL'], $dadosCliente ['MAGENTO_USUARIO'], $dadosCliente ['MAGENTO_SENHA'] );
							$retorno = $magento->ProcessaPedidosWebservice ( $pedidos_disponiveis, $dadosCliente );
							if (is_array ( $retorno )) {
								// gravar logs de erro
								$this->_log->gravaLogErros ( $retorno );
							}
						}
						
						break;
					
					case 'Hakken' :
						
						if (! is_array ( $pedidos_disponiveis )) {
							throw new Exception ( 'Erro ao buscar notas de saida' );
						}
						if (count ( $pedidos_disponiveis ) == 0) {
							echo "Nao existem pedidos de saida disponiveis para integracao " . PHP_EOL;
						} else {
							$magento = new Model_Wpr_Magento_PedidosHakken ( $dadosCliente ['MAGENTO_WSDL'], $dadosCliente ['MAGENTO_USUARIO'], $dadosCliente ['MAGENTO_SENHA'] );
							$retorno = $magento->ProcessaPedidosWebservice ( $pedidos_disponiveis, $dadosCliente );
							if (is_array ( $retorno )) {
								// gravar logs de erro
								$this->_log->gravaLogErros ( $retorno );
							}
						}
						
						break;
					
					case 'Verden' :
						
						if (! is_array ( $pedidos_disponiveis )) {
							throw new Exception ( 'Erro ao buscar notas de saida' );
						}
						if (count ( $pedidos_disponiveis ) == 0) {
							echo "Nao existem pedidos de saida disponiveis para integracao " . PHP_EOL;
						} else {
							$magento = new Model_Wpr_Magento_PedidosVerden ( $dadosCliente ['MAGENTO_WSDL'], $dadosCliente ['MAGENTO_USUARIO'], $dadosCliente ['MAGENTO_SENHA'] );
							$retorno = $magento->ProcessaPedidosWebservice ( $pedidos_disponiveis, $dadosCliente );
							if (is_array ( $retorno )) {
								// gravar logs de erro
								$this->_log->gravaLogErros ( $retorno );
							}
						}
						
						break;
				}
				
				echo "- importacao de pedidos do cliente {$cliente} realizada com sucesso " . PHP_EOL;
			} catch ( Exception $e ) {
				echo "- erros ao importar os pedidos de saída do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
			}
			
			unset ( $this->_magento );
			unset ( $magento );
		}
		echo "- Finalizando cron para cadastrar pedidos de saída da Kpl do cliente {$cliente} " . PHP_EOL;
	}
}