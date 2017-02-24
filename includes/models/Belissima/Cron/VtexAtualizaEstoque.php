<?php
/**
 * atualiza estoque da Vtex baseado na tabela de movimentos_hist de clientes que não estão em inventário
 *
 * @author David Soares <david.soares@totalexpress.com.br>
 * @author Eduardo de Oliveira <eduardo.oliveira@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @total Compatível com PHP 5.3
 * @package wms
 */
class Model_Wms_Cron_VtexAtualizaEstoque extends Model_Wms_Cron_Abstract {

	public function executar() {
		//Após Black Friday, descomentar
		/*if((date('dmY') == '22112012' && date('H') >= 22) || date('dmY') == '23112012' || (date('dmY') == '24112012' && date('H') < 2)) {
			echo "Rotina desativada para Black Friday";
			return;
		}*/




		$db = Db_Factory::getDbWms ();
		$app = new Db ();

		// selecionar os clientes que utilizam integram com a Vtex
		echo "Buscando clientes que utilizam Vtex" . PHP_EOL;

		$sql = "SELECT cli_id FROM clientes WHERE cli_inventario IN (0,1) AND cli_vtex_url IS NOT NULL AND cli_vtex_login IS NOT NULL AND cli_status = 1";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao buscar cliente que utilizam Vtex ' );
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			echo "Não exitem clientes utilizando Vtex no momento" . PHP_EOL;
			return false;
		}

		// nova hora de início
		$nova_hora_inicio = date ( 'Y-m-d H:i:s' );

		// obter a data inicial para consulta do estoque
		$sql = "SELECT cron_campo1 FROM cron_scripts WHERE cron_classe = 'VtexAtualizaEstoque'";
		$res2 = $db->Execute ( $sql );
		if ( ! $res2 ) {
			throw new RuntimeException ( 'Erro ao consultar última data de atualização' );
		}
		$row2 = $db->FetchAssoc ( $res2 );
		$ultima_data = $row2 ['cron_campo1'];
		if ( empty ( $ultima_data ) ) {
			$data_inicio = '0000-00-00';
			$hora_inicio = '00:00:00';
		} else {
			$ultima_data = strtotime ( $ultima_data );
			$data_inicio = date ( 'Y-m-d', $ultima_data );
			$hora_inicio = date ( 'H:i:s', $ultima_data );
		}

		$row = $db->FetchAssoc ( $res );
		while ( $row ) {
			$cli_id = $row ['cli_id'];

			try {
				echo "Atualizando estoque do cliente {$cli_id}" . PHP_EOL;

				$vtex = new Model_Wms_Vtex_Estoque ( $cli_id );
				$vtex->geraAtualizacaoEstoque ( $data_inicio, $hora_inicio );

				echo "Estoque atualizado para o cliente {$cli_id}" . PHP_EOL;

				// atualizar cron
				$sql = "UPDATE cron_scripts SET cron_campo1='{$nova_hora_inicio}' WHERE cron_classe = 'VtexAtualizaEstoque'";
				if ( ! $db->Execute ( $sql ) ) {
					throw new RuntimeException ( 'Erro ao gravar a data da última atualização' );
				}

			} catch ( Exception $e ) {
				echo "Erros ao atualizar estoque do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}

			//enviar produtos que foram retirados do empenho.
			//consulta de empenhos removidos na edição de pedidos e produtos que não foram atualizados por outros motivos.
			echo "Buscando produtos que estão na fila de atualização".PHP_EOL;
			$sql = "SELECT vtacoes_id, vtacoes_id_vtex FROM vtex_acoes WHERE cli_id={$cli_id} AND vtacoes_tipo='Estoque' AND vtacoes_metodo='VtexAtualizaEstoque' LIMIT 100";

			$resVtexAcoes = $db->Execute ( $sql );
			if ( ! $resVtexAcoes ) {
				throw new RuntimeException ( 'Erro ao consultar dados' );
			}
			$qtd = $db->NumRows($resVtexAcoes);
			echo "Produtos na fila de atualização: {$qtd}".PHP_EOL;
			$rowVtexAcoes = $db->FetchAssoc ( $resVtexAcoes );
			while ( $rowVtexAcoes ) {
				$vtex->geraAtualizacaoEstoqueProduto($rowVtexAcoes ['vtacoes_id_vtex'] );
				$rowVtexAcoes = $db->FetchAssoc ( $resVtexAcoes );
			}
			unset ( $vtex );
			echo PHP_EOL;
			echo PHP_EOL;
			$row = $db->FetchAssoc ( $res );
		}
	}

}
