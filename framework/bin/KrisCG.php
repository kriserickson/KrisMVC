<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
require __DIR__ . '/../../config/KrisConfig.php';
require __DIR__ . '/../lib/KrisDB.php';

require __DIR__ . '/Args.php';
require __DIR__ . '/CodeGeneration.php';





$cg = new KrisCG();

$args = new Args();

if ($args->flag('t') || $args->flag('table'))
{
    $table = $args->flag('t') ? $args->flag('t') : $args->flag('table');
    $cg->GenerateModel($table);
}
else
{
    echo 'Usage KrisCG ' . PHP_EOL . '   -t --table  Table Name ' . PHP_EOL;
    echo PHP_EOL . 'With no options the Model is generated.';
}