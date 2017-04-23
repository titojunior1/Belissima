<?php
/**
 * 
 * Classe de gerenciamento de Pedidos de Saída com a VTEX
 * @author Tito Junior
 *
 */
class Model_Wpr_Vtex_Pedido {
	
	/**
	 * 
	 * Relação com dados dos pedido
	 * @var Array
	 */
	private $_array_pedidos = array ();
	
	/**
	 * Caracteres especiais
	 */
	private $_caracteres_especiais = array ( "\"", "'", "\\", "`" );
	
	/**
	 * 
	 * Relação com dados dos pedido
	 * @var Model_Wms_Vtex_Produto
	 */
	private $_model_produto = array ();
	
	/**
	 * 
	 * Objeto Vtex
	 * @var Model_Wpr_Vtex_Vtex
	 */
	private $_vtex;
	
	/**
	 * Variavel  de Objeto da Classe StubVtex.
	 *
	 * @var Model_Wpr_Vtex_StubVtex
	 */
	public $_client;

	/**
	 * Construtor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 * @param int $cliente Id do Cliente.
	 * @param int  $armazem Armazem do Cliente.
	 */
	public function __construct (  ) {

		if ( empty ( $this->_vtex ) ) {
			// Gera objeto de conexão WebService
			$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex ();
			$this->_client = $this->_vtex->_client;
		}
	}
	/**
	 * Verificar CNPJ
	 * @param int $cnpj
	 * @param bool $formatar
	 * @return string | bool
	 */
	public static function validaCnpj($cnpj, $formatar = false) {
	
		// remove tudo que não for número
		$cnpj = self::Numeros ( $cnpj );
	
		if ($formatar) {
			$cnpj_formatado = substr ( $cnpj, 0, 2 ) . '.' . substr ( $cnpj, 2, 3 ) . '.' . substr ( $cnpj, 5, 3 ) . '/' . substr ( $cnpj, 8, 4 ) . '-' . substr ( $cnpj, 12, 2 );
			return $cnpj_formatado;
		} else {
			// cpf falso
			$array_cnpj_falso = array ( '00000000000000', '11111111111111', '22222222222222', '33333333333333', '44444444444444', '55555555555555', '66666666666666', '77777777777777', '88888888888888', '99999999999999', '12345678912345' );
	
			if (empty ( $cnpj ) || strlen ( $cnpj ) != 14 || in_array ( $cnpj, $array_cnpj_falso )) {
				return false;
			} else {
	
				$rev_cnpj = strrev ( substr ( $cnpj, 0, 12 ) );
				for($i = 0; $i <= 11; $i ++) {
					$i == 0 ? $multiplier = 2 : $multiplier;
					$i == 8 ? $multiplier = 2 : $multiplier;
					$multiply = ($rev_cnpj [$i] * $multiplier);
					$sum = $sum + $multiply;
					$multiplier ++;
				}
	
				$rest = $sum % 11;
				if ($rest == 0 || $rest == 1) {
					$dv1 = 0;
				} else {
					$dv1 = 11 - $rest;
				}
	
				$sub_cnpj = substr ( $cnpj, 0, 12 );
				$rev_cnpj = strrev ( $sub_cnpj . $dv1 );
				unset ( $sum );
	
				for($i = 0; $i <= 12; $i ++) {
					$i == 0 ? $multiplier = 2 : $multiplier;
					$i == 8 ? $multiplier = 2 : $multiplier;
					$multiply = ($rev_cnpj [$i] * $multiplier);
					$sum = $sum + $multiply;
					$multiplier ++;
				}
				$rest = $sum % 11;
	
				if ($rest == 0 || $rest == 1) {
					$dv2 = 0;
				} else {
					$dv2 = 11 - $rest;
				}
	
				if ($dv1 == $cnpj [12] && $dv2 == $cnpj [13]) {
					return true;
				} else {
					return false;
				}
			}
		}
	}
	
	/**
	 * Verificar CPF
	 * @param int $cpf
	 * @param bool $formatar
	 * @return string | bool
	 */
	public static function validaCpf($cpf, $formatar = false) {
		if ($formatar) {
	
			$cpf = self::Numeros ( $cpf );
			$cpf_formatado = substr ( $cpf, 0, 3 ) . '.' . substr ( $cpf, 3, 3 ) . '.' . substr ( $cpf, 6, 3 ) . '-' . substr ( $cpf, 9, 2 );
			return $cpf_formatado;
		} else {
			// cpf falso
			$array_cpf_falso = array ( '00000000000', '11111111111', '22222222222', '33333333333', '44444444444', '55555555555', '66666666666', '77777777777', '88888888888', '99999999999', '12345678912' );
			$dv = 0;
	
			// remove tudo que não for número
			$cpf = self::Numeros ( $cpf );
	
			if (empty ( $cpf ) || strlen ( $cpf ) != 11 || in_array ( $cpf, $array_cpf_falso )) {
				return false;
			} else {
	
				$sub_cpf = substr ( $cpf, 0, 9 );
	
				for($i = 0; $i <= 9; $i ++) {
					$dv += ($sub_cpf [$i] * (10 - $i));
				}
	
				if ($dv == 0) {
					return false;
				}
	
				$dv = 11 - ($dv % 11);
	
				if ($dv > 9) {
					$dv = 0;
				}
	
				if ($cpf [9] != $dv) {
					return false;
				}
	
				$dv *= 2;
	
				for($i = 0; $i <= 9; $i ++) {
					$dv += ($sub_cpf [$i] * (11 - $i));
				}
	
				$dv = 11 - ($dv % 11);
	
				if ($dv > 9) {
					$dv = 0;
				}
	
				if ($cpf [10] != $dv) {
					return false;
				}
	
				return true;
			}
		}
	}
	
