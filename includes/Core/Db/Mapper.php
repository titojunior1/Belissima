<?php

/**
 * Total_Db_Mapper
 */
class Core_Db_Mapper {

	const FETCH_OBJECT = 1;

	const FETCH_ARRAY = 2;

	const FETCH_ROW = 3;

	/**
	 * Nome da tabela do banco de dados.
	 * @var string
	 */
	protected $_tableName;

	/**
	 * Mapeamento dos campos da tabela.
	 * A chave deve ser o nome do campo da tabela e o valor
	 * o atributo no objeto.
	 *
	 * @var array
	 */
	protected $_fields = array();

	/**
	 *
	 * @var array
	 */
	protected $_primaryKey = array();

	/**
	 *
	 * @var string
	 */
	protected $_modelClassName = 'Core_Domain_BaseModel';

	/**
	 *
	 * @var Core_Db_AdapterInterface
	 */
	protected $_dbAdapter;

	/**
	 *
	 * @param Core_Db_AdapterInterface $dbAdapter
	 */
	public function __construct(Core_Db_AdapterInterface $dbAdapter) {
		$this->_dbAdapter = $dbAdapter;
	}

	/**
	 *
	 * @return Core_Db_AdapterInterface
	 */
	public function getDbAdapter() {
		return $this->_dbAdapter;
	}

	/**
	 *
	 * @param mixed $id
	 * @throws InvalidArgumentException
	 * @return Core_Domain_BaseModel
	 */
	public function fetch($id) {
		if (! is_array($id)) {
			$id = array(
				$id
			);
		} else if (func_num_args() > 1) {
			$id = func_get_args();
		}

		if (count($this->_primaryKey) != count($id)) {
			throw new InvalidArgumentException('O número de chaves-primária da entidade é diferente do informado.');
		}

		$sql = "SELECT * FROM {$this->_tableName} WHERE ";

		$conditions = array();

		foreach($this->_primaryKey as $key) {
			$conditions[] = " $key = ?";
		}

		$sql .= implode(' AND ', $conditions);

		$result = $this->_dbAdapter->fetch($sql, $id);
		$model = null;

		if ($result) {
			$model = $this->mapFromArray($result);
		}

		return $model;
	}

	/**
	 *
	 * @param array $conditions
	 * @throws LogicException
	 * @return Core_Domain_BaseModel Retorna uma entidade ou nulo.
	 */
	public function fetchOneBy(array $conditions) {
		$colsAndFields = array_flip($this->_fields);

		$where = array();
		$params = array();

		foreach($conditions as $col => $value) {
			if(!array_key_exists($col, $colsAndFields)) {
				throw new LogicException("O atributo [$col] não esta mapeado.");
			}

			$field = $colsAndFields[$col];
			$where[] = "$field = ?";
			$params[] = $value;
		}

		$result = $this->getDbAdapter()->fetch("SELECT * FROM {$this->_tableName} WHERE " . implode(' AND ', $where) . " LIMIT 1", $params);
		$model = null;

		if ($result) {
			$model = $this->mapFromArray($result);
		}

		return $model;

	}

	public function fetchBy(array $conditions) {

	}

	/**
	 *
	 * @param string $where
	 * @param array $order
	 * @param string $limit
	 * @param string $offset
	 * @param integer $fetch
	 * @return Core_Domain_BaseModel
	 */
	public function fetchAll($where = null, $order = array(), $limit = false, $offset = false, $fetch = self::FETCH_OBJECT) {
		$sql = "SELECT * FROM {$this->_tableName} ";

		if (! empty($where)) {
			$sql .= " WHERE {$where}";
		}

		if (! empty($order)) {
			$sql = $this->_applyOrderBy($sql, $order);
		}

		if ($limit !== false && $offset !== false) {
			$sql = $this->_applyLimitOffset($limit, $offset);
		}

		$rows = $this->_dbAdapter->fetchAll($sql);
		$result = array();

		switch ($fetch) {
			case self::FETCH_ROW :
				$result = $rows;
				break;

			case self::FETCH_ARRAY :
				;
				break;
			default :
				foreach($rows as $row) {
					$result[] = $this->mapFromArray($row);
				}
				break;
		}

		return $result;
	}

