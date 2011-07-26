<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
class DebugPDO
{
    /**
     * @var \PDO
     */
    private $_pdo;
    public $DatabaseLog = array();

    public function __construct ($dsn, $username, $password, $options = null)
    {
        $this->_pdo = new PDO($dsn, $username, $password, $options);
    }

    /**
     * @param string $statement
     * @param array|null $driver_options
     * @return PDOStatement
     */
	public function prepare ($statement, array $driver_options = array())
    {
        return new DebugPDOStatement($this->_pdo->prepare($statement, $driver_options), $this);
    }

	public function beginTransaction ()
    {
        return $this->_pdo->beginTransaction();
    }

	public function commit ()
    {
        return $this->_pdo->commit();
    }

	public function rollBack ()
    {
        return $this->_pdo->rollBack();
    }

	public function setAttribute ($attribute, $value)
    {
        return $this->_pdo->setAttribute($attribute, $value);
    }

    /**
     * @param string $statement
     * @return int
     */
	public function exec ($statement)
    {
        return $this->_pdo->exec($statement);
    }

    public function query ($statement)
    {
        return $this->_pdo->query($statement);
    }

	public function lastInsertId ($name = null)
    {
        return $this->_pdo->lastInsertId($name);
    }


	public function errorCode ()
    {
        return $this->_pdo->errorCode();
    }

	public function errorInfo ()
    {
        return $this->_pdo->errorInfo();
    }

	public function getAttribute ($attribute)
    {
        return $this->_pdo->getAttribute($attribute);
    }

	public function quote ($string, $parameter_type = null)
    {
        return $this->_pdo->quote($string, $parameter_type);
    }

    public function AddLog($function, $query, $microseconds)
    {
        $this->DatabaseLog[] = array('function' => $function, 'query' => $query, 'microseconds' => $microseconds);
    }
}

class DebugPDOStatement
{

   private $_statement;


	/**
	 * @var string
	 */
	public $queryString;

    /**
     * @param PDOStatement $statement
     * @param DebugPDO $debugPdo
     * @return \DebugPDOStatement
     *
     */
    public function __construct($statement, $debugPdo)
    {
        $this->_statement = $statement;
        $this->queryString = $statement->queryString;
        $this->_debugPdo = $debugPdo;
    }

    
	public function execute (array $input_parameters = null)
    {
        $startTime = microtime(true);
        $ret = $this->_statement->execute($input_parameters);
        $endTime = microtime(true);

        $this->_debugPdo->AddLog('pdoStatement::execute', $this->queryString, $endTime - $startTime);

        return $ret;
    }

	public function fetch ($fetch_style = null, $cursor_orientation = null, $cursor_offset = null)
    {
        return $this->_statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }


	public function bindParam ($parameter, &$variable, $data_type = null, $length = null, $driver_options = null)
    {
        return $this->_statement->bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

	public function bindColumn ($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        return $this->_statement->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

	public function bindValue ($parameter, $value, $data_type = null)
    {
        return $this->_statement->bindValue($parameter, $value, $data_type);
    }

    public function rowCount ()
    {
        return $this->_statement->rowCount();
    }


	public function fetchColumn ($column_number = null)
    {
        return $this->_statement->fetchColumn($column_number);
    }


	public function fetchAll ($fetch_style = null, $column_index = null, array $ctor_args = null)
    {
        return $this->_statement->fetchAll($fetch_style, $column_index, $ctor_args);
    }

	public function fetchObject ($class_name = null, array $ctor_args = null)
    {
        return $this->_statement->fetchObject($class_name, $ctor_args);
    }

	public function errorCode ()
    {
        return $this->_statement->errorCode();
    }

	public function errorInfo ()
    {
        return $this->_statement->errorInfo();
    }

	public function setAttribute ($attribute, $value)
    {
        return $this->_statement->setAttribute($attribute, $value);
    }


	public function getAttribute ($attribute)
    {
        return $this->_statement->getAttribute($attribute);
    }

	public function columnCount ()
    {
        return $this->_statement->columnCount();
    }


	public function getColumnMeta ($column)
    {
        return $this->_statement->getColumnMeta($column);
    }

	public function setFetchMode ($mode)
    {
        return $this->_statement->setFetchMode($mode);
    }

	public function nextRowset ()
    {
        return $this->_statement->nextRowset();
    }

    public function closeCursor ()
    {
        return $this->_statement->closeCursor();
    }
}