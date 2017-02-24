<?php

/**
 * Classe abstrata para processamento do cadastro de fornecedores via webservice do ERP KPL - �bacos   
 * 
 * @author    Tito Junior <moacir.tito@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     02/07/2014
 * 
 */

abstract class Model_Wms_Kpl_Abstract_Fornecedor extends Model_Wms_Kpl_KplWebService {
	
	/**
	 * Id do movimento.
	 *
	 * @var int
	 */
	private $_climov_id;
	
	/**
	 * Tipo de movimento
	 * 
	 * @var string S ou E
	 */
	private $_climov_tipo;
	
	/**
	 * Id do cliente.
	 *
	 * @var int
	 */
	private $_cli_id;
	
	/**
	 * Id do warehouse.
	 *
	 * @var int
	 */
	private $_empwh_id;
	
	/**
	 * Objeto de Conex�o com a KPL
	 *
	 * @var object
	 */
	private $_kpl;

	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 */
	public function __construct ( $cli_id ) {

		$this->_cli_id = $cli_id;
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
		}
	
	}
	
	/**
	 * 
	 * Processar cadastro de fornecedores via webservice
	 * @param string $guid
	 * @param array $request
	 */
	public function ProcessaFornecedoresWebservice ( $request ) {

		// cria inst�ncia do banco de dados
		$db = Db_Factory::getDbWms ();
		
		$codigoproc = 1;
		
		// array para retorno dos dados ao webservice
		$array_retorno = array ();
		$array_erro = array ();
		
		$array_fornecedores = array ();
		
		if ( ! is_array ( $request ['Rows'] ['DadosFornecedorWMS'] [0] ) ) {
			$fornecedor_mestre [0] = $request ['Rows'] ['DadosFornecedorWMS'];
		
		} else {
			$fornecedor_mestre = $request ['Rows'] ['DadosFornecedorWMS'];
		}
		
		foreach ( $fornecedor_mestre as $i => $d ) {
			
			$array_fornecedores [$i] ['ProtocoloFornecedor'] = $d ['ProtocoloFornecedor'];
			$array_fornecedores [$i] ['CodigoFornecedor'] = $d ['CodigoFornecedorAbacos']; //forn_id_cli
			$array_fornecedores [$i] ['Nome'] = utf8_decode ( $d ['Nome'] );
			$array_fornecedores [$i] ['Endereco'] = utf8_decode ( $d ['Endereco'] ['Logradouro'] );
			$array_fornecedores [$i] ['EndNum'] = utf8_decode ( $d ['Endereco'] ['NumeroLogradouro'] );
			$array_fornecedores [$i] ['EndCompl'] = utf8_decode ( $d ['ComplementoLogradouro'] );
			$array_fornecedores [$i] ['Bairro'] = utf8_decode ( $d ['Endereco'] ['Bairro'] );
			$array_fornecedores [$i] ['CEP'] = utf8_decode ( $d ['Endereco'] ['Cep'] );
			$array_fornecedores [$i] ['Cidade'] = utf8_decode ( $d ['Endereco'] ['Municipio'] );
			$array_fornecedores [$i] ['UF'] = $d ['Endereco'] ['Estado'];
			$array_fornecedores [$i] ['Contato'] = $d ['ContatoNome'];
			$array_fornecedores [$i] ['Telefone'] = $d ['ContatoTelefone'];
			$array_fornecedores [$i] ['Email'] = $d ['ContatoEmail'];
			$array_fornecedores [$i] ['CNPJFornecedor'] = $d ['CPFouCNPJ'];
			$i ++;
		
		}
		
		if ( $array_fornecedores ) {
			foreach ( $array_fornecedores as $indice => $dados_fornecedores ) {
				if ( empty ( $dados_fornecedores ['CodigoFornecedor'] ) || empty ( $dados_fornecedores ['Nome'] ) ) {
					// Retorna erro se algum campo obrigat�rio n�o estiver preenchido
					$array_erro [$indice] = "Fornecedor: {$dados_fornecedores['CodigoFornecedor']} - Nome ou C�digo n�o preenchidos" . PHP_EOL;
					echo "Fornecedor: {$dados_fornecedores['CodigoFornecedor']} - Campos obrigat�rios n�o preenchidos" . PHP_EOL;
				} else {
					// tratar sql
					foreach ( $dados_fornecedores as $key => $val ) {
						$dados_fornecedores [$key] = $db->EscapeString ( $val );
					}
					//verificar se o fornecedor j� existe
					$sql = "SELECT forn_id_cli FROM fornecedores WHERE cli_id = {$this->_cli_id} AND forn_id_cli = '{$dados_fornecedores['CodigoFornecedor']}'";
					$res = $db->Execute ( $sql );
					if ( $res ) {
						if ( $db->NumRows ( $res ) == 0 ) {
							$pais_id = "BR";
							$sql = "INSERT INTO fornecedores ( pais_id, cli_id, forn_nome, forn_endereco, forn_numero, forn_complemento, forn_bairro, forn_cep, forn_cidade, forn_estado, forn_contato, forn_telefone, forn_email, forn_obs, forn_id_cli, forn_cnpj)
	                                VALUES ( '$pais_id',{$this->_cli_id},'{$dados_fornecedores["Nome"]}','{$dados_fornecedores["Endereco"]}','{$dados_fornecedores["EndNum"]}','{$dados_fornecedores["EndCompl"]}','{$dados_fornecedores["Bairro"]}','{$dados_fornecedores["CEP"]}','{$dados_fornecedores["Cidade"]}','{$dados_fornecedores["UF"]}','{$dados_fornecedores["Contato"]}','{$dados_fornecedores["Telefone"]}','{$dados_fornecedores["Email"]}','{$dados_fornecedores["Observacoes"]}', '{$dados_fornecedores["CodigoFornecedor"]}', '{$dados_fornecedores["CNPJFornecedor"]}' )";
							if ( ! $db->Execute ( $sql ) ) {
								// Se n�o conseguiu realizar insert, retorna erro
								throw new RuntimeException ( "Erro ao inserir fornecedor {$dados_fornecedores['Nome']}" );
							} else {
								// Inseriu ok!
								//	$array_erro [$indice] = $this->retorna_erro ( 200002, "" );
								$forn_id = $db->LastInsertId ();
							}
						} else {
							// Se o fornecedor j� estiver cadastrado, retorna erro
							//							$array_erro [$indice] = $this->retorna_erro ( 300004, "Fornecedor ja cadastrado" );
							echo "Fornecedor j� cadastrado" . PHP_EOL;
						}
					} else {
						// Se n�o conseguiu realizar select, retorna erro
						//					$array_erro [$indice] = $this->retorna_erro ( 300014, "" );
						throw new Exception ( "Erro ao consultar fornecedor" . PHP_EOL );
					}
				}
				
				//enviar protocolo de transmiss�o do pedido. 
				try {
					
					$this->_kpl->confirmarFornecedoresDisponiveis ( $dados_fornecedores ['ProtocoloFornecedor'] );
					
					echo "Protocolo Fornecedor: {$dados_fornecedores['ProtocoloFornecedor']}" . PHP_EOL;
				} catch ( Exception $e ) {
					echo $e->getMessage () . PHP_EOL;
				}
			
			}
		}
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}