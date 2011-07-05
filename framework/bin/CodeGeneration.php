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
 * Used for code generation...
 */
class KrisCG extends KrisDB
{

    const UNDERSCORE_PLACEHOLDER = '+=+';

    private $applicationDirectory;
    private $baseModelDirectory;
    private $generatedDirectory;
    private $crudDirectory;

    /**
     * Constructor
     * @param string $appPath
     * @return KrisCG
     */
    public function __construct($appPath = '')
    {
        if (strlen($appPath) == 0)
        {
            $appPath = __DIR__ . '/..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        }

        $this->applicationDirectory = str_replace('/', DIRECTORY_SEPARATOR, KrisConfig::APP_PATH);
        $this->baseModelDirectory = $this->applicationDirectory . 'models';
        $this->generatedDirectory = $appPath . $this->baseModelDirectory . DIRECTORY_SEPARATOR . 'generated';
        $this->crudDirectory = $appPath . $this->baseModelDirectory . DIRECTORY_SEPARATOR . 'crud';
        

        if (!file_exists($this->generatedDirectory))
        {
            mkdir($this->generatedDirectory);
        }
        if (!file_exists($this->crudDirectory))
        {
            mkdir($this->crudDirectory);
            
        }        
    }

    /**
     * Generates a model based on a table name
     * @param $tableName
     * @return void
     */
    public function GenerateModel($tableName)
    {
        $tableName = strtolower($tableName);

        if (!$this->TableExists($tableName))
        {
            die('ERROR: Table '.$tableName.' does not exist');
        }

        $columnNames = $this->GetColumnMetadata($tableName);

        $foreignKeys = $this->GetForeignKeys($tableName, array_keys($columnNames));

        $className = $this->convertDBKeyToClassKey($tableName);

        echo 'Generating class '.$className.PHP_EOL;

        $safeClassName = $className.'View';


        list($properties, $primaryKey, $initializeFields, $fakeFields, $fieldTypes) = $this->GetPropertiesPrimaryKey($columnNames, $foreignKeys);

        $foreignKeyString = $this->GetForeignKeyString($foreignKeys);

        $filename = $safeClassName . '.php';
        
        $this->GenerateBaseClass($tableName, $className, $filename, $properties, $foreignKeyString, $initializeFields, $fakeFields, $primaryKey, $fieldTypes);

        // Don't overwrite a class that has changes....
        if (!file_exists($this->crudDirectory . DIRECTORY_SEPARATOR . $filename))
        {
            $this->GenerateDerivedClass($filename, $safeClassName, $className);
        }
    }

    

    /**
     * @param $columnNames
     * @param $foreignKeys
     * @return array ($properties, $primaryKey, $initializeFields, $fakeFields, $fieldTypes)
     */
    private function GetPropertiesPrimaryKey($columnNames, $foreignKeys)
    {
        $properties = '';
        $primaryKey = '';
        $initializeFields = '';
        $fakeFields = '';
        $fieldTypes = '';

        foreach ($columnNames as $columnName => $columnData)
        {
            $dbKey = $this->convertDBKeyToClassKey($columnName);
            $properties .= '* @property ' . $columnData['type'] . ' $' . $dbKey . PHP_EOL;
            $initializeFields .= (strlen($initializeFields) > 0 ? ', ' : '')."'$dbKey'";
            $fieldTypes .= (strlen($fieldTypes) > 0 ? ', ' : '')."'$dbKey' => '".$columnData['type']."'";

            if ($columnData['primary'])
            {
                $primaryKey = $columnName;
            }
        }

        foreach ($foreignKeys as $foreignKeyId => $foreignKeyData)
        {
            $dbKey = $this->convertDBKeyToClassKey($foreignKeyData['alias']);
            $properties .= '* @property string $' . $dbKey . PHP_EOL;
            $initializeFields .= (strlen($initializeFields) > 0 ? ', ' : '')."'$dbKey'";
            $fakeFields .= (strlen($fakeFields) > 0 ? ', ' : '')."'$dbKey' => '$foreignKeyId'";
        }

        $initializeFields = '$this->initializeRecordSet(array('.$initializeFields.'));'.PHP_EOL;
        if (strlen($fakeFields) > 0)
        {
            $fakeFields = 'protected $_fakeFields = array('.$fakeFields.');';
        }
        return array($properties, $primaryKey, $initializeFields, $fakeFields, $fieldTypes);
    }

    /**
     * Returns all the foreign keys...
     *
     * @param $foreignKeys
     * @return string
     */
    private function GetForeignKeyString($foreignKeys)
    {
        if (count($foreignKeys) > 0)
        {
            $foreignKeyString = 'protected $_foreignKeys = array(';
            $first = true;
            foreach ($foreignKeys as $foreignKey => $foreignKeyProperties)
            {
                if (!$first)
                {
                    $foreignKeyString .= ', ' . PHP_EOL . '       ';
                }
                $foreignKeyString .= "'$foreignKey' => array('table' => '" . $foreignKeyProperties['table'] . "', ".
                    "'field' => '" . $foreignKeyProperties['field'] . "', 'display' => '".$foreignKeyProperties['display']."', ".
                    "'alias' => '" . $foreignKeyProperties['alias'] . "')";
                $first = false;
            }
            $foreignKeyString .= ');' . PHP_EOL;
            return $foreignKeyString;
        }
        return '';
    }


    /**
     * Outputs the base class to a file..
     *
     * @param $tableName
     * @param $className
     * @param $filename
     * @param $properties
     * @param $foreignKeyString
     * @param $initializeFields
     * @param $fakeFields
     * @param $primaryKey
     * @param $fieldTypes
     * @return void
     */
    private function GenerateBaseClass($tableName, $className, $filename, $properties, $foreignKeyString, $initializeFields,
        $fakeFields, $primaryKey, $fieldTypes)
    {
        $output = <<<EOT
<?php
/**
 * Generated Code, do not edit, edit the file ${filename} in {$this->baseModelDirectory}
 */

/**
${properties}
 */
class ${className}Model extends KrisCrudModel
{
    ${foreignKeyString}
    ${fakeFields}

    protected \$_fieldTypes = array(${fieldTypes});

    public \$DisplayName = '${className}';

    /**
     * Constructor.
     */
    function __construct()
    {
        parent::__construct('${primaryKey}', '${tableName}');
        ${initializeFields}
    }
}
?>
EOT;
        $filePath = $this->generatedDirectory . DIRECTORY_SEPARATOR . $className . 'Model' . '.php';
        if (file_exists($filePath))
        {
            unlink($filePath);
        }
        $fp = fopen($filePath, 'w');
        fwrite($fp, $output);
        fclose($fp);
    }

    /**
     * @param $filename
     * @param $safeClassName
     * @param $className
     * @return void
     */
    private function GenerateDerivedClass($filename, $safeClassName, $className)
    {
        $output = <<<EOT
<?php
/**
 * ${filename}
 *
 * Extend the class here, this file will not be overwritten.
 */

/**
 * Constructor.
 */
class ${safeClassName} extends ${className}Model
{

}

?>
EOT;

        $fp = fopen($this->crudDirectory . DIRECTORY_SEPARATOR . $filename, 'w');
        fwrite($fp, $output);
        fclose($fp);
    }

    /**
     * @param $table
     * @return array
     */
    private function GetColumnMetadata($table)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("select COLUMN_NAME, DATA_TYPE, COLUMN_KEY from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME = ? AND TABLE_SCHEMA = ?");

        $columnNames = array();

        if ($stmt->execute(array($table, KrisConfig::DB_DATABASE)))
        {
            $raw_column_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($raw_column_data as $column_data)
            {
                $columnNames[$column_data['COLUMN_NAME']] = array('type' => $this->GetTypeFromDataType($column_data['DATA_TYPE']),
                    'displayType' => $column_data['DATA_TYPE'], 'primary' => $column_data['COLUMN_KEY'] == 'PRI');
            }
        }

        return $columnNames;

    }

