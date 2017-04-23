<?php
/**
 * 
 * Cron para processar integra��o com sistema ERP KPL - �bacos via webservice   
 * @author Tito Junior <titojunior1@gmail.com>
 * 
 */
class Model_Wpr_Cron_KplCron {
	
	/**
	 * 
	 * Objeto Kpl (inst�ncia do webservice kpl)
	 * @var Model_Wpr_Kpl_KplWebService
	 */
	private $_kpl;	

	/**
	 * Construtor
	 * @param 
	 */
	public function __construct () {

		echo "- Iniciando Cron para processar integracao com sistema ERP KPL via webservice" . PHP_EOL;
		
	}

	/**
	 * 
	 * Atualiza estoque do Kpl
	 * 
	 * 
	 */
	public function AtualizaEstoqueKpl () {

		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );		
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService();
		}
		echo "- importando estoques disponiveis do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

		try {
			$chaveIdentificacao = KPL_KEY;
			$estoques = $this->_kpl->EstoquesDisponiveis( $chaveIdentificacao );
			if ( ! is_array ( $estoques ['EstoquesDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Estoque - ' . $estoques );
			}
			if ( $estoques ['EstoquesDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem estoques disponiveis para integracao" . PHP_EOL;
			} else {
				
				$kpl_estoques = new Model_Wpr_Kpl_EstoqueKpl();
				$retorno = $kpl_estoques->ProcessaEstoqueWebservice ( $estoques ['EstoquesDisponiveisResult'] ['Rows'] );
				if(is_array($retorno)){
					// ERRO					
				}	
			}
				
			echo "- importacao de estoque do cliente Verden realizada com sucesso" . PHP_EOL;
		
		} catch ( Exception $e ) {
			echo "- erros ao importar estoque do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );

		echo "- Finalizando cron para atualizar estoque do Kpl" . PHP_EOL;
	}
	
	/**
	 *
	 * Importa os pre�os dispon�veis.
	 * @throws Exception
	 */
	public function AtualizaPrecosKpl () {
	
		ini_set ( 'memory_limit', '512M' );
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService();
		}
		echo "- importando precos do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
	
		try {
			$chaveIdentificacao = KPL_KEY;
			$precos = $this->_kpl->PrecosDisponiveis( $chaveIdentificacao );
			if ( ! is_array ( $precos ['PrecosDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Pre�os - ' . $precos );
			}
			if ( $precos ['PrecosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem precos disponiveis para integracao" . PHP_EOL;
			} else {
					
				$kpl_pre�os = new Model_Wpr_Kpl_Precos();
				$retorno = $kpl_pre�os->ProcessaPrecosWebservice( $precos ['PrecosDisponiveisResult'] ['Rows'] );
				if(is_array($retorno))
				{
					// ERRO
				}
			}
	
			echo "- importacao de precos do cliente Verden realizada com sucesso" . PHP_EOL;
	
		} catch ( Exception $e ) {
			echo "- erros ao importar os precos do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );
	
		echo "- Finalizando cron para atualizar precos da Kpl" . PHP_EOL;
	}

	/**
	 * 
	 * Cadastrar Pedidos Saida do Kpl
	 */
	public function CadastraPedidosSaidaKpl () {

		ini_set ( 'memory_limit', '512M' );
		
		// Solicita Pedidos Saida Dispon�veis
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wpr_Kpl_KplWebService();
			}
			
			echo "- importando pedidos de sa�da do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			try {
				$chaveIdentificacao = KPL_KEY;
				$pedidos_disponiveis = $this->_kpl->PedidosDisponiveis ( $chaveIdentificacao );
				if ( ! is_array ( $pedidos_disponiveis ['PedidosDisponiveisResult'] ) ) {
					throw new Exception ( 'Erro ao buscar notas de sa�da' );
				}
				if ( $pedidos_disponiveis ['PedidosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
					echo "Nao existem pedidos de saida disponiveis para integracao ".PHP_EOL;

				}else{
					$kpl = new Model_Wpr_Kpl_Pedido();
					$retorno = $kpl->ProcessaArquivoSaidaWebservice ( $pedidos_disponiveis ['PedidosDisponiveisResult'] );
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($retorno);					
						}	
					}

					echo "- importacao de pedidos do cliente Verden realizada com sucesso " . PHP_EOL;
					
			} catch ( Exception $e ) {
				echo "- erros ao importar os pedidos de sa�da do cliente Verden: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
		
		echo "- Finalizando cron para cadastrar pedidos de sa�da da Kpl do cliente Verden " . PHP_EOL;
		
	}

	/**
	 * 
	 * Importa os produtos dispon�veis.
	 * @throws Exception
	 */
	public function CadastraProdutosKpl () {

		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService();
		}
		echo "- importando produtos do cliente Belissima - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

		try {
			
			echo PHP_EOL;
			echo "Consultando produtos disponiveis para integracao " . PHP_EOL;
			$chaveIdentificacao = KPL_KEY;
			$produtos = $this->_kpl->ProdutosDisponiveis ( $chaveIdentificacao );
			if ( ! is_array ( $produtos ['ProdutosDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Produtos - ' . $produtos );
			}
			if ( $produtos ['ProdutosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem produtos disponiveis para integracao" . PHP_EOL;
			} else {
				
				$kpl_produtos = new Model_Wpr_Kpl_Produtos();
					$retorno = $kpl_produtos->ProcessaProdutosWebservice ( $produtos ['ProdutosDisponiveisResult'] ['Rows'] );
					if(is_array($retorno))
					{
						// ERRO					
					}	
				}
				
			echo "- importacao de produtos do cliente Belissima realizada com sucesso" . PHP_EOL;
		
		} catch ( Exception $e ) {
			echo "- erros ao importar os produtos do cliente Belissima: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );

		echo "- Finalizando cron para cadastrar produtos do Kpl " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
	}	
}