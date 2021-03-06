<?php

//  This file is part of the KrisMvc framework.
//
//  (c) Kris Erickson
//
//  This source file is subject to the MIT license that is bundled
//  with this source code in the file LICENSE.



/**
 * KrisModel
 * @package Orm
 *
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
    function __construct($primaryKeyName, $tableName)
    {
        $this->_primaryKeyName = $primaryKeyName; //Name of auto-incremented Primary Key
        $this->_tableName = $tableName; //Corresponding table in database
    }

    /**
     * @return string
     */
    public function TableName()
    {
        return $this->_tableName;
    }

    /**
     * Returns the primary key
     *
     * @return string
     */
    public function PrimaryKey()
    {
        return $this->Get($this->_primaryKeyName);
    }

    /**
     * Inserts record into database with a new auto-incremented primary key
     * If the primary key is empty, then the PK column should have been set to auto increment
     *
     * @return bool|KrisModel
     */
    public function Create()
    {
        $dbh = $this->getDatabaseHandle();
        $s1 = $s2 = '';
        $params = array();

        foreach ($this->_recordSet as $key => $value)
        {
            if (!$this->isFakeField($key))
            {
                $dbKey = $this->convertClassKeyToDBKey($key);
                if ($value)
                {
                    $s1 .= (strlen($s1) > 0 ? ',' : ''). $this->quoteDbObject($dbKey);
                    $s2 .= (strlen($s2) > 0 ? ',' : '').'?';
                    $params[] = $value;
                }
            }
        }


        $stmt = $dbh->prepare('INSERT INTO ' . $this->quoteDbObject($this->_tableName) . ' (' . $s1 . ') VALUES (' . $s2 . ')');
        $stmt->execute($params);

        $this->ValidateStatement($stmt);

        if (!$stmt->rowCount())
        {
            return false;
        }

        $this->Set($this->_primaryKeyName, $dbh->lastInsertId());
        return $this;
    }

    /**
     * @param string $query
     * @return PDOStatement
     */
    public function Query($query)
    {
        return $this->getDatabaseHandle()->query($query);
    }

    /**
     * Retrieves (hopefully) one record from the table based on the primary key...
     *
     * @param array|string $primaryKeyOrFieldName
     * @param null|string|array $value
     * @return bool|KrisModel
     */
    public function Retrieve($primaryKeyOrFieldName, $value = null)
    {
        if (is_null($value))
        {
            return $this->bindRecordSet($this->generateStatement(null, array($this->_primaryKeyName), array($primaryKeyOrFieldName), false)->fetch(PDO::FETCH_ASSOC), $this);
        }
        else
        {
            if (!is_array($primaryKeyOrFieldName))
            {
                $primaryKeyOrFieldName = array($primaryKeyOrFieldName);
                $value = array($value);
            }
            return $this->bindRecordSet($this->generateStatement(null, $primaryKeyOrFieldName, $value, false)->fetch(PDO::FETCH_ASSOC), $this);
        }
    }


    /**
     * @param array|string $where
     * @param array $bindings
     * @param bool $likeQuery
     * @param int $count
     * @param int $offset
     * @param string $orderBy
     * @return array
     */
    public function RetrieveMultiple($where, $bindings, $likeQuery = false, $count = 0, $offset, $orderBy = '')
    {
        return $this->returnMultiple($this->generateStatement(null, $where, $bindings, $likeQuery, $count, $offset, $orderBy));
    }


    /**
     * Synonym for Update
     * @see KrisModel::Update
     * @return void
     */
    public function Save()
    {
        $this->Update();
    }


    /**
     * Updates the table with the current values in the model...
     *
     * @return bool
     */
    public function Update()
    {
        $primaryKey = $this->convertDBKeyToClassKey($this->_primaryKeyName);
        if (!isset($this->_recordSet[$primaryKey]) || !$this->_recordSet[$primaryKey])
        {
            return $this->Create();
        }
        else
        {
            return $this->updateFields($this->_recordSet);
        }
    }


    /**
     * Deletes this model from the table
     *
     * @param null|string $primaryKeyOrFieldName
     * @param null|string $value
     * @return bool
     */
    public function Delete($primaryKeyOrFieldName = null, $value = null)
    {
        $primaryKey = $this->_primaryKeyName;

        if (is_null($primaryKeyOrFieldName))
        {
            $value = $this->PrimaryKey();
        }
        else if (is_null($value))
        {
            $value = $primaryKeyOrFieldName;
        }
        else
        {
            $primaryKey = $primaryKeyOrFieldName;
        }

        $stmt = $this->getDatabaseHandle()->prepare($query = 'DELETE FROM ' .
                $this->quoteDbObject($this->_tableName) . ' WHERE ' .
                $this->quoteDbObject($this->convertClassKeyToDBKey($primaryKey)).' = ?');
        $stmt->bindValue(1, $value);
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
    public function Exists($checkDB = false)
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
     * @return int
     */
    public function TotalRecords($where = '', $bindings = '', $likeQuery)
    {
        $res = $this->Select('count('.$this->_primaryKeyName.') AS num_records', $where, $bindings, $likeQuery);
        $row = current($res);
        return (int)$row['num_records'];
    }

    /**
     * Returns an array of rows
     *
     * @param string|array $what
     * @param string|array $where
     * @param string|array $bindings
     * @param bool $likeQuery
     * @param int $pdoFetchMode
     * @return array
     */
    public function Select($what = null, $where = '', $bindings = '', $likeQuery = false, $pdoFetchMode = PDO::FETCH_ASSOC)
    {
        return $this->generateStatement($what, $where, $bindings, $likeQuery)->fetchAll($pdoFetchMode);
    }


    /**
     * Whether or not the record has been edited....
     *
     * @return bool
     */
    public function IsDirty()
    {
        return count($this->_dirty) > 0;
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
    protected function generateStatement($what, $where, $bindings, $likeQuery, $count = 0, $offset = 0, $order = '', $orderAscending = true)
    {
        if (is_scalar($bindings))
        {
            $bindings = count($where) > 0 ? array($bindings) : array();
        }
        $sql = 'SELECT ' . $this->generateWhat($what) . ' FROM ' . $this->_tableName;

        if ((is_array($where) && count($where) > 0) || (!is_array($where) && strlen($where) > 0))
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings, $likeQuery);
        }

        $bindings = $this->GetBindings($bindings, $likeQuery);

        $stmt = $this->getDatabaseHandle()->prepare($this->addLimit($this->addOrder($sql, $order, $orderAscending), $count, $offset));

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);

        return $stmt;
    }

    /**
     * @param array $fields
     * @return bool
     */
    protected function updateFields($fields)
    {

        $set = '';
        $values = array();
        foreach ($fields as $key => $value)
        {
            if (!$this->isFakeField($key))
            {
                $dbKey = $this->convertClassKeyToDBKey($key);
                if (isset($this->_dirty[$key]) && $dbKey != $this->_primaryKeyName)
                {
                    $set .= (strlen($set) > 0 ? ',' : '') . $this->quoteDbObject($dbKey) . '=?';
                    $values[] = $value;
                }
            }
        }

        if (count($values) == 0)
        {
            return false;
        }

        $stmt = $this->getDatabaseHandle()->prepare('UPDATE ' . $this->quoteDbObject($this->_tableName) . ' SET ' . $set . ' WHERE ' . $this->quoteDbObject($this->_primaryKeyName) . '=?');
        $i = 1;

        foreach ($values as $value)
        {
            $stmt->bindValue($i++, $value);
        }

        $stmt->bindValue($i, $this->PrimaryKey());

        $res = $stmt->execute();

        $this->ValidateStatement($stmt);

        return $res;
    }


}