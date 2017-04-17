<?php

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
/**
 * 
 * Classe para processar o cadastro de produtos via webservice do ERP KPL - �bacos 
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
	
	/*
	 * Categorias do produto
	 */
	private $_categorias;
	
	/* 
	 * Marcas do produto
	 */
	private $_marcas;
	
	/**
	 * 
	 * construtor.	 
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService();
		}
		
		$this->_categorias = $this->_kpl->categoriasProdutoDisponiveis(KPL_KEY);
		$this->_marcas = $this->_kpl->marcasDisponiveis(KPL_KEY);
		
	
	}
	
	/**
	 * Metodo que busca as categorias de um produto e faz a rela��o com a categoria VTEX
	 * @param string $descricaoCategoria
	 * 
	 */
	private function _getCategoriaProduto ( $descricaoCategoria ){
		
		if ( ! is_string( $descricaoCategoria ) ){
			throw new InvalidArgumentException( 'Categoria n�o informada ou Invalida' );
		}
		
		$categorias = array();		
		
		if ( ! is_array ( $this->_categorias ['DadosCategoriasProduto'] [0] ) ) {
			$categorias [0] ['Nome'] = $this->_categorias ['DadosCategoriasProduto'] ['Nome'];
			$categorias [0] ['CodigoExternoCategoriaProduto'] = $this->_categorias ['DadosCategoriasProduto'] ['CodigoExternoCategoriaProduto'];
		}else{
			
			foreach ( $this->_categorias ["DadosCategoriasProduto"] as $i => $d ) {
				$categorias [$i] ['Nome'] = $d ['Nome'];
				$categorias [$i] ['CodigoExternoCategoriaProduto'] = $d ['CodigoExternoCategoriaProduto'];
			}
		}
		
		foreach ( $categorias as $categoria ){
			if ( trim($categoria['Nome']) == trim($descricaoCategoria) ){
				return $categoria['CodigoExternoCategoriaProduto'];
			}
		}		
		
		return 0;	
		
	}
	
	/**
	 * Metodo que busca as marcas de um produto e faz a rela��o com a categoria VTEX
	 * @param int $idMarca
	 *
	 */
	private function _getMarcaProduto ( $idMarca ){
	
		if ( ! ctype_digit( $idMarca ) ){
			throw new InvalidArgumentException( 'Marca n�o informada ou Invalida' );
		}
	
		$marcas = array();
	
		if ( ! is_array ( $this->_marcas ['DadosMarcasProdutos'] [0] ) ) {
			
			$marcas [0] ['Nome'] = $this->_marcas ['DadosMarcasProdutos'] ['Nome'];
			$marcas [0] ['CodigoMarca'] = $this->_marcas ['DadosMarcasProdutos'] ['CodigoMarca'];
			$marcas [0] ['CodigoExternoMarca'] = $this->_marcas ['DadosMarcasProdutos'] ['CodigoExternoMarca'];
			
		}else{
				
			foreach ( $this->_marcas ["DadosMarcasProdutos"] as $i => $d ) {
				
				$marcas [$i] ['Nome'] = $d ['Nome'];
				$marcas [$i] ['CodigoMarca'] = $d ['CodigoMarca'];
				$marcas [$i] ['CodigoExternoMarca'] = $d ['CodigoExternoMarca'];
				
			}
		}
	
		foreach ( $marcas as $marca ){
			if ( trim($marca['CodigoMarca']) == trim($idMarca) ){
				return $marca['CodigoExternoMarca'];
			}
		}
	
		return 0;
	
	}
	
	/**
	 *
	 * Buscar Produto.
	 * @param string $refId
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	private function buscaProduto ( $refId ) {
	
		$refId = trim ( $refId );
		if ( empty ( $refId ) ) {
			throw new InvalidArgumentException ( 'RefId do produto inv�lido' );
		}
		
		$refId = (int) $refId;
	
		if ( !$this->_vtex ){
			$this->_vtex = new Model_Wpr_Vtex_Produto();
		}
	
		$retorno = $this->_vtex->buscaCadastroProdutoPai($refId);
	
		return $retorno;
	
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
				'BrandId' => $dados_produtos ['CodigoMarca'],
				'CategoryId' => $dados_produtos ['Categoria'],
				//'DepartmentId' => $dados_produtos ['CodigoFamilia'],
				'Description' => $dados_produtos ['Descricao'],
				'DescriptionShort' => $dados_produtos ['Descricao'],
				'IsActive' => 'true',
				'IsVisible' => 'true',
				'ListStoreId' => array( 'int' => '1' ),
				'MetaTagDescription' => $dados_produtos ['Descricao'],
				'Name' => $dados_produtos ['Nome'],
				'RefId' => $dados_produtos ['CodigoProdutoPai'],
				'Id' => $dados_produtos ['IdProdutoPai'],
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
				'Width' => $dados_produtos ['Largura'],
				'WeightKg' => $dados_produtos ['Peso'],
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
		
		// cole��o de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_produtos = array ();
		
		if ( ! is_array ( $request ['DadosProdutos'] [0] ) ) {
					
			$array_produtos [0] ['ProtocoloProduto'] = $request ['DadosProdutos'] ['ProtocoloProduto'];
			$array_produtos [0] ['Categoria'] = $this->_getCategoriaProduto( $request ['DadosProdutos'] ['DescricaoSubgrupo'] );
			$array_produtos [0] ['Nome'] = $request ['DadosProdutos'] ['NomeProduto'];			
			$array_produtos [0] ['Classificacao'] = isset($request ['DadosProdutos'] ['Classificacao']) ? $request ['DadosProdutos'] ['Classificacao']: '';
			$array_produtos [0] ['Altura'] = $request ['DadosProdutos'] ['Altura'];
			$array_produtos [0] ['Largura'] = $request ['DadosProdutos'] ['Largura'];
			$array_produtos [0] ['Comprimento'] = $request ['DadosProdutos'] ['Comprimento'];
			$array_produtos [0] ['Peso'] = $request ['DadosProdutos'] ['Peso'];
			$array_produtos [0] ['PartNumber'] = $request ['DadosProdutos'] ['CodigoProdutoAbacos'];
			$array_produtos [0] ['CodigoProduto'] = $request ['DadosProdutos'] ['CodigoProduto'];
			$array_produtos [0] ['EanProprio'] = $request ['DadosProdutos'] ['CodigoBarras'];
			$array_produtos [0] ['EstoqueMinimo'] = $request ['DadosProdutos'] ['QtdeMinimaEstoque'];
			$array_produtos [0] ['Descricao'] = empty($request ['DadosProdutos'] ['Descricao'])? $request ['DadosProdutos'] ['NomeProduto'] : str_replace('<BR>','',$request ['DadosProdutos'] ['Descricao']);
			$array_produtos [0] ['CodigoProdutoPai'] = isset($request ['DadosProdutos'] ['CodigoProdutoPai']) ? $request ['DadosProdutos'] ['CodigoProdutoPai']: '';
			$array_produtos [0] ['Unidade'] = isset($request ['DadosProdutos'] ['Unidade']) ? $request ['DadosProdutos'] ['Unidade']: '';
			$array_produtos [0] ['CodigoMarca'] = $this->_getMarcaProduto( $request ['DadosProdutos'] ['CodigoMarca'] );
			$array_produtos [0] ['NomeProdutoReduzido'] = $request ['DadosProdutos'] ['NomeProdutoReduzido'];
			$array_produtos [0] ['CodigoFamilia'] = $request ['DadosProdutos'] ['CodigoFamilia'];
			
			// verifica se produto � pai ou filho
			if ( strstr( $request ['DadosProdutos'] ['CodigoProduto'], '-' ) == true ){
				$array_produtos [0] ['Visibilidade'] = 1; // N�o exibir pois � produto Filho 
			}else{
				$array_produtos [0] ['Visibilidade'] = 4; // Exibir produto Pai
			}
		
		} else {
			
			foreach ( $request ["DadosProdutos"] as $i => $d ) {				
				
				$array_produtos [$i] ['ProtocoloProduto'] = $d ['ProtocoloProduto'];
				$array_produtos [$i] ['Categoria'] = $this->_getCategoriaProduto( $d ['DescricaoSubgrupo'] );				
				$array_produtos [$i] ['Nome'] = $d ['NomeProduto'];
				$array_produtos [$i] ['Classificacao'] = isset($d ['Classificacao']) ? $d ['Classificacao']: '';
				$array_produtos [$i] ['Altura'] = $d ['Altura'];
				$array_produtos [$i] ['Largura'] = $d ['Largura'];
				$array_produtos [$i] ['Comprimento'] = $d ['Comprimento'];
				$array_produtos [$i] ['Peso'] = $d ['Peso'];
				$array_produtos [$i] ['PartNumber'] = $d ['CodigoProdutoAbacos'];
				$array_produtos [$i] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_produtos [$i] ['EanProprio'] = $d ['CodigoBarras'];
				$array_produtos [$i] ['EstoqueMinimo'] = $d ['QtdeMinimaEstoque'];				
				$array_produtos [$i] ['Descricao'] =  empty($d ['Descricao'])? $d ['NomeProduto'] : str_replace('<BR>','',$d  ['Descricao']);				
				$array_produtos [$i] ['CodigoProdutoPai'] = isset($d ['CodigoProdutoPai']) ? $d ['CodigoProdutoPai']: '';
				$array_produtos [$i] ['Unidade'] = isset($d ['Unidade']) ? $d ['Unidade']: '';
				$array_produtos [$i] ['CodigoMarca'] = $this->_getMarcaProduto( $d ['CodigoMarca'] );
				$array_produtos [$i] ['NomeProdutoReduzido'] = $d ['NomeProdutoReduzido'];
				$array_produtos [$i] ['CodigoFamilia'] = $d ['CodigoFamilia'];
								
				// verifica se produto � pai ou filho
				if ( strstr( $d ['CodigoProduto'], '-' ) == true ){
					$array_produtos [$i] ['Visibilidade'] = 1; // N�o exibir pois � produto Filho
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
			$array_retorno = array();
			$array_erro = array();
			$prod_id = NULL;
			$incluir_produto = false;
			// validar campos obrigat�rios
			
			if ( empty ( $dados_produtos ['Nome'] ) || empty ( $dados_produtos ['Descricao'] ) || empty ( $dados_produtos ['PartNumber'] ) || empty ( $dados_produtos ['CodigoProduto'] ) || empty ( $dados_produtos ['EanProprio'] ) ) {
				echo "Produto {$dados_produtos['CodigoProduto']}: Dados obrigat�rios n�o preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_produtos['SKU']}: Dados obrigat�rios n�o preenchidos" . PHP_EOL;
				$erros_produtos ++;
			}
			if ( $erros_produtos == 0 ) {
				
				try {
					echo PHP_EOL;
					echo "Buscando cadastro do produto " . $dados_produtos ['CodigoProduto'] . PHP_EOL;
					$produto = $this->buscaProduto ( $dados_produtos ['CodigoProdutoPai'] );
					if ( $produto->ProductGetByRefIdResult != null ) {
						echo "Adicionando/atualizando produto " . $dados_produtos['CodigoProduto'] . " na loja Vtex" . PHP_EOL;
						$dados_produtos['IdProdutoPai'] = $produto->ProductGetByRefIdResult->Id;
						$this->_enviaProduto ( $dados_produtos );
						$this->_enviaSku( $dados_produtos );
						echo "Produto adicionado. " . PHP_EOL;
					}
					
					//devolver o protocolo do produto
					$this->_kpl->confirmarProdutosDisponiveis ( $dados_produtos ['ProtocoloProduto'] );
					echo "Protocolo Produto: {$dados_produtos['ProtocoloProduto']} enviado com sucesso" . PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao importar produto {$dados_produtos['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar produto {$dados_produtos['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}
