<?php

/**
 * 
 * Classe para processar o cadastro de produtos via webservice do ERP KPL - Ábacos 
 * 
 * @author Tito Junior 
 * 
 */
class Model_Wpr_Kpl_Produtos extends Model_Wpr_Kpl_KplWebService {
	
	/*
	 * Instancia Webservice KPL
	 */
	private $_kpl;
	
	/*
	 * Instancia Webservice Vtex
	 */
	private $_vtex;	
	
	/**
	 * 
	 * construtor.	 
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService();
		}
	
	}
	
	/**
	 * 
	 * Adicionar produto Pai.
	 * @param array $dados_produtos
	 * @throws Exception
	 * @throws RuntimeException
	 */
	private function _enviaProduto ( $dados_produtos ) {
		
		$request =  array(
				'BrandId' => '2000000',
				'CategoryId' => $dados_produtos ['Categoria'],
				'DepartmentId' => $dados_produtos ['CodigoFamilia'],
				'Description' => $dados_produtos ['Descricao'],
				'DescriptionShort' => $dados_produtos ['Descricao'],
				'IsActive' => 'true',
				'IsVisible' => 'true',
				'ListStoreId' => array( 'int' => '1'),
				'MetaTagDescription' => $dados_produtos ['Descricao'],
				'Name' => $dados_produtos ['Nome'],
				'RefId' => $dados_produtos ['PartNumber'],
				'Id' => $dados_produtos ['PartNumber'],
				'Title' => $dados_produtos ['NomeProdutoReduzido']				
		);	
		
		$this->_vtex->enviaProdutoPai($request);
	}
	
	/**
	 *
	 * Adicionar produto filho.
	 * @param array $dados_produtos
	 * @throws Exception
	 * @throws RuntimeException
	 */
	private function _enviaSku ( $dados_produtos ) {
		
		$produto =  array(
				'Height' => $dados_produtos ['Altura'],
				'Width' => '2',
				'WeightKg' => '2',
				'IsActive' => 'true',
				'IsAvaiable' => 'true',
				'IsKit' => 'false',
				'Length' => $dados_produtos ['Comprimento'],
				'ModalId' => '1',
				'Name' => $dados_produtos ['Nome'],
				'ProductName' => $dados_produtos ['Nome'],				
				'ProductId' => $dados_produtos ['CodigoProduto'],
				'RefId' => $dados_produtos ['CodigoProdutoPai'],								
				'Description' => $dados_produtos ['Descricao'],
				'DescriptionShort' => $dados_produtos ['Descricao'],
				'CubicWeight' => $dados_produtos ['Peso']				
		);
	
		$this->_vtex->enviaProdutoFilho($produto);	
	}

