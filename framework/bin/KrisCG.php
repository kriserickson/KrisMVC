<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
require 'Args.php';

require __DIR__ . '/../../config/KrisConfig.php';
require __DIR__ . '/../lib/KrisDB.php';


class KrisCG extends KrisDB
{

    public function GenerateModel($tableName)
    {
        $tableName = strtolower($tableName);
        $columnNames = $this->GetColumnMetadata($tableName);

        $foreignKeys = $this->GetForeignKeys($tableName);

        $appPath = str_replace('/', DIRECTORY_SEPARATOR, KrisConfig::APP_PATH);
        $baseModelDir = $appPath . 'models';
        $generatedDir = __DIR__ . '/..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $baseModelDir . DIRECTORY_SEPARATOR . 'generated';
        $modelDir = __DIR__ . '/..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $baseModelDir;

        if (!file_exists($generatedDir))
        {
            mkdir($generatedDir);
        }

        $className = $this->convertDBKeyToClassKey($tableName);
        $safeClassName = $this->GenerateClassNameFromTableName($className);

        echo 'Generating class '.$className.PHP_EOL;

        $filename = $className . '.php';

        list($properties, $primaryKey, $initializeFields) = $this->GetPropertiesPrimaryKey($columnNames);

        $foreignKeyString = $this->GetForeignKeyString($foreignKeys);

        $this->GenerateBaseClass($tableName, $className, $filename, $properties, $foreignKeyString, $initializeFields, $primaryKey,
            $generatedDir, $baseModelDir);

        // Don't overwrite a class that has changes....
        if (!file_exists($modelDir . DIRECTORY_SEPARATOR . $filename))
        {
            $this->GenerateDerivedClass($filename, $safeClassName, $className, $modelDir);
        }
    }

    private function GenerateClassNameFromTableName($className)
    {
        if (in_array(strtolower($className), array('abstract', 'and', 'array', 'as', 'break', 'case', 'catch', 'class', 'clone',
                'const', 'continue', 'declare', 'default', 'do', 'else', 'elseif', 'enddeclare', 'endfor', 'endforeach',
                'endif', 'endswitch', 'endwhile', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto',
                'if', 'implements', 'interface', 'instanceof', 'namespace', 'new', 'or', 'private', 'protected',
                'public', 'static', 'switch', 'throw', 'try', 'use', 'var', 'while', 'xor', '__class__', '__dir__',
                '__file__', '__line__', '__function__', '__method__', '__namespace__', 'die', 'echo', 'empty', 'exit',
                'eval', 'include', 'include_once', 'isset', 'list', 'require', 'require_once', 'return', 'print', 'unset')))
        {
            $className .= 'Class';
        }

        return $className;
    }

    private function GetPropertiesPrimaryKey($columnNames)
    {
        $properties = '';
        $primaryKey = '';
        $initializeFields = '';
        foreach ($columnNames as $columnName => $columnData)
        {
            $dbKey = $this->convertDBKeyToClassKey($columnName);
            $properties .= '* @property ' . $columnData['type'] . ' $' . $dbKey . PHP_EOL;
            $initializeFields .= (strlen($initializeFields) > 0 ? ', ' : '')."'$dbKey'";
            if ($columnData['primary'])
            {
                $primaryKey = $columnName;
            }
        }
        $initializeFields = '$this->initializeRecordSet(array('.$initializeFields.'));'.PHP_EOL;
        return array($properties, $primaryKey, $initializeFields);
    }

    private function GetForeignKeyString($foreignKeys)
    {
        if (count($foreignKeys) > 0)
        {
            $foreignKeyString = 'public $_foreignKeys = array(';
            $first = true;
            foreach ($foreignKeys as $foreignKey => $foreignKeyProperties)
            {
                if (!$first)
                {
                    $foreignKeyString .= ', ' . PHP_EOL . '       ';
                }
                $foreignKeyString .= "'$foreignKey' => array('table' => '" . $foreignKeyProperties['table'] . "', 'field' => '" . $foreignKeyProperties['field'] . "')";
                $first = false;
            }
            $foreignKeyString .= ');' . PHP_EOL;
            return $foreignKeyString;
        }
        return '';
    }


    private function GenerateBaseClass($tableName, $className, $filename, $properties, $foreignKeyString, $initializeFields, $primaryKey, $generatedDir, $baseModelDir)
    {
        $output = <<<EOT
<?php
/**
 * Generated Code, do not edit, edit the file ${filename} in ${baseModelDir}
 */

/**
${properties}
 */
class ${className}Model extends KrisModel
{
    ${foreignKeyString}

    function __construct()
    {
        parent::__construct('${primaryKey}', '${tableName}');
        ${initializeFields}
    }
}
?>
EOT;
        $filePath = $generatedDir . DIRECTORY_SEPARATOR . $className . 'Model' . '.php';
        if (file_exists($filePath))
        {
            unlink($filePath);
        }
        $fp = fopen($filePath, 'w');
        fwrite($fp, $output);
        fclose($fp);
    }


    private function GenerateDerivedClass($filename, $safeClassName, $className, $modelDir)
    {
        $output = <<<EOT
<?php
/**
 * ${filename}
 *
 * Extend the class here, this file will not be overwritten.
 */

class ${safeClassName} extends ${className}Model
{
}
?>
EOT;

        $fp = fopen($modelDir . DIRECTORY_SEPARATOR . $filename, 'w');
        fwrite($fp, $output);
        fclose($fp);
    }

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
                    'primary' => $column_data['COLUMN_KEY'] == 'PRI');
            }
        }

        return $columnNames;

    }

    private function GetForeignKeys($table)
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

        return $foreignKeys;
    }

    private function GetTypeFromDataType($type)
    {
        switch ($type)
        {
            case 'varchar' : case 'text': case 'char': case 'mediumblob': case 'enum': case 'mediumtext': case 'set': case 'blob':
            case 'tinytext': case 'longblob': case 'time': case 'datetime': case 'date': case 'timestamp':
                return 'string';

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

}

$cg = new KrisCG();

$args = new Args();

if ($args->flag('t') || $args->flag('table'))
{
    $table = $args->flag('t') ? $args->flag('t') : $args->flag('table');
    $cg->GenerateModel($table);
}
else
{
    echo 'Usage KrisCG ' . PHP_EOL . '   -t --table  Table Name ' . PHP_EOL;
    echo PHP_EOL . 'With no options the Model is generated.';
}