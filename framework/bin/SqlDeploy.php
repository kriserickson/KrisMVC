<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once dirname(__FILE__) . '/../lib/orm/KrisDB.php';
require_once dirname(__FILE__) . '/../lib/orm/KrisModel.php';
require_once dirname(__FILE__) . '/../lib/plumbing/AutoLoader.php';
require_once dirname(__FILE__) . '/../lib/plumbing/BucketContainer.php';
require_once dirname(__FILE__) . '/Args.php';
require_once dirname(__FILE__) . '/CodeGenHelpers.php';
require_once dirname(__FILE__) . '/CodeGenDB.php';

/**
 * @throws Exception
 *
 * @property int $ChangesetId
 * @property string $Type
 * @property string $Author
 * @property string $Hash
 * @property string $Executed - Date
 *
 */
class SqlDeploy extends CodeGenDB
{
    const DEPLOY_TABLE = '_db_deploy';


    /**
     * @var bool
     */
    private $_force;

    /**
     * @var string
     */
    private $_directory;

    /**
     * @var array
     */
    private $_allChangesets = array();

    /**
     * @var array
     */
    private $_executeChangesets = array();

    /**
     * @var bool
     */
    private $_markPreconditionComplete;

    /**
     * @var bool
     */
    private $_verbose;

    /**
     * @var bool
     */
    private $_showOnly;

    /**
     * @throws Exception
     *
     */
    function __construct()
    {
        $args = new Args();

        $success = false;

        parent::__construct('changeset_id', self::DEPLOY_TABLE);
        $this->initializeRecordSet(array('ChangesetId', 'Type', 'Author', 'Hash', 'Executed'));

        $command = $args->Command();
        if (in_array($command, array('validate', 'migrate', 'rollback')))
        {
            try
            {                
                $options = array('host' => 'h', 'database' => 'd', 'user' => 'u', 'password' => 'p', 'directory' => 'y');
                if ($command == 'rollback')
                {
                    $options['changeset'] = 'c';
                }
                foreach ($options as $requiredName => $shortcut)
                {
                    if (!($args->flag($shortcut) || $args->flag($requiredName)))
                    {
                        throw new Exception('Missing required argument ' . $requiredName);
                    }
                    $options[$requiredName] = !$args->flag($shortcut) ? $args->flag($requiredName) : $args->flag($shortcut);
                }

                $this->_directory = $options['directory'];
                $this->_force = $args->flag('f') || $args->flag('force');
                $this->_markPreconditionComplete = $args->flag('m') || $args->flag('mark');
                $this->_verbose = $args->flag('v') || $args->flag('verbose');
                $this->_showOnly = $args->flag('s') || $args->flag('showsql');


                $factory = array('PDO' => function() use ($options)
                {
                    $dsn = 'mysql:host=' . $options['host'] . ';dbname=' . $options['database'];
                    return new PDO($dsn, $options['user'], $options['password']);
                });
                AutoLoader::$Container = new BucketContainer($factory);

                $this->_dbh = $this->getDatabaseHandle();

                $this->ValidateMigration();
                if ($command == 'migrate')
                {
                    $this->Migrate();
                }
                elseif ($command == 'rollback')
                {
                    $this->Rollback($options['changeset']);
                }

            }
            catch (Exception $ex)
            {
                $STDERR = fopen("php://stderr", "w");
                fwrite($STDERR, 'Error: ' . $ex->getMessage() . PHP_EOL);
                fclose($STDERR);
                return;
            }
            
            echo 'Success'.PHP_EOL;
        }
        else
        {
            if (strlen($command) > 0 && $command != 'help')
            {
                echo PHP_EOL . 'Error: Invalid Command "' . $command . '"' . PHP_EOL . PHP_EOL;
            }
            if (!$success)
            {
                $this->DisplayUsage();
            }    
        }
        
    }

