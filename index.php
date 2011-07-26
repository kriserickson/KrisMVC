<?php

//===============================================
// Includes
//===============================================
require 'config/KrisConfig.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/includes.php';

//===============================================
// Start the controller
//===============================================

/** @var $controller Controller */
$controller = $container->create('Controller');
$controller->Route(dirname(__FILE__).'/'.KrisConfig::APP_PATH . 'controllers/');


?>