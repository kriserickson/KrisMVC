<?php

/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

//===============================================================
// DB
// Base class that is shared by Model and DBView
//===============================================================

abstract class KrisDB
{
    protected $_dbh = null;
    protected $_recordSet = array(); // for holding all object property variables
    protected $_initializedRecordSet = false;


    /**
     * Used to get a field from the Model/DBView
     *
     * @throws DatabaseException
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        $fixedKey = $this->convertDBKeyToClassKey($key);
        if (!isset($this->_recordSet[$fixedKey]))
        {
            throw new DatabaseException('Invalid key: '.$key);
        }

        return $this->_recordSet[$fixedKey];
    }


    /**
     * Used by the model to set variables, they can be stored later with Insert/Update.
     *
     * @param string $key
     * @param string $val
     * @return KrisDB
     */
    public function set($key, $val)
    {
        $key = $this->convertDBKeyToClassKey($key);
        if (!$this->_initializedRecordSet || isset($this->_recordSet[$key]))
        {
            // If $val is null then isset will return false.  We don't want that cause get uses isset to determin
            // whether the field is valid or not..
            $this->_recordSet[$key] = is_null($val) ? '' : $val;
        }
        return $this;
    }

    /**
     * Magic Function access to get.  Syntactical sugar that allows $db->SomeField rather than $db->get('SomeField')
     *
     * @param string $key
     * @return string
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic Function access to set.  Syntactical sugar that allows $db->SomeField = 'SomeValue' rather than $db->set('SomeField', 'SomeValue');
     *
     * @param string $key
     * @param string $val
     * @return KrisDB
     */
    public function __set($key, $val)
    {
        return $this->set($key, $val);
    }

    /**
    * Used by Model/DBViews to set which fields they wish to be read/written to.
    *
    * @param array $records
    * @return void
    */
    protected function initializeRecordSet($records)
    {
        foreach ($records as $key)
        {
            $this->_recordSet[$this->convertDBKeyToClassKey($key)] = '';
        }
        $this->_initializedRecordSet = true;
    }


    /**
     * Keeps a cache of the the databaseHandle..
     *
     * @return PDO
     */
    protected function getDatabaseHandle()
    {
        if (is_null($this->_dbh))
        {
            $this->_dbh = KrisConfig::GetDatabaseHandle();
        }
        return $this->_dbh;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function quoteDbObject($name)
    {
        if (KrisConfig::$DATABASE_TYPE == KrisConfig::DB_TYPE_MYSQL)
        {
            return '`' . $name . '`';
        }
        elseif (KrisConfig::$DATABASE_TYPE ==  KrisConfig::DB_TYPE_MSSQL)
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
            if (!$this->_initializedRecordSet || isset($bindTo->_recordSet[$key]))
            {
                // If $val is null then isset will return false.  We don't want that cause get uses isset to determin
                // whether the field is valid or not..
                $bindTo->_recordSet[$key] = is_null($val) ? '' : $val;
            }
        }
        return $bindTo;
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
     * Adds the limit onto the query
     *
     * @param string $sql
     * @param int $count
     * @param int $offset
     * @return string
     */
    protected  function addLimit($sql, $count, $offset = 0)
    {
        if ($count > 0)
        {
            // TODO: Write the MSSQL and other versions of offset...
            return $sql .' LIMIT '.($offset > 0 ? $offset.', ' : '').$count;
        }
        return $sql;
    }


    /**
     * Adds ordering to the recordset...
     *
     * @param string $sql
     * @param string|array $order
     * @param bool $orderAscending
     * @return string
     */
    protected function addOrder($sql, $order, $orderAscending = true)
    {
        if ((is_array($order) && count($order) > 0) || strlen($order) > 0)
        {
            if (is_array($order))
            {
                $orderBy = '';
                foreach ($order as $orderKey)
                {
                    $orderBy .= (strlen($orderBy) > 0 ? ', ' : '').$this->convertClassKeyToDBKey($orderKey);
                }
            }
            else
            {
                $orderBy = $this->convertClassKeyToDBKey($order);
            }
            $sql .= ' ORDER BY '.$orderBy.' '.($orderAscending ? 'ASC' : 'DESC');
        }
        return $sql;
    }

    /**
     * Generates which fields to select
     *
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
                $whatString .= (strlen($whatString) > 0 ? ', ' : '') . $this->quoteDbObject($whereName);
            }
            return $whatString;
        }
        return $what;
    }

    /**
     * Generates the where portion of the query
     *
     * @throws DatabaseException
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
                throw new DatabaseException('Count of where (' . count($where) . ') does not equal the count of bindings (' . count($bindings) . ')');
            }
            $whereString = '';
            foreach ($where as $whereName)
            {
                $whereString .= (strlen($whereString) > 0 ? ' AND ' : '') . $this->quoteDbObject($whereName) . ' = ?';
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
            return preg_replace_callback('/_?[A-Z]/', function($matches) { return '_'.strtolower($matches[0]);}, $key);
        }
        return $key;
    }

    protected function convertClassKeyToDisplayField($key)
    {
        if (strpos($key, '_') > 0 && $key[0] == strtolower($key[0]))
        {
            $key = $this->convertDBKeyToClassKey($key);
        }
        return $key[0].preg_replace('/[A-Z]/', ' $0',substr($key,1));
    }

    /**
     * Validates
     *
     * @throws DatabaseException
     * @param PDOStatement $stmt
     * @return void
     */
    protected function ValidateStatement($stmt)
    {
        if ($stmt->errorCode() > 0)
        {
            $info = $stmt->errorInfo();
            throw new DatabaseException('Invalid SQL ['.$stmt->queryString.']'.PHP_EOL.
                        'Error: ' . $info[0] . ' - ' . $info[1] . ' -- ' . $info[2]);
        }
    }

    /**
     * @return array
     */
    public function GetAllFields()
    {
        return array_keys($this->_recordSet);
    }

}

class DatabaseException extends Exception { }
