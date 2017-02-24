<?php
/**
 * Importa os produtos da Vtex
 *
 * @author David Soares <david.soares@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com
 */
class Model_Wms_Cron_VtexImportaProdutos extends Model_Wms_Cron_Abstract {

	public function executar() {

		$db = Db_Factory::getDbWms ();
		$app = new Db ();

		// horarios que a Vtex atualiza o Mandriva
		// Bloqueamos a execução dos crons Vtex neste periodo, pois eles utilizam o próprio webservice para alimentar os dados do mandriva e acabam derrubando nossas conexões
		$hora_inicial_bloqueio = '22:00:00';
		$hora_final_bloqueio = '05:00:00';

		$hora_atual = date('H:i:s');
		//Após Black Friday, descomentar
		/*if($hora_atual >= $hora_inicial_bloqueio || $hora_atual <= $hora_final_bloqueio){
			return false;
		}*/

		$data = date ( 'Y-m-d' );
		// selecionar os clientes que utilizam integram com a Vtex
		echo "Buscando clientes que utilizam Vtex" . PHP_EOL;
		$sql = "SELECT cli_id FROM clientes WHERE cli_vtex_url IS NOT NULL AND cli_vtex_login IS NOT NULL AND cli_id NOT IN(73) AND cli_status = 1";
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
			try {
				echo "Importando produtos do cliente {$cli_id}" . PHP_EOL;
				if($cli_id == 77){
					$vtex = new Model_Wms_Vtex_ProdutoMeuEspelho($cli_id);
					$vtex->atualizaProdutoPendente();
					$vtex->importarProdutoDataId();
				}
				else{
				$vtex = new Model_Wms_Vtex_Produto ( $cli_id );
				//$vtex->importarProdutoData ( $data );
				//Verifica se existem produtos pendentes e Atualiza.
			//	$vtex->atualizaProdutoPendente();
				$vtex->importarProdutoDataId();
				}

				echo "Importação de produtos do cliente {$cli_id} realizada com sucesso" . PHP_EOL;
			} catch ( Exception $e ) {
				echo "Erros ao importar os produtos do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
				echo $e->getTraceAsString() . PHP_EOL;
			}
			echo PHP_EOL;
			echo PHP_EOL;
			unset($vtex);
			$row = $db->FetchAssoc ( $res );
		}
	}

}
