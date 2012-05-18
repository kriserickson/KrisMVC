<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once dirname(__FILE__) . '/../lib/plumbing/AutoLoader.php';
require_once dirname(__FILE__) . '/../lib/plumbing/BucketContainer.php';
require_once dirname(__FILE__) . '/../lib/orm/KrisDB.php';
require_once dirname(__FILE__) . '/../lib/orm/KrisModel.php';
require_once dirname(__FILE__) . '/../lib/debug/DebugPDO.php';
require_once dirname(__FILE__) . '/../lib/helpers/FileHelpers.php';
require_once dirname(__FILE__) . '/../lib/view/Mustache.php';
require_once dirname(__FILE__) . '/Args.php';
require_once dirname(__FILE__) . '/CodeGenDB.php';
require_once dirname(__FILE__) . '/CodeGeneration.php';
require_once dirname(__FILE__) . '/CodeGenHelpers.php';
require_once dirname(__FILE__) . '/SiteDeploy.php';


/**
 * Class that
 */
class KrisCGCommandLineParser
{
    /**
     * @var Args
     */
    private $_args;

    /**
     * @var KrisCG
     */
    private $_cg;

    /**
     * This actually does everything...  Parses the command line or gets values from stdin...
     */
    function __construct()
    {
        $this->_args = new Args();

        $success = false;

        $command = $this->_args->Command();
        if (in_array($command, array('create-table', 'table', 'create-site', 'site', 'create-scaffold', 'scaffold', 'deploy-site', 'deploy-sql')))
        {
            $location = $this->GetLocation();
            if ($location)
            {
                try
                {
                    if ($this->_args->Command() == 'deploy-site')
                    {
                        $success = $this->DeploySite($location);
                    }
                    else if ($this->_args->Command() == 'deploy-sql')
                    {
                        $success = $this->DeploySql($location);
                    }
                    else
                    {
                        $this->_cg = new KrisCG($location);

                        if ($this->_args->Command() == 'create-table' || $this->_args->Command() == 'table')
                        {
                            $success = $this->CreateTable();
                        }
                        else {
                            if ($this->_args->Command() == 'create-site' || $this->_args->Command() == 'site')
                            {
                                $success = $this->CreateSite();
                            }
                            else
                            {
                                if ($this->_args->Command() == 'create-scaffold' || $this->_args->Command() == 'scaffold')
                                {
                                    $success = $this->CreateScaffold();
                                }
                            }
                        }

                    }
                }
                catch (Exception $ex)
                {
                    echo 'Error: ' . $ex->getMessage().PHP_EOL.PHP_EOL;
                }
            }
        }
        else
        {
            if (strlen($command) > 0 && $command != 'help')
            {
                echo PHP_EOL . 'Invalid Command "' . $command . '"' . PHP_EOL . PHP_EOL;
            }
        }
        if (!$success)
        {
            $this->DisplayUsage();
        }
    }


