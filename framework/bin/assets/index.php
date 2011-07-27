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
$controller = AutoLoader::$Container->create('Controller');
$controller->Route(dirname(__FILE__).'/'.KrisConfig::APP_PATH . 'controllers/');
?>