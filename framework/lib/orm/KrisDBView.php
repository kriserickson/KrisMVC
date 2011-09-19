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
 * @throws Exception
 * @package Model
 *
 * KrisDBView
 * Represents a view of the multiple tables in the database...
 */
abstract class KrisDBView  extends KrisDB
{

    protected $_tables;
    protected $_joins;
    protected $_aliases;

    /**
     * Tables should be structured array('user' => array('user_id', 'first_name', 'last_name', 'email'));
     * where the array_key is the table name, and the values are an array of fields, with the first field being the primary key.
     *
     * Joins should be structured array('first_table' => array('join_table', 'first_table_foreign_key', 'join_table_join_key'));
     * if the join_table_join_key is the same as the primary key then the join_table_join_key can be omitted...
     * @throws Exception
     * @param array $tables
     * @param array $joins
     * @param array $alias
     * @return \KrisDBView
     *
     */
    function __construct($tables, $joins, $alias = array())
    {
        $this->_tables = $tables; //Corresponding table in database
        $this->_joins = $joins;
        $this->_aliases = $alias;

        $alias = 1;

        foreach (array_keys($this->_tables) as $tableName)
        {
            if (!isset($this->_aliases[$tableName]))
            {
                $this->_aliases[$tableName] = 't' . $alias++;
            }
        }

        foreach (array_keys($tables) as $tableName)
        {
            if (!isset($joins[$tableName]))
            {
                throw new Exception('Join missing for table: '.$tableName);
            }

        }

    }

    /**
     * @param array|string $where
     * @param array $bindings
     * @param $likeQuery
     * @param int $count
     * @param int $offset
     * @param array|string $order
     * @param bool $distinct
     * @return array
     */
    protected function retrieveMultiple($where, $bindings, $likeQuery, $count = 0, $offset = 0, $order = '', $distinct = false)
    {
        return $this->returnMultiple($this->returnStatement($where, $bindings, $likeQuery, $count, $offset, $order, $distinct));
    }

    /**
     * @param array|string $where
     * @param array $bindings
     * @return KrisDBView
     */
    protected function retrieveOne($where, $bindings)
    {
        return $this->bindRecordSet($this->returnStatement($where, $bindings, false, 1, 0, '', false)->fetch(PDO::FETCH_ASSOC), $this);
    }

    /**
     * @param array $where
     * @param array $bindings
     * @param bool $likeQuery
     * @param int $count
     * @param int $offset
     * @param string $order
     * @param bool $distinct
     * @return PDOStatement
     */
    protected function returnStatement($where, $bindings, $likeQuery, $count, $offset, $order, $distinct)
    {
        $dbh = $this->getDatabaseHandle();

        if (is_scalar($bindings))
        {
            $bindings = $bindings ? array($bindings) : array();
        }

        $sql = $this->generateQuery($where, $bindings, $likeQuery, $count, $offset, $order, $distinct);

        $bindings = $this->GetBindings($bindings, $likeQuery);

        $stmt = $dbh->prepare($sql);

        $stmt->execute($bindings);

        $this->ValidateStatement($stmt);
        return $stmt;
    }


    /**
     * @param string|array $where
     * @param array $bindings
     * @param $likeQuery
     * @param int $count
     * @param int $offset
     * @param array|string $order
     * @param bool $distinct
     * @return string
     */
    private function generateQuery($where, $bindings, $likeQuery, $count, $offset, $order, $distinct)
    {
        $sql = $this->generateSelect($distinct);

        if ((is_array($where) && count($where) > 0) || strlen($where) > 0)
        {
            $sql .= ' WHERE ' . $this->generateWhere($where, $bindings, $likeQuery);
        }

        return $this->addLimit($this->addOrder($sql, $order), $count, $offset);

    }

    /**
     * @param bool $distinct
     * @return string
     */
    private function generateSelect($distinct = false)
    {

        $queryFields = array();
        $from = '';

        foreach ($this->_tables as $tableName => $tableFields)
        {
            foreach ($tableFields as $table_alias => $tableField)
            {
                $queryFields[] = $this->_aliases[$tableName] . '.' . $tableField.(is_string($table_alias) ? ' AS '.$table_alias : '');
            }
            $from .= (strlen($from) > 0 ? ' INNER JOIN ' : '') . $tableName . ' ' . $this->_aliases[$tableName] . ' ' .
                    (strlen($from) > 0 ? $this->generateJoin($tableName) : '');
        }

        return 'SELECT ' . ($distinct ? ' DISTINCT ' : '').  implode(',', $queryFields) . ' FROM ' . $from;
    }

    /**
     * Joins should be structured array('first_table' => array('join_table', 'first_table_foreign_key', 'join_table_join_key'));
     *
     * @param string $tableName
     * @return string
     */
    private function generateJoin($tableName)
    {

        $sql = ' ON (';

        // If it is a simple one field to on field join...
        if (!is_array($this->_joins[$tableName][1]))
        {
            $sql .= $this->_aliases[$tableName].'.'.$this->_joins[$tableName][1].' = '.$this->_aliases[$this->_joins[$tableName][0]].'.';

            if (isset($this->_joins[$tableName][2]))
            {
                $sql .= $this->_joins[$tableName][2];
            }
            else
            {
                $sql .= $this->_tables[$this->_joins[$tableName][0]][0];
            }

        }
        else
        {
            // If it is a more complicate join like table1 t1 JOIN table2 t2 ON (t1.field_1 = t2.field_1 AND t1.field_2 = t2.field_2)
            $joinType = isset($this->_joins[$tableName][3]) ? ' '.$this->_joins[$tableName][3].' ' : ' AND ';

            for ($index = 0; $index < count($this->_joins[$tableName][1]); $index++)
            {
                if ($index > 0)
                {
                    $sql .= $joinType;
                }

                $sql .= $this->_aliases[$tableName].'.'.$this->_joins[$tableName][1][$index].' = '.$this->_aliases[$this->_joins[$tableName][0]].'.';

                if (isset($this->_joins[$tableName][2]) && is_array($this->_joins[$tableName][2]))
                {
                    $sql .= $this->_joins[$tableName][2][$index].'.';
                }
                else if (isset($this->_joins[$tableName][2]))
                {
                    $sql .= $this->_joins[$tableName][2];
                }
                else
                {
                    $sql .= $this->_tables[$this->_joins[$tableName][0]][0];
                }
            }
        }

        return $sql.')';
    }





}