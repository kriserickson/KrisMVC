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
     * @var array - Any javascript created gets placed at the bottom of the page...
     */
    protected $_javascript = array();

    /**
     * @var array - Add any javascript libraries to this array in the derived class...
     */
    protected $_javascriptEditLibraries = array();

    /**
     *
     * @var array - Any data associated with a field, for example images have their upload directory here...
     */
    protected $_fieldData = array();

    /**
     * @var string - The name of the table, can be overridden in View.
     */
    public $DisplayName;

    /**
     * @var bool|string - Name of the sort field, can be override in the view.
     */
    public $SortField = false;




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
            else if ($this->_fieldTypes[$fixedKey] == 'image')
            {
                $value = HtmlHelpers::CreateImage($key, $value,$this->GetFieldData($fixedKey, 'directory'), $this->GetClass('Edit', 'Image'));
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
            $key = $this->_fakeFields[$fixedKey];
            $this->AddJavascript('Edit', 'Select', $key);
            return HtmlHelpers::CreateSelect($key, $this->getForeignKeyValues($key), $this->get($key), '',  $this->GetClass('Edit', 'Select'));
        }
        else
        {
            $value = $this->get($key);
            switch ($this->_fieldTypes[$fixedKey])
            {
                case 'bool':
                    $this->AddJavascript('Edit', 'Select', $key);
                    return HtmlHelpers::CreateSelect($key, array(0 => 'No', 1 => 'Yes'), $value, '', $this->GetClass('Edit', 'Select'));
                case 'text':
                    $this->AddJavascript('Edit', 'TextArea', $key);
                    return HtmlHelpers::CreateTextarea($key, $value, $key, $this->GetClass('Edit', 'TextArea'));
                case 'image':
                    $this->AddJavascript('Edit', 'Image', $key);
                    return HtmlHelpers::CreateImage('img'.$key, $value, $this->GetFieldData($fixedKey, 'directory'), $this->GetClass('Edit', 'Image')).
                        HtmlHelpers::CreateFileInput($key, $key, $this->GetClass('Edit', 'FileInput'), $this->GetFieldData($fixedKey, 'max_size', 1000000));

                // TODO: upload, datetime, integer, etc...
                default:
                    $this->AddJavascript('Edit', 'Input', $key);
                    return HtmlHelpers::CreateInput($key, $value, '', $this->GetClass('Edit', 'Input'), $this->_fieldTypes[$fixedKey]);
            }
        }
    }

    /**
     * @return bool
     */
    public function HasUploads()
    {
        foreach ($this->_fieldTypes as $type)
        {
            if ($type == 'image' || $type == 'upload') // or type is file upload
            {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $view (edit, index, view)
     * @return string
     */
    public function GetJavascript($view)
    {
        $libraries = array();
        
        if ($view == 'edit')
        {
            $libraries = $this->_javascriptEditLibraries;
        }

        $js = '';
        foreach ($libraries as $library)
        {
            $js .= '<script type="text/javascript" src="'.$library.'"></script>'.PHP_EOL;
        }

        $js_script = array_filter($this->_javascript);
        if (count($js_script) > 0)
        {
            $js .= HtmlHelpers::CreateScript('', implode(PHP_EOL, $js_script));
        }

        return $js;
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
     * @param $fields
     * @return void
     */
    public function UpdateSelectedFields($fields)
    {
        $this->updateFields($fields);
        if (count($this->_foreignKeys) > 0)
        {
            $this->retrieve($this->PrimaryKey());
        }

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
    protected function isFakeField($fieldName)
    {
        return is_array($this->_fakeFields) && isset($this->_fakeFields[$fieldName]);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    protected function isForeignKeyField($fieldName)
    {
        return is_array($this->_foreignKeys) && isset($this->_foreignKeys[$this->convertClassKeyToDBKey($fieldName)]);
    }

    /**
     * @param $foreignField
     * @return array
     */
    protected function getForeignKeyValues($foreignField)
    {
        $dbh = $this->getDatabaseHandle();

        $displayFieldSelect = $this->GetForeignFieldSelectDisplay('t1', $this->_foreignKeys[$foreignField]['display'], 'display');

        $stmt = $dbh->prepare('SELECT t1.' . $this->_foreignKeys[$foreignField]['field'] . ' AS value ' . $displayFieldSelect .
                    ' FROM ' . $this->_foreignKeys[$foreignField]['table'].' AS t1');

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
     * Adds the javascript for an action, type and key if it is defined in Model class...
     *
     * @param $action
     * @param $type
     * @param $key
     * @return void
     */
    private function AddJavascript($action, $type, $key)
    {
        $methodName = 'Javascript' . ucfirst($action) . ucfirst($type);
        if (method_exists($this, $methodName))
        {
            $this->_javascript[] =  call_user_func_array(array($this, $methodName), array($key));
        }

    }

    /**
     * If the property exists for a certain class, show that, otherwise use the default ActionTypeClass...
     *
     * @param $action
     * @param $type
     * @return string
     */
    private function GetClass($action, $type)
    {
        $property = ucfirst($action) . ucfirst($type) . 'Class';
        if (property_exists($this, $property))
        {
            return $this[$property];
        }
        else
        {
            return $property;
        }
    }

    /**
     * @param string $field
     * @param string $data
     * @param mixed $default
     *
     * @return string
     */
    private function GetFieldData($field, $data, $default = '')
    {
        if ( isset( $this->_fieldData[$field]) && isset($this->_fieldData[$field][$data]))
        {
            return $this->_fieldData[$field][$data];
        }
        return $default;
    }

    /**
     * @return array
     */
    public function GetUploads()
    {
        $uploads = array();
        foreach ($this->_fieldTypes as $field => $type)
        {
            if ($type == 'image' || $type == 'upload') // or type is file upload
            {
                $field_data = isset( $this->_fieldData[$field]) ? $this->_fieldData[$field] : array();
                $uploads[] = array_merge(array('name' => $this->convertClassKeyToDBKey($field), 'type' => $type), $field_data);
            }
        }
        return $uploads;
    }


}