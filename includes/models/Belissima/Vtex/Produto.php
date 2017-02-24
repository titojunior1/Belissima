<?php
/**
 *
 * Classe de gerenciamento de Produtos com a VTEX 
 *
 */
class Model_Belissima_Vtex_Produto {

	/**
	 * Array para armazenar produtos pai consultados nas rotinas de importação de pais e filhos.
	 *
	 */
	private $_array_produtos_pai = array ();

	/**
	 *
	 * Objeto Vtex
	 * @var Model_Wms_Vtex_Vtex
	 */
	private $_vtex;

	/**
	 * Variavel  de Objeto da Classe StubVtex.
	 * @var Model_Wms_Vtex_StubVtex
	 */
	public $_client;

	/**
	 * Contrutor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 */
	public function __construct ( ) {
		$this->_initVtex ();
	}

	/**
	 * Função para Adicionar um Produto Pai
	 * @param string $id_vtex Id do Produto no VTEX
	 * @return retorna o dados do produto_pai
	 */
	public function adicionarProdutoPai ( $dadosProduto ) {

		try {
			$result = $this->_client->ProductInsertUpdate($dadosProduto);
			return $result;
		} catch ( Exception $e ) {
			$this->_vtex->setErroNovo ( array ( "Id" => $produto_temp ['RefId'], "Metodo" => "_adicionarProdutoPai", "DescricaoErro" => $e->getMessage () ), "Produto_Pai" );
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa um determinado sku
	 * @param Array $sku_dados DTO de produto filho
	 */
	public function adicionarProdutoFilho ( $sku_dados ) {

		if ( ! is_array ( $sku_dados ) ) {
			throw new Exception ( 'Dados do SKU inválidos' );
		}

		$db = Db_Factory::getDbWms ();
		$prod_custo = $db->EscapeString ( $sku_dados ['CostPrice'] );

		if ( empty ( $sku_dados ['ManufacturerCode'] ) ) {
			// valida o código do mandriva
			throw new Exception ( 'Código do mandriva não informado' );
		}

		if ( strlen ( $sku_dados ['ManufacturerCode'] ) > 10 ) {
			// valida o código do mandriva
			throw new Exception ( 'Código do madriva maior do que o esperado' );
		}

		if ( ! in_array ( $sku_dados ['ManufacturerCode'], $this->_array_produtos_pai ) ) {

			//consultar vtex para trazer ProductId do Pai
			$produto_pai_vtex = $this->_client->ProductGetByRefId ( ltrim ( $sku_dados ['ManufacturerCode'], '0' ) );
			if ( empty ( $produto_pai_vtex ['ProductGetByRefIdResult'] ) ) {
				$produto_pai_vtex = $this->_client->ProductGet ( ltrim ( $sku_dados ['ManufacturerCode'], '0' ) );
				if ( empty ( $produto_pai_vtex ['ProductGet'] ) ) {
					throw new DomainException ( "Produto Pai {$sku_dados ['ManufacturerCode']} não encontrado na Vtex" );
				}
			}

			if ( empty ( $produto_pai_vtex ['ProductGetResult'] ) ) {
				$produto_pai_temp = $produto_pai_vtex ['ProductGetByRefIdResult'];
			} else {
				$produto_pai_temp = $produto_pai_vtex ['ProductGetResult'];
			}

			$id_produto_pai = ltrim ( $sku_dados ['ManufacturerCode'], '0' );

			$produto ['prod_id_extra_1'] = $produto_pai_temp ['Id'];

			try {
				// busca pelo produto pai
				$produto_pai = $this->buscaProdutoPai ( $id_produto_pai, $prod_custo );

				if ( empty ( $produto_pai ) ) {
					// se não houver produto pai cadastrado, adiciona o pai
					$produto_pai = $this->_adicionarProdutoPai ( $produto_pai_temp, $prod_custo );
					echo "Produto Pai {$sku_dados['ManufacturerCode']} adicionado pela importação de filho - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
				} else {

					$produto_pai = $this->_atualizaProdutoPai ( $produto_pai ['prod_id'], $produto_pai_temp );
					echo "Produto Pai {$sku_dados['ManufacturerCode']} atualizado pela importação de filho - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
				}

		// atualizar status pai
			} catch ( Exception $e ) {
				throw new Exception ( $e->getMessage () );
			}
			if ( count ( $this->_array_produtos_pai ) == 1000 ) {
				$this->_array_produtos_pai [0] = $sku_dados ['ManufacturerCode'];
			} else {
				$this->_array_produtos_pai [] = $sku_dados ['ManufacturerCode'];

			}

		} else {
			$produto_pai = $this->consultarDadosProdutosPai ( $sku_dados ['ManufacturerCode'] );
		}

		if ( strlen ( $sku_dados ['Id'] ) <= 7 ) {
			// não importar: decidido durantes os testes com Rafael/Vivian/Camila, em 30/7/12
			return;
		}
		$produto ['prod_peso'] = $db->EscapeString ( number_format ( $sku_dados ['WeightKg'] / 1000, 2, '.', '' ) );
		$produto ['prod_ean_proprio'] = $sku_dados ['Id'];
		$produto ['prod_id_parent'] = $produto_pai ['prod_id'];
		$produto ['prod_nome'] = $db->EscapeString ( $sku_dados ['Name'] );
		$produto ['prod_descricao'] = $db->EscapeString ( $produto_pai ['prod_descricao'] );
		$produto ['prod_sku'] = $produto_pai ['prod_sku'];
		$produto ['prod_ncm'] = $produto_pai ['prod_ncm'];

		$prod_altura = $db->EscapeString ( $sku_dados ['Height'] );
		$prod_largura = $db->EscapeString ( $sku_dados ['Width'] );
		$prod_tamanho = $db->EscapeString ( $sku_dados ['Length'] );
		$prod_part_number = $db->EscapeString ( $produto_pai ['prod_part_number'] );
		$prod_valor = $db->EscapeString ( $sku_dados ['Price'] );
		$prod_id_extra_2 = $db->EscapeString ( $sku_dados ['RefId'] );

		$sql = "INSERT INTO produtos (cli_id, amb_id, prod_alt, prod_comp, prod_larg, prod_controle, prod_descricao, prod_minimo, prod_nome, prod_part_number,
					prod_peso, prod_sku, prod_valor, prod_custo, prod_risco, prod_rotat, prod_class, prod_id_parent, prod_ean_proprio, prod_ncm,prod_id_extra_2)
					VALUES ({$this->_cli_id}, 1, '{$prod_altura}', '{$prod_tamanho}', '{$prod_largura}', 1, '{$produto ['prod_descricao']}', 1,
					'{$produto ['prod_nome']}', '{$prod_part_number}', '{$produto ['prod_peso']}', '{$produto ['prod_sku']}', '{$prod_valor}', '{$prod_custo}', 1, 1, 'A',
					{$produto ['prod_id_parent']}, '{$produto ['prod_ean_proprio']}', '{$produto ['prod_ncm']}','{$prod_id_extra_2}')";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( "Erro sistêmico ao inserir produto filho" );
		}
	}

