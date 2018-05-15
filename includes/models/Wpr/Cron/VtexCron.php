<?php
/**
 *
 *
 *
 *
 *
 * Cron para processar integração com sistema ERP VTEX - Ábacos via webservice
 * @author Tito Junior <titojunior1@gmail.com>
 *        
 */
class Model_Wpr_Cron_VtexCron {
	
	/**
	 *
	 *
	 *
	 *
	 *
	 * Objeto Kpl (instância do webservice kpl)
	 * @var Model_Verden_Kpl_KplWebService
	 */
	private $_kpl;

	/**
	 * Construtor
	 * @param
	 *
	 *
	 *
	 *
	 */
	public function __construct() {
		echo "- Iniciando Cron para processar integracao com sistema ERP KPL via webservice" . PHP_EOL;
		
		// Carrega clientes do banco
		$this->_clientes = $this->CarregaClientes ();
	}

	/**
	 * Carrega clientes, utilizando clientes_erp
	 * @return array com Clientes
	 */
	public function CarregaClientes() {
		$clientes = new Model_Wpr_Clientes_ClientesIntegracao ();
		
		return $clientes->carregaClientes ();
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 * Importa os pedidos disponíveis.
	 * @throws Exception
	 */
	public function CadastraPedidosVtex() {
		$status_pedido_vtex = "ready-for-handling"; // baixar pedidos com status CAP: crédito aprovado
		$qtd_pedidos = '100'; // quantidade limite de pedidos por transmissão
		

		// horarios que a Vtex atualiza o Mandriva
		// Bloqueamos a execução dos crons Vtex neste periodo, pois eles utilizam o próprio webservice para alimentar os dados do mandriva e acabam derrubando nossas conexões
		$hora_inicial_bloqueio = '23:59:00';
		$hora_final_bloqueio = '05:00:00';
		
		$hora_atual = date ( 'H:i:s' );
		
		if ($hora_atual >= $hora_inicial_bloqueio || $hora_atual <= $hora_final_bloqueio) {
			return false;
		}
		
		foreach ( $this->_clientes as $cliente => $dadosCliente ) {
			
			switch ($cliente) {
				
				case 'Belissima' :
					
					echo "- importando pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					
					try {
						
						$vtex = new Model_Wpr_Vtex_Pedido ( $dadosCliente ['VTEX_WSDL'], $dadosCliente ['VTEX_USUARIO'], $dadosCliente ['VTEX_SENHA'], $dadosCliente ['VTEX_API_URL'], $dadosCliente ['VTEX_API_KEY'], $dadosCliente ['VTEX_API_TOKEN'] );
						$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos, $dadosCliente );
						
						echo "Pedidos do cliente {$cliente} importados - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					} catch ( Exception $e ) {
						echo "Erros ao importar os pedidos do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
					}
					
					break;
				
				case 'Up2You' :
					
					echo "- importando pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					
					try {
						
						$vtex = new Model_Wpr_Vtex_Pedido ( $dadosCliente ['VTEX_WSDL'], $dadosCliente ['VTEX_USUARIO'], $dadosCliente ['VTEX_SENHA'], $dadosCliente ['VTEX_API_URL'], $dadosCliente ['VTEX_API_KEY'], $dadosCliente ['VTEX_API_TOKEN'] );
						$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos, $dadosCliente );
						
						echo "Pedidos do cliente {$cliente} importados - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					} catch ( Exception $e ) {
						echo "Erros ao importar os pedidos do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
					}
					
					break;
				case 'PetLuni' :
					
					echo "- importando pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					
					try {
						
						$vtex = new Model_Wpr_Vtex_Pedido ( $dadosCliente ['VTEX_WSDL'], $dadosCliente ['VTEX_USUARIO'], $dadosCliente ['VTEX_SENHA'], $dadosCliente ['VTEX_API_URL'], $dadosCliente ['VTEX_API_KEY'], $dadosCliente ['VTEX_API_TOKEN'] );
						$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos, $dadosCliente );
						
						echo "Pedidos do cliente {$cliente} importados - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					} catch ( Exception $e ) {
						echo "Erros ao importar os pedidos do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
					}
					
					break;
				case 'QuatroPatas' :
					
					echo "- importando pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					
					try {
						
						$vtex = new Model_Wpr_Vtex_Pedido ( $dadosCliente ['VTEX_WSDL'], $dadosCliente ['VTEX_USUARIO'], $dadosCliente ['VTEX_SENHA'], $dadosCliente ['VTEX_API_URL'], $dadosCliente ['VTEX_API_KEY'], $dadosCliente ['VTEX_API_TOKEN'] );
						$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos, $dadosCliente );
						
						echo "Pedidos do cliente {$cliente} importados - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					} catch ( Exception $e ) {
						echo "Erros ao importar os pedidos do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
					}
					
					break;
				default :
					
					echo "- importando pedidos de saida do cliente {$cliente} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
						
					try {
					
						$vtex = new Model_Wpr_Vtex_Pedido ( $dadosCliente ['VTEX_WSDL'], $dadosCliente ['VTEX_USUARIO'], $dadosCliente ['VTEX_SENHA'], $dadosCliente ['VTEX_API_URL'], $dadosCliente ['VTEX_API_KEY'], $dadosCliente ['VTEX_API_TOKEN'] );
						$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos, $dadosCliente );
					
						echo "Pedidos do cliente {$cliente} importados - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
					} catch ( Exception $e ) {
					echo "Erros ao importar os pedidos do cliente {$cliente}: " . $e->getMessage () . PHP_EOL;
						}
					
					break;	
			}
		}
		echo PHP_EOL;
		echo PHP_EOL;
		
		unset ( $vtex );
	}
}