<?php

/**
 * Core_Db_MapperFactory
 *
 * @name Core_Db_MapperFactory
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
class Core_Db_MapperFactory {

	private static $_defaultDbAdapter;

	/**
	 *
	 * @return Core_Db_AdapterInterface
	 */
	public static function getDefaultDbAdapter() {
		return self::$_defaultDbAdapter;
	}

	/**
	 *
	 * @param Core_Db_AdapterInterface $dbAdapter
	 */
	public static function setDefaultDbAdapter(Core_Db_AdapterInterface $dbAdapter) {
		self::$_defaultDbAdapter = $dbAdapter;
	}

	/**
	 *
	 * @return boolean
	 */
	public static function hasDefaultDbAdapter() {
		return null !== self::$_defaultDbAdapter;
	}

	/**
	 *
	 * @param string $mapper
	 * @param Core_Db_AdapterInterface $dbAdapter
	 * @throws RuntimeException
	 * @return Core_Db_Mapper
	 */
	public function get($mapper, Core_Db_AdapterInterface $dbAdapter = null) {
		if(!class_exists($mapper)) {
			throw new RuntimeException("A classe mapper '$mapper' não existe.");
		}

		if(null == $dbAdapter) {
			if(!self::hasDefaultDbAdapter()) {
				throw new RuntimeException('Nenhuma instância de Core_Db_AdapterInterface foi definida.');
			}

			$dbAdapter = self::getDefaultDbAdapter();
		}

		return new $mapper($dbAdapter);
	}
}