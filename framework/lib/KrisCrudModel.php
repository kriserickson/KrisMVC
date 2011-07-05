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
 * CrudModel, used for scaffolding and simple crud generation
 * @package Model
 */
class KrisCrudModel extends KrisModel
{
    /**
     * @var array - Collection of foreign key data... ForeignKeyId => array('table' => '', 'field' => '', 'display' => '', 'alias' => '')
     */
    protected $_foreignKeys = array();

    /**
     * @var array - List of fields that aren't in the table, but displayed based on joins of foreign keys...  $FakeFieldName => $FieldId
     */
    protected $_fakeFields = array();

    /**
     * @var array - FieldName -> Type (string, text, int, date, bool, etc)
     */
    protected $_fieldTypes = array();

    /**
     * @var array - Override these to change the sort order.  FieldName => Order...
     */
    protected $_fieldSortOrder = array();

    /**
     * @var array - Override these to change any field aliases.
     */
    protected $_fieldAliases = array();

    /**
     * @var string - The name of the table, can be overridden in View.
     */
    public $DisplayName;

    /**
     * @var bool|string - Name of the sort field, can be override in the view.
     */
    public $SortField = false;

    /**
     * @var string - Class Name of the selects
     */
    public $SelectClass = 'crudSelect';

    /**
     * @var string - Class name of the textareas
     */
    public $TextAreaClass = 'crudTextArea';

    /**
     * @var string - Class name of the inputs
     */
    public $InputClass = 'crudInput';

