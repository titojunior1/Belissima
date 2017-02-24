<?php
/**
 * Importa os produtos pai da Vtex
 *
 * @author David Soares <david.soares@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com
 */
class Model_Wms_Cron_VtexImportaProdutosPai extends Model_Wms_Cron_Abstract {

	public function executar() {

		$db = Db_Factory::getDbWms ();
		$app = new Db ();

		// horarios que a Vtex atualiza o Mandriva
		// Bloqueamos a execução dos crons Vtex neste periodo, pois eles utilizam o próprio webservice para alimentar os dados do mandriva e acabam derrubando nossas conexões
		$hora_inicial_bloqueio = '22:00:00';
		$hora_final_bloqueio = '03:00:00';

		$hora_atual = date ( 'H:i:s' );
		//Após Black Friday, descomentar
		/*if ($hora_atual >= $hora_inicial_bloqueio || $hora_atual <= $hora_final_bloqueio) {
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
				echo "Importando produtos pai do cliente {$cli_id}" . PHP_EOL;
				$vtex = new Model_Wms_Vtex_Produto ( $cli_id );
				$vtex->importarProdutoPaiDataId ();

				echo "Importação de produtos pai do cliente {$cli_id} realizada com sucesso" . PHP_EOL;
			} catch ( Exception $e ) {
				echo "Erros ao importar os produtos do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}

			echo PHP_EOL;
			echo PHP_EOL;
			$row = $db->FetchAssoc ( $res );
		}
		//importação dos produtos EAN
		try {
			$ean = new Model_Wms_Arquivo_EanRiHappy ();
			//captura os arquivos de ean do FTP
			$ean->capturarEan ();


		} catch ( Exception $e ) {
			echo "Erros no processamento de EAN do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
		}
	}

}