    /**
     * @return bool
     */
    private function CreateSite()
    {
        $site = $this->_args->flag(array('s', 'site'), '');
        $host = $this->_args->flag(array('h', 'host'), '');
        $database = $this->_args->flag(array('d', 'database'), '');
        $user = $this->_args->flag(array('u', 'user'), '');
        $password = $this->_args->flag(array('p', 'password'), '');
        $databaseType = $this->_args->flag(array('y', 'database-type'), 'MYSQL');
        $viewType = $this->_args->flag(array('v', 'view-type'), 'Mustache');
        $siteName = $this->_args->flag(array('a', 'site-name'), 'KrisMVC Site');

        if ($this->IsCli())
        {
            if (strlen($site) == 0)
            {
                $site = $this->GetInput('The base url of the site', 'localhost', true);
            }
            if (strlen($this->_args->flag(array('a', 'site-name'))) == 0)
            {
                $siteName = $this->GetInput('Site Name', $siteName);
            }
            if (strlen($this->_args->flag(array('v', 'view-type'))) == 0)
            {
                $viewType = $this->GetInput('View Type', $viewType);
            }
            if (strlen($host) == 0)
            {
                $host = $this->GetInput('Database Host (leave blank for no database access)');
            }
            if (strlen($host) > 0 && strlen($database) == 0)
            {
                $database = $this->GetInput('Database Name', '', true);
            }
            if (strlen($host) > 0 && strlen($user) == 0)
            {
                $user = $this->GetInput('Database User');
            }
            if (strlen($host) > 0 && strlen($password) == 0)
            {
                $password = $this->GetInput('Database User Password');
            }

        }
        else
        {
            if (strlen($site) == 0)
            {
                echo 'You must set a site.' . PHP_EOL;
                return false;
            }
            if (strlen($host) == 0 && !$this->_args->flag(array('n', 'no-database')))
            {
                echo 'You must set a host or set no-database.' . PHP_EOL;
                return false;
            }
            if (strlen($database) == 0 && !$this->_args->flag(array('n', 'no-database')))
            {
                echo 'You must set a database or set no-database.' . PHP_EOL;
                return false;
            }

        }

        try
        {
            $this->_cg->CreateSite($site, $host, $database, $user, $password, $databaseType, $viewType, $siteName);
        }
        catch (Exception $ex)
        {
            echo 'Error Creating Site: ' . $ex->getMessage().PHP_EOL.PHP_EOL;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function CreateTable()
    {
        $table = $this->_args->flag(array('t', 'table', ''));
        if ($this->IsCli())
        {
            if (strlen($table) == 0)
            {
                $table = $this->GetInput('Please Enter Table Name', '', true);
            }
        }
        else
        {
            if (strlen($table) == 0)
            {
                echo 'Error, cannot create table because no table was specified';
                return false;
            }
        }

        $this->_cg->IncludeConfigFile();
        try
        {
            $this->_cg->GenerateModel($table);
        }
        catch (Exception $ex)
        {
            echo 'Error Creating Table: ' . $ex->getMessage().PHP_EOL.PHP_EOL;
        }
        return true;
    }

    /**
     * @param string $location
     * @return bool
     */
    private function DeploySql($location)
    {
        $isLive = $this->_args->flag(array('L', 'live'), false);
        $userName = $this->_args->flag(array('u', 'username'));
        $password = $this->_args->flag(array('p', 'password'));
        $host = $this->_args->flag(array('h', 'host'));
        $port = $this->_args->flag(array('o', 'port'));
        $destdir = $this->_args->flag(array('d', 'dest-directory'));
        $validate  = $this->_args->flag(array('v', 'validate'));
        $mark  = $this->_args->flag(array('m', 'mark'));
        $clearDatabase = $this->_args->flag(array('c', 'clear'));


        try
        {
            $deploy = new SiteDeploy($location, $isLive);
            $deploy->Initialize($userName, $password, $host, $destdir, '', '', $port, true, $clearDatabase);

            $deploy->DeploySql($validate, $mark);
            echo 'Sql Deployed';

            return true;
        }
        catch (Exception $ex)
        {
            echo 'Error: ' . $ex->getMessage().PHP_EOL.PHP_EOL;
            $deploy->WriteDeployFile();
            return true;
        }
    }

    /**
     * @param string $location
     * @return bool
     */
    private function DeploySite($location)
    {
        $isLive = $this->_args->flag(array('L', 'live'), false);
        $userName = $this->_args->flag(array('u', 'username'));
        $password = $this->_args->flag(array('p', 'password'));
        $host = $this->_args->flag(array('h', 'host'));
        $port = $this->_args->flag(array('o', 'port'));
        $temp = $this->_args->flag(array('t', 'temp'));
        $destdir = $this->_args->flag(array('d', 'dest-directory'));
        $yuiLocation = $this->_args->flag(array('y', 'yui-location'));
        $write = $this->_args->flag(array('w', 'write'));
        $clearDatabase = $this->_args->flag(array('c', 'clear'));

        try
        {
            $deploy = new SiteDeploy($location, $isLive);
            $deploy->Initialize($userName, $password, $host, $destdir, $temp, $yuiLocation, $port, false, $clearDatabase);
            if ($write)
            {
                $deploy->WriteDeployFile();
                echo 'Deployment File Written..';
            }
            else
            {
                $deploy->Deploy($clearDatabase);
                $deploy->WriteDeployFile();
                echo 'Site Deployed';

            }
            return true;
        }
        catch (Exception $ex)
        {
            echo 'Error: ' . $ex->getMessage().PHP_EOL.PHP_EOL;
            $deploy->WriteDeployFile();
            return true;
        }
    }


    /**
     * @return bool
     */
    private function CreateScaffold()
    {
        $controllerLocation = $this->_args->flag(array('c', 'scaffold-location'), 'scaffold');
        $controllerName = $this->_args->flag(array('n', 'scaffold-name'), 'Scaffold');
        $viewLocation = $this->_args->flag(array('o', 'view-location'), 'scaffold');

        // Currently on the KrisView PHP type is supported...
        $viewType = $this->_args->flag(array('t', 'view-type'), 'Mustache');

        if ($this->IsCli())
        {
            if (strlen($this->_args->flag(array('L', 'scaffold-location'))) == 0)
            {
                $controllerLocation = $this->GetInput('Controller Location (in app/controllers)', $controllerLocation);
            }
            if (strlen($this->_args->flag(array('n', 'scaffold-name'))) == 0)
            {
                $controllerName = $this->GetInput('Controller Class Name', $controllerName);
            }
            if (strlen($this->_args->flag(array('t', 'view-type'))) == 0)
            {
                $viewType = $this->GetInput('View Type', $viewType);
            }
            if (strlen($this->_args->flag(array('o', 'view-location'))) == 0)
            {
                $viewLocation = $this->GetInput('Scaffold Layout Locations (in app/views)', $viewLocation);
            }
        }


        $this->_cg->IncludeConfigFile();
        try
        {
            $this->_cg->CreateScaffold($controllerLocation, $controllerName, $viewType, $viewLocation);
        }
        catch (Exception $ex)
        {
            echo 'Error Creating Scaffold: ' . $ex->getMessage();
        }
        return true;
    }

    /**
     * @return bool|string
     */
    private function GetLocation()
    {
        if (!($this->_args->flag('l') || $this->_args->flag('location')))
        {
            // Default location in the main directory...
            $location = dirname(dirname(__DIR__));
            if ($this->IsCli())
            {
                $location = $this->GetInput('Location of the project folder', $location);
            }
        }
        else
        {
            $location = !$this->_args->flag('l') ? $this->_args->flag('location') : $this->_args->flag('l');

        }

        return $location;

    }

    /**
     * @return bool
     */
    private function IsCli()
    {
        return defined('STDIN');
    }

    /**
     * @param string $msg
     * @param string $default
     * @param bool $required
     * @return string
     */
    private function GetInput($msg, $default = '', $required = false)
    {
        $handle = fopen('php://stdin', 'r');
        do
        {
            echo $msg . (strlen($default) > 0 ? ' [' . $default . ']' : '') . ': ';

            // We don't want the carriage return...
            $line = rtrim(fgets($handle));
            if (strlen($line) == 0)
            {
                $line = $default;
            }
        } while ($required && strlen($line) == 0);

        fclose($handle);
        return $line;
    }

    /**
     * @return void
     */
    private function DisplayUsage()
    {
        echo 'Usage KrisCG ' . PHP_EOL .
                '   Commands:       Options ' . PHP_EOL . PHP_EOL .
                '   create-table or table               Adds a model to the project of the table specified' . PHP_EOL .
                '                   -t --table              The table to create ' . PHP_EOL . PHP_EOL .
                '   create-site or site                 Creates a new site' . PHP_EOL .
                '                   -s --site               The base url of the site.  For example if you site is http://localhost/test ' . PHP_EOL .
                '                   -n --no-database        If you do not want a database add this flag, otherwise an error will be ' . PHP_EOL .
                '                                           generated' . PHP_EOL .
                '                   -h --host               The database host (ip, name), or the file location for SQLite' . PHP_EOL .
                '                   -d --database           The database name (not required for SQLite)' . PHP_EOL .
                '                   -u --user               The database user' . PHP_EOL .
                '                   -p --password           The database password' . PHP_EOL .
                '                   -y --database-type      The database type (MYSQL, MSSQL, SQLITE, POSTGRESQL (default to MYSQL)' . PHP_EOL .
                '                   -a --site-name          The name of the site' . PHP_EOL .
                '                   -v --view-type          The view engine (Mustache or KrisView)' . PHP_EOL . PHP_EOL .
                '  create-scaffold scaffold             Create the crud scaffolding' . PHP_EOL .
                '                   -L --scaffold-location  The in apps/controller of the scaffold (defaults to "scaffold")' . PHP_EOL .
                '                   -n --scaffold-name      The name of the scaffold controller (defaults to "Scaffold")' . PHP_EOL .
                '                   -s --scaffold-view      The main scaffold layout template (defaults to "scaffold.php")' . PHP_EOL .
                '                   -o --view-location      The location in the views of scaffold view (defaults to "scaffold")' . PHP_EOL .
                '                   -t --view-type          The View Type (currently only "KrisView" is supported)' . PHP_EOL .
                '                   -v --view               The template for View (defaults to "ViewView.php")' . PHP_EOL .
                '                   -e --edit               The template for Edit (defaults to "EditView.php")' . PHP_EOL .
                '                   -i --index              The template for Index (defaults to "IndexView.php")' . PHP_EOL . PHP_EOL .
                '  deploy-site                          Deploys a site ' . PHP_EOL .
                '                   -L --live               Deploys the live version of the site (the default is staging)' . PHP_EOL .
                '                   -u --username           Site username' . PHP_EOL .
                '                   -p --password           Site password' . PHP_EOL .
                '                   -h --host               Site host' . PHP_EOL .
                '                   -w --write              Write the deploy file (does not deploy the site, just writes defaults into' . PHP_EOL .
                '                                           the deploy file)' . PHP_EOL .
                '                   -t --temp               Temp location (defaults to $TEMP/$ProjectName)' . PHP_EOL .
                '                   -c --yui-location       The location of the YUI compressor, note: Java is required to be installed ' . PHP_EOL .
                '  deploy-sql                          Deploys the sql only for a site ' . PHP_EOL .
                '                   -L --live               Deploys the live version of the site (the default is staging)' . PHP_EOL .
                '                   -u --username           Site username' . PHP_EOL .
                '                   -p --password           Site password' . PHP_EOL .
                '                   -h --host               Site host' . PHP_EOL .
                '                   -v --validate           Validate Sql' . PHP_EOL .
                '                   -m --mark           Mark Sql' . PHP_EOL .
                '  Options available for all commands:' . PHP_EOL .
                '                   -l --location       Location of the site (required unless the site is in the default location' . PHP_EOL . PHP_EOL;

    }


}

/**
 * @param int $errorNumber
 * @param string $errorString
 * @param string $errorFile
 * @param int $errorLine
 * @throws ErrorException
 */
function exception_error_handler($errorNumber, $errorString, $errorFile, $errorLine ) {
    throw new ErrorException($errorString, 0, $errorNumber, $errorFile, $errorLine);
}
set_error_handler("exception_error_handler");

new KrisCGCommandLineParser();







// createSite -l "D:\Projects\html\ammara2" -s "http://localhost/ammara2/" -h localhost -d ammara -u root -p myssirk34 -y MYSQL
