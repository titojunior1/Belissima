<?php

/**
 * Core_Db_AdapterInterface
 */
abstract class Core_Db_AdapterInterface {

	public abstract function execute($sql, $params = array());

	public abstract function fetch($sql, $params = array());

	public abstract function fetchAll($sql, $params = array());

	public abstract function getLastInsertId();

	public abstract function getLastMessageError();

}