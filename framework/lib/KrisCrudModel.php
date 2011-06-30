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
    

    /**
     * Used by the model to set variables, they can be stored later with Insert/Update.
     *
     * @param string $key
     * @param string $val
     * @return KrisDB
     */
    public function set($key, $val)
    {
        $fixedKey = $this->convertDBKeyToClassKey($key);
        if (!isset($this->_recordSet[$fixedKey]) || $this->isFakeField($fixedKey))
        {
            throw new DatabaseException('Invalid key: '.$key);
        }

        $this->_recordSet[$fixedKey] = $val;
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
        return $this->bindRecordSet($this->generateStatement(array($this->_primaryKeyName), array($primaryKeyValue))->fetch(PDO::FETCH_ASSOC), $this);
    }

    /**
     * @param string|array $where
     * @param array $bindings
     * @param int $count
     * @param array|string $order
     * @return PDOStatement
     */
    private function generateStatement($where, $bindings, $count = 0, $order = '')
    {
        $dbh = $this->getDatabaseHandle();
        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }

        $tableFields = $this->getTableFields();
        for ($i = 0; $i < count($tableFields); $i++)
        {
            $tableFields[$i] = 't1.'.$tableFields[$i];
        }

        $sql = 'SELECT '.(implode(',', $tableFields)).' FROM ' . $this->_tableName.' t1';

        foreach ($this->$_foreignKeys as $columnName => $foreignFieldData)
        {
            /// array('description_id' => array('table' => 'class_description', 'field' => 'class_description_id', 'display' => 'description', 'alias' => 'description'),)
        }

        if ((is_array($where) && count($where) > 0) || strlen($where) > 0)
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings);
        }

        $stmt = $dbh->prepare($this->addOrder($this->addLimit($sql, $count), $order));

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);

        return $stmt;
    }


    /**
     * @param array $where
     * @param array $bindings
     * @param int $count
     * @param string $orderBy
     * @return bool|KrisModel
     */
    public function retrieveMultiple($where, $bindings, $count = 0, $orderBy = '')
    {
        return $this->returnMultiple($this->generateStatement('*', $where, $bindings, $count, $orderBy));
    }

    private function getTableFields()
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

    private function isFakeField($fieldName)
    {
        return isset($this->_fakeFields) && isset($this->_fakeFields[$fieldName]);
    }
}