	/**
	 * Deixa somente os números
	 * @param string $var
	 * @return string
	 */
	public static function Numeros($var) {
		return preg_replace ( '/[^0-9]/i', '', $var );
	}

	/**
	 * 
	 * Verifica se os produtos do pedido já existem no cadastro, caso não, realiza a importação
	 * @param int $item_id
	 * @throws Exception
	 */
	private function _verificaProdutos ( $item_id ) {

		try {
			if ( ! $this->_model_produto->buscaProdutoFilho ( $item_id ) ) {
				$this->_model_produto->importarProdutoId ( $item_id );
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * 
	 * Retorna a transportadora, baseada no ID da tabela de frete
	 * @param int $freight_id
	 * @throws InvalidArgumentException
	 */
	private function _getTransportadora ( $freight_id ) {

		if ( empty ( $freight_id ) ) {
			throw new InvalidArgumentException ( 'ID da tabela de frete inválido (' . $freight_id . ')' );
		}
		
		$db = Db_Factory::getDbWms ();
		
		// busca o id da tabela de frete na tabela transportadora_codigos_tabela
		$sql = "SELECT trans_id_cli FROM transportadora_codigos_tabela tct
				INNER JOIN transportadora t ON (tct.trans_id = t.trans_id)
				WHERE transtab_codigo = '{$freight_id}' AND trans_status = 1";
		
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao consultar tabela de frete' );
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			return '1'; //código trans_id_cli default da Ri Happy E-commerce
		}
		$row = $db->FetchAssoc ( $res );
		
		// retorna trans_id_cli
		return $row ['trans_id_cli'];
	}

	/**
	 * Processa o array de Pedidos
	 * @param array $dados Array de Dados de pedidos
	 *
	 */
	private function _importarPedidos ( $dados_pedido ) {

		echo "Conectando ao WebService Kpl... " . PHP_EOL;
		$this->_kpl = new Model_Wpr_Kpl_Clientes();
		echo "Conectado!" . PHP_EOL;
		echo PHP_EOL;
		
		$qtdPedidos = count($dados_pedido);
		echo "Pedidos encontrados para integracao: " . $qtdPedidos . PHP_EOL;
			
		// erros
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_precos = array ();
		
		foreach ( $dados_pedido as $i => $d ) {
		
			$dadosCliente = array();
			$dadosPedido = array();
				
			echo PHP_EOL;
				
			// formatar CPF
			$cpfFormatado = $this->Numeros($d->Client->CpfCnpj);
			echo "Tratando dados para cadastro de cliente codigo: " . $cpfFormatado . PHP_EOL;
				
			// formata sexo
			if ( empty ( $d->Client->Gender ) ){
				$sexoCliente = 'tseMasculino';
				$sexoClientePedido = 'M';
			}else{
				$sexoCliente = 'tseFeminino';
				$sexoClientePedido = 'F';
			}
				
			//Manipulando dados para cadastro/atualização de cliente
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Email'] = $d->Client->Email;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['CPFouCNPJ'] = $cpfFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Codigo'] = $cpfFormatado;
				
			//valida se é pessoa PF, caso não é PJ
			$validaCpf = $this->validaCpf($cpfFormatado);
			if ( $validaCpf ){
				$tipoPessoa = 'tpeFisica';
			}else{
				$tipoPessoa = 'tpeJuridica';
			}
				
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['TipoPessoa']	= $tipoPessoa;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Documento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Nome'] = $d->Address->ReceiverName;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['NomeReduzido'] = $d->Address->ReceiverName;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Sexo'] = $sexoCliente;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataNascimento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Telefone'] = substr ( $d->Address->Phone, 5, strlen ( $d->Address->Phone ) );
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Celular'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataCadastro'] = '';
				
			//$infosAdicionaisPedido = $this->_magento->buscaInformacoesAdicionaisPedido($d->increment_id);
		
			$cepEntregaFormatado = $this->Numeros($d->Address->ZipCode);
			$cepCobrancaFormatado = $this->Numeros($d->Address->ZipCode);
				
			// Dados do Endereço
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'] = $d->Address->Street;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'] = $d->Address->Number;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'] = $d->Address->More;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'] = substr ( $d->Address->Neighborhood, 0, 40 );
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Municipio'] = $d->Address->City;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Estado'] = $d->Address->State;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Cep'] = $cepEntregaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['TipoLocalEntrega'] = 'tleeDesconhecido'; // informação não vem da magento
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Pais'] = 'BR';
			// Dados do Endereço de Cobrança
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Logradouro'] = $d->Address->Street;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['NumeroLogradouro'] = $d->Address->Number;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ComplementoEndereco'] = $d->Address->More;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Bairro'] = substr ( $d->Address->Neighborhood, 0, 40 );
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Municipio'] = $d->Address->City;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Estado'] = $d->Address->State;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Cep'] = $cepCobrancaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['TipoLocalEntrega'] = 'tleeDesconhecido';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Pais'] = 'BR';
			// Dados do Endereço de Entrega
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Logradouro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['NumeroLogradouro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ComplementoEndereco'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Bairro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'];
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Municipio'] = $d->Address->City;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Estado'] = $d->Address->State;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Cep'] = $cepEntregaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['TipoLocalEntrega'] = 'tleeDesconhecido';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Pais'] = 'BR';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['ClienteEstrangeiro'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['RegimeTributario'] = '';
			
			try {
			
				echo "Efetuando cadastro/atualizacao de cliente " . $cpfFormatado . PHP_EOL;
				$this->_kpl->adicionaCliente( $dadosCliente [$i] ['Cliente'] );
				echo "Cliente adicionado com sucesso " . PHP_EOL;
			
			} catch (Exception $e) {
				echo "Erro ao cadastrar cliente " . $cpfFormatado . ' - ' . $e->getMessage() . PHP_EOL;
				continue;
			}
			

			echo "Tratando dados para cadastro de pedido: " . $d->Id . PHP_EOL;
			
			//Seguindo com criação de Pedidos
			$dadosPedido [$i] ['NumeroDoPedido'] = $d->Id;
			$dadosPedido [$i] ['EMail'] = $d->Client->Email;
			$dadosPedido [$i] ['CPFouCNPJ'] = $cpfFormatado;
			$dadosPedido [$i] ['CodigoCliente'] = $cpfFormatado;
			//$dadosPedido [$i] ['CondicaoPagamento'] = 'COMPRAS'; //Validar
			$dadosPedido [$i] ['ValorPedido'] = number_format($d->subtotal, 2, '.', ''); // PENDENTE
			$dadosPedido [$i] ['ValorFrete'] = number_format($d->shipping_amount, 2, '.', ''); // PENDENTE
			$dadosPedido [$i] ['ValorDesconto'] = str_replace('-', '', number_format($d->discount_amount, 2, '.', '')); // PENDENTE
			$dadosPedido [$i] ['ValorEncargos'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorEmbalagemPresente'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorReceberEntrega'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorTrocoEntrega'] = '0.00'; // PENDENTE
			
			//Tratamento específico pra data
			list($data, $hora) = explode('T',  $d->PurchaseDate);
			list($horaNova, $horaAdicional) = explode('.',  $hora);
			
			list($ano, $mes, $dia) = explode('-', $data);
			$dataFormatada = $dia.$mes.$ano.' '.$horaNova;
			
			$dadosPedido [$i] ['DataVenda'] = $dataFormatada;

			$dadosPedido [$i] ['Transportadora'] = $infosAdicionaisPedido->shipping_method;
			$dadosPedido [$i] ['EmitirNotaSimbolica'] = 0; //Boolean
			$dadosPedido [$i] ['Lote'] = 1; // Cadastrar um Padrão KPL
			$dadosPedido [$i] ['DestNome'] = $d->Address->ReceiverName;
			$dadosPedido [$i] ['DestSexo'] = $sexoClientePedido;
			$dadosPedido [$i] ['DestEmail'] = $d->Client->Email;
			$dadosPedido [$i] ['DestTelefone'] = substr ( $d->Address->Phone, 5, strlen ( $d->Address->Phone ) );
			
			// Dados do Endereço
			$dadosPedido [$i] ['DestLogradouro'] = $d->Address->Street;
			$dadosPedido [$i] ['DestNumeroLogradouro'] = $d->Address->Number;
			$dadosPedido [$i] ['DestComplementoEndereco'] = $d->Address->More;
			$dadosPedido [$i] ['DestBairro'] = substr ( $d->Address->Neighborhood, 0, 40 );;
			
			$dadosPedido [$i] ['DestMunicipio'] = $d->Address->City;
			$dadosPedido [$i] ['DestEstado'] = $d->Address->State;
			$dadosPedido [$i] ['DestCep'] = $d->Address->ZipCode;
			$dadosPedido [$i] ['DestTipoLocalEntrega'] = 'tleeDesconhecido';
			$dadosPedido [$i] ['DestPais'] = 'BR';
			$dadosPedido [$i] ['DestCPF'] = $cpfFormatado;
			$dadosPedido [$i] ['DestTipoPessoa'] = $tipoPessoa;
			$dadosPedido [$i] ['DestDocumento'] = $cpfFormatado;
			$dadosPedido [$i] ['PedidoJaPago'] = 1; //Boolean
			// 			$dadosPedido [$i] ['DestEstrangeiro'] = '';
			// 			$dadosPedido [$i] ['DestInscricaoEstadual'] = '';
			// 			$dadosPedido [$i] ['DestReferencia'] = "";
			// 			$dadosPedido [$i] ['DataDoPagamento'] = '';
			// 			$dadosPedido [$i] ['OptouNFPaulista'] = ''; //Necessário verificar essa opção
			// 			//$dadosPedido [$i] ['CartaoPresenteBrinde'] = 1;
			
			
			
			
			
		}
		
		#################### VTEX ABAIXO ###################
		try {
			
			$var_erro = 0;
			$dados_item = NULL;
			//$array_temp ['Campanha'] = $dados_pedido ['Campaign'];
			$array_temp ['TipoVenda'] = "vendavel";
			$array_temp ['TipoEstoque'] = "varejo";
			$array_temp ['Reenvio'] = "";
			$array_temp ['Prioridade'] = "0";
			$array_temp ['CondFrete'] = "0";
			echo "Importando pedido {$dados_pedido ['Id']} ... ";
			$array_temp ['Pedido'] = $dados_pedido ['Id'];
			list ( $anop, $mesp, $diap ) = explode ( '-', substr ( $dados_pedido ['PurchaseDate'], 0, 10 ) );
			$array_temp ['DataPedido'] = $anop . '-' . $mesp . '-' . $diap;
			$array_temp ['Observacoes'] = "";
			
			$natureza = "VENDANORMAL";
			$array_temp ['Natureza'] = $natureza;
			
			$array_temp ['CatPessoa'] = "CONSFINAL";
			$array_temp ['IsencaoIcms'] = "";
			$array_temp ['NumeroECT'] = "";
			if ( $dados_pedido ['IsGiftList'] == 'true' ) {
				$array_temp ['Presente'] = '1';
			} else {
				$array_temp ['Presente'] = '0';
			}
			
			// dados do destinatario
			$array_temp ['DestNome'] = $dados_pedido ['Address'] ['ReceiverName'];
			$array_temp ['DestCpfCnpj'] = str_replace ( array ( '.', ',', '-', '/' ), '', $dados_pedido ['Client'] ['CpfCnpj'] );
			$array_temp ['DestIe'] = $dados_pedido ['Client'] ['StateInscription'];
			$array_temp ['DestEnd'] = $dados_pedido ['Address'] ['Street'];
			$array_temp ['DestEndNum'] = $dados_pedido ['Address'] ['Number'];
			$array_temp ['DestCompl'] = $dados_pedido ['Address'] ['More'];
			$array_temp ['DestPontoRef'] = $dados_pedido ['Address'] ['ReferencePoint'];
			$array_temp ['DestBairro'] = substr ( $dados_pedido ['Address'] ['Neighborhood'], 0, 40 );
			$array_temp ['DestCidade'] = $dados_pedido ['Address'] ['City'];
			$array_temp ['DestEstado'] = $dados_pedido ['Address'] ['State'];
			$array_temp ['DestCep'] = $dados_pedido ['Address'] ['ZipCode'];
			$array_temp ['DestDdd'] = substr ( $dados_pedido ['Address'] ['Phone'], 3, 2 );
			$array_temp ['DestTelefone1'] = substr ( $dados_pedido ['Address'] ['Phone'], 5, strlen ( $dados_pedido ['Address'] ['Phone'] ) );
			$array_temp ['DestEmail'] = $dados_pedido ['Client'] ['Email'];
			
			$array_transportadoras = $this->_vtex->trataArrayDto ( $dados_pedido ['OrderDeliveries'] ['OrderDeliveryDTO'] );
			
			foreach ( $array_transportadoras as $i => $dados_entregas ) {
				$array_produtos = array ();
				$ProdutosSaidaArray = array ();
				$valor_total_produtos = array ();
				if(!empty($dados_entregas['FreightIdV3'])){
					$array_temp ['TransId'] = $this->_getTransportadora ( $dados_entregas ['FreightIdV3'] );
				}else{
					
					$array_temp ['TransId'] = $this->_getTransportadora ( $dados_entregas ['FreightId'] );
				}
				
				// agendamento
				$array_temp ['Agendamento'] = NULL;
				$data_agendamento = $dados_entregas ['ScheduledDate'];
				if ( ! empty ( $data_agendamento ) ) {
					$data_agendamento_sub = substr ( $data_agendamento, 0, 10 );
					list ( $anoag, $mesag, $diaag ) = explode ( '-', $data_agendamento_sub );
					$array_temp ['Agendamento'] ['Data'] = $diaag . '/' . $mesag . '/' . $anoag;
					$periodo_agendamento = $dados_entregas ['ScheduledShift'];
					$periodo_agendamento = strtolower ( substr ( $periodo_agendamento, 0, 1 ) );
					$array_temp ['Agendamento'] ['Periodo1'] = $periodo_agendamento;
					$array_temp ['Agendamento'] ['Periodo2'] = NULL;
				}

				$dados_item = $this->_vtex->trataArrayDto ( $dados_entregas ['OrderItems'] ['OrderItemDTO'] );
				
				$ultimo_item_id = NULL;
				$agrupar = false;
				$qtd_itens = count ( $dados_item ) - 1;
				foreach ( $dados_item as $key => $item ) {
					
					try {
						//não importar item que é kit
						if ( $item ['IsKit'] == "true" ) {
							continue;
						}
						
						$this->_verificaProdutos ( $item ['ItemId'] );
						
						$item_temp ['EanProprio'] = $item ['ItemId'];
						$valor_total_produtos [$i] [$item ['StockLikelyId']] += (($item ['Cost'] + $item ['ShippingCostOff']) - (($item ['Cost'] - $item ['CostOff']) - $item ['CupomValue']));
						$item_temp ['Valor'] = number_format ( $item ['Cost'], 2, '.', '' ); // valor unitário
						

						if ( $item ['CostOff'] == 0 ) {
							$item_temp ['ClassificacaoFiscal'] = 'ENVIOBRINDE';
						} else {
							$item_temp ['ClassificacaoFiscal'] = $natureza;
						}
						$item_temp ['Frete'] = number_format ( $item ['ShippingCostOff'], 2, '.', '' ); // valor do frete
						if ( $item_temp ['ClassificacaoFiscal'] == 'ENVIOBRINDE' ) {
							$item_temp ['Desconto'] = 0; // valor_desconto = valor_unitario - valor_final 
						} else {
							$item_temp ['Desconto'] = number_format ( ($item ['Cost'] - $item ['CostOff']) + $item ['CupomValue'], 2, '.', '' ); // valor_desconto = valor_unitario - valor_final valor cupom de desconto 						
						

						}
						$item_temp ['Qtd'] = 1;
						$dados_item_servicos = $this->_vtex->trataArrayDto ( $item ['OrderItemServices'] ['OrderItemServiceDTO'] );
						if ( ! empty ( $dados_item_servicos ) ) {
							foreach ( $dados_item_servicos as $servico ) {
								// se for serviço de embalagem, capturar dados
								// ignorar outros tipos de serviço 
								

								/* TIPO DE SERVIÇO DA VTEX:
							 * 127: Cartão Presente
							 * 128: Embalagem de Presente 
							 * 512: Nota Fiscal para Presente 
							 */
								
								if ( $servico ['StockKeepingUnitService'] ['ServiceId'] == 127 ) {
									// trata cartão presente   ServiceId
									$item_serv ['Tipo'] = 'Cartao_Presente';
									$item_serv ['Cartao_Presente'] ['De'] = $servico ['GiftCardFrom'];
									$item_serv ['Cartao_Presente'] ['Para'] = $servico ['GiftCardTo'];
									$item_serv ['Cartao_Presente'] ['Mensagem'] = $servico ['GiftCardMessage'];
									$item_serv ['Preco'] = number_format ( $servico ['StockKeepingUnitService'] ['ServicePrice'] ['Price'], 2, '.', '' );
								
								} elseif ( $servico ['StockKeepingUnitService'] ['ServiceId'] == 128 ) {
									// trata Embalagem de Presente 
									$item_serv ['Tipo'] = 'Embalagem_Presente';
									$item_serv ['Preco'] = number_format ( $servico ['Price'], 2, '.', '' );
								
								} elseif ( $servico ['StockKeepingUnitService'] ['ServiceId'] == 512 ) {
									// trata Nota Fiscal de Presente 
									$item_serv ['Tipo'] = 'Nota_Presente';
									$item_serv ['Preco'] = number_format ( $servico ['StockKeepingUnitService'] ['ServicePrice'] ['Price'], 2, '.', '' );
									$array_temp ['Presente'] = '1';
								
								} else {
									// trata Serviço desconhecido
									$item_serv ['Tipo'] = 'Servico_Desconhecido';
									$item_serv ['Preco'] = number_format ( $servico ['StockKeepingUnitService'] ['ServicePrice'] ['Price'], 2, '.', '' );
								}
								
								$item_serv ['ServiceId'] = $servico ['StockKeepingUnitService'] ['ServiceId'];
								
								$item_temp ['Servicos'] [] = $item_serv;
								$item_serv = NULL;
							}
						}
						if ( $this->_cli_id == 60 || $this->_cli_id == 68 ) {
							if ( ! $item ['StockLikelyId'] ) {
								$item ['StockLikelyId'] = 1;
							}
							$ProdutosSaidaArray [$item ['StockLikelyId']] [] = $item_temp;
						} else {
							
							$ProdutosSaidaArray [] = $item_temp;
						}
						$item_temp = NULL;
					} catch ( Exception $e ) {
						$this->_vtex->setErro ( array ( "Id" => $dados_pedido ['Id'], "Metodo" => "_importaProduto", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
						throw new Exception ( $e->getMessage () );
					}
				}
				
				$dados_pagamento = $dados_pedido ['OrderPayments'] ['OrderPaymentDTO'];
				
				// verifica ID da forma de pagamento da Vtex
				if ( empty ( $dados_pagamento ['PaymentId'] ) ) {
					
					// Se for nulo, é um vale-compra
					// neste caso atribuir os dados do comprador com os mesmos dados do destinatário
					$array_temp ['DadosComprador'] ['CompNome'] = substr ( $dados_pedido ['Address'] ['ReceiverName'], 0, 48 );
					$array_temp ['DadosComprador'] ['CompCpfCnpj'] = str_replace ( array ( '.', ',', '-', '/' ), '', $dados_pedido ['Client'] ['CpfCnpj'] );
					$array_temp ['DadosComprador'] ['CompIe'] = $dados_pedido ['Client'] ['StateInscription'];
					$array_temp ['DadosComprador'] ['CompCep'] = $dados_pedido ['Address'] ['ZipCode'];
					$array_temp ['DadosComprador'] ['CompEnd'] = $dados_pedido ['Address'] ['Street'] . ', ' . $dados_pedido ['Address'] ['Number'];
					$array_temp ['DadosComprador'] ['CompEndNum'] = $dados_pedido ['Address'] ['Number'];
					$array_temp ['DadosComprador'] ['CompCompl'] = $dados_pedido ['Address'] ['More'];
					$array_temp ['DadosComprador'] ['CompPontoRef'] = $dados_pedido ['Address'] ['ReferencePoint'];
					$array_temp ['DadosComprador'] ['CompBairro'] = substr ( $dados_pedido ['Address'] ['Neighborhood'], 0, 40 );
					$array_temp ['DadosComprador'] ['CompCidade'] = $dados_pedido ['Address'] ['City'];
					$array_temp ['DadosComprador'] ['CompEstado'] = $dados_pedido ['Address'] ['State'];
					$array_temp ['DadosComprador'] ['CompEmail'] = $dados_pedido ['Client'] ['Email'];
				
				} else {
					
					// Caso seja um pagamento conhecido, capturar os dados do comprador a partir dos dados do "pagador"
					$array_temp ['DadosComprador'] ['CompNome'] = substr ( $dados_pedido ['Client'] ['FirstName'] . ' ' . $dados_pedido ['Client'] ['LastName'], 0, 48 );
					$array_temp ['DadosComprador'] ['CompCpfCnpj'] = str_replace ( array ( '.', ',', '-', '/' ), '', $dados_pedido ['Client'] ['CpfCnpj'] );
					$array_temp ['DadosComprador'] ['CompIe'] = $dados_pedido ['Client'] ['StateInscription'];
					$array_temp ['DadosComprador'] ['CompCep'] = $dados_pagamento ['ZipCode'];
					$array_temp ['DadosComprador'] ['CompEnd'] = $dados_pagamento ['Street'] . ', ' . $dados_pagamento ['Number'];
					$array_temp ['DadosComprador'] ['CompEndNum'] = $dados_pagamento ['Number'];
					$array_temp ['DadosComprador'] ['CompCompl'] = '';
					$array_temp ['DadosComprador'] ['CompPontoRef'] = $dados_pagamento ['ReferencePoint'];
					$array_temp ['DadosComprador'] ['CompBairro'] = substr ( $dados_pagamento ['Neighborhood'], 0, 40 );
					$array_temp ['DadosComprador'] ['CompCidade'] = $dados_pagamento ['City'];
					$array_temp ['DadosComprador'] ['CompEstado'] = $dados_pagamento ['State'];
					$array_temp ['DadosComprador'] ['CompEmail'] = $dados_pedido ['Client'] ['Email'];
				}
				
				if ( $this->_cli_id == 60 || $this->_cli_id == 68 ) {
					
					foreach ( $ProdutosSaidaArray as $id_estoque => $array_produtos ) {
						
						//definir a loja_id
// 						if ( $id_estoque == 1 ) {
// 							$cli_id = 60;
// 						} else {
// 							$cli_id = 68;
// 						}
						$cli_id = 68;
						if($dados_pedido['StoreId'] == 3){
							$dados_pedido['StoreId'] = 1;
						}
						$db = Db_Factory::getDbWms ();
						$sql = "SELECT loja_id FROM lojas WHERE cli_id={$cli_id} AND loja_cod='{$dados_pedido['StoreId']}' ";
						
						$res = $db->Execute ( $sql );
						if ( ! $res ) {
							throw new RuntimeException ( "Erro ao consultar loja" );
						}
						if ( $db->NumRows ( $res ) == 0 ) {
							$loja_id = 11;
						} else {
							
							$row = $db->FetchAssoc ( $res );
							$loja_id = $row ['loja_id'];
						}
						//dados de pagamento para que o valor dos produtos fique correto
						

						if ( empty ( $dados_pagamento ) ) {
							if( empty ( $dados_pedido ['AffiliateId'] )){
								throw new DomainException ( 'Dados de pagamento inválidos' );
							}else{
								$array_pagamento = array ();
								$dados_pagamento ['PaymentId'] = $dados_pedido ['AffiliateId'];								
								$pagamento_temp ['Parcela'] = 1;
								$pagamento_temp ['Vencimento'] = $array_temp ['DataPedido'];
								
								$pagamento_temp ['Valor'] = number_format ( $valor_total_produtos [$i] [$id_estoque], 2, '.', '' );
								$arrayFormaRecebimento = $this->_getFormaPagamentoAfiliado ( $dados_pagamento ['PaymentId'] );
								$pagamento_temp ['FormaRecebimento'] = $arrayFormaRecebimento ['FormaRecebimento']; 
								$pagamento_temp ['PaymentId'] = $arrayFormaRecebimento ['IdFormaRecebimento'];
								$pagamento_temp ['Percentual'] = 100;
								$array_pagamento [] = $pagamento_temp;						
								
								// Comportamento necessário para Atribuir informações de Afiliado na tabela notas_saida_pag_dados
								$array_temp ['DadosComprador'] ['CompNome'] = substr ( $dados_pedido ['Client'] ['FirstName'] . ' ' . $dados_pedido ['Client'] ['LastName'], 0, 48 );
								$array_temp ['DadosComprador'] ['CompCpfCnpj'] = str_replace ( array ( '.', ',', '-', '/' ), '', $dados_pedido ['Client'] ['CpfCnpj'] );								

								if($dados_pedido['AffiliateId']=='CSU'){
									
									$array_temp ['DadosComprador'] ['CompCep'] = '06440182'; // Específico CSU
									$array_temp ['DadosComprador'] ['CompEnd'] = 'Rua Piauí'; // Específico CSU
									$array_temp ['DadosComprador'] ['CompEndNum'] = '136'; // Específico CSU
									$array_temp ['DadosComprador'] ['CompCompl'] = 'bloco A,B'; // Específico CSU
									$array_temp ['DadosComprador'] ['CompBairro'] = 'Aldeia'; // Específico CSU
									$array_temp ['DadosComprador'] ['CompCidade'] = 'Barueri'; // Específico CSU
									$array_temp ['DadosComprador'] ['CompEstado'] = 'SP'; // Específico CSU

								}elseif ($dados_pedido['AffiliateId'] == 'Multiplus'){

									$array_temp ['DadosComprador'] ['CompCep'] = '04544051'; // Específico Multiplus
									$array_temp ['DadosComprador'] ['CompEnd'] = 'Rua Ministro Jesuíno Cardoso'; // Específico Multiplus
									$array_temp ['DadosComprador'] ['CompEndNum'] = '454'; // Específico Multiplus
									$array_temp ['DadosComprador'] ['CompCompl'] = '2º Andar Ed. The One'; // Específico Multiplus								
									$array_temp ['DadosComprador'] ['CompBairro'] = 'Vila Nova Conceição'; // Específico Multiplus
									$array_temp ['DadosComprador'] ['CompCidade'] = 'São Paulo'; // Específico Multiplus
									$array_temp ['DadosComprador'] ['CompEstado'] = 'SP'; // Específico Multiplus
									
								}

								$array_temp ['DadosComprador'] ['CompEmail'] = $dados_pedido ['Client'] ['Email'];
							}
							
						}else{
							$array_pagamento = array ();
							$pagamento_temp ['Parcela'] = 1;
							$pagamento_temp ['Vencimento'] = $array_temp ['DataPedido'];
							
							$pagamento_temp ['Valor'] = number_format ( $valor_total_produtos [$i] [$id_estoque], 2, '.', '' );
							$pagamento_temp ['FormaRecebimento'] = $this->_getFormaPagamento ( $dados_pagamento ['PaymentId'] );
							$pagamento_temp ['PaymentId'] = $dados_pagamento ['PaymentId'];
							$pagamento_temp ['Percentual'] = 100;
							$array_pagamento [] = $pagamento_temp;
						}
						
						$array_temp ['Pagamento'] = $array_pagamento;
						
						$array_temp ['Loja'] = $loja_id;
						
						$array_temp ['ProdutosSaidaArray'] = $array_produtos;
						
						$this->_pedidos_cliente [$i] [$id_estoque] ['PedidosSaidaArray'] [] = $array_temp;
					}
				} else {
					
					$array_temp ['ProdutosSaidaArray'] = $ProdutosSaidaArray;
					$pedidos_array ['PedidosSaidaArray'] [] = $array_temp;
					$this->_array_pedidos ['PedidosSaidaArray'] [] = $array_temp;
				}
			}
			
			echo 'Ok!' . PHP_EOL;
		
		} catch ( Exception $e ) {
			echo 'Erro: ' . $e->getMessage () . PHP_EOL;
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * 
	 * Retona a nomenclatura do paymentId da Vtex
	 * @param int $payment_id
	 */
	private function _getFormaPagamento ( $payment_id ) {

		$db = Db_Factory::getDbWms ();
		if ( empty ( $payment_id ) ) {
			$payment_id = '0'; // se estiver vazio, significa que é um vale-compra
		}
		
		if ( ! ctype_digit ( $payment_id ) ) {
			throw new InvalidArgumentException ( "ID do pagamento inválido {$payment_id}" );
		}
		
		// seleciona a forma de pagamento existente na tabela vtex_formas_pagamento
		$sql = "SELECT vtexformpag_nome, vtexformpag_tipo FROM vtex_formas_pagamento WHERE vtexformpag_id = {$payment_id}";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao consultar forma de pagamento' );
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			throw new DomainException ( 'Forma de pagamento não encontrada' );
		}
		$row = $db->FetchAssoc ( $res );
		
		// retonar com letras maiusculas
		return strtoupper ( $row ['vtexformpag_tipo'] );
	}
	
	/**
	 * 
	 * Retona a nomenclatura do paymentId da Vtex para quando o pedido for de incentivo
	 * @param int $payment_name
	 * @return array Contendo Nomenclatura e ID da forma de pagamento
	 */
	private function _getFormaPagamentoAfiliado ( $payment_name ) {

		$db = Db_Factory::getDbWms ();
		$arrayFormaPagamento = array();
		if ( empty ( $payment_name ) ) {
			throw new InvalidArgumentException ( "ID do pagamento inválido {$payment_name}" );
		}
		
		// seleciona a forma de pagamento existente na tabela vtex_formas_pagamento
		$sql = "SELECT vtexformpag_id, vtexformpag_nome, vtexformpag_tipo FROM vtex_formas_pagamento WHERE vtexformpag_nome = '{$payment_name}'";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao consultar forma de pagamento' );
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			throw new DomainException ( 'Forma de pagamento não encontrada' );
		}
		$row = $db->FetchAssoc ( $res );
		
		$arrayFormaPagamento['FormaRecebimento'] =  strtoupper ( $row['vtexformpag_tipo'] );
		$arrayFormaPagamento['IdFormaRecebimento'] = $row['vtexformpag_id'];
		
		// retonar com letras maiusculas
		return $arrayFormaPagamento;
	}

	/**
	 * 
	 * Gera a movimentação dos pedidos
	 * @throws Exception
	 */
	private function _geraMovimentacao () {

		try {
			
			if ( ! empty ( $this->_array_pedidos ['PedidosSaidaArray'] ) ) {
				foreach ( $this->_array_pedidos ['PedidosSaidaArray'] as $pedido ) {
					if ( ! in_array ( $pedido ['Pedido'], $pe_erro ) ) {
						$tentativa = 0;
						while ( $tentativa <= 5 ) {
							try {
								$retorno = $this->_mudarStatusPedido ( $pedido ['Pedido'], "ERP" );
								$tentativa = 10;
								$ok = true;
							} catch ( Exception $e ) {
								echo "Erro na tentativa {$tentativa} ... ";
								$ok = false;
							}
							$tentativa ++;
						}
						if ( ! $ok ) {
							echo "Erro ao atualizar o status " . $retorno;
						}
					}
				}
			}
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Importa uma determinada quantidade de pedidos que estejam em um determinado status
	 * @param string $statusOrder Descrição do Status
	 * @param int 	 $Quantidade  Quantidade de pedidos a ser importado
	 * @return retorna mensagem em caso de erro
	 */
	public function importarPedidosStatusQuantidade($status, $quantidade) {
		
		if (empty ( $status )) {
			throw new InvalidArgumentException ( 'Status inválido' );
		}
		
		if (! ctype_digit ( $quantidade )) {
			throw new InvalidArgumentException ( 'Quantidade inválida' );
		}
		
		try {
			$pedidos = $this->_client->OrderGetByStatusByQuantity ( $status, $quantidade );
		} catch ( Exception $e ) {
			throw new RuntimeException ( 'Erro ao consultar Pedidos por status' );
		}
		if (! is_object( $pedidos )) {
			throw new DomainException ( 'Nenhum pedido pendente neste status - '. $pedidos );
		}
		
		if (empty ( $pedidos->OrderGetByStatusByQuantityResult )) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		$dados_pedidos = $this->_vtex->trataArrayDto ( $pedidos->OrderGetByStatusByQuantityResult->OrderDTO );
		
		try {
			$this->_importarPedidos ( $dados_pedidos );
		} catch ( Exception $e ) {
			$this->_vtex->setErro ( array ("Id" => $value ['Id'], "Metodo" => "importarPedidosStatusQuantidade", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
		
		}
		
		
		try{
			
			$this->_geraMovimentacao ();
			
			// grava logs de erro se existirem
			$this->_vtex->gravaLogVtex ();
		
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	
	}

	/**
	 * Importa um determinado pedido da Vtex
	 * @param string $statusOrder Descrição do Status
	 * @param int 	 $Quantidade  Quantidade de pedidos a ser importado
	 * @return retorna mensagem em caso de erro
	 */
	public function importarPedidosId ( $id_pedido ) {

		$db = Db_Factory::getDbWms ();
		
		if ( ! ctype_digit ( $this->_empwh_id ) ) {
			throw new LogicException ( 'ID do Armazem inválido' );
		}
		
		$id_pedido = trim ( $id_pedido );
		if ( ! ctype_digit ( $id_pedido ) ) {
			throw new InvalidArgumentException ( 'Pedido de saída inválido' );
		}
		
		try {
			$pedidos = $this->_client->OrderGet ( $id_pedido );
		} catch ( Exception $e ) {
			throw new RuntimeException ( 'Erro ao consultar Pedidos por status' );
		}
		
		if ( ! is_array ( $pedidos ) ) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		if ( empty ( $pedidos ['OrderGetResult'] ) ) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		$dados_pedidos = $this->_vtex->trataArrayDto ( $pedidos ['OrderGetResult'] );
		foreach ( $dados_pedidos as $key => $value ) {
			try {
				$this->_importarPedido ( $value );
			} catch ( Exception $e ) {
				$this->_vtex->setErro ( array ( "Id" => $value ['Id'], "Metodo" => "importarPedidosId", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
			}
		}
		try {
			if ( $this->_cli_id == 60 || $this->_cli_id == 68 ) {
				
				foreach ( $this->_pedidos_cliente as $transportadoras => $pedidos_transportadora ) {
					
					//@TODO Alinhar melhor forma de alterar junto com o andre. Demanda: Alterar o id 60 para 68
					foreach ( $pedidos_transportadora as $id_estoque => $this->_array_pedidos ) {
						if ( $id_estoque != 1 ) {
							$sql = "SELECT empwh_id FROM clientes_warehouse WHERE cli_id=68";
							$res = $db->Execute ( $sql );
							if ( ! $res ) {
								throw new RuntimeException ( 'Erro ao consultar armazém do cliente' );
							}
							$row = $db->FetchAssoc ( $res );
							$this->_empwh_id = $row ['empwh_id'];
							$this->_cli_id = 68;
						} else {
							
							$this->_cli_id = 60;
							$sql = "SELECT empwh_id FROM clientes_warehouse WHERE cli_id={$this->_cli_id}";
							$res = $db->Execute ( $sql );
							if ( ! $res ) {
								throw new RuntimeException ( 'Erro ao consultar armazém do cliente' );
							}
							$row = $db->FetchAssoc ( $res );
							$this->_empwh_id = $row ['empwh_id'];
						
						}
						$this->_geraMovimentacao ();
					
					}
				
				}
			} else {
				$this->_geraMovimentacao ();
			}
			
			// grava logs de erro se existirem
			$this->_vtex->gravaLogVtex ();
		
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	
	}

	/**
	 * Muda Status de um Array de ID de pedidos
	 * @param array $array_id Array de Pedidos para Mudança de Status
	 * @param array $array_id_erro Array de Pedidos que tiveram erro e não serão atualizados 
	 * @param int $nro_movimento Id do Cliente
	 */
	private function _mudarStatusPedido ( $order_id, $status ) {

		if(empty($order_id)){
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		if ( empty ( $status ) ) {
			$status = 'ERP';
		}
		
		try {
			// atualiza status do pedido
			$retorno_status = $this->_client->OrderChangeStatus ( $order_id, $status );
			
			if ( ! $retorno_status == FALSE ) {
				if ( ! is_array ( $retorno_status ) || (is_array ( $retorno_status ) && ! empty ( $retorno_status ['faultcode'] )) ) {
					throw new RuntimeException ( 'Erro ao tentar alterar status do pedido' );
				}
			}
		} catch ( Exception $e ) {
			$this->_vtex->setErro ( array ( "Id" => $order_id, "Metodo" => "_mudarStatusPedido", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
		}
		
	}

	/**
	 * Altera o status do pedido
	 */
	public function alterarStatusPedido ( $order_id, $status ) {

		if ( ! ctype_digit ( $order_id ) ) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		try {
			$this->_mudarStatusPedido ( $order_id, $status );
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}

	/**
	 * Consulta se foir alterado e deleta da tabela se estiver no status correto
	 */
	public function consultarPedido ( $order_id ) {

		if ( ! ctype_digit ( $order_id ) ) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		try {
			$pedidos = $this->_client->OrderGet ( $order_id );
		} catch ( Exception $e ) {
			throw new RuntimeException ( 'Erro ao consultar pedido por status' );
		}
		
		if ( ! is_array ( $pedidos ) ) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		if ( empty ( $pedidos ['OrderGetResult'] ['OrderDeliveries'] ['OrderDeliveryDTO'] ['OrderStatusId'] ) ) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		if ( $pedidos ['OrderGetResult'] ['OrderDeliveries'] ['OrderDeliveryDTO'] ['OrderStatusId'] == 'ERP' ) {
			$db = Db_Factory::getDbWms ();
			$sql_deletar = "DELETE FROM vtex_pedidos_cancelar WHERE pedido = {$order_id}";
			$res_deletar = $db->Execute ( $sql_deletar );
			if ( ! $res_deletar ) {
				throw new RuntimeException ( 'Erro ao deletar pedido da mudança de status' );
			}
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

	/**
	 * 
	 * efetua cancelamento de um pedido no vtex
	 * @param int $not_id
	 */
	public function cancelaPedidoSaida ( $not_id ) {

		if ( ! ctype_digit ( $not_id ) ) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		
		try {
			// captura dados do pedido
			$model_pedido = new Model_Wms_Saida_Pedido ( $not_id );
			$dados_pedido = $model_pedido->getDadosPedido ( $not_id );
			
			// verifica se o pedido está cancelado
			if ( $dados_pedido ['not_status'] != 6 ) {
				throw new DomainException ( 'O pedido não está apto à ser cancelado' );
			}
			
			// altera status do pedido no vtex
			$this->_mudarStatusPedido ( $dados_pedido ['not_pedido'], 'CAN' );
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}

}