	/**
	 * Função para Atualizar um Produto Pai
	 * @param string $id_vtex Id do Produto no VTEX
	 * @return retorna o dados do produto_pai
	 */
	private function _atualizaProdutoPai ( $prod_id, $produto_temp ) {

		$db = Db_Factory::getDbWms ();
		if ( ! ctype_digit ( $produto_temp ['RefId'] ) ) {
			throw new InvalidArgumentException ( 'ID do produto pai Inválido ' );
		}

		$prod_id = trim ( $prod_id );
		if ( ! ctype_digit ( $prod_id ) ) {
			throw new InvalidArgumentException ( 'ID do produto wms Inválido ' );
		}

		try {
			$produto_temp ['TaxCode'] = str_replace ( array ( '.', ',', '-', '/' ), '', $produto_temp ['TaxCode'] ); // remove possiveis caracteres especiais do NCM


			if ( empty ( $produto_temp ['DescriptionShort'] ) ) {
				// throw new DomainException ( "Produto Pai {$id_vtex} não contém a referência do fornecedor" );
			}
			$nome_produto = $db->EscapeString ( $produto_temp ['Name'] );
			$desc_produto = $db->EscapeString ( $produto_temp ['Description'] );
			$ncm_produto = $db->EscapeString ( $produto_temp ['TaxCode'] );
			$part_number = $db->EscapeString ( $produto_temp ['DescriptionShort'] );
			$prod_id_extra_1 = $db->EscapeString ( $produto_temp ['Id'] );

			if ( $ncm_produto == 'H' ) {
				throw new DomainException ( 'Erro ao atualizar produto H' );
			}
			echo "Atualizacao Pai: Nome:{$nome_produto} Descricao: {$desc_produto}" . PHP_EOL;
			$sql = "UPDATE produtos SET prod_descricao = '{$desc_produto}', prod_nome = '{$nome_produto}', prod_ncm = '{$ncm_produto}',	prod_part_number = '{$part_number}', prod_id_extra_1 = '{$prod_id_extra_1}'
					WHERE prod_id = {$prod_id}";
			$res = $db->Execute ( $sql );
			if ( ! $res ) {
				throw new RuntimeException ( 'Erro ao inserir produto pai' );
			}

			$produto_pai ['prod_id'] = $prod_id;
			$produto_pai ['prod_nome'] = $nome_produto;
			$produto_pai ['prod_descricao'] = $desc_produto;
			$produto_pai ['prod_sku'] = $produto_temp ['RefId'];
			$produto_pai ['prod_ncm'] = $ncm_produto;
			$produto_pai ['prod_part_number'] = $part_number;
			$produto_pai ['prod_id_extra_1'] = $prod_id_extra_1;

			//atualizar produtos pai de outras contas
			$sql = "SELECT prod_id,cli_id FROM produtos WHERE prod_sku='{$produto_pai['prod_sku']}' AND cli_id IN (61,62,63,68,69,70,71) AND prod_id_parent IS NULL";
			$res = $db->Execute ( $sql );
			if ( ! $res ) {
				throw new RuntimeException ( "Erro ao procurar produtos Pai das outras contas" );
			}
			if ( $db->NumRows ( $res ) > 0 ) {
				$row = $db->FetchAssoc ( $res );
				while ( $row ) {
					$sql_update = "UPDATE produtos SET prod_descricao = '{$desc_produto}', prod_nome = '{$nome_produto}', prod_ncm = '{$ncm_produto}', prod_id_extra_1 = '{$prod_id_extra_1}',prod_part_number = '{$part_number}'
					WHERE prod_id = {$row['prod_id']}";
					$res_update = $db->Execute ( $sql_update );
					if ( ! $res_update ) {
						throw new RuntimeException ( "Erro sistêmico ao atualizar produto pai" );
					}
					$row = $db->FetchAssoc ( $res );
				}
			}

			return $produto_pai;
		} catch ( Exception $e ) {
			$this->_vtex->setErroNovo ( array ( "Id" => $produto_temp ['RefId'], "Metodo" => "_adicionarProdutoPai", "DescricaoErro" => $e->getMessage () ), "Produto_Pai" );
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Atualiza os dados de um determinado sku
	 * @param int $prod_id Id do produto no WMS
	 * @param Array $sku_dados dados do Sku
	 * @return retorna mensagem em caso de erro
	 */
	private function _atualizaProdutoFilho ( $prod_id, $sku_dados ) {

		if ( ! is_array ( $sku_dados ) ) {
			throw new Exception ( 'Dados do SKU inválidos' );
		}

		$db = Db_Factory::getDbWms ();
		$prod_custo = $db->EscapeString ( $sku_dados ['CostPrice'] );

		if ( empty ( $sku_dados ['ManufacturerCode'] ) ) {
			// valida o código do mandriva
			throw new Exception ( 'Código do mandriva não informado' );
		}

		if ( strlen ( $sku_dados ['ManufacturerCode'] ) > 10 ) {
			// valida o código do mandriva
			throw new Exception ( 'Código do madriva maior do que o esperado' );
		}

		$produto ['prod_peso'] = number_format ( $sku_dados ['WeightKg'] / 1000, 2, '.', '' );

		$produto ['prod_id_extra_1'] = $sku_dados ['RefId'];
		$produto ['prod_ean_proprio'] = $sku_dados ['Id'];

		if ( ! in_array ( $sku_dados ['ManufacturerCode'], $this->_array_produtos_pai ) ) {

			try {

				//consultar dados do Pai na VTEX
				//consultar vtex para trazer ProductId do Pai
				$produto_pai_vtex = $this->_client->ProductGetByRefId ( ltrim ( $sku_dados ['ManufacturerCode'], '0' ) );
				if ( empty ( $produto_pai_vtex ['ProductGetByRefIdResult'] ) ) {
					$produto_pai_vtex = $this->_client->ProductGet ( ltrim ( $sku_dados ['ManufacturerCode'], '0' ) );
					if ( empty ( $produto_pai_vtex ['ProductGet'] ) ) {
						throw new DomainException ( "Produto Pai {$sku_dados ['ManufacturerCode']} não encontrado na Vtex" );
					}
				}

				if ( empty ( $produto_pai_vtex ['ProductGetResult'] ) ) {
					$produto_pai_temp = $produto_pai_vtex ['ProductGetByRefIdResult'];
				} else {
					$produto_pai_temp = $produto_pai_vtex ['ProductGetResult'];
				}

				// busca pelo produto pai
				$produto_pai = $this->buscaProdutoPai ( $produto_pai_temp ['RefId'], $prod_custo );
				if ( empty ( $produto_pai ) ) {

					//verificar se o sku e id_extra_1 são iguais
					$sql = "SELECT prod_id FROM produtos WHERE prod_sku='{$sku_dados['ManufacturerCode']}' AND cli_id IN(60,61,62,63,68,69,70,71) AND prod_id_parent IS NULL";
					$res = $db->Execute ( $sql );
					if ( ! $res ) {
						throw new RuntimeException ( 'Erro ao consultar dados de sku e prod_id_extra_1' );
					}
					if ( $db->NumRows ( $res ) == 0 ) {
						throw new DomainException ( 'Produto pai não cadastrado' );

					}
					$row = $db->FetchAssoc ( $res );
					while ( $row ) {

						$sql_update = "UPDATE produtos SET prod_id_extra_1 = '{$produto_pai_temp ['Id']}'
					WHERE prod_id = {$row['prod_id']}";
						$res_update = $db->Execute ( $sql_update );
						if ( ! $res_update ) {
							throw new RuntimeException ( "Erro sistêmico ao atualizar prod_id_extra_1 do prod_id = {$row['prod_id']}" );
						}
						$row = $db->FetchAssoc ( $res );
					}

					//nova busca de produto pai...
					$produto_pai = $this->buscaProdutoPai ( $produto_pai_temp ['Id'], $prod_custo );

				}
				// atualizar status pai
				$produto_pai = $this->_atualizaProdutoPai ( $produto_pai ['prod_id'], $produto_pai_temp );
				echo "Produto Pai {$sku_dados['ManufacturerCode']} atualizado pela atualizacao do filho {$sku_dados ['Id']} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			} catch ( Exception $e ) {

				$consultaProdId = "SELECT * from vtex_acoes where vtacoes_id_vtex = '{$sku_dados ['Id']}' AND vtacoes_metodo = '_atualizaProdutoFilho'";
				$resConsultaProdId = $db->Execute ( $consultaProdId );

				if ( $db->NumRows ( $resConsultaProdId ) == 0 ) {
					$sqlInsert = "INSERT INTO vtex_acoes( cli_id, vtacoes_tipo, vtacoes_id_vtex, vtacoes_metodo)VALUES( {$this->_cli_id}, 'Produto', '{$sku_dados ['Id']}', '_atualizaProdutoFilho')";
					$resInsert = $db->Execute ( $sqlInsert );
					if ( ! $resInsert ) {
						throw new RuntimeException ( 'Erro ao inserir pedido' );
					}
				}
				throw new Exception ( $e->getMessage () );
			}
			if ( count ( $this->_array_produtos_pai ) == 1000 ) {
				$this->_array_produtos_pai [0] = $sku_dados ['ManufacturerCode'];
			} else {
				$this->_array_produtos_pai [] = $sku_dados ['ManufacturerCode'];

			}

		} else {
			$produto_pai = $this->consultarDadosProdutosPai ( $sku_dados ['ManufacturerCode'] );
		}

		$consultaProdId = "SELECT * from vtex_acoes where vtacoes_id_vtex = '{$sku_dados ['Id']}' AND vtacoes_metodo = '_atualizaProdutoFilho'";
		$resConsultaProdId = $db->Execute ( $consultaProdId );

		if ( $db->NumRows ( $resConsultaProdId ) > 0 ) {
			$sql_delete = "DELETE FROM vtex_acoes WHERE vtacoes_id_vtex = '{$sku_dados ['Id']}' AND vtacoes_metodo = '_atualizaProdutoFilho'";
			$resDelete = $db->Execute ( $sql_delete );
			if ( ! $resDelete ) {
				throw new RuntimeException ( 'Erro ao deletar pedido' );
			}
		}

		$produto ['prod_id_parent'] = $produto_pai ['prod_id'];
		$produto ['prod_nome'] = $db->EscapeString ( $sku_dados ['Name'] );
		$produto ['prod_descricao'] = $db->EscapeString ( $produto_pai ['prod_descricao'] );
		$produto ['prod_sku'] = $produto_pai ['prod_sku'];
		$produto ['prod_ncm'] = $produto_pai ['prod_ncm'];

		$prod_altura = $db->EscapeString ( $sku_dados ['Height'] );
		$prod_largura = $db->EscapeString ( $sku_dados ['Width'] );
		$prod_tamanho = $db->EscapeString ( $sku_dados ['Length'] );
		$prod_part_number = $db->EscapeString ( $produto_pai ['prod_part_number'] );
		$prod_ultima_atualizacao = $db->EscapeString ( str_replace ( 'T', ' ', $sku_dados ['DateUpdated'] ) );
		$prod_valor = $db->EscapeString ( $sku_dados ['Price'] );
		echo "Atualizacao Filho: Nome: {$produto['prod_nome']} Descricao:  {$produto['prod_descricao']}" . PHP_EOL;
		$sql = "UPDATE produtos SET prod_alt='{$prod_altura}', prod_comp='{$prod_tamanho}', prod_larg='{$prod_largura}', prod_descricao='{$produto ['prod_descricao']}',
				prod_nome='{$produto ['prod_nome']}', prod_part_number='{$prod_part_number}', prod_ultima_atualizacao='{$prod_ultima_atualizacao}',  prod_id_parent = {$produto ['prod_id_parent']},
				prod_peso='{$produto ['prod_peso']}', prod_sku='{$produto ['prod_sku']}', prod_custo='{$prod_custo}', prod_valor='{$prod_valor}', prod_ean_proprio='{$produto ['prod_ean_proprio']}', prod_ncm='{$produto ['prod_ncm']}'
				WHERE prod_id = {$prod_id} ";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( "Erro sistêmico ao atualizar produto filho" );
		}

		if ( $this->_cli_id == 60 || $this->_cli_id == 68 ) {

			//consultar produto filho na conta Ri Happy Ecommerce Barueri
			$sql = "SELECT prod_id, prod_sku FROM produtos WHERE prod_ean_proprio = '{$sku_dados['Id']}' AND cli_id=68";
			$res = $db->Execute ( $sql );
			if ( ! $res ) {
				throw new RuntimeException ( "Erro sistêmico ao procurar produto filho - Barueri" );
			}
			if ( $db->AffectedRows () > 0 ) {
				$row = $db->FetchAssoc ( $res );
				$prod_id_barueri = $row ['prod_id'];
				$prod_sku_barueri = $row ['prod_sku'];

				//verificar prod_id_parent do EAN E-commerce


				$sql = "SELECT prod_id FROM produtos WHERE cli_id=68 AND prod_sku='{$prod_sku_barueri}'AND prod_ean_proprio IS NULL";
				$res = $db->Execute ( $sql );
				if ( ! $res ) {
					throw new RuntimeException ( "Erro ao consultar produto Pai Barueri" );
				}
				if ( $db->NumRows ( $res ) > 0 ) {
					$row = $db->FetchAssoc ( $res );
					$prod_id_parent_barueri = $row ['prod_id'];
				}
				 echo "Atualizacao Filho Barueri: Nome: {$produto['prod_nome']} Descricao:  {$produto['prod_descricao']}".PHP_EOL;
				$sql = "UPDATE produtos SET prod_alt='{$prod_altura}', prod_comp='{$prod_tamanho}', prod_larg='{$prod_largura}', prod_descricao='{$produto ['prod_descricao']}',
				prod_nome='{$produto ['prod_nome']}', prod_part_number='{$prod_part_number}', prod_ultima_atualizacao='{$prod_ultima_atualizacao}',  prod_id_parent = {$prod_id_parent_barueri},
				prod_peso='{$produto ['prod_peso']}', prod_sku='{$produto ['prod_sku']}', prod_custo='{$prod_custo}', prod_valor='{$prod_valor}', prod_ean_proprio='{$produto ['prod_ean_proprio']}', prod_ncm='{$produto ['prod_ncm']}'
				WHERE prod_id = {$prod_id_barueri} ";
				$res = $db->Execute ( $sql );
				if ( ! $res ) {
					throw new RuntimeException ( "Erro sistêmico ao atualizar produto filho - Barueri" );
				}
			}
		}
	}

	/**
	 *
	 * verifica o controle antes de iniciar a atualização de produtos
	 * @throws RuntimeException
	 */
	private function _consultaControleBaixa () {

		$db = Db_Factory::getDbWms ();

		$sql = "SELECT vtprodcon_id, vtprodcon_dateupdated FROM vtex_produtos_controle WHERE cli_id={$this->_cli_id} ORDER BY vtprodcon_id DESC LIMIT 1";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro sistêmico ao consultar controle de atualização de produtos' );
		}

		$array_retorno = array ();

		if ( $db->NumRows ( $res ) == 0 ) {
			// no caso da primeira baixa, utilizar dados padrão
			$array_retorno ['startingStockKeepingUnitId'] = 1;
			$array_retorno ['dateUpdated'] = '2012-01-01T00:00:00';

		} else {
			$row = $db->FetchAssoc ( $res );
			$array_retorno ['startingStockKeepingUnitId'] = $row ['vtprodcon_sku_id'];
			$array_retorno ['dateUpdated'] = $row ['vtprodcon_dateupdated'];
		}

		return $array_retorno;
	}

