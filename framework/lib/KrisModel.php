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
// Model
// Simplistic ORM that represents a table in the database...
// TODO: Replace with something a little better
//===============================================================
abstract class KrisModel extends KrisDB
{

    protected $_primaryKeyName;
    protected $_tableName;

    function __construct($primaryKeyName = '', $tableName = '')
    {
        $this->_primaryKeyName = $primaryKeyName; //Name of auto-incremented Primary Key
        $this->_tableName = $tableName; //Corresponding table in database
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
        return $this->bindRecordSet($this->generateStatement('*',array($this->_primaryKeyName), array($primaryKeyValue))->fetch(PDO::FETCH_ASSOC), $this);
    }


    /**
     * @param array|string $where
     * @param array $bindings
     * @param int $count
     * @param string $orderBy
     * @return bool|KrisModel
     */
    public function retrieveMultiple($where, $bindings, $count = 0, $orderBy = '')
    {
        return $this->returnMultiple($this->generateStatement('*', $where, $bindings, $count, $orderBy));
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

        return count($this->generateStatement('1', array($this->_primaryKeyName), array($this->_recordSet[$this->_primaryKeyName]))->fetchAll());

    }



    /**
     * Returns an array of what/where
     *
     * @param string|array $what
     * @param string|array $where
     * @param string $bindings
     * @param int $pdo_fetch_mode
     * @return array
     */
    public function select($what = '*', $where = '', $bindings = '', $pdo_fetch_mode = PDO::FETCH_ASSOC)
    {
        return $this->generateStatement($what, $where, $bindings)->fetchAll($pdo_fetch_mode);
    }

    /**
     * @param string|array $what
     * @param string|array $where
     * @param array $bindings
     * @param int $count
     * @param array|string $order
     * @return PDOStatement
     */
    private function generateStatement($what, $where, $bindings, $count = 0, $order = '')
    {
        $dbh = $this->getDatabaseHandle();
        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }
        $sql = 'SELECT ' . $this->generateWhat($what) . ' FROM ' . $this->_tableName;

        if ((is_array($where) && count($where) > 0) || strlen($where) > 0)
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings);
        }

        $stmt = $dbh->prepare($this->addOrder($this->addLimit($sql, $count), $order));

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);

        return $stmt;
    }


}