    /**
     * @return void
     */
    private function DisplayUsage()
    {
        echo 'Usage SqlDeploy ' . PHP_EOL .
                '   Commands:       Options ' . PHP_EOL . PHP_EOL .
                '   validate                            Validates the deployment files in directory' . PHP_EOL .
                '   migrate                             Runs the migration' . PHP_EOL .
                '   rollback                            Runs the rollback command on a single changesets' . PHP_EOL .
                '                   -c --changeset      The id of the changeset to rollback' . PHP_EOL . PHP_EOL .
                '  Required for all commands:' . PHP_EOL .
                '                   -y --directory      Location migration files (normally app/sql from the base directory)' . PHP_EOL .
                '                   -h --host           Database host.' . PHP_EOL .
                '                   -d --database       Database name' . PHP_EOL .
                '                   -u --user           Database user.' . PHP_EOL .
                '                   -h --password       Database password.' . PHP_EOL . PHP_EOL;
                '  Options:' . PHP_EOL .
                '                   -f --force          Ignore non-fatal errors' . PHP_EOL . PHP_EOL.
                '                   -m --mark           Mark changesets that fail their precondition as complete' . PHP_EOL .
                '                   -v --verbose        Maximum verbosity' . PHP_EOL .
                '                   -s --showsql        Only shows sql, does not execute'.PHP_EOL.PHP_EOL;

    }

    /**
     * @throws Exception
     * @return void
     */
    private function Migrate()
    {
        foreach ($this->_executeChangesets as $changeSetId => $changeset)
        {
            // 'action', 'hash', 'type', 'author'
            $actions = str_getcsv($changeset['action'], ';');
            foreach ($actions as $action)
            {
                if (strlen(trim($action)) > 0)
                {
                    if ($this->_verbose)
                    {
                        echo 'Executing changeset #'.$changeSetId.' : '.$action.PHP_EOL;
                    }

                    if (!$this->_showOnly)
                    {
                        $res = $this->_dbh->exec($action);
                    }
                    else
                    {
                        if (!$this->_verbose)
                        {
                            echo $action.PHP_EOL;
                        }
                        $res = true;
                    }
                    if ($res === false)
                    {

                        $res = $this->_dbh->errorInfo();

                        $this->Rollback($changeSetId, true);

                        throw new Exception('Failed to migrate database error ('.$this->_dbh->errorCode().'): '.$res[2].PHP_EOL.$action);
                    }
                }
            }
            if (!$this->_showOnly)
            {
                $this->MarkChangesetComplete($changeSetId, $changeset['type'], $changeset['author'], $changeset['hash']);
            }
        }
    }

    /**
     * @throws Exception
     * @param int $changeSetId
     * @param bool $force
     * @return void
     */
    private function Rollback($changeSetId, $force = false)
    {
        if ($force || $this->Retrieve($changeSetId))
        {
            $changeSet = $this->_allChangesets[$changeSetId];
            $rollback = trim($changeSet->rollback);
            if (strlen($rollback) > 0)
            {
                if ($this->_verbose)
                {
                    echo 'Rolling back changeset '.$changeSetId.': '.$rollback.PHP_EOL;
                }
                $res = $this->_dbh->exec($rollback);
                if (!$force)
                {
                    if ($res === false)
                    {
                        $res = $this->_dbh->errorInfo();
                        throw new Exception('Failed to rollback database error ('.$this->_dbh->errorCode().'): '.$this->_dbh->errorCode().'): '.
                                    $res[2].PHP_EOL.$rollback.PHP_EOL);
                    }
                    $this->Delete();
                }
                else if ($res === false && $this->_verbose)
                {
                    echo 'Failed to rollback changeset: '.$changeSet.': '.$rollback.PHP_EOL;
                }
            }
            else
            {
                if (!$force)
                {
                    throw new Exception('Failed to rollback, empty rollback');
                }
            }

        }
        else
        {
            throw new Exception('Changeset '.$changeSetId.' has not yet been run');
        }
    }
    