	/**
	 *
	 * grava o historico de atualização de produtos
	 * @param int $qtd_atualizada
	 * @param int $qtd_inserida
	 * @param int $qtd_erros
	 * @throws RuntimeException
	 */
	private function _gravaControleBaixas ( $qtd_atualizada, $qtd_inserida, $qtd_erros, $vtprodcon_dateupdated ) {

		$db = Db_Factory::getDbWms ();

		$sql = "INSERT INTO vtex_produtos_controle
				(cli_id, vtprodcon_dateupdated, vtprodcon_qtd_atualizada, vtprodcon_qtd_inserida, vtprodcon_qtd_erros)
				VALUES ({$this->_cli_id}, '{$vtprodcon_dateupdated}', {$qtd_atualizada}, {$qtd_inserida}, {$qtd_erros})";

		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao gravar controle de produto vtex' );
		}
	}

	/**
	 *
	 * retorna o Maior ID do SKU do cliente
	 */
	private function _getMaiorSku () {

		$db = Db_Factory::getDbWms ();

		// captura o maior ean proprio
		$sql = "SELECT MAX(convert(prod_ean_proprio, signed)) as prod_ean_proprio FROM produtos WHERE cli_id = {$this->_cli_id}";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro sistêmico ao consultar maior ID do sku' );
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			return false;
		}

		$row = $db->FetchAssoc ( $res );
		return $row ['prod_ean_proprio'];
	}

	/**
	 * Inicializa webservice VTEX.
	 */
	protected function _initVtex (  ) {

		// Gera objeto de conexão WebService
		if ( isset ( $this->_vtex ) ) {
			unset ( $this->_vtex );
		}
		$this->_vtex = Model_Belissima_Vtex_Vtex::getVtex();
		$this->_client = $this->_vtex->_client;
	}

	/**
	 *
	 * Verifica se a ultima atualização do sku é menor que a atual
	 * @param int $prod_id
	 * @param datetime $date_updated
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws DomainException
	 */
	private function _verificaAtualizacao ( $prod_id, $date_updated ) {

		$prod_id = trim ( $prod_id );
		if ( ! ctype_digit ( $prod_id ) ) {
			throw new InvalidArgumentException ( 'ID do produto inválido' );
		}

		$db = Db_Factory::getDbWms ();

		// busca a data da ultima atualização do prod_id
		$sql = "SELECT prod_ultima_atualizacao FROM produtos WHERE prod_id = {$prod_id}";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro sistêmico ao consultar atualização do produto' );
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			throw new DomainException ( 'Produto não localizado' );
		}

		$row = $db->FetchAssoc ( $res );

		if ( empty ( $row ['prod_ultima_atualizacao'] ) ) {
			// se não houver ultima atualização: permite atualizar
			return true;
		}

		if ( $row ['prod_ultima_atualizacao'] < $date_updated ) {
			// se a data do sku for maior que a data da ultima atualização: permite atualizar
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Função para encontrar o Id de um determinado SKU
	 * @param string sku Sku do produto
	 * @param string cli_id id do cliente
	 * @return retorna o id do produto
	 */
	public function buscaProdutoFilho ( $ean_proprio ) {

		$db = Db_Factory::getDbWms ();
		if ( empty ( $ean_proprio ) ) {
			throw new InvalidArgumentException ( 'Código do produto inválido' );
		}
		$sql1 = "SELECT prod_id FROM produtos WHERE prod_ean_proprio = '{$ean_proprio}' AND cli_id = {$this->_cli_id}";
		$qry_sts = $db->Execute ( $sql1 );
		if ( ! $qry_sts ) {
			throw new RuntimeException ( 'Erro sistêmico ao consultar SKU' );
		}

		if ( $db->NumRows ( $qry_sts ) == 0 ) {
			return false;
		}
		$row_sts = $db->FetchAssoc ( $qry_sts );

		return $row_sts ['prod_id'];

	}

	/**
	 * Função para encontrar o Id de um determinado Produto Pai, Caso não exista adiciona
	 * @param string $id_vetx Id do Produto no VTEX
	 * @param decimal $prod_custo
	 * @return retorna dados do Produto Pai
	 */
	public function buscaProdutoPai ( $id_vtex, $prod_custo = NULL ) {

		$db = Db_Factory::getDbWms ();

		$id_vtex = trim ( $id_vtex );
		if ( ! ctype_digit ( $id_vtex ) ) {
			throw new InvalidArgumentException ( 'SKU do produto inválido' );
		}

		// verificar se o produto pai existe
		$sql = "SELECT prod_id, prod_nome, prod_descricao, prod_sku, prod_ncm, prod_part_number, prod_custo, prod_id_extra_1 FROM produtos WHERE prod_sku = '{$id_vtex}' AND cli_id = {$this->_cli_id} AND prod_id_parent IS NULL";
		$qry = $db->Execute ( $sql );
		if ( ! $qry ) {
			throw new RuntimeException ( 'Erro sistêmico ao buscar Produto' );
		}

		if ( $db->NumRows ( $qry ) == 0 ) {
			return NULL;
		}
		$row = $db->FetchAssoc ( $qry );

		if ( ! empty ( $prod_custo ) ) {
			// verificar custo do produto
			if ( "{$row ['prod_custo']}" != "{$prod_custo}" && ($this->_cli_id == 60 || $this->_cli_id == 68)) {
				$sql = "UPDATE produtos SET prod_custo='{$prod_custo}' WHERE prod_sku = '{$id_vtex}' AND prod_id_parent IS NULL AND cli_id IN (60, 61, 62, 63,68,69,70,71)";
				if ( ! $db->Execute ( $sql ) ) {
					throw new RuntimeException ( 'Erro sistêmico ao atualizar o preço de custo do produto pai' );
				}
			}
		}
		return $row;
	}

	/**
	 *
	 * Consultar dados de produto pai já consultado.
	 * @param string $prod_sku
	 * @throws RuntimeException
	 */
	public function consultarDadosProdutosPai ( $prod_sku ) {

		$db = Db_Factory::getDbWms ();
		$sql = "SELECT prod_id, prod_descricao,prod_sku,prod_ncm FROM produtos WHERE cli_id = {$this->_cli_id} AND prod_sku='{$prod_sku}' AND prod_ean_proprio is NULL";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( "Erro ao consultar produto" );
		}
		$row = $db->FetchAssoc ( $res );

		return $row;
	}

	/**
	 * Importa um determinado sku
	 * @param int id Id do Sku
	 * @return retorna false em caso de erro
	 */
	public function importarProdutoId ( $id ) {

		$id = trim ( $id );
		if ( ! ctype_digit ( $id ) ) {
			throw new Exception ( 'ID Inválido' );
		}
		try {
			// verifica se o filho já existe


			// busca filho
			$sku_dados = $this->_client->StockKeepingUnitGet ( $id );

			if ( ($sku_dados ['StockKeepingUnitGetResult'] == null) || (! is_array ( $sku_dados )) ) {
				throw new Exception ( 'Erro ao buscar SKU' );
			}

			// remover zero à esquerda
			$sku_dados ['StockKeepingUnitGetResult'] ['ManufacturerCode'] = ltrim ( $sku_dados ['StockKeepingUnitGetResult'] ['ManufacturerCode'], '0' );

			if ( is_array ( $sku_dados ) && ! empty ( $sku_dados ['faultcode'] ) ) {

				// lança o erro como exception
				$erro_dados = $sku_dados ['faultstring'] ['!'];
				throw new Exception ( $erro_dados );
			}

			try {
				$prod_id = $this->buscaProdutoFilho ( $sku_dados ['StockKeepingUnitGetResult'] ['Id'] );
				if ( $prod_id == false ) {
					// caso não, tenta adicionar o produto filho
					$this->_adicionarProdutoFilho ( $sku_dados ['StockKeepingUnitGetResult'] );
					echo "EAN proprio {$sku_dados ['StockKeepingUnitGetResult'] ['Id']} adicionado - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
				} else {
					// caso exista, tenta atualizar os dados
					$this->_atualizaProdutoFilho ( $prod_id, $sku_dados ['StockKeepingUnitGetResult'] );
					echo "EAN proprio {$sku_dados ['StockKeepingUnitGetResult'] ['Id']} atualizado - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

				}
			} catch ( Exception $e ) {
				$this->_vtex->setErroNovo ( array ( "Id" => $sku_dados ['StockKeepingUnitGetResult'] ['Id'], "Metodo" => "importarProdutoId", "DescricaoErro" => $e->getMessage () ), "SKU" );
				throw new DomainException ( $e->getMessage () );
			}

			// grava logs de erro se existirem
			$this->_vtex->gravaLogVtex ();

		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa os skus novos ou atualiza a partir de uma determinada data e ID
	 *
	 * @return retorna false em caso de erro
	 */
	public function importarProdutoDataId () {

		$pesquisa = $this->_consultaControleBaixa ();
		$ultima_data = $pesquisa ['dateUpdated'];
		// capturar maior ID do sku
		$ultimo_sku_id = 10000000;

		$this->importarProdutoDataIdAction ( $ultima_data, $ultimo_sku_id, true );

	}

	/**
	 * Importação de produtos VTEX.
	 * @param datetime $ultima_data
	 * @param int $ultimo_sku_id
	 * @param boolean $gravar_log
	 * @throws Exception
	 */
	public function importarProdutoDataIdAction ( $ultima_data, $ultimo_sku_id = '10000000', $gravar_log = true ) {

		$db = Db_Factory::getDbWms ();
		try {

			// capturar maior ID do sku
			//	$maior_ean = $this->_getMaiorSku ();
			$maior_ean = false;

			$qtd_atualizada = $qtd_inserida = $qtd_erros = 0;

			$ultima_data = str_replace ( 'T', ' ', $ultima_data );

			//Subtrair 10 minutos do horário atual para a futura busca
			$ultima_data = strtotime ( "$ultima_data - 10 minutes" );
			$ultima_data = date ( "Y-m-d H:i:s", $ultima_data );
			$ultima_data = str_replace ( ' ', 'T', $ultima_data );

			while ( $ultimo_sku_id < $maior_ean || $maior_ean == false ) {
				$reinicia_sku = false;
				// busca filhos por data de inserção
				echo "Buscando 20 registros a partir de {$ultima_data} / SKU {$ultimo_sku_id} ... " . PHP_EOL;
				$sku_dados = $this->_client->StockKeepingUnitGetAllFromUpdatedDateAndId ( $ultima_data, $ultimo_sku_id, 20 );
				//$sku_dados = $this->_client->StockKeepingUnitGetAllFromUpdatedDateAndId ( '2012-07-13T00:20:00', $ultimo_sku_id, 100 );


				if ( ! is_array ( $sku_dados ) ) {
					$sku_dados = $db->EscapeString ( $sku_dados );
					if ( $gravar_log ) {
						$this->_vtex->setErroNovo ( array ( "Id" => $ultimo_sku_id, "Metodo" => "importarProdutoDataId", "DescricaoErro" => $sku_dados ), "SKU" );
						$this->_vtex->gravaLogVtex ();
					}
					break;
				}

				$array_sku = $sku_dados ['StockKeepingUnitGetAllFromUpdatedDateAndIdResult'] ['StockKeepingUnitDTO'];

				if ( ! is_array ( $array_sku ) ) {
					break;
				}

				$dados_itens = $this->_vtex->trataArrayDto ( $sku_dados ['StockKeepingUnitGetAllFromUpdatedDateAndIdResult'] ['StockKeepingUnitDTO'] );
				$array_erros = array ();
				foreach ( $dados_itens as $item ) {
					try {

						// remover zero à esquerda
						$item ['ManufacturerCode'] = ltrim ( $item ['ManufacturerCode'], '0' );

						// verifica se o produto filho existe
						$prod_id = $this->buscaProdutoFilho ( $item ['Id'] );

						echo $item ['Id'] . ' - ' . $item ['DateUpdated'] . PHP_EOL;
						$ultimo_sku_id = $item ['Id'];
						$DateUpdated = str_replace ( 'T', ' ', $item ['DateUpdated'] );

						if ( $prod_id == false ) {
							// caso não, tenta adicionar o produto filho
							$this->_adicionarProdutoFilho ( $item );
							echo "EAN próprio {$item ['Id']} adicionado - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

							$qtd_inserida ++;
						} else {
							// caso exista, tenta atualizar os dados
							$atualizar = $this->_verificaAtualizacao ( $prod_id, $DateUpdated );
							// verificar se devemos atualizar
							if ( $atualizar ) {
								$this->_atualizaProdutoFilho ( $prod_id, $item );
								echo "EAN próprio {$item ['Id']} atualizado - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
								$qtd_atualizada ++;
							}
						}

						$ultima_data = str_replace ( 'T', ' ', $ultima_data );

					} catch ( Exception $e ) {
						// guarda erros individuais
						if ( $gravar_log ) {
							$this->_vtex->setErroNovo ( array ( "Id" => $item ['Id'], "Metodo" => "importarProdutoDataId", "DescricaoErro" => $e->getMessage () ), "SKU" );
						}
						$qtd_erros ++;
					}
				}
				if ( $reinicia_sku ) {
					$ultima_data = $inicial_data;
					$ultimo_sku_id = 10000000;
					echo "continuei do {$ultimo_sku_id} |||| {$ultima_data}" . PHP_EOL;
				} else {
					$ultimo_sku_id ++;
					$ultima_data = str_replace ( ' ', 'T', $ultima_data );
					echo "continuei do {$ultimo_sku_id} |||| {$ultima_data}" . PHP_EOL;
				}

				echo PHP_EOL;

				$this->_initVtex ( $this->_cli_id );

			}

			if ( ! empty ( $qtd_inserida ) || ! empty ( $qtd_atualizada ) ) {

				$data_atual = date ( "Y-m-d H:i:s" );
				$data_atual = str_replace ( ' ', 'T', $data_atual );

				// gravar log de consulta
				if ( $gravar_log ) {
					$this->_gravaControleBaixas ( $qtd_atualizada, $qtd_inserida, $qtd_erros, $data_atual );
				}
			}

		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa os skus novos ou atualiza a partir de uma determinada data
	 * @param date dateUpdated Data para pesquisa
	 * @return retorna false em caso de erro
	 */
	public function importarProdutoData ( $data ) {

		if ( empty ( $data ) ) {
			throw new Exception ( 'Data Inválida' );
		}
		try {
			// busca filhos por data de inserção
			$sku_dados = $this->_client->StockKeepingUnitGetAllFromUpdatedDate ( $data );

			if ( ! is_array ( $sku_dados ['StockKeepingUnitGetAllFromUpdatedDateResult'] ['StockKeepingUnitDTO'] ) ) {
				throw new Exception ( 'Erro ao buscar Produtos ' . __LINE__ );
			}

			$dados_itens = $this->_vtex->trataArrayDto ( $sku_dados ['StockKeepingUnitGetAllFromUpdatedDateResult'] ['StockKeepingUnitDTO'] );
			$array_erros = array ();
			foreach ( $dados_itens as $item ) {
				try {
					// verifica se o produto filho existe


					$prod_id = $this->buscaProdutoFilho ( $item ['Id'] );
					if ( $prod_id == false ) {
						// caso não, tenta adicionar o produto filho
						$this->_adicionarProdutoFilho ( $item );
					} else {
						// caso exista, tenta atualizar os dados
						$this->_atualizaProdutoFilho ( $prod_id, $item );
					}

				} catch ( Exception $e ) {
					// guarda erros individuais
					$array_erros [] = "Item: {$item ['Id']}: " . $e->getMessage ();
					$this->_vtex->setErroNovo ( array ( "Id" => $item ['Id'], "Metodo" => "importarProdutoData", "DescricaoErro" => $e->getMessage () ), "SKU" );
				}
			}

			if ( ! empty ( $array_erros ) ) {
				$str_erros = implode ( ' ; ', $array_erros );
				throw new DomainException ( $str_erros );
			}

			// grava logs de erro se existirem
			$this->_vtex->gravaLogVtex ();

		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa os skus novos ou atualiza a partir de uma determinada data
	 * @param date dateUpdated Data para pesquisa
	 * @return retorna false em caso de erro
	 */
	public function importarProdutoPaiId ( $idProduct ) {

		$idProduct = trim ( $idProduct );
		if ( ! ctype_digit ( $idProduct ) ) {
			throw new InvalidArgumentException ( 'ID produto inválido' );
		}

		if ( strlen ( $idProduct ) > 10 ) {
			throw new InvalidArgumentException ( 'ID Mandriva inválido' );
		}

		$qtd_inserida = $qtd_atualizada = 0;

		try {
			// busca filhos por data de inserção
			$produto_dados = $this->_client->ProductGetByRefId ( $idProduct );

			if ( empty ( $produto_dados ['ProductGetByRefIdResult'] ) ) {
				$produto_dados = $this->_client->ProductGet ( $idProduct );
				if ( empty ( $produto_dados ['ProductGet'] ) ) {
					throw new DomainException ( "Produto Pai {$idProduct} não encontrado na Vtex" );
				}
			}

			if ( empty ( $produto_dados ['ProductGetResult'] ) ) {
				$produto_temp = $produto_dados ['ProductGetByRefIdResult'];

			} else {
				$produto_temp = $produto_dados ['ProductGetResult'];
			}

			$dados_itens = $this->_vtex->trataArrayDto ( $produto_temp );
			$array_erros = array ();
			foreach ( $dados_itens as $item ) {
				try {
					// verifica se o produto filho existe


					$prod_id = $this->buscaProdutoPai ( $item ['RefId'] );
					if ( empty ( $prod_id ) ) {
						// caso não, tenta adicionar o produto filho
						$this->_adicionarProdutoPai ( $item, 0 );
						echo "Produto Pai {$item['RefId']} adicionado pela importacao de produto pai - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
						$qtd_inserida ++;

					} else {
						// caso exista, tenta atualizar os dados
						//verificar se os dados de nome e descrição da VTEX são os mesmos que estão cadastrados no WMS.


						if ( ($prod_id ['prod_nome'] != $item ['Name']) || ($prod_id ['prod_descricao'] != $item ['Description']) || ($prod_id ['prod_ncm'] != $item ['TaxCode']) || ($prod_id ['prod_part_number'] != $item ['DescriptionShort']) || ($prod_id ['prod_part_number'] != $item['Id']) ) {
							echo 'Atualizar produto' . PHP_EOL;
							$this->_atualizaProdutoPai ( $prod_id ['prod_id'], $item );
							echo "Produto Pai {$item['RefId']} atualizado pela atualizacao de produto pai - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
							$qtd_atualizada ++;
						}

						echo '.' . PHP_EOL;
					}

				} catch ( Exception $e ) {
					// guarda erros individuais
					$array_erros [] = "Item: {$item ['Id']}: " . $e->getMessage ();
					$this->_vtex->setErroNovo ( array ( "Id" => $item ['Id'], "Metodo" => "importarProdutoPaiId", "DescricaoErro" => $e->getMessage () ), "Produto_Pai" );
				}
			}

			if ( ! empty ( $array_erros ) ) {
				$str_erros = implode ( ' ; ', $array_erros );
				throw new DomainException ( $str_erros );
			}

			// grava logs de erro se existirem
			$this->_vtex->gravaLogVtex ();

		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa novos ou atualiza Produtos pais sem filhos
	 * @return retorna false em caso de erro
	 */
	public function importarProdutoPaiDataId () {

		try {

			$pesquisa = $this->_consultaControleBaixa ();

			// capturar maior ID do sku
			// $maior_ean = $this->_getMaiorSku ();
			$maior_ean = 9999999;
			$ultimo_sku_id = 1;

			$qtd_atualizada = $qtd_inserida = $qtd_erros = 0;

			$ultima_data = $pesquisa ['dateUpdated'];

			// capturar produtos com data base de 2 dias anteriores
			$data_pesquisa = mktime ( NULL, NULL, NULL, date ( 'm' ), date ( 'd' ) - 2, date ( 'Y' ) );
			echo $data_hora_pesquisa = date ( 'Y-m-d', $data_pesquisa ) . 'T' . '00:00:00';

			while ( $ultimo_sku_id < $maior_ean ) {
				$reinicia_sku = false;
				// busca filhos por data de inserção
				echo "Buscando 20 registros a partir de {$data_hora_pesquisa} / SKU {$ultimo_sku_id} ... " . PHP_EOL;
				$sku_dados = $this->_client->StockKeepingUnitGetAllFromUpdatedDateAndId ( $data_hora_pesquisa, $ultimo_sku_id, 20 );
				//$sku_dados = $this->_client->StockKeepingUnitGetAllFromUpdatedDateAndId ( '2012-08-03T00:20:00', $ultimo_sku_id, 20 );


				$array_sku = $sku_dados ['StockKeepingUnitGetAllFromUpdatedDateAndIdResult'] ['StockKeepingUnitDTO'];

				if ( ! is_array ( $array_sku ) ) {
					break;
				}

				$dados_itens = $this->_vtex->trataArrayDto ( $sku_dados ['StockKeepingUnitGetAllFromUpdatedDateAndIdResult'] ['StockKeepingUnitDTO'] );

				$array_erros = array ();
				foreach ( $dados_itens as $item ) {

					try {

						echo $item ['Id'] . ' - ' . $item ['DateUpdated'] . PHP_EOL;

						if ( strlen ( $item ['Id'] ) > 7 ) {
							echo 'ID maior que 7 digitos' . PHP_EOL;
							return false;
						}

						$ultimo_sku_id = $item ['Id'];
						$DateUpdated = str_replace ( 'T', ' ', $item ['DateUpdated'] );

						// tenta importar o pai
						$this->importarProdutoPaiId ( $item ['Id'] );

					} catch ( Exception $e ) {
						// guarda erros individuais
						$this->_vtex->setErroNovo ( array ( "Id" => $item ['Id'], "Metodo" => "importarProdutoDataId", "DescricaoErro" => $e->getMessage () ), "SKU" );
						$qtd_erros ++;
					}
				}

				// grava logs de erro se existirem
				$this->_vtex->gravaLogVtex ();

				// return $ultimo_sku_id;
				$ultimo_sku_id ++;
				echo "continuei do {$ultimo_sku_id} |||| {$data_hora_pesquisa}" . PHP_EOL;

				echo PHP_EOL;
			}

		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	public function atualizaProdutoPendente () {

		$db = Db_Factory::getDbWms ();

		echo "Buscando produtos pendentes de atualização";

		$sql = "SELECT cli_id, vtacoes_tipo, vtacoes_id_vtex, vtacoes_metodo FROM vtex_acoes WHERE cli_id={$this->_cli_id} AND vtacoes_metodo='_atualizaProdutoFilho'";
		$res = $db->Execute ( $sql );

		if ( ! $res ) {
			throw new RuntimeException ( "Erro sistêmico ao buscar produtos" );
		}

		if ( $db->NumRows ( $res ) == 0 ) {
			echo 'Nenhum item pendente a atualizar' . PHP_EOL;
			return;
		}

		$row = $db->FetchAssoc ( $res );
		while ( $row ) {
			$vtacoes_tipo = $row ['vtacoes_tipo'];
			$vtacoes_id_vtex = $row ['vtacoes_id_vtex'];
			$vtacoes_metodo = $row ['vtacoes_metodo'];

			if ( $vtacoes_metodo == '_atualizaProdutoFilho' ) {
				$this->importarProdutoId ( $vtacoes_id_vtex );
			}
			$row = $db->FetchAssoc ( $res );
		}
	}

	/**
	 * Importar produtos filhos através do código do Pai
	 * return false caso haja erro
	 */
	public function importaProdutoFilhoByManufacturerCode ( $manufacturer ) {

		$manufacturer = trim ( $manufacturer );
		if ( ! ctype_digit ( $manufacturer ) ) {
			throw new Exception ( 'SKU Inválido' );
		}

		try {
			// busca filho através do Código do Pai
			$sku_dados = $this->_client->StockKeepingUnitGetByManufacturerCode ( $manufacturer );

			if ( ($sku_dados ['StockKeepingUnitGetByManufacturerCodeResult'] == null) || (! is_array ( $sku_dados )) ) {
				throw new Exception ( 'Erro ao buscar SKU' );
			}

			if ( is_array ( $sku_dados ) && ! empty ( $sku_dados ['faultcode'] ) ) {

				// lança o erro como exception
				$erro_dados = $sku_dados ['faultstring'] ['!'];
				throw new Exception ( $erro_dados );
			}

			$dados_itens = $this->_vtex->trataArrayDto ( $sku_dados ['StockKeepingUnitGetByManufacturerCodeResult'] ['StockKeepingUnitDTO'] );

			foreach ( $dados_itens as $item ) {
				echo "Importando Filho {$item ['Id']}" . PHP_EOL;
				$result = $this->importarProdutoId ( $item ['Id'] );
				echo PHP_EOL;
			}

		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 *
	 * Retorna os possiveis erros que ocorreram no meio do processo
	 */
	public function getErrosProcessamento () {

		// verifica se o array que grava os erros está vazio
		$erro = $this->_vtex->getErros ();
		if ( ! empty ( $erro ) ) {
			return $erro;
		}
	}
}
?>

