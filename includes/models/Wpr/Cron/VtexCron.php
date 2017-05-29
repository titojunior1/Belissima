<?php
/**
 * 
 * Cron para processar integração com sistema ERP VTEX - Ábacos via webservice   
 * @author Tito Junior <titojunior1@gmail.com>
 * 
 */
class Model_Wpr_Cron_VtexCron {
	
	/**
	 * 
	 * Objeto Kpl (instância do webservice kpl)
	 * @var Model_Verden_Kpl_KplWebService
	 */
	private $_kpl;	

	/**
	 * Construtor
	 * @param 
	 */
	public function __construct () {

		echo "- Iniciando Cron para processar integracao com sistema ERP KPL via webservice" . PHP_EOL;
		
	}


	/**
	 * 
	 * Importa os pedidos disponíveis.
	 * @throws Exception
	 */
	public function CadastraPedidosVtex() {
		
		
		$status_pedido_vtex = "CAP"; // baixar pedidos com status CAP: crédito aprovado
		$qtd_pedidos = '100'; // quantidade limite de pedidos por transmissão
		
		
		// horarios que a Vtex atualiza o Mandriva
		// Bloqueamos a execução dos crons Vtex neste periodo, pois eles utilizam o próprio webservice para alimentar os dados do mandriva e acabam derrubando nossas conexões
		$hora_inicial_bloqueio = '23:59:00';
		$hora_final_bloqueio = '05:00:00';
		
		$hora_atual = date('H:i:s');
		
		//Após Black Friday, descomentar
		if($hora_atual >= $hora_inicial_bloqueio || $hora_atual <= $hora_final_bloqueio){
			return false;
		}

		try {
			echo "Importando pedidos do cliente" . PHP_EOL;
			$vtex = new Model_Wpr_Vtex_Pedido();
			$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos );
			//$vtex->importarPedidoId(607143); // Afiliado
			//$vtex->importarPedidoId(619385); // PENDENTE INTEGRAÇÃO
			//$vtex->importarPedidoId(619477);

			//$erros_proc = $vtex->getErrosProcessamento ();

			echo "Pedidos do cliente Importados" . PHP_EOL;
		} catch ( Exception $e ) {
			$erros_proc = $vtex->getErrosProcessamento ();
			echo "Erros ao importar os pedidos do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
		}
		echo PHP_EOL;
		echo PHP_EOL;
		
		unset($vtex);
		
	}
	
	
}