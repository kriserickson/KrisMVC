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
     * @var array - FieldName -> Type (string, text, int, date, bool, image, upload, etc)
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
     * @var array - Add any javascript libraries to this array in the derived class...
     */
    protected $_cssEditStyles = array();

    /**
     *
     * @var array - Any data associated with a field, for example images have their upload directory here...
     */
    protected $_fieldData = array();

    /**
     * @var bool - Whether or not to show the primary key...  Override in the child class..
     */
    protected $_showPrimaryKey = false;

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
     * Allows for changing of the auto-generated foreign keys...
     *
     * @param string $field - field that is to be changed...
     * @param string|array $name - the name of the field(s) to link to..
     * @param string $alias - the name of the alias...
     * @return void
     */
    protected function SetForeignKey($field, $name, $alias)
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
     * @return bool|array
     */
    public function RetrieveMultiple($where = null, $bindings = null, $likeQuery = false, $count = 0, $offset = 0, $orderBy = '', $orderAscending = true)
    {
        if ($where != null && !is_array($where))
        {
            $where = array($where);
            $bindings = is_array($bindings) ? $bindings : array($bindings);
        }
        return $this->returnMultiple($this->generateStatement(null, $where, $bindings, $likeQuery, $count, $offset, $orderBy, $orderAscending));
    }

    /**
     * @param $what
     * @param string|array $where
     * @param array $bindings
     * @param $likeQuery
     * @param int $count
     * @param int $offset
     * @param array|string $order
     * @param bool $orderAscending
     * @return PDOStatement
     */
    protected function generateStatement($what, $where, $bindings, $likeQuery, $count = 0, $offset = 0, $order = '', $orderAscending = true)
    {
        $dbh = $this->getDatabaseHandle();
        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }

        $select = 'SELECT ' . $this->generateWhat($what, 't1');
        $from = ' FROM ' . $this->_tableName . ' t1';
        $tables = array($this->_tableName => 't1');

        $tableIndex = 2;

        foreach ($this->_foreignKeys as $columnName => $foreignFieldData)
        {

            $tableAlias = 't' . $tableIndex++;
            $tables[$foreignFieldData['table']] = $tableAlias;
            $from .= ' INNER JOIN ' . $foreignFieldData['table'] . ' ' . $tableAlias . ' ON (t1.' . $columnName . ' = ' . $tableAlias . '.' . $foreignFieldData['field'] . ') ';
            $displayField = $foreignFieldData['display'];
            $aliasField = $foreignFieldData['alias'];

            $displayFieldSelect = $this->GetForeignFieldSelectDisplay($tableAlias, $displayField, $aliasField);

            $select .= $displayFieldSelect;
        }

        $sql = $select . $from;

        if ((is_array($where) && count($where) > 0) || (!is_array($where) && strlen($where) > 0))
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings, $likeQuery, $tables);
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
                $displayFieldSelect .= ($i > 0 ? ", ' '," : '') . $tableAlias . '.' . $this->quoteDbObject($displayField[$i]);
            }
            $displayFieldSelect .= ')';
        }
        else
        {
            $displayFieldSelect = ', ' . $tableAlias . '.' . $this->quoteDbObject($displayField);
        }

        $displayFieldSelect .= ' AS ' . $aliasField;
        return $displayFieldSelect;
    }


    /**
     * @return array
     */
    protected function GetTableFields()
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
    protected function GetDisplayFields()
    {
        $fields = array();
        $index = 0;
        foreach (array_keys($this->_recordSet) as $field)
        {
            if (!$this->isForeignKeyField($field) && ($this->_showPrimaryKey || $this->convertClassKeyToDBKey($field) != $this->_primaryKeyName))
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
        $value = $this->Get($key);
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
            else if ($this->_fieldTypes[$fixedKey] == 'enum')
            {
                $value = isset($this->_fieldData[$fixedKey][$value]) ? $this->_fieldData[$fixedKey][$value] : '';
            }
            else if ($this->_fieldTypes[$fixedKey] == 'date')
            {
                $value = date('Y-m-d', strtotime($value));
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
            return HtmlHelpers::CreateSelect($key, $this->getForeignKeyValues($key), $this->Get($key), '',  $this->GetClass('Edit', 'Select'));
        }
        else
        {
            $value = $this->Get($key);
            switch ($this->_fieldTypes[$fixedKey])
            {
                case 'bool':
                    $this->AddJavascript('Edit', 'Select', $key);
                    return HtmlHelpers::CreateSelect($key, array(0 => 'No', 1 => 'Yes'), $value, '', $this->GetClass('Edit', 'Select'));
                case 'text':
                    $this->AddJavascript('Edit', 'TextArea', $key);
                    return HtmlHelpers::CreateTextarea($key, $value, $key, $this->GetClass('Edit', 'TextArea'));
                case 'enum':
                    $this->AddJavascript('Edit', 'Select', $key);
                    return HtmlHelpers::CreateSelect($key, $this->_fieldData[$fixedKey], $value, '', $this->GetClass('Edit', 'Select'));
                case 'image':
                    $this->AddJavascript('Edit', 'Image', $key);
                    return HtmlHelpers::CreateImage('img'.$key, $value, $this->GetFieldData($fixedKey, 'directory'), $this->GetClass('Edit', 'Image')).
                        HtmlHelpers::CreateFileInput($key, $key, $this->GetClass('Edit', 'FileInput'), $this->GetFieldData($fixedKey, 'max_size', 1000000));
                case 'date':
                    $this->AddJavascript('Edit', 'Date', $key);
                    return HtmlHelpers::CreateInput($key, date('Y-m-d', strtotime($value)), '', $this->GetClass('Edit', 'Date'), $this->_fieldTypes[$fixedKey]);
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
        $styles = array();
        
        if ($view == 'edit')
        {
            $libraries = $this->_javascriptEditLibraries;
            $styles = $this->_cssEditStyles;
        }

        $js = '';
        foreach ($libraries as $library)
        {
            $js .= '<script type="text/javascript" src="'.$library.'"></script>'.PHP_EOL;
        }
        foreach ($styles as $style)
        {
            $js .= '<link href="'.$style.'" rel="stylesheet" type="text/css"/>';
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
            $this->Retrieve($this->PrimaryKey());
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
            $fields[$this->convertClassKeyToDBKey($field)] = $this->convertFieldToDisplayField($field);
        }

        return $fields;
    }

    /**
     * @param string $field
     * @return string
     */
    protected function convertFieldToDisplayField($field)
    {
        if (isset($this->_fieldAliases[$field]))
        {
            return $this->_fieldAliases[$field];
        }
        return parent::convertFieldToDisplayField($field);
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
    protected function AddJavascript($action, $type, $key)
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
    protected function GetClass($action, $type)
    {
        $property = ucfirst($action) . ucfirst($type) . 'Class';
        if (property_exists($this, $property))
        {
            return $this->$property;
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
    protected function GetFieldData($field, $data, $default = '')
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

    /**
     * @param string $field - dbField to be validated...
     * @param string $value -
     * @return string - error message or a blank string if there is no validation error.
     */
    public function ValidateField($field, $value)
    {
        $fieldKey = $this->convertDBKeyToClassKey($field);
        $fieldType = $this->_fieldTypes[$fieldKey];
        $error = '';
        switch ($fieldType)
        {
            case 'string':
                // We should probably know the max string length but for now we will make sure that it is not more 255 characters.
                if (strlen($value) > 255)
                {
                    $error = 'String too long';
                }
                break;
            case 'float':
                if (!is_numeric($value))
                {
                    $error = 'Must be a number.';
                }
                break;
            case 'int' :
                if ((int)$value != $value)
                {
                    $error = 'Must be an integer.';
                }
                break;
            case 'date':
                if (strtotime($value) === false)
                {
                    $error = 'Must be a valid date.';
                }
                break;
            case 'bool':
                if ($value != 1 && $value != 0)
                {
                    $error = 'Must be a valid boolean';
                }
                break;
            case 'enum':
                if (!isset($this->_fieldData[$fieldKey]))
                {
                    $error = 'Must be a valid part of the enumeration';
                }
                break;
            case 'text': // Nothing to validate in text...
            case 'image': case 'upload':// Image and upload are validated in the controller
                default:
                break;

        }

        if (strlen($error) > 0)
        {
            return $this->convertFieldToDisplayField($field).' value "'.$value.'" is invalid. '.$error;
        }
        return '';
    }


}