<?php
/**
 * Generated Code, do not edit, edit the file CronView.php in /srv/restaurant/app/models
 */

/**
* @property int $CronId - DBType int
* @property string $Name - DBType varchar
* @property string $Frequency - DBType varchar
* @property int $Pid - DBType int
* @property bool $IsEnabled - DBType tinyint
* @property string $LastRun - DBType timestamp
* @method CronModel[] RetrieveMultiple(array $where = null, array $bindings = null, bool $likeQuery = false, int $count = 0, int $offset = 0, string $orderBy = '', bool $orderAscending = false)
* @method CronModel Retrieve(string $primaryKeyOrFieldName, string $value = null)
* @method CronModel|bool Create
*/
class CronModel extends KrisCrudModel
{
    const FIELD_CRON_ID = 'CronId';
    const FIELD_NAME = 'Name';
    const FIELD_FREQUENCY = 'Frequency';
    const FIELD_PID = 'Pid';
    const FIELD_IS_ENABLED = 'IsEnabled';
    const FIELD_LAST_RUN = 'LastRun';


    protected $_fieldTypes = array('CronId' => 'int', 'Name' => 'string', 'Frequency' => 'string', 'Pid' => 'int', 'IsEnabled' => 'bool', 'LastRun' => 'string');

    /**
     * @var string
     */
    public $DisplayName = 'Cron';

    /**
     * Constructor.
     */
    function __construct($cronId = null)
    {
        parent::__construct('cron_id', 'cron');
        $this->initializeRecordSet(array('CronId', 'Name', 'Frequency', 'Pid', 'IsEnabled', 'LastRun'));
        if (!is_null($cronId))
        {
            $this->Retrieve($cronId);
        }
    }

    /**
     * @return int
     */
    public function LastRunTimestamp()
    {
        if (strlen($this->LastRun) > 0)
        {
            return strtotime($this->LastRun);
        }
        return 0;
    }

    /**
     * @return string
     */
    public function GetHumanFrequency() {
        $matches = false;
        if ($this->Frequency == 'W') {
            return 'Weekly';
        } elseif (preg_match('/M(\d+)/', $this->Frequency, $matches)) {
            if ($matches == 0) {
                return '**Error**';
            }
            return 'Every ' . $matches[1] . ' minute' . ($matches[1] > 1 ? 's' : '');
        } elseif (preg_match('/H(\d+)/', $this->Frequency, $matches)) {
            if ($matches[1] == 0) {
                return '**Error**';
            }
            return 'Every ' . $matches[1] . ' hour'.($matches[1] > 1 ? 's' : '');
        }
        elseif (preg_match('/D(\d+)/', $this->Frequency, $matches)) {
            $offset = $matches[1];
            $hoursAfterMidnight = $offset;
            if ($hoursAfterMidnight > 12) {
                $amPm = 'pm';
                $hoursAfterMidnight -= 12;
            } else {
                if ($hoursAfterMidnight == 0) {
                    $hoursAfterMidnight = 12;
                }
                $amPm = 'am';
            }
            // GMT if we switch back...  Eventually convert this to user time...
            return "At $hoursAfterMidnight:00 $amPm";
        }
        else if ($this->Frequency == '*') {
            return 'Every Minute';
        } else {
            return '**Error**';
        }

    }
}