	/**
	 * 
	 * Processar cadastro de produtos via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaProdutosWebservice ( $request ) {

		// produtos
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_produtos = array ();
		
		if ( ! is_array ( $request ['DadosProdutos'] [0] ) ) {
					
			$array_produtos [0] ['ProtocoloProduto'] = $request ['DadosProdutos'] ['ProtocoloProduto'];
			$array_produtos [0] ['Categoria'] = isset($request ['DadosProdutos'] ['CodigoGrupo']) ? $request ['DadosProdutos'] ['CodigoGrupo']: '1';
			$array_produtos [0] ['Nome'] = utf8_encode($request ['DadosProdutos'] ['NomeProduto']);			
			$array_produtos [0] ['Classificacao'] = isset($request ['DadosProdutos'] ['Classificacao']) ? $request ['DadosProdutos'] ['Classificacao']: '';
			$array_produtos [0] ['Altura'] = $request ['DadosProdutos'] ['Altura'];
			$array_produtos [0] ['Largura'] = $request ['DadosProdutos'] ['Largura'];
			$array_produtos [0] ['Comprimento'] = $request ['DadosProdutos'] ['Comprimento'];
			$array_produtos [0] ['Peso'] = $request ['DadosProdutos'] ['Peso'];
			$array_produtos [0] ['PartNumber'] = $request ['DadosProdutos'] ['CodigoProdutoAbacos'];
			$array_produtos [0] ['CodigoProduto'] = $request ['DadosProdutos'] ['CodigoProduto'];
			$array_produtos [0] ['EanProprio'] = $request ['DadosProdutos'] ['CodigoBarras'];
			$array_produtos [0] ['EstoqueMinimo'] = $request ['DadosProdutos'] ['QtdeMinimaEstoque'];
			//$array_produtos [0] ['ValorVenda'] = '0.00';
			$array_produtos [0] ['Descricao'] =  utf8_encode(empty($request ['DadosProdutos'] ['Descricao'])? $request ['DadosProdutos'] ['NomeProduto'] : str_replace('<BR>','',$request ['DadosProdutos'] ['Descricao'])) ;
			//$array_produtos [0] ['ValorCusto'] = isset($request ['DadosProdutos'] ['ValorCusto']) ? $request ['DadosProdutos'] ['ValorCusto']: '';
			$array_produtos [0] ['CodigoProdutoPai'] = isset($request ['DadosProdutos'] ['CodigoProdutoPai']) ? $request ['DadosProdutos'] ['CodigoProdutoPai']: '';
			$array_produtos [0] ['Unidade'] = isset($request ['DadosProdutos'] ['Unidade']) ? $request ['DadosProdutos'] ['Unidade']: '';
			$array_produtos [0] ['CodigoMarca'] = $request ['DadosProdutos'] ['CodigoMarca'];
			$array_produtos [0] ['NomeProdutoReduzido'] = $request ['DadosProdutos'] ['NomeProdutoReduzido'];
			$array_produtos [0] ['CodigoFamilia'] = $request ['DadosProdutos'] ['CodigoFamilia'];
			
			// verifica se produto é pai ou filho
			if ( strstr( $request ['DadosProdutos'] ['CodigoProduto'], '-' ) == true ){
				$array_produtos [0] ['Visibilidade'] = 1; // Não exibir pois é produto Filho 
			}else{
				$array_produtos [0] ['Visibilidade'] = 4; // Exibir produto Pai
			}
		
		} else {
			
			foreach ( $request ["DadosProdutos"] as $i => $d ) {				
				
				$array_produtos [$i] ['ProtocoloProduto'] = $d ['ProtocoloProduto'];
				$array_produtos [$i] ['Categoria'] = isset($d ['CodigoGrupo']) ? $d ['CodigoGrupo']: '1';				
				$array_produtos [$i] ['Nome'] = utf8_encode($d ['NomeProduto']) ;
				$array_produtos [$i] ['Classificacao'] = isset($d ['Classificacao']) ? $d ['Classificacao']: '';
				$array_produtos [$i] ['Altura'] = $d ['Altura'];
				$array_produtos [$i] ['Largura'] = $d ['Largura'];
				$array_produtos [$i] ['Comprimento'] = $d ['Comprimento'];
				$array_produtos [$i] ['Peso'] = $d ['Peso'];
				$array_produtos [$i] ['PartNumber'] = $d ['CodigoProdutoAbacos'];
				$array_produtos [$i] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_produtos [$i] ['EanProprio'] = $d ['CodigoBarras'];
				$array_produtos [$i] ['EstoqueMinimo'] = $d ['QtdeMinimaEstoque'];
				//$array_produtos [$i] ['ValorVenda'] = '0.00';				
				$array_produtos [$i] ['Descricao'] =  utf8_encode(empty($d ['Descricao'])? $d ['NomeProduto'] : str_replace('<BR>','',$d  ['Descricao'])) ;
				//$array_produtos [$i] ['ValorCusto'] = isset($d ['ValorCusto']) ? $d ['ValorCusto']: '';				
				$array_produtos [$i] ['CodigoProdutoPai'] = isset($d ['CodigoProdutoPai']) ? $d ['CodigoProdutoPai']: '';
				$array_produtos [$i] ['Unidade'] = isset($d ['Unidade']) ? $d ['Unidade']: '';
				$array_produtos [$i] ['CodigoMarca'] = $d ['CodigoMarca'];
				$array_produtos [$i] ['NomeProdutoReduzido'] = $d ['NomeProdutoReduzido'];
				$array_produtos [$i] ['CodigoFamilia'] = $d ['CodigoFamilia'];
								
				// verifica se produto é pai ou filho
				if ( strstr( $d ['CodigoProduto'], '-' ) == true ){
					$array_produtos [$i] ['Visibilidade'] = 1; // Não exibir pois é produto Filho
				}else{
					$array_produtos [$i] ['Visibilidade'] = 4; // Exibir produto Pai
				}
			}
		}
		
		$qtdProdutos = count($array_produtos);
		
		echo PHP_EOL;
		echo "Produtos encontrados para integracao: " . $qtdProdutos . PHP_EOL;
		echo PHP_EOL;
		
		
		echo "Conectando ao WebService Vtex... " . PHP_EOL;
		$this->_vtex = new Model_Wpr_Vtex_Produto();
		echo "Conectado!" . PHP_EOL;
		echo PHP_EOL;
		
		// Percorrer array de produtos
		foreach ( $array_produtos as $indice => $dados_produtos ) {
			$erros_produtos = 0;
			$array_inclui_produtos = array ();
			$prod_id = NULL;
			$incluir_produto = false;
			// validar campos obrigatórios
			
			if ( empty ( $dados_produtos ['Nome'] ) || empty ( $dados_produtos ['Descricao'] ) || empty ( $dados_produtos ['PartNumber'] ) || empty ( $dados_produtos ['CodigoProduto'] ) || empty ( $dados_produtos ['EanProprio'] ) ) {
				echo "Produto {$dados_produtos['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_produtos['SKU']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_produtos ++;
			}
			if ( $erros_produtos == 0 ) {
				
				try {
					echo PHP_EOL;
					echo "Buscando cadastro do produto " . $dados_produtos ['SKU'] . PHP_EOL;
					//$produto = $this->buscaProduto ( $dados_produtos ['SKU'] );
					$produto = false;
					if ( $produto == false ) {
						echo "Adicionando/atualizando produto " . $dados_produtos['SKU'] . " na loja Vtex" . PHP_EOL;
						$this->_enviaProduto ( $dados_produtos );
						$this->_enviaSku( $dados_produtos );
						echo "Produto adicionado. " . PHP_EOL;
					}
					
					//devolver o protocolo do produto
					$this->_kpl->confirmarProdutosDisponiveis ( $dados_produtos ['ProtocoloProduto'] );
					echo "Protocolo Produto: {$dados_produtos['ProtocoloProduto']} enviado com sucesso" . PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao importar produto {$dados_produtos['SKU']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar produto {$dados_produtos['EanProprio']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}

