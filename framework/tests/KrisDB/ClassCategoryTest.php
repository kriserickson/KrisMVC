<?php

// Example DBView class for testing...

/**
 * @property int $CategoryId
 * @property string $Name
 * @property string $Image
 * @property string $Code
 * @property string $ShortDescription
 */
class ClassCategoryTest extends KrisDBView
{
    function __construct()
    {
        parent::__construct(array('category' => array('category_id', 'name', 'image', 'code', 'short_description'),
                'class' => array()),
            array('category' => array(),
                'class' => array('category', array('category_id', 'sub_category_id'), 'category_id', 'OR')),
            array('category' => 'ca', 'class' => 'cl'));

        $this->initializeRecordSet(array('CategoryId', 'Name', 'Image', 'Code', 'ShortDescription'));

    }

    /**
     * @return array
     */
    public function getActiveCategories()
    {
         return $this->retrieveMultiple('ca.category_id <> ? AND offered = ? and image <> ?', array(0,1,''), false, 0, 'category_position', true);
    }


}
 
