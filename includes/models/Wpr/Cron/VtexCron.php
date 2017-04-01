<?php
/**
 * 
 * Cron para processar integração com sistema ERP VTEX - Ábacos via webservice   
 * @author Tito Junior <titojunior1@gmail.com>
 * 
 */
class Model_Wpr_Cron_VtexCron {
	
	/**
	 * 
	 * Objeto Kpl (instância do webservice kpl)
	 * @var Model_Verden_Kpl_KplWebService
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
	 * Importa os produtos disponíveis.
	 * @throws Exception
	 */
	public function CadastraProdutosKpl () {

		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService();
		}
		echo "- importando produtos do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

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
				
				echo "- importacao de produtos do cliente Verden realizada com sucesso" . PHP_EOL;
		
		} catch ( Exception $e ) {
			echo "- erros ao importar os produtos do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );

		echo "- Finalizando cron para cadastrar produtos do Kpl " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
	}
	
	
}