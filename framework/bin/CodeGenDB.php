<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

abstract class CodeGenDB extends KrisModel
{
    /**
     * Returns whether or not a table exists..
     *
     * @param $tableName
     * @return bool
     */
    protected function TableExists($tableName)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare('select TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ?');

        if ($stmt->execute(array($tableName)))
        {
            $this->ValidateStatement($stmt);

            return $stmt->rowCount() > 0;
        }

        return false;
    }


    /**
     * @param string $tableName
     * @param string $fieldName
     * @return bool
     */
    protected function FieldExists($tableName, $fieldName)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("select COLUMN_NAME, DATA_TYPE, COLUMN_KEY from INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ? and COLUMN_NAME = ?");

        if ($stmt->execute(array($tableName, $fieldName)))
        {
            $this->ValidateStatement($stmt);

            return $stmt->rowCount() > 0;
        }

        return false;

    }

    /**
     * @param string $tableName
     * @param string $indexName
     * @return bool
     */
    protected function IndexExists($tableName, $indexName)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("select INDEX_NAME from INFORMATION_SCHEMA.STATISTICS where `TABLE_CATALOG` is null and `TABLE_SCHEMA` = DATABASE() and `TABLE_NAME` = ? and `INDEX_NAME` = ?");

        if ($stmt->execute(array($tableName, $indexName)))
        {
            $this->ValidateStatement($stmt);

            return $stmt->rowCount() > 0;
        }

        return false;

    }


/**
     * @param $type
     * @return string
     */
    protected function GetTypeFromDataType($type)
    {
        // TODO: Make this work with non-mysql types...
        switch (strtolower($type))
        {
            case 'varchar' : case 'char': case 'set':
                return 'string';
            case 'mediumblob': case 'blob': case 'longblob':
                return 'blob';
            case 'text': case 'mediumtext': case 'tinytext':
                return 'text';
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
     * @param $table
     * @return array
     */
    protected function GetColumnMetadata($table)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("select COLUMN_NAME, DATA_TYPE, COLUMN_KEY from INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ?");

        $columnNames = array();

        if ($stmt->execute(array($table)))
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
    protected function GetForeignKeys($table, $usedColumnNames)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.TABLE_CONSTRAINTS c
            INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu ON
            (c.CONSTRAINT_NAME = cu.CONSTRAINT_NAME AND cu.TABLE_NAME = c.TABLE_NAME AND cu.TABLE_SCHEMA = c.TABLE_SCHEMA)
            WHERE c.TABLE_SCHEMA = DATABASE() AND c.CONSTRAINT_TYPE = ? AND c.TABLE_NAME = ?");

        $foreignKeys = array();

        if ($stmt->execute(array('FOREIGN KEY', $table)))
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
                while (isset($usedColumnNames[$columnName . $alias]))
                {
                    $alias = '_c' . $aliasCount++;
                }
                $foreignKeys[$column]['alias'] = $columnName . $alias;
                if (!$columnData['primary'] && $columnData['type'] == 'string')
                {
                    $usedColumnNames[$columnName . $alias] = true;
                    break;
                }

            }
        }

        return $foreignKeys;
    }

}