    /**
     * @param $table
     * @param $usedColumnNames
     * @return array
     */
    private function GetForeignKeys($table, $usedColumnNames)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.TABLE_CONSTRAINTS c
            INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu ON
            (c.CONSTRAINT_NAME = cu.CONSTRAINT_NAME AND cu.TABLE_NAME = c.TABLE_NAME AND cu.TABLE_SCHEMA = c.TABLE_SCHEMA)
            WHERE c.CONSTRAINT_TYPE = ? AND c.TABLE_SCHEMA = ? AND c.TABLE_NAME = ?");

        $this->ValidateStatement($stmt);

        $foreignKeys = array();

        if ($stmt->execute(array('FOREIGN KEY', KrisConfig::DB_DATABASE, $table)))
        {
            $this->ValidateStatement($stmt);

            $foreign_key_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($foreign_key_data as $foreign_key)
            {
                $foreignKeys[$foreign_key['COLUMN_NAME']] = array('table' => $foreign_key['REFERENCED_TABLE_NAME'],
                    'field' => $foreign_key['REFERENCED_COLUMN_NAME']);

            }
        }



        foreach ($foreignKeys as $column => $colData)
        {
            $referencedTableColumns = $this->GetColumnMetadata($colData['table']);
            $alias = '';
            $aliasCount = 1;
            foreach ($referencedTableColumns as $columnName => $columnData)
            {
                $foreignKeys[$column]['display'] = $columnName;
                while (isset($usedColumnNames[$columnName.$alias]))
                {
                    $alias = '_c'.$aliasCount++;
                }
                $foreignKeys[$column]['alias'] = $columnName.$alias;
                if (!$columnData['primary'] && $columnData['type'] == 'string')
                {
                    $usedColumnNames[$columnName.$alias] = true;
                    break;
                }

            }
        }

        return $foreignKeys;
    }

    /**
     * @param $type
     * @return string
     */
    private function GetTypeFromDataType($type)
    {
        // TODO: Make this work with non-mysql types...
        switch ($type)
        {
            case 'varchar' : case 'text': case 'char': case 'mediumblob': case 'mediumtext': case 'set': case 'blob':
            case 'tinytext': case 'longblob':
                return 'string';

            case 'time': case 'timestamp': case 'datetime': case 'date': case 'enum':
                return $type;

            case 'bigint': case 'longtext': case 'int': case 'mediumint': case 'smallint':
                return 'int';

            case 'tinyint':
                return 'bool';

            case 'decimal': case 'float': case 'double':
                return 'float';

            default:
                return 'mixed'; // Really unknown...

        }
    }

    /**
     * Returns whether or not a table exists..
     *
     * @param $tableName
     * @return bool
     */
    private function TableExists($tableName)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");

        $this->ValidateStatement($stmt);

        if ($stmt->execute(array(KrisConfig::DB_DATABASE, $tableName)))
        {
            $this->ValidateStatement($stmt);

            return $stmt->rowCount() > 0;
        }

        return false;
    }


}

?>