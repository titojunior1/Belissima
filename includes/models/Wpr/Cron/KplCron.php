<?php
/**
 *
 *
 *
 *
 *
 * Cron para processar integração com sistema ERP KPL - Ábacos via webservice
 * @author Tito Junior <titojunior1@gmail.com>
 *        
 */
class Model_Wpr_Cron_KplCron {
	
	/**
	 *
	 *
	 *
	 *
	 *
	 * Objeto Kpl (instância do webservice kpl)
	 * @var Model_Wpr_Kpl_KplWebService
	 */
	private $_kpl;
	
	/**
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
	 */
	public function __construct() {
		echo "- Iniciando Cron para processar integracao com sistema ERP KPL via webservice" . PHP_EOL;
		
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
	 * Atualiza estoque do Kpl
	 */
	public function AtualizaEstoqueKpl() {
		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );
		
		foreach ( $this->_clientes as $cliente => $dadosCliente ) {
			
			echo "- importando estoques disponiveis do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			
			try {
				
				echo PHP_EOL;
				echo "Consultando estoques disponiveis para integracao " . PHP_EOL;
				
				$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
				
				$estoques = $this->_kpl->EstoquesDisponiveis ( $dadosCliente ['KPL_KEY'] );
				
				switch ($cliente) {
					
					case 'Belissima' :
						
						if (! is_array ( $estoques ['EstoquesDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar Estoque - ' . $estoques );
						}
						if ($estoques ['EstoquesDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem estoques disponiveis para integracao" . PHP_EOL;
						} else {
							
							$kpl_estoques = new Model_Wpr_Kpl_EstoqueKpl ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
							$retorno = $kpl_estoques->ProcessaEstoqueWebservice ( $estoques ['EstoquesDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// ERRO					
							}
						}
						break;
					
					case 'VetorScan' :
						
						if (! is_array ( $estoques ['EstoquesDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar Estoque - ' . $estoques );
						}
						if ($estoques ['EstoquesDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem estoques disponiveis para integracao" . PHP_EOL;
						} else {
							
							$kpl_estoques = new Model_Wpr_Kpl_EstoqueKplVetorScan ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
							$retorno = $kpl_estoques->ProcessaEstoqueWebservice ( $estoques ['EstoquesDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// ERRO					
							}
						}
						break;
				}
				
				echo "- importacao de estoque do cliente {$cliente} realizada com sucesso" . PHP_EOL;
			} catch ( Exception $e ) {
				echo "- erros ao importar estoque do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
			}
			
			unset ( $this->_kpl );
			unset ( $kpl_estoques );
		}
		echo "- Finalizando cron para atualizar estoque do Kpl" . PHP_EOL;
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 * Importa os preços disponíveis.
	 * @throws Exception
	 */
	public function AtualizaPrecosKpl() {
		ini_set ( 'memory_limit', '512M' );
		
		foreach ( $this->_clientes as $cliente => $dadosCliente ) {
			
			echo "- importando precos disponiveis do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			
			try {
				
				$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
				
				$precos = $this->_kpl->PrecosDisponiveis ( $dadosCliente ['KPL_KEY'] );
				
				switch ($cliente) {
					
					case 'Belissima' :
						
						if (! is_array ( $precos ['PrecosDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar Preços - ' . $precos );
						}
						if ($precos ['PrecosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem precos disponiveis para integracao" . PHP_EOL;
						} else {
							
							$kpl_preços = new Model_Wpr_Kpl_Precos ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'], $dadosCliente ['VTEX_API_URL'], $dadosCliente ['VTEX_API_KEY'], $dadosCliente ['VTEX_API_TOKEN'] );
							$retorno = $kpl_preços->ProcessaPrecosWebservice ( $precos ['PrecosDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// ERRO
							}
						}
						break;
					
					case 'VetorScan' :
						
						if (! is_array ( $precos ['PrecosDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar Preços - ' . $precos );
						}
						if ($precos ['PrecosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem precos disponiveis para integracao" . PHP_EOL;
						} else {
							
							$kpl_preços = new Model_Wpr_Kpl_PrecosVetorScan( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
							$retorno = $kpl_preços->ProcessaPrecosWebservice ( $precos ['PrecosDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// ERRO
							}
						}
						break;
				}
				
				echo "- importacao de precos do cliente Belissima realizada com sucesso" . PHP_EOL;
			} catch ( Exception $e ) {
				echo "- erros ao importar os precos do cliente Belissima: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
			unset ( $kpl_preços );
		}
		echo "- Finalizando cron para atualizar precos da Kpl" . PHP_EOL;
	}

	/**
	 * Método para atualizar status de pedido da KPL para o Magento
	 */
	public function atualizaStatusPedido() {
		ini_set ( 'memory_limit', '512M' );
		
		foreach ( $this->_clientes as $cliente => $dadosCliente ) {
			
			echo "- Atualizando status de pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			
			try {
				
				$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
				
				$status_disponiveis = $this->_kpl->statusPedidosDisponiveis ( $dadosCliente ['KPL_KEY'] );
				
				switch ($cliente) {
					case 'Belissima' :
						if (! is_array ( $status_disponiveis ['StatusPedidoDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar status dos pedidos' );
						}
						if ($status_disponiveis ['StatusPedidoDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem status disponiveis para integracao " . PHP_EOL;
						} else {
							$kpl = new Model_Wpr_Kpl_StatusPedido ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
							$retorno = $kpl->ProcessaStatusWebservice ( $status_disponiveis ['StatusPedidoDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// gravar logs de erro
								$this->_log->gravaLogErros ( $retorno );
							}
						}
						break;
					
					case 'VetorScan' :
						if (! is_array ( $status_disponiveis ['StatusPedidoDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar status dos pedidos' );
						}
						if ($status_disponiveis ['StatusPedidoDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem status disponiveis para integracao " . PHP_EOL;
						} else {
							$kpl = new Model_Wpr_Kpl_StatusPedido ();
							$retorno = $kpl->ProcessaStatusWebservice ( $status_disponiveis ['StatusPedidoDisponiveisResult'] ['Rows'] );
							if (is_array ( $retorno )) {
								// gravar logs de erro
								$this->_log->gravaLogErros ( $retorno );
							}
						}
						break;
				}
				
				echo "- importacao de status de pedidos do cliente {$cliente} realizada com sucesso " . PHP_EOL;
			} catch ( Exception $e ) {
				echo "- erros ao importar os status de pedidos de saída do cliente Belissima: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
			unset ( $kpl );
		}
		echo "- Finalizando cron para atualizar status de pedidos de saída da Kpl do cliente Belissima " . PHP_EOL;
	}

	/**
	 * Cadastrar Pedidos Saida do Kpl
	 */
	public function CadastraPedidosSaidaKpl() {
		ini_set ( 'memory_limit', '512M' );
		
		// Solicita Pedidos Saida Disponíveis
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService ();
		}
		
		echo "- importando pedidos de saída do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
		try {
			$chaveIdentificacao = KPL_KEY;
			$pedidos_disponiveis = $this->_kpl->PedidosDisponiveis ( $chaveIdentificacao );
			if (! is_array ( $pedidos_disponiveis ['PedidosDisponiveisResult'] )) {
				throw new Exception ( 'Erro ao buscar notas de saída' );
			}
			if ($pedidos_disponiveis ['PedidosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
				echo "Nao existem pedidos de saida disponiveis para integracao " . PHP_EOL;
			} else {
				$kpl = new Model_Wpr_Kpl_Pedido ();
				$retorno = $kpl->ProcessaArquivoSaidaWebservice ( $pedidos_disponiveis ['PedidosDisponiveisResult'] );
				if (is_array ( $retorno )) {
					// gravar logs de erro						
					$this->_log->gravaLogErros ( $retorno );
				}
			}
			
			echo "- importacao de pedidos do cliente Verden realizada com sucesso " . PHP_EOL;
		} catch ( Exception $e ) {
			echo "- erros ao importar os pedidos de saída do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		
		echo "- Finalizando cron para cadastrar pedidos de saída da Kpl do cliente Verden " . PHP_EOL;
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 * Importa os produtos disponíveis.
	 * @throws Exception
	 */
	public function CadastraProdutosKpl() {
		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );
		
		foreach ( $this->_clientes as $cliente => $dadosCliente ) {
			
			echo "- importando produtos do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			
			try {
				
				echo PHP_EOL;
				echo "Consultando produtos disponiveis para integracao " . PHP_EOL;
				
				$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
				
				$produtos = $this->_kpl->ProdutosDisponiveis ( $dadosCliente ['KPL_KEY'] );
				
				switch ($cliente) {
					
					case 'Belissima' :
						
						if (! is_array ( $produtos ['ProdutosDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar Produtos - ' . $produtos );
						}
						if ($produtos ['ProdutosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem produtos disponiveis para integracao" . PHP_EOL;
						} else {
							$kpl_produtos = new Model_Wpr_Kpl_Produtos ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
							$retorno = $kpl_produtos->ProcessaProdutosWebservice ( $produtos ['ProdutosDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// ERRO					
							}
						}
						break;
					
					case 'VetorScan' :
						
						if (! is_array ( $produtos ['ProdutosDisponiveisResult'] )) {
							throw new Exception ( 'Erro ao buscar Produtos - ' . $produtos );
						}
						if ($produtos ['ProdutosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
							echo "Nao existem produtos disponiveis para integracao" . PHP_EOL;
						} else {
							$kpl_produtos = new Model_Wpr_Kpl_ProdutosVetorScan ( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
							$retorno = $kpl_produtos->ProcessaProdutosWebservice ( $produtos ['ProdutosDisponiveisResult'] ['Rows'], $dadosCliente );
							if (is_array ( $retorno )) {
								// ERRO
							}
						}
						
						break;
				}
				
				echo "- importacao de produtos do cliente {$cliente} realizada com sucesso" . PHP_EOL;
			} catch ( Exception $e ) {
				echo "- erros ao importar os produtos do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
			}
			
			unset ( $this->_kpl );
			unset ( $kpl_produtos );
		}
		
		echo "- Finalizando cron para cadastrar produtos do Kpl " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
	}
}