    /**
     * @param string $primaryKeyName
     * @param string $tableName
     */
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
        return $this->bindRecordSet($this->generateStatement(array($this->_primaryKeyName), array($primaryKeyValue), false)->fetch(PDO::FETCH_ASSOC), $this);
    }

    /**
     * Allows for changing of the auto-generated foreign keys...
     *
     * @param string $field - field that is to be changed...
     * @param string|array $name - the name of the field(s) to link to..
     * @param string $alias - the name of the alias...
     * @return void
     */
    public function SetForeignKey($field, $name, $alias)
    {
        $old_alias = $this->_foreignKeys[$field]['alias'];
        $this->_foreignKeys[$field]['display'] = $name;
        $this->_foreignKeys[$field]['alias'] = $alias;
        unset($this->_fakeFields[$this->convertDBKeyToClassKey($old_alias)]);
        unset($this->_recordSet[$this->convertDBKeyToClassKey($old_alias)]);
        $this->_recordSet[$this->convertDBKeyToClassKey($alias)] = '';
        $this->_fakeFields[$this->convertDBKeyToClassKey($alias)] = $field;
    }


    /**
     * @param array $where
     * @param array $bindings
     * @param bool $likeQuery
     * @param int $count
     * @param int $offset
     * @param string $orderBy
     * @param bool $orderAscending
     * @return bool|KrisCrudModel
     */
    public function retrieveMultiple($where, $bindings, $likeQuery = false, $count = 0, $offset = 0, $orderBy = '', $orderAscending = true)
    {
        return $this->returnMultiple($this->generateStatement($where, $bindings, $likeQuery, $count, $offset, $orderBy, $orderAscending));
    }

    /**
     * @param string|array $where
     * @param array $bindings
     * @param $likeQuery
     * @param int $count
     * @param int $offset
     * @param array|string $order
     * @param bool $orderAscending
     * @return PDOStatement
     */
    private function generateStatement($where, $bindings, $likeQuery, $count = 0, $offset = 0, $order = '', $orderAscending = true)
    {
        $dbh = $this->getDatabaseHandle();
        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }

        $tableFields = $this->GetTableFields();
        for ($i = 0; $i < count($tableFields); $i++)
        {
            $tableFields[$i] = 't1.' . $this->convertClassKeyToDBKey($tableFields[$i]);
        }

        $select = 'SELECT ' . (implode(',', $tableFields));
        $from = ' FROM ' . $this->_tableName . ' t1';

        $tableIndex = 2;

        foreach ($this->_foreignKeys as $columnName => $foreignFieldData)
        {

            $tableAlias = 't' . $tableIndex++;
            $from .= ' INNER JOIN ' . $foreignFieldData['table'] . ' ' . $tableAlias . ' ON (t1.' . $columnName . ' = ' . $tableAlias . '.' . $foreignFieldData['field'] . ') ';
            $displayField = $foreignFieldData['display'];
            $aliasField = $foreignFieldData['alias'];

            $displayFieldSelect = $this->GetForeignFieldSelectDisplay($tableAlias, $displayField, $aliasField);

            $select .= $displayFieldSelect;
        }

        $sql = $select . $from;

        if ((is_array($where) && count($where) > 0) || (!is_array($where) && strlen($where) > 0))
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings, $likeQuery);
        }

        $bindings = $this->GetBindings($bindings, $likeQuery);


        $stmt = $dbh->prepare($this->addLimit($this->addOrder($sql, $order, $orderAscending), $count, $offset));

        $this->ValidateStatement($stmt);

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);

        return $stmt;
    }

    /**
     * @param $tableAlias
     * @param $displayField
     * @param $aliasField
     * @return string
     */
    private function GetForeignFieldSelectDisplay($tableAlias, $displayField, $aliasField)
    {
        if (is_array($displayField))
        {
            // TODO: Make work with databases other than MySql
            $displayFieldSelect = ', CONCAT(';
            for ($i = 0; $i < count($displayField); $i++)
            {
                $displayFieldSelect .= ($i > 0 ? ", ' '," : '') . $tableAlias . '.' . $displayField[$i];
            }
            $displayFieldSelect .= ')';
        }
        else
        {
            $displayFieldSelect = ', ' . $tableAlias . '.' . $displayField;
        }

        $displayFieldSelect .= ' AS ' . $aliasField;
        return $displayFieldSelect;
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
     * Gets the display fields (TableId, Name, etc) rather than the database fields (table_id, name, etc).
     * Gets their database field name, however...
     *
     * @return array
     */
    private function GetDisplayFields()
    {
        $fields = array();
        $index = 0;
        foreach (array_keys($this->_recordSet) as $field)
        {
            if (!$this->isForeignKeyField($field) && $this->convertClassKeyToDBKey($field) != $this->_primaryKeyName)
            {
                if (isset($this->_fieldSortOrder[$field]))
                {
                    $index = $this->_fieldSortOrder[$field];
                }
                $fields[$index++] = $field;
            }
        }

        ksort($fields);
        return $fields;
    }

    /**
     * Returns the display value (currently fixes tinyints to be yes/no...)
     *
     * @param $key
     * @return string|int|float
     */
    public function GetDisplayValue($key)
    {
        $value = $this->get($key);
        $fixedKey = $this->convertDBKeyToClassKey($key);
        if (isset($this->_fieldTypes[$fixedKey]))
        {
            if ($this->_fieldTypes[$fixedKey] == 'bool')
            {
                $value = $value ? 'Yes' : 'No';
            }
        }
        return $value;
    }

    /**
     * @param $key
     *
     * @internal param $fieldName
     * @return string
     */
    public function GetEditValue($key)
    {
        $fixedKey = $this->convertDBKeyToClassKey($key);
        if (isset($this->_fakeFields[$fixedKey]))
        {
            $value = $this->get($this->_fakeFields[$fixedKey]);
            return $this->getSelect($this->_fakeFields[$fixedKey], $this->getForeignKeyValues($this->_fakeFields[$fixedKey]), $value, $this->SelectClass);
        }
        else {
            $value = $this->get($key);
            if ($this->_fieldTypes[$fixedKey] == 'bool')
            {
                return $this->getSelect($key, array(0 => 'No', 1 => 'Yes'), $value, $this->SelectClass);
            }
            else
            {
                if ($this->_fieldTypes[$fixedKey] == 'text')
                {
                    return $this->getTextArea($key, $value, $this->TextAreaClass);
                }
                else
                {
                    if ($this->_fieldTypes[$fixedKey] == 'datetime')
                    {
                        // Date picker and the following...
                        // TODO 'timestamp', 'date', 'enum':'
                        return '';
                    }
                    else
                    {
                        return $this->getInput($key, $value, $this->InputClass, $this->_fieldTypes[$fixedKey]);
                    }
                }
            }
        }

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
     * @param string $field
     * @return string
     */
    protected function convertClassKeyToDisplayField($field)
    {
        if (isset($this->_fieldAliases[$field]))
        {
            return $this->_fieldAliases[$field];
        }
        return parent::convertClassKeyToDisplayField($field);
    }


    /**
     * @param string $fieldName
     * @return bool
     */
    private function isFakeField($fieldName)
    {
        return is_array($this->_fakeFields) && isset($this->_fakeFields[$fieldName]);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    private function isForeignKeyField($fieldName)
    {
        return is_array($this->_foreignKeys) && isset($this->_foreignKeys[$this->convertClassKeyToDBKey($fieldName)]);
    }

    /**
     * @param $foreignField
     * @return array
     */
    private function getForeignKeyValues($foreignField)
    {
        $dbh = $this->getDatabaseHandle();

        $displayFieldSelect = $this->GetForeignFieldSelectDisplay('t1', $this->_foreignKeys[$foreignField]['display'], 'display');

        $stmt = $dbh->prepare('SELECT t1.' . $this->_foreignKeys[$foreignField]['field'] . ' AS value ' . $displayFieldSelect .
                    ' FROM ' . $this->_foreignKeys[$foreignField]['table'].' AS t1');

        $this->ValidateStatement($stmt);

        $stmt->execute();

        $this->ValidateStatement($stmt);

        $ret = array();

        while ($rs = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $ret[$rs['value']] = $rs['display'];
        }

        return $ret;

    }


    /**
     * Create an HTML select...
     *
     * @param string $key
     * @param array $values
     * @param string $defaultValue
     * @param string $class
     * @return string
     */
    private function getSelect($key, $values, $defaultValue, $class = '')
    {
        $select = '<select name="' . $key . '" id="' . $key . '"' . (strlen($class) > 0 ? ' class="' . $class . '"'
                : '') . '>' . PHP_EOL;
        foreach ($values as $value => $display)
        {
            $select .= '<option value="' . $value . '"' . ($defaultValue == $value ? ' selected="selected"'
                    : '') . '>' . $display . '</option>.PHP_EOL';
        }
        return $select . '</select>' . PHP_EOL;
    }


    /**
     * Generates a text input...
     *
     * @param string $key
     * @param string $value
     * @param string $class
     * @param string $validation
     * @return string
     */
    private function getInput($key, $value, $class, $validation)
    {
        // TODO: Validation...
        return '<input name="' . $key . '" id="' . $key . '" class="' . $class . '" value="' . $value . '"/>';
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $class
     * @return string
     */
    private function getTextArea($key, $value, $class)
    {
        return '<textarea name="' . $key . '" id="' . $key . '" class="' . $class . '">' . $value . '</textarea>';
    }


}