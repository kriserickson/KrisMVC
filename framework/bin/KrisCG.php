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
require dirname(__FILE__) . '/../lib/orm/DebugPDO.php';
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
    private $_args;

    /**
     * This actually does everything...  Parses the command line or gets values from stdin...
     */
    function __construct()
    {
        $this->_args = new Args();

        $success = false;

        if (strlen($this->_args->Command()) > 0)
        {
            $location = $this->GetLocation();
            if ($location)
            {
                $cg = new KrisCG($location);
                if ($this->_args->Command() == 'createTable')
                {
                    $success = $this->CreateTable($cg);
                }
                else if ($this->_args->Command() == 'createSite')
                {
                    $success = $this->CreateSite($cg);
                }

            }
        }
        if (!$success)
        {
            $this->DisplayUsage();
        }
    }


    /**
     * @param KrisCG $cg
     * @return bool
     */
    private function CreateSite($cg)
    {
        $site = $this->_args->flag(array('s', 'site'), '');
        $host = $this->_args->flag(array('h', 'host'), '');
        $database = $this->_args->flag(array('d', 'database'), '');
        $user = $this->_args->flag(array('u', 'user'), '');
        $password = $this->_args->flag(array('p', 'password'), '');
        $databaseType = $this->_args->flag(array('y', 'type'), '');
        
        if ($this->IsCli())
        {
            if (strlen($site) == 0)
            {
                $site = $this->GetInput('The base url of the site', 'localhost', true);
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
            if (strlen($host) > 0 && strlen($user) == 0)
            {
                $password = $this->GetInput('Database User Password');
            }
        }
        else
        {
            if (strlen($site) == 0)
            {
                echo 'You must set a site...'.PHP_EOL;
                return false;
            }
            if (strlen($host) == 0)
            {

            }
            if (strlen($databaseType) == 0)
            {
                $databaseType = 'MYSQL';
            }
        }

        $cg->CreateSite($site, $host, $database, $user, $password, $databaseType);
        return true;
    }

    /**
     * @param KrisCG $cg
     * @return bool
     */
    private function CreateTable($cg)
    {
        if (!$this->_args->flag(array('t', 'table')))
        {

            if ($this->IsCli())
            {
                $table = $this->GetInput('Please Enter Table Name');
            }
            else
            {
                echo 'Error, cannot create table because no table was specified';
                return false;
            }
        }
        else
        {
            $table = $this->_args->flag(array('t', 'table'));
        }
        $cg->IncludeConfigFile();
        $cg->GenerateModel($table);
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
            echo $msg.(strlen($default) > 0 ? ' ['.$default.']' : '').': ';

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
                '   createTable     -t --table          Adds a model to the project of the table specified' . PHP_EOL . PHP_EOL .
                '   createSite                          Creates a new site' . PHP_EOL .
                '                   -s --site           The base url of the site.  For example if you site is http://localhost/test ' . PHP_EOL .
                '                   -h --host           The database host (ip, name), or the file location for SQLite' . PHP_EOL .
                '                   -d --database       The database name (not required for SQLite)' . PHP_EOL .
                '                   -u --user           The database user' . PHP_EOL .
                '                   -p --password       The database password' . PHP_EOL .
                '                   -y --type           The database type (MYSQL, MSSQL, SQLITE, POSTGRESQL (default to MYSQL)' . PHP_EOL. PHP_EOL.
                '   Options available for all commands:' . PHP_EOL .
                        '                   -l --location       Location of the site' . PHP_EOL . PHP_EOL;

    }


}

new KrisCGCommandLineParser(new Args());







// createSite -l "D:\Projects\html\ammara2" -s "http://localhost/ammara2/" -h localhost -d ammara -u root -p myssirk34 -y MYSQL