    /**
     * @throws Exception
     * @return void
     */
    private function GetExecutedChangesets()
    {
        if (!$this->TableExists(self::DEPLOY_TABLE))
        {
            $this->CreateDeployDatabase();
        }

        foreach ($this->_allChangesets as $changeSetId => $changeSet)
        {
            $action = trim($changeSet->action);
            $hash = md5($action);
            if ($this->Retrieve($changeSetId))
            {
                // If the hash has changed then we are in a bad state, and throw an exception...
                if (!$this->Hash == $hash)
                {
                    throw new Exception('Changeset #' . $changeSetId . ' has changed');
                }
                if ($this->_verbose)
                {
                    echo 'Changeset #'.$changeSetId.' already run'.PHP_EOL;
                }
            }
            else
            {
                $author = (string)$changeSet['author'];
                $type = (string)$changeSet['type'];
                $errorMsg = '';

                if ($this->ValidatePrecondition($changeSet->precondition, $errorMsg))
                {
                    $this->_executeChangesets[$changeSetId] = array('action' => $action, 'hash' => $hash, 'type' => $type, 'author' => $author);
                    echo 'Executing changeset #'.$changeSetId.PHP_EOL;
                }
                else
                {
                    if ($this->_markPreconditionComplete)
                    {
                        $this->MarkChangesetComplete($changeSetId, $type, $author, $hash);
                    }
                    else
                    {
                        $error = 'Precondition failed: ' . $errorMsg.PHP_EOL;
                        if ($this->_force)
                        {

                            echo $error;
                        }
                        else
                        {
                            throw new Exception($error);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $changeSetId
     * @param string $type
     * @param string $author
     * @param string $hash
     * @return void
     */
    private function MarkChangesetComplete($changeSetId, $type, $author, $hash)
    {
        $this->ChangesetId = $changeSetId;
        $this->Type = $type;
        $this->Author = $author;
        $this->Hash = $hash;
        $this->Executed = date(self::ISO_DATE_STRING);
        $this->Create();
    }

    /**
     * @return void
     */
    private function ValidateMigration()
    {
        $this->LoadAllChangesetsXml();

        $this->GetExecutedChangesets();

    }

    /**
     * @throws Exception
     * @return void
     */
    private function LoadAllChangesetsXml()
    {
        libxml_use_internal_errors(true);

        $xsd = CodeGenHelpers::BuildPath(__DIR__, 'DeploySql.xsd');

        $files = array();

        foreach (glob(CodeGenHelpers::BuildPath($this->_directory, '*.xml')) as $sqlFile)
        {
            if ($this->_verbose)
            {
                echo 'Validating xml file '.basename($sqlFile).PHP_EOL;
            }
            $xml = new XMLReader();
            if ($xml->open($sqlFile))
            {
                $xml->setSchema($xsd);
                $xml->setParserProperty(XMLReader::VALIDATE, true);
                if (!$xml->isValid())
                {
                    $errors = $this->GetAllLibxmlErrors();
                    throw new Exception('Invalid Deployment file : ' . $sqlFile . PHP_EOL . $errors);
                }
                $xml->close();

                $simpleXml = new SimpleXMLElement(file_get_contents($sqlFile));

                $result = $simpleXml->xpath('/deploySql/changeSet');
                foreach ($result as $changeSet)
                {
                    $id = (int)$changeSet['id'];
                    if (isset($this->_allChangesets[$id]))
                    {
                        $error = 'Duplicate changeset id: ' . $id . ' in deployment file: ' . $files[$id];
                        if (basename($sqlFile) != $files[$id])
                        {
                            $error .= ' and file ' . basename($sqlFile);
                        }
                        throw new Exception($error);
                    }
                    $files[$id] = basename($sqlFile);
                    $this->_allChangesets[$id] = $changeSet;
                }
            }
            else
            {
                throw new Exception('Unable to open schema file: ' . $sqlFile);
            }

        }

        ksort($this->_allChangesets, SORT_NUMERIC);
        if ($this->_verbose)
        {
            echo 'Found '.count($this->_allChangesets).' changesets'.PHP_EOL;
        }
    }

    private function GetLibXmlError($error)
    {
        $return = '';
        switch ($error->level)
        {
            case LIBXML_ERR_WARNING:
                $return .= 'Warning ('.$error->code.'): ';
                break;
            case LIBXML_ERR_ERROR:
                $return .= 'Error ('.$error->code.'): ';
                break;
            case LIBXML_ERR_FATAL:
                $return .= 'Fatal Error ('.$error->code.'): ';
                break;
        }
        $return .= trim($error->message);
        if ($error->file)
        {
            $return .= ' in '.$error->file;
        }
        $return .= ' on line '.$error->line.PHP_EOL;

        return $return;
    }

    /**
     * @return string
     */
    private function GetAllLibxmlErrors()
    {
        $res = '';
        $errors = libxml_get_errors();
        foreach ($errors as $error)
        {
            $res .= $this->GetLibXmlError($error);
        }
        libxml_clear_errors();

        return $res;
    }

    /**
     * @throws Exception
     * @return void
     */
    private function CreateDeployDatabase()
    {
        if ($this->_verbose)
        {
            echo 'Creating deployment database'.PHP_EOL;
        }
        $this->_dbh->exec("CREATE TABLE ".self::DEPLOY_TABLE." (changeset_id INTEGER UNSIGNED NOT NULL, ".
            "type ENUM('create', 'populate', 'alterSchema', 'alterData') NOT NULL, ".
            "author VARCHAR(45) NOT NULL, hash VARCHAR(45) NOT NULL, executed DATETIME NOT NULL,  PRIMARY KEY (changeset_id))");
        if (!$this->TableExists(self::DEPLOY_TABLE))
        {
            throw new Exception('Unable to create table '.self::DEPLOY_TABLE);
        }
    }

    /**
     * @param SimpleXMLElement $precondition
     * @param string $error [out]
     * @return bool
     */
    private function ValidatePrecondition($precondition, &$error)
    {
        foreach ($precondition->children() as $child)
        {
            /** @var $child SimpleXMLElement */
            switch ($child->getName())
            {
                case 'tableExists' :
                    $success = $this->TableExists($child['table']);
                    $error = $success ? '' : 'table '.$child['table'].' does not exist';
                    break;
                case 'tableDoesntExist' :
                    $success = !$this->TableExists($child['table']);
                    $error = $success ? '' : 'table '.$child['table'].' exists';
                    break;
                case 'fieldExists' :
                    $success = $this->FieldExists($child['table'], $child['field']);
                    $error = $success ? '' : 'field '.$child['table'].'.'.$child['field'].' does not exist';
                    break;
                case 'fieldDoesntExist' :
                    $success = !$this->FieldExists($child['table'], $child['field']);
                    $error = $success ? '' : 'field '.$child['table'].'.'.$child['field'].' exists';
                    break;
                case 'preconditionNone' :
                    $success = true;
                    break;
                case 'indexDoesntExist' :
                    $success = !$this->IndexExists($child['table'], $child['index']);
                    $error = $success ? '' : 'index '.$child['table'].'.'.$child['index'].' exists';
                    break;
                case 'dataDoesntExist':
                    $success = !$this->DataExists($child['table'], $child['field'], $child['value']);
                    $error = $success ? '' : 'data '.$child['table'].'.'.$child['field'].' = '.$child['value'].' exists';
                    break;
                case 'dataNotEqual':
                    $success = !$this->DataIsEqual($child['table'], $child['field'], $child['key'], $child['keyValue'], (string)$child);
                    $error = $success ? '' : 'data '.$child['table'].'.'.$child['field'].' = '.$child['value'].' is equal to'.(string)$child;
                    break;
                default: $success = false;
            }
            if (!$success)
            {
                return false;
            }
        }

        return true;

    }

     /**
     * @param string $table
     * @param string $field
     * @param string $value
     * @return bool
     */
    private function DataExists($table, $field, $value)
    {
        $stmt = $this->_dbh->prepare('SELECT '.$field.' FROM '.$table.' WHERE '.$field.' = ?');

        if ($stmt->execute(array($value)))
        {
            $this->ValidateStatement($stmt);

            return $stmt->rowCount() > 0;
        }

        return false;
    }

    /**
     * @param string $table
     * @param string $field
     * @param string $key
     * @param string $keyValue
     * @param string $data
     * @return bool
     */
    private function DataIsEqual($table, $field, $key, $keyValue, $data)
    {
        $stmt = $this->_dbh->prepare('SELECT '.$field.' FROM '.$table.' WHERE '.$key.' = ?');

        if ($stmt->execute(array($keyValue)))
        {
            $this->ValidateStatement($stmt);
            return trim($data) == trim($stmt->fetchColumn(0));
        }

        return false;
    }



}

function exception_error_handler($errorNumber, $errorString, $errorFile, $errorLine ) {
    throw new ErrorException($errorString, 0, $errorNumber, $errorFile, $errorLine);
}
set_error_handler("exception_error_handler");

new SqlDeploy(new Args());