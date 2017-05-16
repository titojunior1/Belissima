<?php
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
/**
 * 
 * Classe de gerenciamento de Pedidos de Sa�da com a VTEX
 * @author Tito Junior
 *
 */
class Model_Wpr_Vtex_Pedido {
	
	/**
	 * 
	 * Rela��o com dados dos pedido
	 * @var Array
	 */
	private $_array_pedidos = array ();
	
	/**
	 * Caracteres especiais
	 */
	private $_caracteres_especiais = array ( "\"", "'", "\\", "`" );
	
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
	
	/*
	 *  URL para integra��o via REST VTEX
	*/
	private $_url;
	
	/*
	 *  Token para integra��o via REST VTEX
	*/
	private $_token;
	
	/*
	 *  Chave para integra��o via REST VTEX
	*/
	private $_key;

	/**
	 * Construtor.
	 * @param string $ws Endere�o do Webservice.
	 * @param string $login Login de Conex�o do Webservice.
	 * @param string $pass Senha de Conex�o do Webservice.
	 */
	public function __construct (  ) {

		if ( empty ( $this->_vtex ) ) {
			// Gera objeto de conex�o WebService
			$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex ();
			$this->_client = $this->_vtex->_client;
		}
		
		$this->_url = VTEX_API_URL;
		$this->_key = VTEX_API_KEY;
		$this->_token = VTEX_API_TOKEN;
	}
	/**
	 * Verificar CNPJ
	 * @param int $cnpj
	 * @param bool $formatar
	 * @return string | bool
	 */
	public static function validaCnpj($cnpj, $formatar = false) {
	
		// remove tudo que n�o for n�mero
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
	
			// remove tudo que n�o for n�mero
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
	 * Deixa somente os n�meros
	 * @param string $var
	 * @return string
	 */
	public static function Numeros($var) {
		return preg_replace ( '/[^0-9]/i', '', $var );
	}

	/**
	 * 
	 * Retorna a transa��o do pagamento, baseada no ID do pedido
	 * @param int $order_id
	 * @throws InvalidArgumentException
	 */
	private function _getDadosPagamento ( $order_id ) {
		
		if (empty($order_id)){
			throw new InvalidArgumentException( 'IdV3 de pedido inv�lido ou n�o informado' );
		}
		
		$url = sprintf($this->_url, "oms/pvt/orders/{$order_id}/payment-transaction");
		$headers = array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-VTEX-API-AppKey' => $this->_key,
				'X-VTEX-API-AppToken' => $this->_token
		);
		
		$request = Requests::get($url, $headers);
				
		if (! $request->success) {
			throw new RuntimeException('Falha ao obter formas de pagamento. [' . $request->body . ']');
		}
		
		return json_decode($request->body);
		
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
		
		// cole��o de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_precos = array ();
		
		foreach ( $dados_pedido as $i => $d ) {
		
			$dadosCliente = array();
			$dadosPedido = array();
			$valor_total_produtos = '0';
			$valor_total_frete = '0';
			$valor_total_desconto = '0';
			$array_transportadoras = array();
			
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
				
			//Manipulando dados para cadastro/atualiza��o de cliente
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Email'] = $d->Client->Email;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['CPFouCNPJ'] = $cpfFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Codigo'] = $cpfFormatado;
				
			//valida se � pessoa PF, caso n�o � PJ
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
				
			// Dados do Endere�o
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'] = $d->Address->Street;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'] = $d->Address->Number;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'] = $d->Address->More;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'] = substr ( $d->Address->Neighborhood, 0, 40 );
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Municipio'] = $d->Address->City;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Estado'] = $d->Address->State;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Cep'] = $cepEntregaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['TipoLocalEntrega'] = 'tleeDesconhecido'; // informa��o n�o vem da magento
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Pais'] = 'BR';
			// Dados do Endere�o de Cobran�a
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
			// Dados do Endere�o de Entrega
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
			
			//Seguindo com cria��o de Pedidos
			$dadosPedido [$i] ['NumeroDoPedido'] = $d->Id;
			$dadosPedido [$i] ['NumeroDoPedidoV3'] = $d->IdV3;
			$dadosPedido [$i] ['EMail'] = $d->Client->Email;
			$dadosPedido [$i] ['CPFouCNPJ'] = $cpfFormatado;
			$dadosPedido [$i] ['CodigoCliente'] = $cpfFormatado;
			//$dadosPedido [$i] ['CondicaoPagamento'] = 'COMPRAS'; //Validar			
			$dadosPedido [$i] ['ValorEncargos'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorEmbalagemPresente'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorReceberEntrega'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorTrocoEntrega'] = '0.00'; // PENDENTE
			
			//Tratamento espec�fico pra data
			list($data, $hora) = explode('T',  $d->PurchaseDate);
			list($horaNova, $horaAdicional) = explode('.',  $hora);
			
			list($ano, $mes, $dia) = explode('-', $data);
			$dataFormatada = $dia.$mes.$ano.' '.$horaNova;
			
			$dadosPedido [$i] ['DataVenda'] = $dataFormatada;
			
			$array_transportadoras = $this->_vtex->trataArrayDto ( (array) $d->OrderDeliveries->OrderDeliveryDTO );

			$dadosPedido [$i] ['Transportadora'] = $array_transportadoras[0]['FreightIdV3'];
			$dadosPedido [$i] ['EmitirNotaSimbolica'] = 0; //Boolean
			$dadosPedido [$i] ['Lote'] = 1; // Cadastrar um Padr�o KPL
			$dadosPedido [$i] ['DestNome'] = $d->Address->ReceiverName;
			$dadosPedido [$i] ['DestSexo'] = $sexoClientePedido;
			$dadosPedido [$i] ['DestEmail'] = $d->Client->Email;
			$dadosPedido [$i] ['DestTelefone'] = substr ( $d->Address->Phone, 5, strlen ( $d->Address->Phone ) );
			
			// Dados do Endere�o
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
			$dadosPedido [$i] ['DataDoPagamento'] = $dataFormatada;
			// 			$dadosPedido [$i] ['DestEstrangeiro'] = '';
			// 			$dadosPedido [$i] ['DestInscricaoEstadual'] = '';
			// 			$dadosPedido [$i] ['DestReferencia'] = "";
			// 			
			// 			$dadosPedido [$i] ['OptouNFPaulista'] = ''; //Necess�rio verificar essa op��o
			// 			//$dadosPedido [$i] ['CartaoPresenteBrinde'] = 1;			
			
			// Itens			
			$dados_item = $this->_vtex->trataArrayDto ( (array) $d->OrderDeliveries->OrderDeliveryDTO->OrderItems->OrderItemDTO );

			foreach ($dados_item as $it => $item){
				
				$item = (object) $item;
				
				//Verificar se o item atual � o mesmo sku do item anterior
				if ($item->ProductId == $dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it -1] ['CodigoProduto']){
					continue;
				}
				//n�o importar item que � kit
				if ( $item->IsKit == "true" ) {
					continue;
				}
				$valor_total_produtos += (($item->Cost) - ((($item->Cost - $item->CostOff))));
				$valor_total_frete += $item->ShippingCostOff;
				$valor_total_desconto += number_format ( ($item->Cost - $item->CostOff) + $item->CupomValue, 2, '.', '' );
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['CodigoProduto'] = $item->ItemId;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['QuantidadeProduto'] = (int) 1;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['PrecoUnitario'] = number_format ( $item->Cost, 2, '.', '' ); // valor unit�rio
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['MensagemPresente'] = $item->gift_message_available;
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['PrecoUnitarioBruto'] = number_format($item->price, 2, '.', '');
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['Brinde'] = '';
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['ValorReferencia'] = '';
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['EmbalagemPresente'] = '';
			}			
			$totalPedidoPagamento = ($valor_total_produtos + $valor_total_frete) - $valor_total_desconto;
			
			$dados_pagamento = $this->_getDadosPagamento($dadosPedido [$i] ['NumeroDoPedidoV3']);
			$dadosPagamento = $this->_vtex->trataArrayDto ( (array) $dados_pagamento );
			
			// Tipos de forma de pagamento			
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['FormaPagamentoCodigo'] = $dadosPagamento ['0']['payments']['0']->paymentSystemName;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['Valor'] = number_format( $totalPedidoPagamento, 2, '.', '');
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoQtdeParcelas'] = $dadosPagamento ['0']['payments']['0']->installments;
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['BoletoVencimento'] = ''; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['BoletoNumeroBancario'] = ''; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaNumeroBanco'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaCodigoAgencia'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVCodigoAgencia'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaContaCorrente'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVContaCorrente'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['PreAutorizadaNaPlataforma'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVContaCorrente'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoTID'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNSU'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNumeroToken'] = 1; // Necess�rio integrar API pagar.me
			//$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CodigoTransacaoGateway'] = 1; // Necess�rio integrar API pagar.me
			$dadosPedido [$i] ['ValorPedido'] = number_format( $valor_total_produtos, 2, '.', '');
			$dadosPedido [$i] ['ValorFrete'] = number_format($valor_total_frete, 2, '.', '');
			$dadosPedido [$i] ['ValorDesconto'] = number_format($valor_total_desconto, 2, '.', '');
			try {
			
				echo "Importando pedido " . $dadosPedido [$i] ['NumeroDoPedido'] . PHP_EOL;
				$this->_kpl->cadastraPedido( $dadosPedido );
				echo "Pedido importado/atualizado com sucesso" . PHP_EOL;
			
				echo "Atualizando status de pedido {$dadosPedido [$i] ['NumeroDoPedido']} no ambiente VTEX" . PHP_EOL;
				$this->_mudarStatusPedido($dadosPedido [$i] ['NumeroDoPedido'], 'ERP');
				echo "Status atualizado com sucesso" . PHP_EOL;
			
			} catch (Exception $e) {
				echo "Erro ao importar pedido " . $dadosPedido [$i] ['NumeroDoPedido'] . ' - ' . $e->getMessage() . PHP_EOL;
				continue;
			}			
			
		}
	}

	/**
	 * Importa uma determinada quantidade de pedidos que estejam em um determinado status
	 * @param string $statusOrder Descri��o do Status
	 * @param int 	 $Quantidade  Quantidade de pedidos a ser importado
	 * @return retorna mensagem em caso de erro
	 */
	public function importarPedidosStatusQuantidade($status, $quantidade) {
		
		if (empty ( $status )) {
			throw new InvalidArgumentException ( 'Status inv�lido' );
		}
		
		if (! ctype_digit ( $quantidade )) {
			throw new InvalidArgumentException ( 'Quantidade inv�lida' );
		}
		
		try {
			$pedidos = $this->_client->OrderGetByStatusByQuantity ( $status, $quantidade );
		} catch ( Exception $e ) {
			throw new RuntimeException ( 'Erro ao consultar Pedidos por status' );
		}
		if (! is_object( $pedidos )) {
			throw new DomainException ( 'Nenhum pedido pendente neste status - '. $pedidos );
		}
		
		if ( empty ( $pedidos->OrderGetByStatusByQuantityResult->OrderDTO ) ) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		$dados_pedidos = $this->_vtex->trataArrayDto ( $pedidos->OrderGetByStatusByQuantityResult->OrderDTO );
		
		try {
			$this->_importarPedidos ( $dados_pedidos );
		} catch ( Exception $e ) {
			$this->_vtex->setErro ( array ("Id" => $value ['Id'], "Metodo" => "importarPedidosStatusQuantidade", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
		
		}		
	
	}
	
	/**
	 * Importa um determinado pedido
	 * @param string $idPedido
	 * @return retorna mensagem em caso de erro
	 */
	public function importarPedidoId($idPedido) {
	
		if (empty ( $idPedido )) {
			throw new InvalidArgumentException ( 'Status inv�lido' );
		}
	
		try {
			$pedidos = $this->_client->OrderGet($idPedido);
		} catch ( Exception $e ) {
			throw new RuntimeException ( 'Erro ao consultar Pedidos por status' );
		}
		if (! is_object( $pedidos )) {
			throw new DomainException ( 'Nenhum pedido encontrado - '. $pedidos );
		}
	
		if ( empty ( $pedidos->OrderGetResult ) ) {
			throw new DomainException ( 'Nenhum pedido encontrado' );
		}
		
		$pedidos1->OrderGetResult->OrderGetDTO [] = $pedidos->OrderGetResult;
	
		$dados_pedidos = $this->_vtex->trataArrayDto ( $pedidos1->OrderGetResult->OrderGetDTO );
	
		try {
			$this->_importarPedidos ( $dados_pedidos );
		} catch ( Exception $e ) {
			$this->_vtex->setErro ( array ("Id" => $value ['Id'], "Metodo" => "importarPedidosStatusQuantidade", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
	
		}
	
	}

	/**
	 * Muda Status de um Array de ID de pedidos
	 * @param array $array_id Array de Pedidos para Mudan�a de Status
	 * @param array $array_id_erro Array de Pedidos que tiveram erro e n�o ser�o atualizados 
	 * @param int $nro_movimento Id do Cliente
	 */
	private function _mudarStatusPedido ( $order_id, $status ) {
	
		/*$url = sprintf($this->_url, "oms/pvt/orders/{$order_id}/changestate/ready-for-handling");
		$headers = array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-VTEX-API-AppKey' => $this->_key,
				'X-VTEX-API-AppToken' => $this->_token
		);
	
		$request = Requests::post($url, $headers);
	
		if (! $request->success) {
			throw new RuntimeException('Falha na comunica��o com o webservice. [' . $request->body . ']');
		}*/
		
		if(empty($order_id)){
			throw new InvalidArgumentException ( 'ID do pedido inv�lido' );
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
			throw new RuntimeException ( 'Erro ao tentar alterar status do pedido - ' . $e->getMessage() );
		}
		
	}

	/**
	 * Consulta se foir alterado e deleta da tabela se estiver no status correto
	 */
	public function consultarPedido ( $order_id ) {

		if ( ! ctype_digit ( $order_id ) ) {
			throw new InvalidArgumentException ( 'ID do pedido inv�lido' );
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
				throw new RuntimeException ( 'Erro ao deletar pedido da mudan�a de status' );
			}
		}
	
	}

	/**
	 * 
	 * Retorna os possiveis erros que ocorreram no meio do processo
	 */
	public function getErrosProcessamento () {

		// verifica se o array que grava os erros est� vazio
		$erro = $this->_vtex->getErros ();
		if ( ! empty ( $erro ) ) {
			return $erro;
		}
	}

}
