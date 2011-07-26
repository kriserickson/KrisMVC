<?php

//===============================================
// Includes
//===============================================
require 'config/KrisConfig.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/includes.php';

//===============================================
// Start the controller
//===============================================
$controller = new KrisController(dirname(__FILE__).'/'.KrisConfig::APP_PATH . 'controllers/');
?>