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
require dirname(__FILE__) . '/../lib/helpers/FileHelpers.php';
require dirname(__FILE__) . '/Args.php';
require dirname(__FILE__) . '/CodeGeneration.php';


$args = new Args();

if (!($args->flag('l') || $args->flag('location')))
{
    // Default location in the main directory...
    $location =  dirname(dirname(dirname(__FILE__))); //  same as dirname(__FILE__).'/../..';
}
else
{
    $location = !$args->flag('l') ? $args->flag('location') : $args->flag('l');
}



$cg = new KrisCG($location);
if ($args->command() == 'createTable')
{
    if (!$args->flag(array('t', 'table')))
    {
        echo 'Error, cannot create table because no table was specified';
    }
    $cg->IncludeConfigFile();
    $cg->GenerateModel($args->flag(array('t', 'table')));
}
else if ($args->command() == 'createSite')
{
    $cg->CreateSite($args->flag(array('s', 'site'), ''), $args->flag(array('h', 'host'), ''), $args->flag(array('d', 'database'), ''),
        $args->flag(array('u', 'user'), ''), $args->flag(array('p', 'password'), ''), $args->flag(array('y', 'type'), 'MYSQL'));
}
else
{
    echo 'Usage KrisCG ' . PHP_EOL .
        '   Options available for all commands:'.PHP_EOL.
        '                   -l --location       Location of the site'.PHP_EOL.PHP_EOL.
        '   Commands:       Options '.PHP_EOL.PHP_EOL.
        '   createTable     -t --table          Adds a model to the project of the table specified'.PHP_EOL.PHP_EOL.
        '   createSite                          Creates a new site'.PHP_EOL.
        '                   -s --site           The base url of the site.  For example if you site is http://localhost/test '.PHP_EOL.
        '                   -h --host           The database host (ip, name), or the file location for SQLite'.PHP_EOL.
        '                   -d --database       The database name (not required for SQLite)'.PHP_EOL.
        '                   -u --user           The database user'.PHP_EOL.
        '                   -p --password       The database password'.PHP_EOL.
        '                   -y --type           The database type (MYSQL, MSSQL, SQLITE, POSTGRESQL (default to MYSQL)'.PHP_EOL;


}