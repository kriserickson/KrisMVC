<?php
/**
 * Generated Code, do not edit, edit the file CronLogView.php in /srv/restaurant/app/models
 */

/**
* @property int $CronLogId - DBType int
* @property int $CronId - DBType int
* @property string $ExecutionDate - DBType datetime
* @property bool $Success - DBType tinyint
* @property string $Message - DBType text
* @method CronLogModel[] RetrieveMultiple(array $where = null, array $bindings = null, bool $likeQuery = false, int $count = 0, int $offset = 0, string $orderBy = '', bool $orderAscending = false)
* @method CronLogModel Retrieve(string $primaryKeyOrFieldName, string $value = null)
* @method CronLogModel|bool Create
*/
class CronLogModel extends KrisCrudModel
{
    const FIELD_CRON_LOG_ID = 'CronLogId';
    const FIELD_CRON_ID = 'CronId';
    const FIELD_EXECUTION_DATE = 'ExecutionDate';
    const FIELD_SUCCESS = 'Success';
    const FIELD_MESSAGE = 'Message';

    
    

    protected $_fieldTypes = array('CronLogId' => 'int', 'CronId' => 'int', 'ExecutionDate' => 'string', 'Success' => 'bool', 'Message' => 'string');

    public $DisplayName = 'CronLog';

    /**
     * Constructor.
     */
    function __construct()
    {
        parent::__construct('cron_log_id', 'cron_log');
        $this->initializeRecordSet(array('CronLogId', 'CronId', 'ExecutionDate', 'Success', 'Message'));

    }
}
