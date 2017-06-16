<?php

/**
 * 
 * Classe de gerenciamento de atualização de estoque com a Kpl
 * @author Tito Junior 
 *
 */

class Model_Wpr_Kpl_EstoqueKpl extends Model_Wpr_Kpl_KplWebService {	
	
	/**
	 * Variavel  de Objeto da Classe Kpl.
	 *
	 * @var Model_Wms_kpl
	 */
	public $_client;
	
	public $_vtex;
	
	/**
	 * Construtor.
	 *	  
	 */
	public function __construct( $ws, $key ) {		
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $ws, $key );
		}
	
	}
	
	/**
	 * 
	 * Processar estoque dos produtos via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaEstoqueWebservice ( $request, $dadosCliente ) {

		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_estoques = array ();
		
		if ( ! is_array ( $request ['DadosEstoque'] [0] ) ) {
					
			$array_estoques [0] ['ProtocoloEstoque'] = $request ['DadosEstoque'] ['ProtocoloEstoque'];
			$array_estoques [0] ['CodigoProduto'] = $request ['DadosEstoque'] ['CodigoProduto'];
			$array_estoques [0] ['CodigoProduto'] = $request ['DadosEstoque'] ['CodigoProduto'];
			$array_estoques [0] ['CodigoProdutoPai'] = $request ['DadosEstoque'] ['CodigoProdutoPai'];
			$array_estoques [0] ['CodigoProdutoAbacos'] = $request ['DadosEstoque'] ['CodigoProdutoAbacos'];
			$array_estoques [0] ['SaldoMinimo'] = $request ['DadosEstoque'] ['SaldoMinimo'];
			$array_estoques [0] ['SaldoDisponivel'] = $request ['DadosEstoque'] ['SaldoDisponivel'];
			$array_estoques [0] ['NomeAlmoxarifadoOrigem'] = $request ['DadosEstoque'] ['NomeAlmoxarifadoOrigem'];
			$array_estoques [0] ['IdentificadorProduto'] = $request ['DadosEstoque'] ['IdentificadorProduto'];
			$array_estoques [0] ['CodigoProdutoParceiro'] = $request ['DadosEstoque'] ['CodigoProdutoParceiro'];
			
		} else {
			
			foreach ( $request ["DadosEstoque"] as $i => $d ) {
				
				$array_estoques [$i] ['ProtocoloEstoque'] = $d ['ProtocoloEstoque'];
				$array_estoques [$i] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_estoques [$i] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_estoques [$i] ['CodigoProdutoPai'] = $d ['CodigoProdutoPai'];
				$array_estoques [$i] ['CodigoProdutoAbacos'] = $d ['CodigoProdutoAbacos'];
				$array_estoques [$i] ['SaldoMinimo'] = $d ['SaldoMinimo'];
				$array_estoques [$i] ['SaldoDisponivel'] = $d ['SaldoDisponivel'];
				$array_estoques [$i] ['NomeAlmoxarifadoOrigem'] = $d ['NomeAlmoxarifadoOrigem'];
				$array_estoques [$i] ['IdentificadorProduto'] = $d ['IdentificadorProduto'];
				$array_estoques [$i] ['CodigoProdutoParceiro'] = $d ['CodigoProdutoParceiro'];
			}
		}
		
		$qtdEstoques = count($array_estoques);
		
		echo PHP_EOL;
		echo "Estoques encontrados para integracao: " . $qtdEstoques . PHP_EOL;
		echo PHP_EOL;
		
		echo "Conectando ao WebService Vtex... " . PHP_EOL;
		$this->_vtex = new Model_Wpr_Vtex_Estoque( $dadosCliente['VTEX_WSDL'], $dadosCliente['VTEX_USUARIO'], $dadosCliente['VTEX_SENHA'], $dadosCliente['VTEX_API_URL'], $dadosCliente['VTEX_API_KEY'], $dadosCliente['VTEX_API_TOKEN'] );
		echo "Conectado!" . PHP_EOL;
		echo PHP_EOL;
		
		// Percorrer array de preços
		foreach ( $array_estoques as $indice => $dados_estoque ) {
			$erros_estoques = 0;			
			
			if ( $dados_estoque ['SaldoDisponivel'] == NULL ) {
				echo "Estoque do produto {$dados_estoque['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_estoque['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_estoques ++;
			}
			if ( $erros_estoques == 0 ) {
				
				try {
					echo PHP_EOL;
					echo "Buscando cadastro do produto " . $dados_estoque['CodigoProduto'] . PHP_EOL;
					$produto = $this->_vtex->buscaCadastroProduto( $dados_estoque['CodigoProduto'] );
					if ( !empty ( $produto->StockKeepingUnitGetByRefIdResult ) ) {
						echo "Atualizando Estoque " . $dados_estoque['CodigoProduto'] . PHP_EOL;
						echo "Quantidade disponivel " . $dados_estoque['SaldoDisponivel'] . PHP_EOL;
						$dados_estoque['IdProduto'] = $produto->StockKeepingUnitGetByRefIdResult->Id;
						$this->_vtex->atualizaArmazemSkuRest('1_1', $dados_estoque['IdProduto'], $dados_estoque['SaldoDisponivel']);
						echo "Estoque atualizado. " . PHP_EOL;
					}else{
						throw new RuntimeException( 'Produto nao encontrado' );
					} 
										
					$this->_kpl->ConfirmarEstoquesDisponiveis ( $dados_estoque ['ProtocoloEstoque'] );
					echo "Protocolo Estoque: {$dados_estoque ['ProtocoloEstoque']} enviado com sucesso" . PHP_EOL;
					echo PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao importar estoque {$dados_estoque['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar estoque {$dados_estoque['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}		
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}	

}
