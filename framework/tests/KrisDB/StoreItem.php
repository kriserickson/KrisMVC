<?php

/**
 * Example Model for testing...git a
 */

/**
 * @property string Name
 * @property string Description
 * @property decimal Price
 * @property string Image
 * @property int Multiple
 */
class StoreItem extends KrisModel
{
    public $StoreItems = array();

    function __construct($id = '')
    {
      parent::__construct('store_id', 'store');
      $this->initializeRecordSet(array('StoreId', 'Name','Description','Price','Image','Multiple'));
      if ($id)
      {
          $this->retrieve($id);
      }
    }

}
