<?php
abstract class KrisDB
{
    protected $_dbh = null;
    protected $_recordSet = array(); // for holding all object property variables
    protected $_compressArray = true;


    function get($key)
    {
        return $this->_recordSet[$this->convertDBKeyToClassKey($key)];
    }

    protected function clearRecordSet($records)
    {
        foreach ($records as $key)
        {
            $this->_recordSet[$this->convertDBKeyToClassKey($key)] = '';
        }
    }

    function set($key, $val)
    {
        $key = $this->convertDBKeyToClassKey($key);
        if (isset($this->_recordSet[$key]))
        {
            $this->_recordSet[$key] = $val;
        }
        return $this;
    }

    function __get($key)
    {
        return $this->get($key);
    }

    function __set($key, $val)
    {
        return $this->set($key, $val);
    }

    /**
     * @return PDO
     */
    protected function getDatabaseHandler()
    {
        if (is_null($this->_dbh))
        {
            $this->_dbh = KrisConfig::GetDatabaseHandler();
        }
        return $this->_dbh;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function quoteDbObject($name)
    {
        if (KrisConfig::$DATABASE_QUOTE_STYLE == KrisConfig::QuoteStyleMysql)
        {
            return '`' . $name . '`';
        }
        elseif (KrisConfig::$DATABASE_QUOTE_STYLE ==  KrisConfig::QuoteStyleMssql)
        {
            return '[' . $name . ']';
        }
        else
        {
            return '"' . $name . '"';
        }
    }

    /**
     * Binds a model to a row in a recordSet
     *
     * @param array $rs
     * @param KrisDB $bindTo
     * @return bool|KrisDB
     */
    protected function bindRecordSet($rs, $bindTo)
    {
        if (!$rs)
        {
            return false;
        }
        foreach ($rs as $key => $val)
        {
            $key = $this->convertDBKeyToClassKey($key);
            if (isset($bindTo->_recordSet[$key]))
            {
                $bindTo->_recordSet[$key] = is_scalar($this->_recordSet[$key]) ? $val : unserialize($this->_compressArray
                            ? gzinflate($val) : $val);
            }
        }
        return $this;
    }

    /**
     * Returns multiple instances of a model based on a statement...
     *
     * @param PDOStatement $stmt
     * @return array
     */
    protected function returnMultiple($stmt)
    {
        $arr = array();
        $class = get_class($this);
        while ($rs = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $model = new $class();
            $this->bindRecordSet($rs, $model);
            $arr[] = $model;

        }
        return $arr;
    }

    /**
     * @param string $sql
     * @param int $count
     * @return string
     */
    protected  function addLimit($sql, $count)
    {
        if ($count > 0)
        {
            return $sql .' LIMIT '.$count;
        }
        return $sql;
    }


    /**
     * @param string $sql
     * @param string|array $order
     * @return string
     */
    protected function addOrder($sql, $order)
    {
        if ((is_array($order) && count($order) > 0) || strlen($order) > 0)
        {
            if (is_array($order))
            {
                $order = implode(',' , $order);
            }
            return $sql.' ORDER BY '.$order;
        }
        return $sql;
    }

    /**
     * @param array|string $what
     * @return string
     */
    protected function generateWhat($what)
    {
        if (is_array($what))
        {
            $whatString = '';
            foreach ($what as $whereName)
            {
                $whatString .= (strlen($whatString) > 0 ? ', ' : '') . $this->quoteDbObject($whereName) . ' = ?';
            }
            return $whatString;
        }
        return $what;
    }

    /**
     * @throws Exception
     * @param array|string $where
     * @param array $bindings
     * @return string
     */
    protected function generateWhere($where, $bindings)
    {
        if (is_array($where))
        {
            if (count($where) != count($bindings))
            {
                throw new Exception('Count of where (' . count($where) . ') does not equal the count of bindings (' . count($bindings) . ')');
            }
            $whereString = '';
            foreach ($where as $whereName)
            {
                $whereString .= (strlen($whereString) > 0 ? ', ' : '') . $this->quoteDbObject($whereName) . ' = ?';
            }
            return $whereString;
        }
        return $where;
    }

    /**
     * Converts a key like record_id to RecordId
     * @param string $key
     * @return string
     */
    protected function convertDBKeyToClassKey($key)
    {
        if (strpos($key, '_') !== false ||  $key[0] != strtoupper($key[0]))
        {
            return str_replace(' ', '',ucwords(str_replace('_', ' ', $key)));
        }
        return $key;
    }

    /**
     * Converts a key like RecordId to record_id
     * @param string $key
     * @return string
     */
    protected function convertClassKeyToDBKey($key)
    {
        if (strpos($key, '_') === false || $key[0] != strtolower($key[0]))
        {
            $key = strtolower(substr($key, 0, 1)).substr($key, 1);
            return preg_replace_callback('/_?[A-Z]/', create_function('$matches', 'return \'_\'.strtolower($matches[0]);'), $key);
        }
        return $key;
    }


}