	/**
	 *
	 * @param string $sql
	 * @param array $params
	 * @param Core_Domain_BaseModel $model
	 * @return array
	 */
	public function fetchByQuery($sql, $params=array(), Core_Domain_BaseModel $model = null) {
		$rows = $this->getDbAdapter()->fetchAll($sql, $params);
		$results = array();

		foreach($rows as $row) {
			$results[] = $this->mapFromArray($row, $model);
 		}

		return $results;
	}

	/**
	 * Executa a inserção da entidade no banco.
	 *
	 * @param Core_Domain_BaseModel $model
	 * @return Core_Domain_BaseModel
	 */
	public function insert(Core_Domain_BaseModel $model) {
		$data = $model->toArray();

		$colsAndValues = $this->_normalizeData($data);
		$cols = $this->_extractColumns($data);

		$vals = array_values($colsAndValues);
		$binds = rtrim(str_repeat('?, ', count($vals)), ', ');

		$sql = "INSERT INTO {$this->_tableName} (" . implode(', ', $cols) . ") VALUES ({$binds})";

		$this->_dbAdapter->execute($sql, $vals);

		$id = $this->_dbAdapter->getLastInsertId();

		if(false === $id) {
			throw new RuntimeException('Erro ao inserir registro. [' . $this->_dbAdapter->getLastMessageError() . ']');
		}

		return $this->fetch($id);
	}

	/**
	 * Atualiza a entidade.
	 *
	 * @param Core_Domain_BaseModel $model
	 * @throws RuntimeException
	 * @return boolean Retorna falso em caso de erro.
	 */
	public function update(Core_Domain_BaseModel $model) {
		$data = $model->toArray();

		$colsAndValues = $this->_normalizeData($data);
		$pksAndValues = $this->_extractKeys($colsAndValues);

		// remove pks
		foreach($colsAndValues as $key => $value) {
			$found = array_search($key, $this->_primaryKey);

			if ($found !== false) {
				unset($colsAndValues[$key]);
			}
		}

		if (empty($pksAndValues)) {
			throw new RuntimeException("Não possível determinar as chaves-primárias para o modelo [" . get_class($model) . "]. Verifique se o atributo _primaryKeys foi definido no modelo.");
		}

		$values = array();
		$set = array();

		foreach($colsAndValues as $col => $value) {
			$set[] = "{$col} = ?";
			$values[] = $value;
		}

		$where = array();

		foreach($pksAndValues as $pk => $value) {
			$where[] = "$pk = ?";
			$values[] = $value;
		}

		$sql = "UPDATE {$this->_tableName} SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $where);

		return $this->_dbAdapter->execute($sql, $values);
	}

	public function save(Core_Domain_BaseModel $model) {

	}

	public function delete(Core_Domain_BaseModel $model) {

	}

	/**
	 *
	 * @param array $data
	 * @return Core_Domain_BaseModel
	 */
	public function mapFromArray($data, Core_Domain_BaseModel $model = null) {
		$options = array();

		foreach($this->_fields as $field => $attrib) {
			if (array_key_exists($field, $data)) {
				$options[$attrib] = $data[$field];
			}
		}

		return null === $model ? new $this->_modelClassName($options) : $model->fromArray($options);
	}

	protected function _normalizeData($data) {
		$normalized = array();

		foreach($this->_fields as $col => $field) {
			if (array_key_exists($field, $data)) {
				$normalized[$col] = $data[$field];
			}
		}

		return $normalized;
	}

	protected function _extractColumns($data, $ignore = array()) {
		$cols = array();

		foreach($data as $key => $value) {
			$col = array_search($key, $this->_fields);

			if ($col !== false && ! in_array($col, $ignore)) {
				$cols[] = $col;
			}
		}

		return $cols;
	}

	protected function _extractKeys($data) {
		$cols = array();

		foreach($data as $key => $value) {
			$col = array_search($key, $this->_primaryKey);

			if ($col !== false) {
				$cols[$this->_primaryKey[$col]] = $value;
			}
		}

		return $cols;
	}

	/**
	 *
	 * @param string $sql
	 * @param array $order
	 * @return string
	 */
	protected function _applyOrderBy($sql, array $order) {
		$orderBy = array();

		foreach($order as $col => $dir) {
			$orderBy[] = "$col $dir";
		}

		return $sql . " ORDER BY " . implode(' ,', $orderBy);
	}

	/**
	 *
	 * @param string $sql
	 * @param integer $limit
	 * @param integer $offset
	 * @return string
	 */
	protected function _applyLimitOffset($sql, $limit, $offset) {
		return "{$sql} LIMIT {$limit} OFFSET {$offset}";
	}
}