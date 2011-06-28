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
require '../lib/KrisDB.php';
require '../../config/KrisConfig.php';

class KrisCG extends KrisDB
{

    public function GenerateModel($table)
    {
        $columnNames = $this->GetColumnMetadata($table);

        $appPath = str_replace('/', DIRECTORY_SEPARATOR, KrisConfig::APP_PATH);

        $baseModelDir = $appPath.'models';
        $generatedDir = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$baseModelDir.DIRECTORY_SEPARATOR.'generated';
        $modelDir = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$baseModelDir;

        if (!file_exists($generatedDir))
        {
            mkdir($generatedDir);
        }


        $className = $this->convertDBKeyToClassKey($table);
        $filename = $className.'.php';

        $properties = '';
        $primaryKey = '';
        foreach ($columnNames as $columnName => $columnData)
        {
            $properties .= '* @property '.$columnData['type'].' $'.$this->convertDBKeyToClassKey($columnName).PHP_EOL;
            if ($columnData['primary'])
            {
                $primaryKey = $columnName;
            }

        }

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
    function __construct()
    {
        parent::__construct('${primaryKey}', '${table}');
    }
}
?>
EOT;
        $fp = fopen($generatedDir.DIRECTORY_SEPARATOR.$className.'Model'.'.php', 'w');
        fwrite($fp, $output);
        fclose($fp);

        if (!file_exists($modelDir.DIRECTORY_SEPARATOR.$filename))
        {
                    $output = <<<EOT
<?php
/**
 * ${filename}
 *
 * Extend the class here, this file will not be overwritten.
 */

/**
${properties}
 */
class ${className} extends ${className}Model
{
    function __construct()
    {
        parent::__construct('${primaryKey}', '${table}');
    }
}
?>
EOT;

            $fp = fopen($modelDir.DIRECTORY_SEPARATOR.$filename, 'w');
            fwrite($fp, $output);
            fclose($fp);
        }
    }

    private function GetColumnMetadata($table)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("select COLUMN_NAME, DATA_TYPE, COLUMN_KEY from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME = ? AND TABLE_SCHEMA = ?");

        $columnNames = array();

        if ($stmt->execute(array($table, KrisConfig::DB_DATABASE)))
        {
            $raw_column_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($raw_column_data as $array)
            {
                $columnNames[$array['COLUMN_NAME']] = array('type' => $this->GetTypeFromDataType($array['DATA_TYPE']),
                    'primary' => $array['COLUMN_KEY'] == 'PRI');
            }
        }

        return $columnNames;

    }

    private function GetTypeFromDataType($type)
    {
        switch ($type)
        {
            case 'varchar' :
            case 'text':
            case 'char':
            case 'mediumblob':
            case 'enum':
            case 'mediumtext':
            case 'set':
            case 'blob':
            case 'tinytext':
            case 'longblob':
            case 'time':
            case 'datetime':
            case 'date':
            case 'timestamp':
                return 'string';

            case 'bigint':
            case 'longtext':
            case 'int':
            case 'mediumint':
            case 'smallint':
                return 'int';

            case 'tinyint':
                return 'bool';

            case 'decimal':
            case 'float':
            case 'double':
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
    $table = $args->flag('t') ? $args->flag('t')  : $args->flag('table');
    $cg->GenerateModel($table);
}
else
{
    echo 'Usage KrisCG ' . PHP_EOL . '   -t --table  Table Name ' . PHP_EOL;
    echo PHP_EOL . 'With no options the Model is generated.';
}