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

     /**
     * @test
     */
    function convertClassKeyToDisplayTest()
    {
        $this->assertEquals('Record Id', $this->_krisDB->convertClassKeyToDisplayField('RecordId'));
        $this->assertEquals('Sub Category Id', $this->_krisDB->convertClassKeyToDisplayField('SubCategoryId'));
        $this->assertEquals('Sub Category Id', $this->_krisDB->convertClassKeyToDisplayField('sub_category_id'));

        $this->assertEquals($this->_krisDB->convertClassKeyToDisplayField('RecordId'),
            $this->_krisDB->convertClassKeyToDisplayField($this->_krisDB->convertDBKeyToClassKey('record_id')));
    }

    /**
     * @test
     */
    function testQuoting()
    {
        KrisConfig::$DATABASE_TYPE = KrisConfig::DB_TYPE_MYSQL;
        $this->assertEquals('`test`', $this->_krisDB->quoteDbObject('test'));

        KrisConfig::$DATABASE_TYPE = KrisConfig::DB_TYPE_MSSQL;
        $this->assertEquals('[test]', $this->_krisDB->quoteDbObject('test'));

        KrisConfig::$DATABASE_TYPE = KrisConfig::DB_TYPE_SQLITE;
        $this->assertEquals('"test"', $this->_krisDB->quoteDbObject('test'));

        KrisConfig::$DATABASE_TYPE = KrisConfig::DB_TYPE_POSTGRESQL;
        $this->assertEquals('"test"', $this->_krisDB->quoteDbObject('test'));


    }

    function testBindRecordSetNoInitialize()
    {
        $testResult2 = 'test_result2';
        $testField2 = 'test_field_2';
        $testResult1 = 'test_result_1';
        $testField1 = 'test_field1';

        $rs = array($testField1 => $testResult1, $testField2 => $testResult2);
        $this->_krisDB->bindRecordSet($rs, $this->_krisDB);

        $this->assertEquals($testResult1, $this->_krisDB->TestField1);
        $this->assertEquals($testResult1, $this->_krisDB->get('TestField1'));
        $this->assertEquals($testResult1, $this->_krisDB->get($testField1));
        
        $this->assertEquals($testResult2, $this->_krisDB->TestField2);
        $this->assertEquals($testResult2, $this->_krisDB->get('TestField2'));
        $this->assertEquals($testResult2, $this->_krisDB->get($testField2));
    }

    /**
     * @test
     */
    function testBindRecordSetInitialize()
    {
        $testResult2 = 'test_result2';
        $testField2 = 'test_field_2';
        $testResult1 = 'test_result_1';
        $testField1 = 'test_field1';

        // We are only initializing testField1 so only it's the only member that should be accessible.
        $this->_krisDB->initializeRecordSet(array('testField1'));

        $rs = array($testField1 => $testResult1, $testField2 => $testResult2);
        $this->_krisDB->bindRecordSet($rs, $this->_krisDB);

        $this->assertEquals($testResult1, $this->_krisDB->TestField1);
        $this->assertEquals($testResult1, $this->_krisDB->get('TestField1'));
        $this->assertEquals($testResult1, $this->_krisDB->get($testField1));

        $this->setExpectedException('DatabaseException', 'Invalid key: TestField2');
        $res = $this->_krisDB->TestField2;

    }

    /**
     * @test
     */
    function testAddFunctions()
    {
        $sql = 'SELECT * FROM table';

        $this->assertEquals($sql.' LIMIT 2', $this->_krisDB->addLimit($sql, 2));
        $this->assertEquals($sql.' ORDER BY table_id', $this->_krisDB->addOrder($sql, 'table_id'));
    }

    /**
     * @test
     */
    function testGenerateWhat()
    {
        KrisConfig::$DATABASE_TYPE = KrisConfig::DB_TYPE_MYSQL;

        $sql = '`foo`, `bar`';

        $this->assertEquals($sql, $this->_krisDB->generateWhat($sql));
        $this->assertEquals($sql, $this->_krisDB->generateWhat(array('foo','bar')));
    }

    /**
     * @test
     */
    function testGenerateWhere()
    {
        KrisConfig::$DATABASE_TYPE = KrisConfig::DB_TYPE_MYSQL;

        $sql = '`foo` = ? AND `bar` = ?';

        $this->assertEquals($sql, $this->_krisDB->generateWhere($sql, array('bindFoo', 'bindBar')));
        $this->assertEquals($sql, $this->_krisDB->generateWhere(array('foo','bar'), array('bindFoo', 'bindBar')));

        $this->setExpectedException('DatabaseException', 'Count of where (3) does not equal the count of bindings (2)');
        $this->_krisDB->generateWhere(array('foo','bar', 'baz'), array('bindFoo', 'bindBar'));
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

        function initializeRecordSet($records)
        {
            return parent::initializeRecordSet($records);
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

        function convertClassKeyToDisplayField($key)
        {
            return parent::convertClassKeyToDisplayField($key);
        }


}