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
										'KPL_WSDL' => 'http://3378bbb.ws.kpl.com.br/Abacoswsplataforma.asmx?wsdl',
										'KPL_KEY' => 'C8DF11BE-753A-400C-ACE8-A694EC4F9DB3',
										'VTEX_WSDL' => 'http://webservice-belissimacosmeticos.vtexcommerce.com.br/AdminWebService/Service.svc?singleWsdl',
										'VTEX_USUARIO' => 'caue.diniz@wprtecnologia.com',
										'VTEX_SENHA' => 'Mudar@123',
										'VTEX_API_URL' => 'http://belissimacosmeticos.vtexcommercestable.com.br/api/%s',
										'VTEX_API_KEY' => 'caue.diniz@wprtecnologia.com',
										'VTEX_API_TOKEN' => 'Mudar@123'									
									),
							'VetorScan' => array (
										'KPL_WSDL' => 'http://3484c18.ws.kpl.com.br/Abacoswsplataforma.asmx?wsdl',
										'KPL_KEY' => '5DCA8582-1EB2-4CAE-9DC3-CF6002DD989D',
										'MAGENTO_WSDL' => 'http://www.lojaverdenbikes.com/api/v2_soap?wsdl=1',
										'MAGENTO_USUARIO' => 'erp_api',
										'MAGENTO_SENHA' => 'wpr@1020@'
									)
							);		
		
		return $clientes;
	}
	
	
} 