<?php
/**
 * 
 * Classe de Singleton de integração com a VTEX 
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
			$this->_client = new Model_Wpr_Vtex_StubVtex ( VTEX_WSDL, VTEX_USUARIO, VTEX_SENHA );
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
		if (self::$_vtex instanceof Model_Wpr_Vtex_Vtex === false) {
			
			try {
				$vtex = new Model_Wpr_Vtex_Vtex();
				
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
}
?>
