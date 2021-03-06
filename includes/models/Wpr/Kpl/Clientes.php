<?php
/**
 * 
 * Classe para processar o cadastro de clientes via webservice do ERP KPL - �bacos 
 * 
 * @author Tito Junior 
 * 
 */
class Model_Wpr_Kpl_Clientes extends Model_Wpr_Kpl_KplWebService {
	
	/*
	 * Instancia Webservice KPL
	 */
	private $_kpl;
	
	/*
	 * Instancia Webservice Magento
	 */
	private $_chaveIdentificacao;
	
	/**
	 * 
	 * construtor.	 
	 */
	function __construct($ws, $key) {
		
		if ( empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wpr_Kpl_KplWebService ( $ws, $key  );
		}
		$this->_chaveIdentificacao = $key;
	
	}
	
	/**
	 * 
	 * Adicionar cliente.
	 * @param array $dadosCliente
	 * @throws Exception
	 * @throws RuntimeException
	 */
	public function adicionaCliente ( $dadosCliente ) {	 

		$retorno = $this->_kpl->cadastraCliente($this->_chaveIdentificacao, $dadosCliente);
		
		if ( $retorno ['CadastrarClienteResult'] ['Rows'] ['DadosClientesResultado'] ['Resultado'] ['Codigo'] == '200002' ){
			return true;
		}else{
			throw new RuntimeException( $retorno ['CadastrarClienteResult'] ['Rows'] ['DadosClientesResultado'] ['Resultado'] ['Descricao'] );
		}		

	}
	
	/**
	 *
	 * Cadastra Pedido
	 * @param array $dadosPedido
	 * @throws Exception
	 * @throws RuntimeException
	 */
	public function cadastraPedido ( $dadosPedido ) {
	
		$retorno = $this->_kpl->cadastraPedidoKpl($this->_chaveIdentificacao, $dadosPedido);
	
		if ( $retorno ['InserirPedidoResult'] ['Rows'] ['DadosPedidosResultado'] ['Resultado'] ['Codigo'] == '200002' ){
			return true;
		}else{
			throw new RuntimeException( $retorno ['InserirPedidoResult'] ['Rows'] ['DadosPedidosResultado'] ['Resultado'] ['ExceptionMessage'] );
		}
	
	}

}

