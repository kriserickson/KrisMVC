<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


/**
 * @package Model
 *
 * Model
 * Simplistic ActiveRecord style ORM that represents a table in the database...
 */
abstract class KrisModel extends KrisDB
{

    protected $_primaryKeyName;
    protected $_tableName;

    /**
     * @param string $primaryKeyName
     * @param string $tableName
     */
    function __construct($primaryKeyName = '', $tableName = '')
    {
        $this->_primaryKeyName = $primaryKeyName; //Name of auto-incremented Primary Key
        $this->_tableName = $tableName; //Corresponding table in database
    }

    /**
     * Returns the primary key
     *
     * @return string
     */
    function PrimaryKey()
    {
        return $this->get($this->_primaryKeyName);
    }

    /**
     * Inserts record into database with a new auto-incremented primary key
     * If the primary key is empty, then the PK column should have been set to auto increment
     *
     * @return bool|KrisModel
     */
    public function create()
    {
        $dbh = $this->getDatabaseHandle();
        $s1 = $s2 = '';
        foreach ($this->_recordSet as $key => $value)
        {
            if ($key != $this->_primaryKeyName || $value)
            {
                $s1 .= (strlen($s1) > 0 ? ',' : ''). $this->quoteDbObject($key);
                $s2 .= (strlen($s2) > 0 ? ',' : '').'?';
            }
        }
        $stmt = $dbh->prepare('INSERT INTO ' . $this->quoteDbObject($this->_tableName) . ' (' . $s1 . ') VALUES (' . $s2 . ')');
        $i = 1;
        foreach ($this->_recordSet as $key => $value)
        {
            if ($key != $this->_primaryKeyName || $value)
            {
                $stmt->bindValue($i++, $value);
            }
        }
        $stmt->execute();

        $this->ValidateStatement($stmt);

        if (!$stmt->rowCount())
        {
            return false;
        }
        $this->set($this->_primaryKeyName, $dbh->lastInsertId());
        return $this;
    }

    /**
     * Retrieves (hopefully) one record from the table based on the primary key...
     *
     * @param $primaryKeyValue
     * @return bool|KrisDB
     */
    public function retrieve($primaryKeyValue)
    {
        return $this->bindRecordSet($this->generateStatement('*',array($this->_primaryKeyName), array($primaryKeyValue), false)->fetch(PDO::FETCH_ASSOC), $this);
    }


    /**
     * @param array|string $where
     * @param array $bindings
     * @param bool $likeQuery
     * @param int $count
     * @param int $offset
     * @param string $orderBy
     * @return bool|KrisModel
     */
    public function retrieveMultiple($where, $bindings, $likeQuery = false, $count = 0, $offset, $orderBy = '')
    {
        return $this->returnMultiple($this->generateStatement('*', $where, $bindings, $likeQuery, $count, $offset, $orderBy));
    }



    /**
     * Updates the table with the current values in the model...
     *
     * @return bool
     */
    public function update()
    {
        $dbh = $this->getDatabaseHandle();
        $set = '';
        foreach ($this->_recordSet as $key => $value)
        {
            $this->
            $set .= (strlen($set) > 0 ? ',' : '') . $this->quoteDbObject($key) . '=?';
        }
        $stmt = $dbh->prepare('UPDATE ' . $this->quoteDbObject($this->_tableName) . ' SET ' . $set . ' WHERE ' . $this->quoteDbObject($this->_primaryKeyName) . '=?');
        $i = 1;
        foreach ($this->_recordSet as $value)
        {
            $stmt->bindValue($i++, $value);
        }
        $stmt->bindValue($i, $this->_recordSet[$this->_primaryKeyName]);
        $res = $stmt->execute();

        $this->ValidateStatement($stmt);

        return $res;
    }

    /**
     * Deletes this model from the table
     *
     * @return bool
     */
    public function delete()
    {
        $dbh = $this->getDatabaseHandle();
        $stmt = $dbh->prepare('DELETE FROM ' . $this->quoteDbObject($this->_tableName) . ' WHERE ' . $this->quoteDbObject($this->_primaryKeyName) . '=?');
        $stmt->bindValue(1, $this->_recordSet[$this->_primaryKeyName]);
        $res = $stmt->execute();
        $this->ValidateStatement($stmt);

        return $res;
    }

    /**
     * returns true if primary key is a positive integer
     * if checkDB is set to true, this function will return true if there exists such a record in the database
     *
     * @param bool $checkDB
     * @return bool|int
     */
    public function exists($checkDB = false)
    {
        if ((int)$this->_recordSet[$this->_primaryKeyName] < 1)
        {
            return false;
        }
        if (!$checkDB)
        {
            return true;
        }

        return count($this->generateStatement('1', array($this->_primaryKeyName), array($this->_recordSet[$this->_primaryKeyName]), false)->fetchAll());

    }

    /**
     * @param string $where
     * @param string $bindings
     * @param bool $likeQuery
     * @return
     */
    public function totalRecords($where = '', $bindings = '', $likeQuery)
    {
        $res = $this->select('count('.$this->_primaryKeyName.') AS num_records', $where, $bindings, $likeQuery);
        $row = current($res);
        return $row['num_records'];
    }

    /**
     * Returns an array of what/where
     *
     * @param string|array $what
     * @param string|array $where
     * @param string|array $bindings
     * @param bool $likeQuery     *
     * @param int $pdoFetchMode
     * @return array
     */
    public function select($what = '*', $where = '', $bindings = '', $likeQuery = false, $pdoFetchMode = PDO::FETCH_ASSOC)
    {
        return $this->generateStatement($what, $where, $bindings, $likeQuery)->fetchAll($pdoFetchMode);
    }

    /**
     * @param string|array $what
     * @param string|array $where
     * @param array $bindings
     * @param bool $likeQuery
     * @param int $count
     * @param int $offset
     * @param array|string $order
     * @param bool $orderAscending
     * @return PDOStatement
     */
    private function generateStatement($what, $where, $bindings, $likeQuery, $count = 0, $offset = 0, $order = '', $orderAscending = true)
    {
        $dbh = $this->getDatabaseHandle();
        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }
        $sql = 'SELECT ' . $this->generateWhat($what) . ' FROM ' . $this->_tableName;

        if ((is_array($where) && count($where) > 0) || (!is_array($where) && strlen($where) > 0))
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings, $likeQuery);
        }

        $bindings = $this->GetBindings($bindings, $likeQuery);

        $stmt = $dbh->prepare($this->addLimit($this->addOrder($sql, $order, $orderAscending), $count, $offset));

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);

        return $stmt;
    }



}