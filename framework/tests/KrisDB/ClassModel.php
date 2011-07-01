<?php
/**
 * Example generated CrudModel for testing
 *
 * Generated Code, do not edit, edit the file Class.php in app\models
 */

/**
* @property int $ClassId
* @property int $CategoryId
* @property int $SubCategoryId
* @property int $Day
* @property string $StartTime
* @property float $Length
* @property float $Cost
* @property int $InstructorId
* @property int $MaxStudents
* @property string $StartDate
* @property string $EndDate
* @property int $DescriptionId
* @property bool $Offered
* @property bool $Multiple
* @property float $DropInCost
* @property string $Description
* @property string $Name
* @property string $FirstName
* @property string $NameC1

 */
class ClassModel extends KrisCrudModel
{
    protected $_foreignKeys = array('description_id' => array('table' => 'class_description', 'field' => 'class_description_id', 'display' => 'description', 'alias' => 'description'), 
       'category_id' => array('table' => 'category', 'field' => 'category_id', 'display' => 'name', 'alias' => 'name'), 
       'instructor_id' => array('table' => 'instructor', 'field' => 'instructor_id', 'display' => 'first_name', 'alias' => 'first_name'), 
       'sub_category_id' => array('table' => 'category', 'field' => 'category_id', 'display' => 'name', 'alias' => 'name_c1'));

    protected $_fakeFields = array('Description' => true, 'Name' => true, 'FirstName' => true, 'NameC1' => true);

    function __construct()
    {
        parent::__construct('class_id', 'class');
        $this->initializeRecordSet(array('ClassId', 'CategoryId', 'SubCategoryId', 'Day', 'StartTime', 'Length', 'Cost', 'InstructorId', 'MaxStudents', 'StartDate', 'EndDate', 'DescriptionId', 'Offered', 'Multiple', 'DropInCost', 'Description', 'Name', 'FirstName', 'NameC1'));

    }
}
?>