<?php
/**
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

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param null|array $options
     */
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
        $startTime = microtime(true);
        $ret = new DebugPDOStatement($this->_pdo->prepare($statement, $driver_options), $this);
        $endTime = microtime(true);

        $this->AddLog('pdo::prepare', $statement, $endTime - $startTime);

        return $ret;

    }

    /**
     * @return bool
     */
	public function beginTransaction ()
    {
        return $this->_pdo->beginTransaction();
    }

    /**
     * @return bool
     */
	public function commit ()
    {
        return $this->_pdo->commit();
    }

    /**
     * @return bool
     */
	public function rollBack ()
    {
        return $this->_pdo->rollBack();
    }

    /**
     * @param int $attribute
     * @param mixed $value
     * @return bool
     */
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

    /**
     * @param string $statement
     * @return PDOStatement
     */
    public function query ($statement)
    {
        return $this->_pdo->query($statement);
    }

    /**
     * @param null|string $name
     * @return string
     */
	public function lastInsertId ($name = null)
    {
        return $this->_pdo->lastInsertId($name);
    }

    /**
     * @return mixed
     */
	public function errorCode ()
    {
        return $this->_pdo->errorCode();
    }

    /**
     * @return array
     */
	public function errorInfo ()
    {
        return $this->_pdo->errorInfo();
    }

    /**
     * @param int $attribute
     * @return mixed
     */
	public function getAttribute ($attribute)
    {
        return $this->_pdo->getAttribute($attribute);
    }

    /**
     * @param string $string
     * @param null|int $parameter_type
     * @return string
     */
	public function quote ($string, $parameter_type = null)
    {
        return $this->_pdo->quote($string, $parameter_type);
    }

    /**
     * @param string $function
     * @param string $query
     * @param float $microseconds
     * @return void
     */
    public function AddLog($function, $query, $microseconds)
    {
        $this->DatabaseLog[] = array('function' => $function, 'query' => $query, 'microseconds' => $microseconds);
    }

    /**
     * @param int $addMicroseconds
     * @return void
     */
    public function AddToLog($addMicroseconds)
    {
        $this->DatabaseLog[count($this->DatabaseLog) - 1]['microseconds'] += $addMicroseconds;
    }
}

/**
 *
 */
class DebugPDOStatement
{

    /**
     * @var \PDOStatement
     */
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

    /**
     * @param array|null $input_parameters
     * @return bool
     */
	public function execute (array $input_parameters = null)
    {
        $startTime = microtime(true);
        $ret = $this->_statement->execute($input_parameters);
        $endTime = microtime(true);

        $this->_debugPdo->AddToLog($endTime - $startTime);

        return $ret;
    }

    /**
     * @param int|null $fetch_style
     * @param int|null $cursor_orientation
     * @param int|null $cursor_offset
     * @return mixed
     */
	public function fetch ($fetch_style = null, $cursor_orientation = null, $cursor_offset = null)
    {
        $startTime = microtime(true);
        $ret = $this->_statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        $endTime = microtime(true);

        $this->_debugPdo->AddToLog($endTime - $startTime);

        return $ret;
    }

    /**
     * @param mixed $parameter
     * @param mixed $variable
     * @param null|int $dataType
     * @param null|int $length
     * @param null|int $driverOptions
     * @return bool
     */
	public function bindParam ($parameter, &$variable, $dataType = null, $length = null, $driverOptions = null)
    {
        return $this->_statement->bindParam($parameter, $variable, $dataType, $length, $driverOptions);
    }

    /**
     * @param mixed $column
     * @param mixed $param
     * @param int|null $type
     * @param int|null $maxLength
     * @param mixed|null $driverData
     * @return bool
     */
	public function bindColumn ($column, &$param, $type = null, $maxLength = null, $driverData = null)
    {
        return $this->_statement->bindColumn($column, $param, $type, $maxLength, $driverData);
    }

    /**
     * @param mixed $parameter
     * @param mixed $value
     * @param null|int $data_type
     * @return bool
     */
	public function bindValue ($parameter, $value, $data_type = null)
    {
        return $this->_statement->bindValue($parameter, $value, $data_type);
    }

    /**
     * @return int
     */
    public function rowCount ()
    {
        return $this->_statement->rowCount();
    }


    /**
     * @param null|int $column_number
     * @return string
     */
	public function fetchColumn ($column_number = null)
    {
        return $this->_statement->fetchColumn($column_number);
    }

    /**
     * @param null|int $fetch_style
     * @return array
     */
	public function fetchAll ($fetch_style = null)
    {
        $startTime = microtime(true);
        $ret = $this->_statement->fetchAll($fetch_style);
        $endTime = microtime(true);

        $this->_debugPdo->AddToLog($endTime - $startTime);

        return $ret;

    }

    /**
     * @param string|null $class_name
     * @param array|null $ctor_args
     * @return mixed
     */
	public function fetchObject ($class_name = null, array $ctor_args = null)
    {
        return $this->_statement->fetchObject($class_name, $ctor_args);
    }

    /**
     * @return string
     */
	public function errorCode ()
    {
        return $this->_statement->errorCode();
    }

    /**
     * @return array
     */
	public function errorInfo ()
    {
        return $this->_statement->errorInfo();
    }

    /**
     * @param int $attribute
     * @param mixed $value
     * @return bool
     */
	public function setAttribute ($attribute, $value)
    {
        return $this->_statement->setAttribute($attribute, $value);
    }


    /**
     * @param int $attribute
     * @return mixed
     */
	public function getAttribute ($attribute)
    {
        return $this->_statement->getAttribute($attribute);
    }

    /**
     * @return int
     */
	public function columnCount ()
    {
        return $this->_statement->columnCount();
    }

    /**
     * @param int $column
     * @return array
     */
	public function getColumnMeta ($column)
    {
        return $this->_statement->getColumnMeta($column);
    }

    /**
     * @param int $mode
     * @return bool
     */
	public function setFetchMode ($mode)
    {
        return $this->_statement->setFetchMode($mode);
    }

    /**
     * @return bool
     */
	public function nextRowset ()
    {
        return $this->_statement->nextRowset();
    }

    /**
     * @return bool
     */
    public function closeCursor ()
    {
        return $this->_statement->closeCursor();
    }
}