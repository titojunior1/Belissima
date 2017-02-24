<?php
/**
 * 
 * Classe de Singleton de integração com a VTEX 
 *
 */
class Model_Belissima_Vtex_Vtex {
	
	/**
	 * Array de Mensagens de Erros.
	 * @var Array
	 */
	public $_array_erros = array ();
	
	/**
	 * Variavel  de Objeto da Classe StubVtex.
	 * @var Model_Wms_Vtex_StubVtex
	 */
	public $_client;
	
	/**
	 * 
	 * Objeto Singleton
	 * @var Model_Wms_Vtex_Vtex
	 */
	private static $_vtex;
	
	/**
	 * Contrutor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 */
	private function __construct() {
		
		try {
			// Gera objeto de conexão WebService
			$this->_client = new Model_Belissima_Vtex_StubVtex ( VTEX_WSDL, VTEX_USUARIO, VTEX_SENHA );
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * Garante a instancia única desta classe
	 * @throws Exception
	 */
	public static function getVtex() {
		if (self::$_vtex instanceof Model_Belissima_Vtex_Vtex === false) {
			
			try {
				$vtex = new Model_Belissima_Vtex_Vtex();
				
				self::$_vtex = $vtex;
			} catch ( Exception $e ) {
				throw new Exception ( $e->getMessage () );
			}
		}
		return self::$_vtex;
	}
	
	/**
	 * 
	 * Grava os log de erros na tabelavtex_log_erro
	 * @throws RuntimeException
	 */
	public function gravaLogVtex() {
		$db = Db_Factory::getDbWms ();
		if (is_array ( $this->_array_erros ) && ! empty ( $this->_array_erros )) {
			foreach ( $this->_array_erros as $tipo_acao => $array_erros ) { // foreach por acão (Pedido ; SKU ; Produto Pai ; Estoque)
				foreach ( $array_erros as $erros ) { // foreach para os erros individuais
				
					$data_atual = date('Y-m-d H:i:s');
					$msg_email = "cli_id = {$this->_cli_id} \n  Tipo = {$tipo_acao} \n Erro = {$erros['Id']} \n metodo = {$erros['Metodo']} \n Desc. Erro = {$erros['DescricaoErro']} \n data = {$data_atual}";
					$assunto_email = "Erro Vtex {$tipo_acao}";
					$destinatarios_email = "alerta.sistemas@ti.totalexpress.com.br";
					$erros['DescricaoErro'] = $db->EscapeString($erros['DescricaoErro']);					
					// enviar email informando o erro
					//@mail($destinatarios_email, $assunto_email, $msg_email);
									
/*					// inserir linha a linha os erros
					$sql = "INSERT INTO vtex_log_erro (cli_id, vtlogerr_tipo, vtlogerr_id_vtex, vtlogerr_metodo, vtlogerr_erro, vtlogerr_data, vtlogerr_hora) 
	 						VALUES ('{$this->_cli_id}', '{$tipo_acao}', '{$erros['Id']}', '{$erros['Metodo']}', '{$erros['DescricaoErro']}', NOW(), NOW())";
					$res = $db->Execute ( $sql );
					if (! $res) {
						throw new RuntimeException ( 'Erro ao gravar log de erros' );
					}*/
				} // foreach para os erros individuais
			} // foreach por acão (Pedido ; SKU ; Produto Pai ; Estoque)
		}
	}
	
	/**
	 * Adiciona mensagem de erro ao array
	 * @param array $erro Dados de Erro
	 */
	public function setErro($erro, $tipo) {
		$this->_array_erros [$tipo] [] = $erro;
	}
	
	/**
	 * Adiciona mensagem de erro ao array
	 * @param array $erro Dados de Erro
	 */
	public function setErroNovo($erros, $tipo_acao) {
		$db = Db_Factory::getDbWms ();
		$data_atual = date('Y-m-d H:i:s');
		$msg_email = "cli_id = {$this->_cli_id} \n  Tipo = {$tipo_acao} \n Erro = {$erros['Id']} \n metodo = {$erros['Metodo']} \n Desc. Erro = {$erros['DescricaoErro']} \n data = {$data_atual}";
		$assunto_email = "Erro Vtex {$tipo_acao}";
		$destinatarios_email = "alerta.sistemas@ti.totalexpress.com.br";
		$erros['DescricaoErro'] = $db->EscapeString($erros['DescricaoErro']);			
		// enviar email informando o erro
		//@mail($destinatarios_email, $assunto_email, $msg_email);
			
		// inserir linha a linha os erros
		$sql = "INSERT INTO vtex_log_erro (cli_id, vtlogerr_tipo, vtlogerr_id_vtex, vtlogerr_metodo, vtlogerr_erro, vtlogerr_data, vtlogerr_hora)
		VALUES ('{$this->_cli_id}', '{$tipo_acao}', '{$erros['Id']}', '{$erros['Metodo']}', '{$erros['DescricaoErro']}', NOW(), NOW())";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( 'Erro ao gravar log de erros' );
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function getErros() {
		return $this->_array_erros;
	}
	
	/**
	 * Imprime o array de erros
	 */
	public function PrintErros() {
		if (count ( $this->_array_erros ) > 0) {
			print_r ( $this->_array_erros );
		}
	}
	
	/**
	 * Cria um Arquivo de Log
	 * @param string $arquivo Endereço e Nome do Arquivo de Log
	 * @param string $msg     Mensagem a ser colocada no Log
	 */
	public function VtexLog($arquivo, $msg) {
		$data = date ( "d-m-y" );
		$hora = date ( "H:i:s" );
		$ip = Model_Ip::getRemoteIp();
		$mensagem = "[$hora][$ip]> $msg \n";
		$manipular = fopen ( "$arquivo", "a+b" );
		fwrite ( $manipular, $mensagem );
		fclose ( $manipular );
	}
	
	/**
	 * 
	 * Trata Objetos DTO, pois caso haja apenas um retorno, não é informado a key do array
	 * @param Array $array_dto
	 */
	public function trataArrayDto($array_dto) {
		if (! is_array ( $array_dto )) {
			return NULL;
		}
		if (! array_key_exists ( 0, $array_dto )) {
			// se não achar a chave zero, insere a chave zero
			$array_tratado [0] = $array_dto;
		} else {
			$array_tratado = $array_dto;
		}
		return $array_tratado;
	}

}
?>
