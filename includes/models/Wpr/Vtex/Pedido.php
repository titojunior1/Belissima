<?php
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
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
	 *  URL para integração via REST VTEX
	*/
	private $_url;
	
	/*
	 *  Token para integração via REST VTEX
	*/
	private $_token;
	
	/*
	 *  Chave para integração via REST VTEX
	*/
	private $_key;

	/**
	 * Construtor.
	 * @param string $ws Endereço do Webservice.
	 * @param string $login Login de Conexão do Webservice.
	 * @param string $pass Senha de Conexão do Webservice.
	 * @param string $apiUrl Endereço do Webservice.
	 * @param string $apiKey Login de Conexão do Webservice.
	 * @param string $apiToken Senha de Conexão do Webservice.
	 */
	public function __construct ( $ws, $login, $pass, $apiUrl, $apiKey, $apiToken ) {

		if ( empty ( $this->_vtex ) ) {
			// Gera objeto de conexão WebService
			$this->_vtex = Model_Wpr_Vtex_Vtex::getVtex ( $ws, $login, $pass );
			$this->_client = $this->_vtex->_client;
		}
		
		$this->_url = $apiUrl;
		$this->_key = $apiKey;
		$this->_token = $apiToken;
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
					$dv += @($sub_cpf [$i] * (10 - $i));
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
					$dv += @($sub_cpf [$i] * (11 - $i));
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
	 * Retorna a transação do pagamento, baseada no ID do pedido
	 * @param int $order_id
	 * @throws InvalidArgumentException
	 */
	private function _getDadosPagamento ( $order_id ) {
		
		if (empty($order_id)){
			throw new InvalidArgumentException( 'IdV3 de pedido inválido ou não informado' );
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
	 *
	 * Retorna os dados do produto, baseada no ID do item
	 * @param int $order_id
	 * @throws InvalidArgumentException
	 */
	private function _getDadosProduto ( $itemId ) {
	
		if (empty($itemId)){
			throw new InvalidArgumentException( 'Id do produto inválido ou não informado' );
		}
		
		$dadosProduto = $this->_vtex->_client->StockKeepingUnitGet($itemId);
	
		if ($dadosProduto->StockKeepingUnitGetResult == null) {
			throw new RuntimeException('Produto nao encontrado');
		}
	
		return $dadosProduto->StockKeepingUnitGetResult;
	
	}

	/**
	 * Processa o array de Pedidos
	 * @param array $dados Array de Dados de pedidos
	 *
	 */
	private function _importarPedidos ( $dados_pedido, $dadosCliente ) {

		echo "Conectando ao WebService Kpl... " . PHP_EOL;
		$this->_kpl = new Model_Wpr_Kpl_Clientes( $dadosCliente ['KPL_WSDL'], $dadosCliente ['KPL_KEY'] );
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
			$valor_total_produtos = '0';
			$valor_total_frete = '0';
			$valor_total_desconto = '0';
			$array_transportadoras = array();
			
			echo PHP_EOL;
			echo "Buscando dados do pedido: " . $d->orderId . PHP_EOL;

            $url = sprintf($this->_url, 'oms/pvt/orders/');
            $url = $url . $d->orderId;

            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-VTEX-API-AppKey' => $this->_key,
                'X-VTEX-API-AppToken' => $this->_token
            );

            $request = Requests::get($url, $headers);

            if (! $request->success) {
                throw new RuntimeException('Falha ao buscar dados do Pedido. [' . $request->body . ']');
            }

            $pedidoCompleto = json_decode($request->body);

			// formatar CPF
			$cpfFormatado = $this->Numeros($pedidoCompleto->clientProfileData->document);
			echo "Tratando dados para cadastro de cliente codigo: " . $cpfFormatado . PHP_EOL;
				
			// formata sexo
			if ( empty ($pedidoCompleto->clientProfileData->gender ) ){
				$sexoCliente = 'tseMasculino';
				$sexoClientePedido = 'M';
			}else{
				$sexoCliente = 'tseFeminino';
				$sexoClientePedido = 'F';
			}
				
			//Manipulando dados para cadastro/atualização de cliente
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Email'] = $pedidoCompleto->clientProfileData->email;
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
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Nome'] = $pedidoCompleto->shippingData->address->receiverName;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['NomeReduzido'] = $pedidoCompleto->shippingData->address->receiverName;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Sexo'] = $sexoCliente;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataNascimento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Telefone'] = substr ( $pedidoCompleto->clientProfileData->phone, 5, strlen ( $pedidoCompleto->clientProfileData->phone ) );
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Celular'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataCadastro'] = '';
				
			//$infosAdicionaisPedido = $this->_magento->buscaInformacoesAdicionaisPedido($d->increment_id);
		
			$cepEntregaFormatado = $this->Numeros($pedidoCompleto->shippingData->address->postalCode);
			$cepCobrancaFormatado = $this->Numeros($pedidoCompleto->shippingData->address->postalCode);
				
			// Dados do Endereço
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'] = $pedidoCompleto->shippingData->address->street;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'] = $pedidoCompleto->shippingData->address->number;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'] = $pedidoCompleto->shippingData->address->complement;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'] = substr ( $pedidoCompleto->shippingData->address->neighborhood, 0, 40 );
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Municipio'] = $pedidoCompleto->shippingData->address->city;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Estado'] = $pedidoCompleto->shippingData->address->state;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Cep'] = $cepEntregaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['TipoLocalEntrega'] = 'tleeDesconhecido'; // informação não vem da magento
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Pais'] = 'BR';
			// Dados do Endereço de Cobrança
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Logradouro'] = $pedidoCompleto->shippingData->address->street;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['NumeroLogradouro'] = $pedidoCompleto->shippingData->address->number;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ComplementoEndereco'] = $pedidoCompleto->shippingData->address->complement;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Bairro'] = substr ( $pedidoCompleto->shippingData->address->neighborhood, 0, 40 );
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Municipio'] = $pedidoCompleto->shippingData->address->city;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Estado'] = $pedidoCompleto->shippingData->address->state;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Cep'] = $cepCobrancaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['TipoLocalEntrega'] = 'tleeDesconhecido';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Pais'] = 'BR';
			// Dados do Endereço de Entrega
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Logradouro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['NumeroLogradouro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ComplementoEndereco'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Bairro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'];
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Municipio'] = $pedidoCompleto->shippingData->address->city;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Estado'] = $pedidoCompleto->shippingData->address->state;
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
			$dadosPedido [$i] ['NumeroDoPedido'] = $d->orderId;
			$dadosPedido [$i] ['NumeroDoPedidoV3'] = $d->orderId;
			$dadosPedido [$i] ['EMail'] = $pedidoCompleto->clientProfileData->email;
			$dadosPedido [$i] ['CPFouCNPJ'] = $cpfFormatado;
			$dadosPedido [$i] ['CodigoCliente'] = $cpfFormatado;
			//$dadosPedido [$i] ['CondicaoPagamento'] = 'COMPRAS'; //Validar			
			$dadosPedido [$i] ['ValorEncargos'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorEmbalagemPresente'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorReceberEntrega'] = '0.00'; // PENDENTE
			$dadosPedido [$i] ['ValorTrocoEntrega'] = '0.00'; // PENDENTE
			
			//Tratamento específico pra data
			list($data, $hora) = explode('T',  $d->creationDate);
			list($horaNova, $horaAdicional) = explode('.',  $hora);
			
			list($ano, $mes, $dia) = explode('-', $data);
			$dataFormatada = $dia.$mes.$ano.' '.$horaNova;
			
			$dadosPedido [$i] ['DataVenda'] = $dataFormatada;
			
			$array_transportadoras = $this->_vtex->trataArrayDto ( (array) $pedidoCompleto->shippingData->logisticsInfo );
            $valor_total_frete = number_format ($pedidoCompleto->totals[2]->value/100, 2, '.', '' );
            $valor_total_desconto = abs(number_format ($pedidoCompleto->totals[1]->value/100, 2, '.', '' ));
            $array_transportadoras = $this->_vtex->trataArrayDto ( (array) $array_transportadoras[0]->deliveryIds );

			$dadosPedido [$i] ['Transportadora'] = $array_transportadoras[0]->courierId;
			$dadosPedido [$i] ['EmitirNotaSimbolica'] = 0; //Boolean
			$dadosPedido [$i] ['Lote'] = 1; // Cadastrar um Padrão KPL
			$dadosPedido [$i] ['DestNome'] = $pedidoCompleto->shippingData->address->receiverName;
			$dadosPedido [$i] ['DestSexo'] = $sexoClientePedido;
			$dadosPedido [$i] ['DestEmail'] = $pedidoCompleto->clientProfileData->email;
			$dadosPedido [$i] ['DestTelefone'] = substr ( $pedidoCompleto->clientProfileData->phone, 5, strlen ( $pedidoCompleto->clientProfileData->phone ) );
			
			// Dados do Endereço
			$dadosPedido [$i] ['DestLogradouro'] = $pedidoCompleto->shippingData->address->street;
			$dadosPedido [$i] ['DestNumeroLogradouro'] = $pedidoCompleto->shippingData->address->number;
			$dadosPedido [$i] ['DestComplementoEndereco'] = $pedidoCompleto->shippingData->address->complement;
			$dadosPedido [$i] ['DestBairro'] = substr ( $pedidoCompleto->shippingData->address->neighborhood, 0, 40 );;
			
			$dadosPedido [$i] ['DestMunicipio'] = $pedidoCompleto->shippingData->address->city;
			$dadosPedido [$i] ['DestEstado'] = $pedidoCompleto->shippingData->address->state;
			$dadosPedido [$i] ['DestCep'] = $cepEntregaFormatado;
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
			// 			$dadosPedido [$i] ['OptouNFPaulista'] = ''; //Necessário verificar essa opção
			// 			//$dadosPedido [$i] ['CartaoPresenteBrinde'] = 1;			
			
			// Itens			
			$dados_item = $this->_vtex->trataArrayDto ( (array) $pedidoCompleto->items );

			foreach ($dados_item as $it => $item){
				
				$item = (object) $item;
				
				//Verificar se o item atual é o mesmo sku do item anterior
				if ($item->productId == $dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it -1] ['CodigoProduto']){
					continue;
				}
				//não importar item que é kit
				if ( $item->IsKit == "true" ) {
					continue;
				}
				$valor_total_produtos += number_format ((($item->price) - ((($item->Cost - $item->CostOff))))/100, 2, '.', '' ) * $item->quantity;
				
				try {
						
					$dadosItemProduto = $this->_getDadosProduto($item->id);
						
				} catch (Exception $e) {
					echo "Erro ao buscar Produto " . $item->id . ' - ' . $e->getMessage() . PHP_EOL;
					continue;
				}
								
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['CodigoProduto'] = $dadosItemProduto->RefId;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['QuantidadeProduto'] = (int) $item->quantity;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['PrecoUnitario'] = number_format ( $item->price/100, 2, '.', '' ); // valor unitário
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['MensagemPresente'] = $item->gift_message_available;
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['PrecoUnitarioBruto'] = number_format($item->price, 2, '.', '');
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['Brinde'] = '';
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['ValorReferencia'] = '';
				//$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['EmbalagemPresente'] = '';
			}			
			$totalPedidoPagamento = ($valor_total_produtos + $valor_total_frete) - $valor_total_desconto;
			
			try {					
				$dados_pagamento = $this->_getDadosPagamento($dadosPedido [$i] ['NumeroDoPedidoV3']);					
			} catch (Exception $e) {
				echo "Erro ao obter dados de pagamento " . $dadosPedido [$i] ['NumeroDoPedidoV3'] . ' - ' . $e->getMessage() . PHP_EOL;
				continue;
			}			
			
			$dadosPagamento = $this->_vtex->trataArrayDto ( (array) $dados_pagamento );
			
			if($dados_pagamento != null){
				$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['FormaPagamentoCodigo'] = $dadosPagamento ['0']['payments']['0']->paymentSystemName;
				$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['Valor'] = number_format( $totalPedidoPagamento, 2, '.', '');
				$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoQtdeParcelas'] = $dadosPagamento ['0']['payments']['0']->installments;
			}else{
				$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['FormaPagamentoCodigo'] = 'AFILIADO';
				$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['Valor'] = number_format( $totalPedidoPagamento, 2, '.', '');
				$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoQtdeParcelas'] = '1';
			}			
			
			$dadosPedido [$i] ['ValorPedido'] = number_format( $valor_total_produtos, 2, '.', '');
			$dadosPedido [$i] ['ValorFrete'] = number_format($valor_total_frete, 2, '.', '');
			$dadosPedido [$i] ['ValorDesconto'] = number_format($valor_total_desconto, 2, '.', '');
			
			try {
			
				echo "Importando pedido " . $dadosPedido [$i] ['NumeroDoPedidoV3'] . PHP_EOL;
				$this->_kpl->cadastraPedido( $dadosPedido );
				echo "Pedido importado/atualizado com sucesso" . PHP_EOL;
			
				echo "Atualizando status de pedido {$dadosPedido [$i] ['NumeroDoPedidoV3']} no ambiente VTEX" . PHP_EOL;
				$this->_mudarStatusPedido($dadosPedido [$i] ['NumeroDoPedidoV3'], 'ERP');
				echo "Status atualizado com sucesso" . PHP_EOL;
			
			} catch (Exception $e) {
				echo "Erro ao importar pedido " . $dadosPedido [$i] ['NumeroDoPedidoV3'] . ' - ' . $e->getMessage() . PHP_EOL;
				continue;
			}			
			
		}
	}

	/**
	 * Importa uma determinada quantidade de pedidos que estejam em um determinado status
	 * @param string $statusOrder Descrição do Status
	 * @param int 	 $Quantidade  Quantidade de pedidos a ser importado
	 * @param array $dadosCliente Dados de integração do cliente
	 * @return retorna mensagem em caso de erro
	 */
	public function importarPedidosStatusQuantidade($status, $quantidade, $dadosCliente) {
		
		if (empty ( $status )) {
			throw new InvalidArgumentException ( 'Status inválido' );
		}
		
		if (! ctype_digit ( $quantidade )) {
			throw new InvalidArgumentException ( 'Quantidade inválida' );
		}
		
		try {

            $url = sprintf($this->_url, 'oms/pvt/orders?f_status=');
            $url = $url . $status;
            //$url = 'http://piushop.vtexcommercestable.com.br/api/oms/pvt/orders/837821710331-01';


            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-VTEX-API-AppKey' => $this->_key,
                'X-VTEX-API-AppToken' => $this->_token
            );

            $request = Requests::get($url, $headers);

            if (! $request->success) {
                throw new RuntimeException('Falha na comunicação com o webservice. [' . $request->body . ']');
            }

            $pedidos = json_decode($request->body);
			//$pedidos = $this->_client->OrderGetByStatusByQuantity ( $status, $quantidade );
		} catch ( Exception $e ) {
			throw new RuntimeException ( 'Erro ao consultar Pedidos por status' );
		}
		if (! is_object( $pedidos )) {
			throw new DomainException ( 'Nenhum pedido pendente neste status - '. $pedidos );
		}
		
		if ( empty ( $pedidos->list ) && empty ( $pedidos->orderId ) ) {
			throw new DomainException ( 'Nenhum pedido pendente neste status' );
		}
		
		if( $pedidos->orderId != null ){
			$pedidos1->OrderGetByStatusByQuantityResult->OrderDTO [] = $pedidos;
		}else{
			$pedidos1->OrderGetByStatusByQuantityResult->OrderDTO = $pedidos->list;
		}
		
		$dados_pedidos = $this->_vtex->trataArrayDto ( $pedidos1->OrderGetByStatusByQuantityResult->OrderDTO );
		
		try {
			$this->_importarPedidos ( $dados_pedidos, $dadosCliente );
		} catch ( Exception $e ) {
			$this->_vtex->setErro ( array ("Id" => $value ['Id'], "Metodo" => "importarPedidosStatusQuantidade", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
		
		}		
	
	}
	
	/**
	 * Importa um determinado pedido
	 * @param string $idPedido
	 * @return retorna mensagem em caso de erro
	 */
	public function importarPedidoId($idPedido, $dadosCliente) {
	
		if (empty ( $idPedido )) {
			throw new InvalidArgumentException ( 'Status inválido' );
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
			$this->_importarPedidos ( $dados_pedidos, $dadosCliente );
		} catch ( Exception $e ) {
			$this->_vtex->setErro ( array ("Id" => $value ['Id'], "Metodo" => "importarPedidosStatusQuantidade", "DescricaoErro" => $e->getMessage () ), "Pedido_Saida" );
	
		}
	
	}

	/**
	 * Muda Status de um Array de ID de pedidos
	 * @param array $array_id Array de Pedidos para Mudança de Status
	 * @param array $array_id_erro Array de Pedidos que tiveram erro e não serão atualizados 
	 * @param int $nro_movimento Id do Cliente
	 */
	private function _mudarStatusPedido ( $order_id, $status ) {
	
		$url = sprintf($this->_url, "oms/pvt/orders/{$order_id}/start-handling");
		$headers = array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'X-VTEX-API-AppKey' => $this->_key,
				'X-VTEX-API-AppToken' => $this->_token
		);
	
		$request = Requests::post($url, $headers);
	
		if (! $request->success) {
			throw new RuntimeException('Falha na comunicação com o webservice. [' . $request->body . ']');
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

}
