<?php
/**
 * Generated Code, do not edit, edit the file {{{filename}}} in {{{BaseModelDirectory}}}
 */

/**
{{{properties}}}
 */
class {{{className}}}Model extends KrisCrudModel
{
    {{{foreignKeyString}}}
    {{{fakeFields}}}

    protected $_fieldTypes = array({{{fieldTypes}}});

    public $DisplayName = '{{{className}}}';

    /**
     * Constructor.
     */
    function __construct()
    {
        parent::__construct('{{{primaryKey}}}', '{{{tableName}}}');
        {{{initializeFields}}}
    }
}
?>