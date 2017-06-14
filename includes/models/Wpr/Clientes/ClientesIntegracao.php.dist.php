<?php
/**
 * Classe para armazenar informações de clientes 
 * @author Tito Junior
 *
 */
class Model_Wpr_Clientes_ClientesIntegracao {
	
	public function __construct(){}
	
	public function carregaClientes(){		
		
		$clientes = array ( 
							'Belissima' => array ( 
										'KPL_WSDL' => '',
										'KPL_KEY' => '',
									),
							'VetorScan' => array (
										'KPL_WSDL' => '',
										'KPL_KEY' => '',
									)
							);

		return $clientes;
	}
	
	
} 