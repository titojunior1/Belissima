<?php

/**
 * Core_Danfe_GeradorPdfDanfe
 *
 * @name Core_Danfe_GeradorPdfDanfe
 * @author Humberto.rodrigues_a
 *
 */
class Core_Danfe_GeradorPdfDanfe {

	/**
	 *
	 * @var string
	 */
	private $_path;

	/**
	 */
	public function __construct($path) {
		$this->_validarDiretorio($path);
		$this->_path = realpath($path);
	}

	/**
	 * Gera o PDF da DANFE a partir de XML.
	 *
	 * @param string $xml
	 * @param string $chaveDanfe
	 * @see http://www.nfephp.org/
	 */
	public function gerarDeXml($xml, $chaveDanfe) {
		$xmlDanfe = new DanfeNFePHP($xml);
		$chave = $xmlDanfe->montaDANFE();
		$file = $this->_path . DIRECTORY_SEPARATOR . "NFe{$chaveDanfe}.pdf";

		$xmlDanfe->printDANFE($file,'F');
	}

	/**
	 * Gera o PDF da DANFE a partir de um string codificada em base 64.
	 *
	 * @param string $conteudo
	 * @param string $chaveDanfe
	 * @throws RuntimeException
	 * @see http://php.net/manual/pt_BR/function.base64-decode.php, http://php.net/manual/pt_BR/function.base64-encode.php
	 */
	public function gerarDeBase64($conteudo, $chaveDanfe) {
		$file = $this->_path . DIRECTORY_SEPARATOR . "NFe{$chaveDanfe}.pdf";

		$handle = @fopen($file, "w");

		if(!$handle) {
			throw new RuntimeException('Falha ao tentar gerar o arquivo. Verifique as permissões do diretório.');
		}

		$stream = base64_decode($conteudo);

		if(!$stream) {
			throw new RuntimeException('Falha ao tentar decodificar os dados. Verifique se o conteúdo não contém espaços.');
		}

		@fwrite($handle, $stream);
		@fclose($handle);
	}

	private function _validarDiretorio($path) {
		if(!is_writable($path)) {
			throw new RuntimeException("O diretório '{$path}' não existe e/ou não tem permissão de escrita.");
		}
	}
}