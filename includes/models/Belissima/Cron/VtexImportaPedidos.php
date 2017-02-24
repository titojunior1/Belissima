<?php
/**
 * Importa os pedidos da Vtex
 *
 * @author David Soares <david.soares@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com
 */
class Model_Wms_Cron_VtexImportaPedidos extends Model_Wms_Cron_Abstract {

	public function executar() {

		$db = Db_Factory::getDbWms ();
		$app = new Db ();

		$status_pedido_vtex = "CAP"; // baixar pedidos com status CAP: crédito aprovado
		$qtd_pedidos = '100'; // quantidade limite de pedidos por transmissão
		#$qtd_pedidos ='50';

		// horarios que a Vtex atualiza o Mandriva
		// Bloqueamos a execução dos crons Vtex neste periodo, pois eles utilizam o próprio webservice para alimentar os dados do mandriva e acabam derrubando nossas conexões
		$hora_inicial_bloqueio = '21:00:00';
		$hora_final_bloqueio = '05:00:00';

		$hora_atual = date('H:i:s');

		//Após Black Friday, descomentar
		/*if($hora_atual >= $hora_inicial_bloqueio || $hora_atual <= $hora_final_bloqueio){
			return false;
		}*/


		// selecionar os clientes que utilizam integram com a Vtex
		echo "Buscando clientes que utilizam Vtex" . PHP_EOL;

		$sql = "SELECT c.cli_id, cw.empwh_id FROM clientes c
				INNER JOIN clientes_warehouse cw ON (c.cli_id = cw.cli_id)
				WHERE c.cli_vtex_url IS NOT NULL AND c.cli_vtex_login IS NOT NULL AND c.cli_id NOT IN (73) AND c.cli_inventario = 0 AND c.cli_status = 1";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( 'Erro ao buscar cliente que utilizam Vtex ' );
		}
		if ($db->NumRows ( $res ) == 0) {
			echo "Não exitem clientes utilizando Vtex no momento" . PHP_EOL;
			return false;
		}
		$row = $db->FetchAssoc ( $res );

		while ( $row ) {
			$cli_id = $row ['cli_id'];
			echo "Cliente: {$cli_id}".PHP_EOL;
			$empwh_id = $row ['empwh_id'];

			if($cli_id == 77){

				try {
					echo "Importando pedidos do cliente {$cli_id}" . PHP_EOL;
					$vtex = new Model_Wms_Vtex_PedidoMeuEspelho( $cli_id, $empwh_id );
					$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos );

					$erros_proc = $vtex->getErrosProcessamento ();

					echo "Pedidos do cliente {$cli_id} Importados" . PHP_EOL;
				} catch ( Exception $e ) {
					$erros_proc = $vtex->getErrosProcessamento ();
					echo "Erros ao importar os pedidos do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
				}
				echo PHP_EOL;
				echo PHP_EOL;
			}else{

	/*	$sql2 = "SELECT pedido FROM vtex_pedidos_cancelar ";
		$res2 = $db->Execute ( $sql2 );
		if (!$res2) {
			throw new RuntimeException ( 'Erro ao consultar pedido para mudança de status' );
		}
		if ($db->NumRows ( $res2 ) > 0) {
			$row2 = $db->FetchAssoc ( $res2 );

			while ( $row2 ) {
				$order_id = $row2 ['pedido'];
				$status = 'ERP';
				try {
					$vtex = new Model_Wms_Vtex_Pedido ( $cli_id, $empwh_id );
					$vtex->alterarStatusPedido ( $order_id, $status );
					$vtex->consultarPedido($order_id); //['OrderStatusId'}

				} catch ( Exception $e ) {
					$erros_proc = $vtex->getErrosProcessamento ();
				}

				$row2 = $db->FetchAssoc ( $res2 );
			}
		}
*/

			try {
				echo "Importando pedidos do cliente {$cli_id}" . PHP_EOL;
				$vtex = new Model_Wms_Vtex_Pedido ( $cli_id, $empwh_id );
				$vtex->importarPedidosStatusQuantidade ( $status_pedido_vtex, $qtd_pedidos );

				$erros_proc = $vtex->getErrosProcessamento ();

				echo "Pedidos do cliente {$cli_id} Importados" . PHP_EOL;
			} catch ( Exception $e ) {
				$erros_proc = $vtex->getErrosProcessamento ();

				echo "Erros ao importar os pedidos do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
			echo PHP_EOL;
			echo PHP_EOL;
			}
		unset($vtex);
		$row = $db->FetchAssoc ( $res );
		}
	}

}
