<?php



class KrisDBTest extends PHPUnit_Framework_TestCase
{
    private $_krisDB;

	function __construct()
    {
        $this->_krisDB = new KrisDBExposeProtected();
    }


    /**
     * @test
     */
    function convertDBKeyToClassKeyTest()
    {
        $this->assertEquals('RecordId', $this->_krisDB->convertDBKeyToClassKey('record_id'));
        $this->assertEquals('SubCategoryId', $this->_krisDB->convertDBKeyToClassKey('sub_category_id'));
        $this->assertEquals('SubCategoryId', $this->_krisDB->convertDBKeyToClassKey('SubCategoryId'));

        $this->assertEquals($this->_krisDB->convertDBKeyToClassKey('record_id'),
            $this->_krisDB->convertDBKeyToClassKey($this->_krisDB->convertClassKeyToDBKey('RecordId')));
    }

    /**
     * @test
     */
    function convertClassKeyToDBKeyTest()
    {
        $this->assertEquals('record_id', $this->_krisDB->convertClassKeyToDBKey('RecordId'));
        $this->assertEquals('sub_category_id', $this->_krisDB->convertClassKeyToDBKey('SubCategoryId'));
        $this->assertEquals('sub_category_id', $this->_krisDB->convertClassKeyToDBKey('sub_category_id'));

        $this->assertEquals($this->_krisDB->convertClassKeyToDBKey('RecordId'),
            $this->_krisDB->convertClassKeyToDBKey($this->_krisDB->convertDBKeyToClassKey('record_id')));
    }

}


/**
 * Class used to expose protected functions in KrisDB.
 */
class KrisDBExposeProtected extends KrisDB
{
        /**
         * @param string $name
         * @return string
         */
        function quoteDbObject($name)
        {
            return parent::quoteDbObject($name);
        }

        /**
         *
         * @param array $rs
         * @param KrisDB $bindTo
         * @return bool|KrisDB
         */
        function bindRecordSet($rs, $bindTo)
        {
            return parent::bindRecordSet($rs, $bindTo);
        }

        /**
         * Returns multiple instances of a model based on a statement...
         *
         * @param PDOStatement $stmt
         * @return array
         */
        function returnMultiple($stmt)
        {
            return parent::returnMultiple($stmt);
        }

        /**
         * @param string $sql
         * @param int $count
         * @return string
         */
        function addLimit($sql, $count)
        {
            return parent::addLimit($sql, $count);
        }


        /**
         * @param string $sql
         * @param string|array $order
         * @return string
         */
        function addOrder($sql, $order)
        {
            return parent::addOrder($sql, $order);
        }

        /**
         * @param array|string $what
         * @return string
         */
        function generateWhat($what)
        {
            return parent::generateWhat($what);
        }

        /**
         * @throws Exception
         * @param array|string $where
         * @param array $bindings
         * @return string
         */
        function generateWhere($where, $bindings)
        {
            return parent::generateWhere($where, $bindings);
        }

        /**
         * Converts a key like record_id to RecordId
         * @param string $key
         * @return string
         */
        function convertDBKeyToClassKey($key)
        {
            return parent::convertDBKeyToClassKey($key);
        }

        /**
         * Converts a key like RecordId to record_id
         * @param string $key
         * @return string
         */
        function convertClassKeyToDBKey($key)
        {
            return parent::convertClassKeyToDBKey($key);
        }


}