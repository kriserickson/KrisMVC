<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require dirname(__FILE__) . '/../lib/orm/KrisDB.php';
require dirname(__FILE__) . '/../lib/debug/DebugPDO.php';
require dirname(__FILE__) . '/../lib/helpers/FileHelpers.php';
require dirname(__FILE__) . '/../lib/plumbing/AutoLoader.php';
require dirname(__FILE__) . '/../lib/view/Mustache.php';
require dirname(__FILE__) . '/Args.php';
require dirname(__FILE__) . '/CodeGeneration.php';

if (!defined('__DIR__'))
{
    define('__DIR__', dirname(__FILE__));
}

/**
 * Class that
 */
class KrisCGCommandLineParser
{
    /**
     * @var \Args
     */
    private $_args;

    /**
     * @var \KrisCG
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
        if (in_array($command, array('create-table', 'table', 'create-site', 'site', 'create-scaffold', 'scaffold')))
        {
            $location = $this->GetLocation();
            if ($location)
            {
                $this->_cg = new KrisCG($location);
                try
                {
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
                catch (Exception $ex)
                {
                    echo 'Error: ' . $ex;
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

        $this->_cg->CreateSite($site, $host, $database, $user, $password, $databaseType, $viewType, $siteName);
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
        $this->_cg->GenerateModel($table);
        return true;
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
        $viewType = $this->_args->flag(array('t', 'view-type'), 'KrisView');

        if (false) //$this->IsCli())
        {
            if (strlen($this->_args->flag(array('l', 'scaffold-location'))) == 0)
            {
                $controllerLocation = $this->GetInput('Controller Location (in app/controllers)', $controllerLocation);
            }
            if (strlen($this->_args->flag(array('n', 'scaffold-name'))) == 0)
            {
                $controllerLocation = $this->GetInput('Controller Class Name', $controllerName);
            }
            if (strlen($this->_args->flag(array('t', 'view-type'))) == 0)
            {
                $controllerLocation = $this->GetInput('View Type', $viewType);
            }
            if (strlen($this->_args->flag(array('o', 'view-location'))) == 0)
            {
                $controllerLocation = $this->GetInput('Scaffold Layout Locations (in app/views)', $viewLocation);
            }
        }

        $this->_cg->IncludeConfigFile();
        $this->_cg->CreateScaffold($controllerLocation, $controllerName, $viewType, $viewLocation);
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
                '   createTable or table                Adds a model to the project of the table specified' . PHP_EOL .
                '                   -t --table              The table to create ' . PHP_EOL . PHP_EOL .
                '   createSite or site                  Creates a new site' . PHP_EOL .
                '                   -s --site           The base url of the site.  For example if you site is http://localhost/test ' . PHP_EOL .
                '                   -n --no-database        If you do not want a database add this flag, otherwise an error will be generated' . PHP_EOL .
                '                   -h --host               The database host (ip, name), or the file location for SQLite' . PHP_EOL .
                '                   -d --database           The database name (not required for SQLite)' . PHP_EOL .
                '                   -u --user               The database user' . PHP_EOL .
                '                   -p --password           The database password' . PHP_EOL .
                '                   -y --database-type      The database type (MYSQL, MSSQL, SQLITE, POSTGRESQL (default to MYSQL)' . PHP_EOL .
                '                   -a --site-name          The name of the site' . PHP_EOL .
                '                   -v --view-type          The view engine (Mustache or KrisView)' . PHP_EOL . PHP_EOL .
                '  createScaffold scaffold              Create the crud scaffolding' . PHP_EOL .
                '                   -l --scaffold-location  The in apps/controller of the scaffold (defaults to "scaffold")' . PHP_EOL .
                '                   -n --scaffold-name      The name of the scaffold controller (defaults to "Scaffold")' . PHP_EOL .
                '                   -s --scaffold-view      The main scaffold layout template (defaults to "scaffold.php")' . PHP_EOL .
                '                   -o --view-location      The location in the views of scaffold view (defaults to "scaffold")' . PHP_EOL .
                '                   -t --view-type          The View Type (currently only "KrisView" is supported)' . PHP_EOL .
                '                   -v --view               The template for View (defaults to "ViewView.php")' . PHP_EOL .
                '                   -e --edit               The template for Edit (defaults to "EditView.php")' . PHP_EOL .
                '                   -i --index              The template for Index (defaults to "IndexView.php")' . PHP_EOL . PHP_EOL .
                '   Options available for all commands:' . PHP_EOL .
                '                   -l --location       Location of the site' . PHP_EOL . PHP_EOL;

    }


}

new KrisCGCommandLineParser(new Args());







// createSite -l "D:\Projects\html\ammara2" -s "http://localhost/ammara2/" -h localhost -d ammara -u root -p myssirk34 -y MYSQL