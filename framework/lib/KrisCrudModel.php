<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
class KrisCrudModel extends KrisModel
{
    protected $_foreignKeys = array();
    protected $_fakeFields = array();
    public $DisplayName;

    function __construct($primaryKeyName, $tableName)
    {
        parent::__construct($primaryKeyName, $tableName);
    }

    /**
     * Retrieves (hopefully) one record from the table based on the primary key...
     *
     * @param $primaryKeyValue
     * @return bool|KrisDB
     */
    public function retrieve($primaryKeyValue)
    {
        return $this->bindRecordSet($this->generateStatement(array($this->_primaryKeyName), array($primaryKeyValue))->fetch(PDO::FETCH_ASSOC), $this);
    }


    /**
     * @param array $where
     * @param array $bindings
     * @param int $count
     * @param int $offset
     * @param string $orderBy
     * @param bool $orderAscending
     * @return bool|KrisModel
     */
    public function retrieveMultiple($where, $bindings, $count = 0, $offset = 0, $orderBy = '', $orderAscending = true)
    {
        return $this->returnMultiple($this->generateStatement($where, $bindings, $count, $offset, $orderBy, $orderAscending));
    }

    /**
     * @param string|array $where
     * @param array $bindings
     * @param int $count
     * @param int $offset
     * @param array|string $order
     * @param $orderAscending
     * @return PDOStatement
     */
    private function generateStatement($where, $bindings, $count = 0, $offset = 0, $order = '', $orderAscending)
    {
        $dbh = $this->getDatabaseHandle();
        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }

        $tableFields = $this->GetTableFields();
        for ($i = 0; $i < count($tableFields); $i++)
        {
            $tableFields[$i] = 't1.'.$this->convertClassKeyToDBKey($tableFields[$i]);
        }

        $select = 'SELECT '.(implode(',', $tableFields));
        $from = ' FROM ' . $this->_tableName.' t1';

        $tableIndex = 2;

        foreach ($this->_foreignKeys as $columnName => $foreignFieldData)
        {
            if (!isset($tableAliases[$foreignFieldData['table']]))
            {
                $tableAlias = $tableAliases[$foreignFieldData['table']] = 't'.$tableIndex++;
                $from .= ' INNER JOIN '.$foreignFieldData['table'].' '.$tableAlias.' ON (t1.'.$columnName.' = '.$tableAlias.'.'.$foreignFieldData['field'].') ';
                        

            }
            $select .= ', '.$tableAliases[$foreignFieldData['table']].'.'.$foreignFieldData['display'].' AS '.$foreignFieldData['alias'];
        }

        $sql =  $select.$from;

        if ((is_array($where) && count($where) > 0) || (!is_array($where) && strlen($where) > 0))
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings);
        }

        $stmt = $dbh->prepare($this->addLimit($this->addOrder($sql, $order, $orderAscending), $count, $offset));

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);

        return $stmt;
    }


    /**
     * @return array
     */
    public function GetTableFields()
    {
        $fields = array();
        foreach (array_keys($this->_recordSet) as $field)
        {
            if (!$this->isFakeField($field))
            {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Gets the display fields (TableId, Name, etc) rather than the database fields (table_id, name, etc)
     *
     * @return array
     */
    public function GetDisplayFields()
    {
        $fields = array();
        foreach (array_keys($this->_recordSet) as $field)
        {
            if (!$this->isForeignKeyField($field) && $this->convertClassKeyToDBKey($field) != $this->_primaryKeyName)
            {
                $fields[] = $field;
            }
        }

        return $fields;
    }


    /**
     * Gets the database fields (table_id, name, etc) rather than display fields  (TableId, Name, etc)
     *
     * @return array
     */
    public function GetDatabaseFields()
    {
        $fields = array();
        foreach (array_keys($this->_recordSet) as $field)
        {
            $fields[] = $this->convertClassKeyToDBKey($field);
        }

        return $fields;
    }

     /**
     * Gets the display fields  (TableId, Name, etc) indexed by the database fields (table_id, name, etc)
     *
     * @return array
     */
    public function GetDisplayAndDatabaseFields()
    {
        $fields = array();
        foreach ($this->GetDisplayFields() as $field)
        {
            $fields[$this->convertClassKeyToDBKey($field)] = $this->convertClassKeyToDisplayField($field);
        }

        return $fields;
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    private function isFakeField($fieldName)
    {
        return is_array($this->_fakeFields) && isset($this->_fakeFields[$fieldName]);
    }

    private function isForeignKeyField($fieldName)
    {
        return is_array($this->_foreignKeys) && isset($this->_foreignKeys[$this->convertClassKeyToDBKey($fieldName)]);
    }

}