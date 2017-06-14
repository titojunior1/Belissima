<?php
/**
 * 
 * Classe de Singleton de integra��o com a VTEX 
 *
 */
class Model_Wpr_Vtex_Vtex {
	
	/**
	 * Array de Mensagens de Erros.
	 * @var Array
	 */
	public $_array_erros = array ();
	
	/**
	 * Variavel  de Objeto da Classe StubVtex.
	 * @var Model_Wpr_Vtex_StubVtex
	 */
	public $_client;
	
	/**
	 * 
	 * Objeto Singleton
	 * @var Model_Wpr_Vtex_Vtex
	 */
	private static $_vtex;
	
	/**
	 * Contrutor.
	 * @param string $ws Endere�o do Webservice.
	 * @param string $login Login de Conex�o do Webservice.
	 * @param string $pass Senha de Conex�o do Webservice.
	 */
	private function __construct( $ws, $login, $pass ) {
		
		try {
			// Gera objeto de conex�o WebService
			$this->_client = new Model_Wpr_Vtex_StubVtex ( $ws, $login, $pass );
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * Garante a instancia �nica desta classe
	 * @param string $ws Endere�o do Webservice.
	 * @param string $login Login de Conex�o do Webservice.
	 * @param string $pass Senha de Conex�o do Webservice. 
	 * @throws Exception
	 */
	public static function getVtex( $ws, $login, $pass ) {
		if (self::$_vtex instanceof Model_Wpr_Vtex_Vtex === false) {
			
			try {
				$vtex = new Model_Wpr_Vtex_Vtex( $ws, $login, $pass );
				
				self::$_vtex = $vtex;
			} catch ( Exception $e ) {
				throw new Exception ( $e->getMessage () );
			}
		}
		return self::$_vtex;
	}
	
	/**
	 * Adiciona mensagem de erro ao array
	 * @param array $erro Dados de Erro
	 */
	public function setErro($erro, $tipo) {
		$this->_array_erros [$tipo] [] = $erro;
	}
	
	/**
	 *
	 * Trata Objetos DTO, pois caso haja apenas um retorno, n�o � informado a key do array
	 * @param Array $array_dto
	 */
	public function trataArrayDto($array_dto) {
		if (! is_array ( $array_dto )) {
			return NULL;
		}
		if (! array_key_exists ( 0, $array_dto )) {
			// se n�o achar a chave zero, insere a chave zero
			$array_tratado [0] = $array_dto;
		} else {
			$array_tratado = $array_dto;
		}
		return $array_tratado;
	}
}
?>
