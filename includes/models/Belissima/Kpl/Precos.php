<?php
/**
 * 
 * Classe para processar as atualizações de preço no ERP KPL - Ábacos 
 * 
 * @author    Tito Junior 
 * 
 */

class Model_Belissima_Kpl_Precos extends Model_Belissima_Kpl_KplWebService {
	
	/*
	 * Instancia Webservice Vtex
	*/
	private $_vtex;
	
	/*
	 * Instancia Webservice KPL
	*/
	private $_kpl;
	
	/*
	 *  URL para integração via REST VTEX
	 */
	private $_url;
	
	/*
	 *  Token para integração via REST VTEX
	*/
	private $_token;
	
	/*
	 *  Chave para integração via REST VTEX
	*/
	private $_key;
	
	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 * @param int $empwh_id
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Belissima_Kpl_KplWebService (  );
		}
		$this->_url = VTEX_API_URL;
		$this->_key = VTEX_API_KEY;
		$this->_token = VTEX_API_TOKEN;
	
	}

	/**
	 * 
	 * Método para atualização de preço dos produtos	 
	 * @throws RuntimeException
	 */
	private function _atualizaPrecoRest ( $dados_precos ) {
				
		$data = array(
            array(
					'itemId' => $dados_precos['CodigoProduto'],
					'salesChannel' => '1',
					'price' => $dados_precos['PrecoPromocional'],
					'listPrice' => $dados_precos['PrecoTabela'],
					'validFrom' => $dados_precos['DataInicioPromocao'],
					'validTo' => $dados_precos['DataTerminoPromocao'],
			)
        );

        $url = sprintf($this->_url, 'pricing/pvt/price-sheet');
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
	 * Buscar Produto.
	 * @param string $sku
	 * @param string $part_number
	 * @param int $ean_proprio
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function buscaProduto ( $sku, $part_number, $ean_proprio ) {		
		
		// verificar se o produto existe
		
		// BUSCAR PRODUTO MAGENTO		
	
	}

	/**
	 * 
	 * Processar cadastro de preços via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaPrecosWebservice ( $request ) {

		// erros
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_precos = array ();
		
		if ( ! is_array ( $request ['DadosPreco'] [0] ) ) {
					
			$array_precos [0] ['ProtocoloPreco'] = $request ['DadosPreco'] ['ProtocoloPreco'];
			$array_precos [0] ['CodigoProduto'] = $request ['DadosPreco'] ['CodigoProduto'];
			$array_precos [0] ['CodigoProdutoPai'] = $request ['DadosPreco'] ['CodigoProdutoPai'];
			$array_precos [0] ['CodigoProdutoAbacos'] = $request ['DadosPreco'] ['CodigoProdutoAbacos'];
			$array_precos [0] ['NomeLista'] = $request ['DadosPreco'] ['NomeLista'];
			$array_precos [0] ['PrecoTabela'] = $request ['DadosPreco'] ['PrecoTabela'];
			$array_precos [0] ['PrecoPromocional'] = ( $request ['DadosPreco'] ['PrecoPromocional'] == 0 )? '' : $request ['DadosPreco'] ['PrecoPromocional'];
			if ( !empty( $request ['DadosPreco'] ['DataInicioPromocao'] ) && !empty( $request ['DadosPreco'] ['DataTerminoPromocao'] ) ){
				list($dataInicioPromo, $horaInicioPromo) = explode(' ', $request ['DadosPreco'] ['DataInicioPromocao']);
				$dia=substr($dataInicioPromo,0,2);
				$mes=substr($dataInicioPromo,2,2);
				$ano=substr($dataInicioPromo,4,4);
				$dataInicioPromo = $ano.'-'.$mes.'-'.$dia;
				$array_precos [0] ['DataInicioPromocao'] = $dataInicioPromo . 'T' . $horaInicioPromo;
				list($dataFimPromo, $horaFimPromo) = explode(' ', $request ['DadosPreco'] ['DataTerminoPromocao'] );
				$dia=substr($dataFimPromo,0,2);
				$mes=substr($dataFimPromo,2,2);
				$ano=substr($dataFimPromo,4,4);
				$dataFimPromo = $ano.'-'.$mes.'-'.$dia;
				$array_precos [0] ['DataTerminoPromocao'] = $dataFimPromo . 'T' . $horaFimPromo;
			}			
			$array_precos [0] ['DescontoMaximoProduto'] = $request ['DadosPreco'] ['DescontoMaximoProduto'];
			$array_precos [0] ['CodigoProdutoParceiro'] = $request ['DadosPreco'] ['CodigoProdutoParceiro'];
			
		} else {
			
			foreach ( $request ["DadosPreco"] as $i => $d ) {
				
				$array_precos [$i] ['ProtocoloPreco'] = $d ['ProtocoloPreco'];
				$array_precos [$i] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_precos [$i] ['CodigoProdutoPai'] = $d ['CodigoProdutoPai'];
				$array_precos [$i] ['CodigoProdutoAbacos'] = $d ['CodigoProdutoAbacos'];
				$array_precos [$i] ['NomeLista'] = $d ['NomeLista'];
				$array_precos [$i] ['PrecoTabela'] = $d ['PrecoTabela'];
				$array_precos [$i] ['PrecoPromocional'] = ( $d ['PrecoPromocional'] == 0 )? '' : $d ['PrecoPromocional'];
				if ( !empty( $d ['DataInicioPromocao'] ) && !empty( $d ['DataTerminoPromocao'] ) ){
					list($dataInicioPromo, $horaInicioPromo) = explode(' ', $d ['DataInicioPromocao']);
					$dia=substr($dataInicioPromo,0,2);
					$mes=substr($dataInicioPromo,2,2);
					$ano=substr($dataInicioPromo,4,4);
					$dataInicioPromo = $ano.'-'.$mes.'-'.$dia;
					$array_precos [$i] ['DataInicioPromocao'] = $dataInicioPromo . 'T' . $horaInicioPromo;
					list($dataFimPromo, $horaFimPromo) = explode(' ', $d ['DataTerminoPromocao']);
					$dia=substr($dataFimPromo,0,2);
					$mes=substr($dataFimPromo,2,2);
					$ano=substr($dataFimPromo,4,4);
					$dataFimPromo = $ano.'-'.$mes.'-'.$dia;
					$array_precos [$i] ['DataTerminoPromocao'] = $dataFimPromo . 'T' . $horaFimPromo;
				}				
				$array_precos [$i] ['DescontoMaximoProduto'] = $d ['DescontoMaximoProduto'];
				$array_precos [$i] ['CodigoProdutoParceiro'] = $d ['CodigoProdutoParceiro'];
			}
		}
		
		
		$qtdPrecos = count($array_precos);
		
		echo PHP_EOL;
		echo "Precos encontrados para integracao: " . $qtdPrecos . PHP_EOL;
		echo PHP_EOL;
		
// 		echo "Conectando ao WebService Vtex... " . PHP_EOL;
// 		$this->_vtex = new Model_Belissima_Vtex_Preco();
// 		echo "Conectado!" . PHP_EOL;
		
		// Percorrer array de preços
		foreach ( $array_precos as $indice => $dados_precos ) {
			$erros_precos = 0;
			$array_inclui_precos = array ();			
			
			if ( empty ( $dados_precos ['PrecoTabela'] ) ) {
				echo "Preco do produto {$dados_precos['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_precos['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_precos ++;
			}
			if ( $erros_precos == 0 ) {
				
				try {
					echo PHP_EOL;
					echo "Buscando cadastro do produto " . $dados_precos['CodigoProduto'] . PHP_EOL;
					$produto = 1;//$this->_magento->buscaProduto($dados_precos['CodigoProduto']);
					if ( !empty ( $produto ) ) {
						echo "Atualizando Preco " . $dados_precos['CodigoProduto'] . PHP_EOL;
						echo "Preco Tabela: R$" . $dados_precos['PrecoTabela'] . PHP_EOL;
						echo "Preco Promocional: R$" . $dados_precos['PrecoPromocional'] . PHP_EOL;
						echo "Data Inicio: " . $dados_precos['DataInicioPromocao'] . PHP_EOL;
						echo "Data Fim: " . $dados_precos['DataTerminoPromocao'] . PHP_EOL;						
						$this->_atualizaPrecoRest( $dados_precos );
						echo "Preco atualizado. " . PHP_EOL;
						
					}else{
						throw new RuntimeException( 'Produto não encontrado' );
					} 
										
					$this->_kpl->confirmarPrecosDisponiveis ( $dados_precos ['ProtocoloPreco'] );
					echo "Protocolo Preco: {$dados_precos ['ProtocoloPreco']} enviado com sucesso" . PHP_EOL;		

				} catch ( Exception $e ) {
					echo "Erro ao importar preco {$dados_precos['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar preco {$dados_precos['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}		
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}

