<?php


require(dirname(__FILE__).'/ClassModelTest.php');

/**
 * Test class for KrisModel.
 * Generated by PHPUnit on 2011-06-27 at 23:07:16.
 */
class KrisCrudModelTest extends PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function testGetOne()
    {
        $classId = 23;
        $categoryId = 1;
        $subcategoryId = 0;
        $day = 4;
        $startTime = '19:00:00';
        $length = 1;
        $cost = 109.00;
        $instructorId = 5;
        $maxStudents = 15;
        $startDate = '2011-04-14 00:00:00';
        $endDate = '2011-06-16 00:00:00';
        $descriptionId = 3;
        $offered = 0;
        $multiple = 0;
        $dropInCost = 15.0;
        $description = 'Drums\'Provided! No Experience Necessary!';
        $name = 'Drumming';
        $firstName = 'Bernard';
        $name1 = 'Drumming';

        $sql = 'SELECT t1.class_id,t1.category_id,t1.sub_category_id,t1.day,t1.start_time,t1.length,t1.cost,t1.instructor_id,t1.max_students,t1.start_date,t1.end_date,t1.description_id,t1.offered,t1.multiple,t1.drop_in_cost, t2.description AS description, t3.name AS name, t4.first_name AS first_name, t5.name AS name_c1 FROM class t1 INNER JOIN class_description t2 ON (t1.description_id = t2.class_description_id)  INNER JOIN category t3 ON (t1.category_id = t3.category_id)  INNER JOIN instructor t4 ON (t1.instructor_id = t4.instructor_id)  INNER JOIN category t5 ON (t1.sub_category_id = t5.category_id)  WHERE `class_id` = ?';

        $PDOMock = $this->getMock('MockPDO', array('prepare'), array(), '', false);
        $stmtMock = $this->getMock('PDOStatement', array('execute', 'errorCode', 'fetch'));

        $PDOMock->expects($this->once())->method('prepare')->with($sql)->will($this->returnValue($stmtMock));
        
        $stmtMock->expects($this->once())->method('execute')->with(array($classId));
        $stmtMock->expects($this->once())->method('errorCode')->will($this->returnValue(0));
        $stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->will($this->returnValue(array('class_id' => $classId,
            'category_id' => $categoryId, 'sub_category_id' => $subcategoryId, 'day' => $day, 'start_time' => $startTime,'length' => $length,
            'cost' => $cost, 'instructor_id' => $instructorId, 'max_students' => $maxStudents, 'start_date' => $startDate, 'end_date' => $endDate,
            'description_id' => $descriptionId, 'offered' => $offered, 'multiple' => $multiple, 'drop_in_cost' => $dropInCost, 'description' => $description,
            'name' => $name, 'first_name' => $firstName, 'name_c1' => $name1)));

        $crudMock = $this->getMock('ClassModelTest', array('getDatabaseHandle'));

        $crudMock->expects($this->once())->method('getDatabaseHandle')->will($this->returnValue($PDOMock));

        /** @var $crudMock ClassModelTest */
        $crudMock->retrieve($classId);


        $this->assertEquals($classId, $crudMock->ClassId);
        $this->assertEquals($categoryId, $crudMock->CategoryId);
        $this->assertEquals($subcategoryId, $crudMock->SubCategoryId);
        $this->assertEquals($day, $crudMock->Day);
        $this->assertEquals($startTime, $crudMock->StartTime);
        $this->assertEquals($length, $crudMock->Length);
        $this->assertEquals($cost, $crudMock->Cost);
        $this->assertEquals($instructorId, $crudMock->InstructorId);
        $this->assertEquals($maxStudents, $crudMock->MaxStudents);
        $this->assertEquals($startDate, $crudMock->StartDate);
        $this->assertEquals($endDate, $crudMock->EndDate);
        $this->assertEquals($descriptionId, $crudMock->DescriptionId);
        $this->assertEquals($offered, $crudMock->Offered);
        $this->assertEquals($multiple, $crudMock->Multiple);
        $this->assertEquals($dropInCost, $crudMock->DropInCost);
        $this->assertEquals($description , $crudMock->Description);
        $this->assertEquals($name , $crudMock->Name);
        $this->assertEquals($firstName, $crudMock->FirstName);
        $this->assertEquals($name1, $crudMock->NameC1);

    }
}

/**
 * MockPDO class allows mocking of the PDO object which doens't normally work...
 */
class MockPDO
{

}


?>
